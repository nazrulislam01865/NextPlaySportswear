<?php

namespace App\Services\Payments;

/**
 * Preserve only fields required to match and validate a signed Stripe event.
 * Full Stripe objects may include billing details, emails and card information.
 */
final class SafeStripeWebhookPayload
{
    public static function fromVerified(array $event): array
    {
        $object = (array) data_get($event, 'data.object', []);
        $allowed = [
            'id', 'payment_status', 'status', 'payment_intent', 'currency',
            'amount_total', 'amount_received', 'charge', 'client_reference_id',
        ];
        $safe = [];
        foreach ($allowed as $key) {
            $value = $object[$key] ?? null;
            // No expanded Stripe objects or nested customer/payment details.
            if (is_int($value) || is_bool($value)
                || (is_string($value) && strlen($value) <= 255)) {
                $safe[$key] = $value;
            }
        }
        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $safeMetadata = [];
        foreach (['order_id', 'order_number', 'payment_id', 'order_payment_id', 'installment_id'] as $key) {
            $value = $metadata[$key] ?? null;
            if ((is_string($value) || is_int($value)) && strlen((string) $value) <= 100) {
                $safeMetadata[$key] = (string) $value;
            }
        }
        if ($safeMetadata !== []) {
            $safe['metadata'] = $safeMetadata;
        }
        if (isset($object['last_payment_error'])) {
            $error = (array) $object['last_payment_error'];
            $safe['last_payment_error'] = array_filter([
                'code' => self::safeErrorCode($error['code'] ?? null),
                'decline_code' => self::safeErrorCode($error['decline_code'] ?? null),
            ]);
        }

        return [
            'id' => (string) ($event['id'] ?? ''),
            'type' => (string) ($event['type'] ?? ''),
            'livemode' => (bool) ($event['livemode'] ?? false),
            'data' => ['object' => $safe],
        ];
    }

    private static function safeErrorCode(mixed $code): ?string
    {
        return is_string($code) && preg_match('/^[a-z0-9_]{1,80}$/D', $code) ? $code : null;
    }
}
