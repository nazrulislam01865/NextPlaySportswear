<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Laravel-compatible e2a API transport. All other mailers remain unchanged.
 */
final class E2aTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $sender,
        private readonly string $apiUrl,
        private readonly int $timeout = 20,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        if ($this->apiKey === '' || ! filter_var($this->sender, FILTER_VALIDATE_EMAIL)) {
            throw new TransportException('E2A API key or sender address is not configured.');
        }

        if (! str_starts_with($this->apiUrl, 'https://')) {
            throw new TransportException('E2A API URL must use HTTPS.');
        }

        $from = $email->getFrom();
        if (count($from) !== 1 || strcasecmp($from[0]->getAddress(), $this->sender) !== 0) {
            throw new TransportException('E2A sender must match MAIL_FROM_ADDRESS and the registered E2A agent.');
        }

        $text = $email->getTextBody();
        $html = $email->getHtmlBody();

        if ($text === null || trim($text) === '') {
            $text = html_entity_decode(
                strip_tags(str_replace(['<br>', '<br/>', '</p>', '</div>'], "\n", (string) $html)),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );
        }

        $payload = [
            'to' => $this->addresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
            'text' => trim((string) $text),
        ];

        if ($payload['to'] === []) {
            throw new TransportException('E2A requires at least one To recipient.');
        }

        if ($html !== null && $html !== '') {
            $payload['html'] = $html;
        }

        foreach (['cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $field => $addresses) {
            if ($addresses !== []) {
                $payload[$field] = $this->addresses($addresses);
            }
        }

        if ($email->getReplyTo() !== []) {
            $replyTo = $this->addresses($email->getReplyTo());
            $payload['reply_to'] = count($replyTo) === 1 ? $replyTo[0] : $replyTo;
        }

        $attachments = [];
        foreach ($email->getAttachments() as $part) {
            $body = $part->getBody();
            if (is_resource($body)) {
                $body = stream_get_contents($body);
            }

            if (! is_string($body)) {
                throw new TransportException('E2A attachment content could not be read.');
            }

            $attachments[] = [
                'filename' => $part->getFilename() ?: 'attachment',
                'content_type' => $part->getContentType(),
                'data' => base64_encode($body),
            ];
        }

        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        // Persisted on the EmailMessage before queuing: retries of the same
        // logical email use the same key and E2A will not send duplicate mail.
        $idHeader = $email->getHeaders()->get('X-NextPlay-Delivery-ID');
        $idempotencyKey = $idHeader?->getBodyAsString() ?: (string) Str::uuid();

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(max(1, $this->timeout))
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post(
                    rtrim($this->apiUrl, '/') . '/agents/' . rawurlencode($this->sender) . '/messages',
                    $payload,
                );
        } catch (\Throwable $exception) {
            // Avoid leaking API credentials or message contents into logs.
            throw new TransportException('E2A network request failed.', 0, $exception);
        }

        if (! $response->successful()) {
            $code = $response->json('error.code');
            throw new TransportException(
                'E2A rejected the email (HTTP ' . $response->status() .
                (is_string($code) && preg_match('/^[a-z_]+$/', $code) ? ', ' . $code : '') . ').'
            );
        }

        $status = $response->json('status');
        if ($status === 'pending_review') {
            throw new TransportException('E2A is holding the email for approval (pending_review).');
        }

        if (! in_array($status, ['accepted', 'sent', 'scheduled'], true)
            || ! is_string($response->json('message_id'))
        ) {
            throw new TransportException('E2A did not confirm acceptance of the email.');
        }
    }

    /** @param array<int, Address> $addresses */
    private function addresses(array $addresses): array
    {
        return array_map(static fn (Address $address): string => $address->toString(), $addresses);
    }

    public function __toString(): string
    {
        return 'e2a';
    }
}
