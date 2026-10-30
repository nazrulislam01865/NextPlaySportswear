<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** File-backed defaults for automatic and manual forwarding; no database writes. */
final class ForwardingSettings
{
    private const PATH = 'email/forwarding-settings.json';

    /** @return array{recipients: string, message: string} */
    public function get(): array
    {
        $default = ['recipients' => '', 'message' => 'Please review and confirm the attached request details.'];
        $disk = Storage::disk('local');
        if (! $disk->exists(self::PATH)) {
            return $default;
        }
        $decoded = json_decode($disk->get(self::PATH), true);
        return is_array($decoded) ? array_merge($default, array_intersect_key($decoded, $default)) : $default;
    }

    public function save(string $recipients, string $message): void
    {
        $data = json_encode(['recipients' => $recipients, 'message' => $message], JSON_THROW_ON_ERROR);
        if (! Storage::disk('local')->put(self::PATH, $data)) {
            throw new RuntimeException('Could not save forwarding settings. Check storage directory permissions.');
        }
    }

    /** @return array<int,array{email:string}> */
    public function recipients(string $input): array
    {
        $emails = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\s]+/', $input) ?: []))));
        if (count($emails) < 1 || count($emails) > 5) {
            throw new RuntimeException('Enter between 1 and 5 recipient email addresses.');
        }
        foreach ($emails as $email) {
            if (strlen($email) > 254 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Invalid recipient address: '.$email);
            }
        }
        return array_map(static fn (string $email): array => ['email' => $email], $emails);
    }
}
