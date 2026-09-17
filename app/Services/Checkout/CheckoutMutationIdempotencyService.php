<?php

namespace App\Services\Checkout;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CheckoutMutationIdempotencyService
{
    private const TTL_SECONDS = 900;

    public function replay(Request $request, string $operation, array $payload): ?array
    {
        $key = $this->key($request);
        if ($key === null) {
            return null;
        }

        $sessionKey = $this->sessionKey($request, $operation, $key);
        $entry = (array) $request->session()->get($sessionKey, []);
        if ($entry === []) {
            return null;
        }

        if ((int) ($entry['expires_at'] ?? 0) < time()) {
            $request->session()->forget($sessionKey);
            return null;
        }

        if (! hash_equals((string) ($entry['fingerprint'] ?? ''), $this->fingerprint($payload))) {
            throw new ConflictHttpException('The same Idempotency-Key cannot be reused with a different checkout payload.');
        }

        return is_array($entry['result'] ?? null) ? $entry['result'] : null;
    }

    public function remember(Request $request, string $operation, array $payload, array $result): void
    {
        $key = $this->key($request);
        if ($key === null) {
            return;
        }

        $request->session()->put($this->sessionKey($request, $operation, $key), [
            'fingerprint' => $this->fingerprint($payload),
            'result' => $result,
            'expires_at' => time() + self::TTL_SECONDS,
        ]);
    }

    private function key(Request $request): ?string
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));
        if ($key === '') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $key)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Idempotency-Key must be 8-128 characters using letters, numbers, dot, underscore, colon, or dash.',
            ]);
        }

        return $key;
    }

    private function sessionKey(Request $request, string $operation, string $key): string
    {
        $userId = (string) ($request->user('web')?->getAuthIdentifier() ?: 'guest');
        return 'nextplay_api.checkout_idempotency.'.hash('sha256', $userId.'|'.$operation.'|'.$key);
    }

    private function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
