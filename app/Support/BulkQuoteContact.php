<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Bulk-quote-specific support contact, managed from the existing admin settings. */
final class BulkQuoteContact
{
    private const CONFIG_PATH = 'nextplay/bulk-quote-contact.json';

    private static ?string $savedNumber = null;

    public static function configuredNumber(): string
    {
        if (self::$savedNumber !== null) {
            return self::$savedNumber;
        }

        $json = Storage::disk('local')->exists(self::CONFIG_PATH)
            ? Storage::disk('local')->get(self::CONFIG_PATH) : null;
        $settings = is_string($json) ? json_decode($json, true) : null;
        $number = is_array($settings) ? ($settings['whatsapp_number'] ?? '') : '';
        return self::$savedNumber = is_string($number) ? $number : '';
    }

    public static function whatsappNumber(): string
    {
        return self::configuredNumber() ?: (string) config('storefront.whatsapp', '');
    }

    public static function whatsappUrl(): ?string
    {
        $saved = self::configuredNumber();
        if ($saved !== '') {
            $digits = preg_replace('/\D+/', '', $saved);
            return $digits ? 'https://wa.me/'.$digits : null;
        }

        // Respect an existing custom storefront WhatsApp URL until explicitly overridden.
        $default = config('storefront.whatsapp_url');
        if (is_string($default) && trim($default) !== '') {
            return $default;
        }
        $digits = preg_replace('/\D+/', '', (string) config('storefront.whatsapp'));
        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public static function save(string $number): void
    {
        if (! Storage::disk('local')->put(self::CONFIG_PATH, json_encode([
            'whatsapp_number' => $number,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            throw new \RuntimeException('Unable to save the bulk quote contact number.');
        }
        self::$savedNumber = $number;
    }
}
