<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** File-based storefront icon configuration; no schema change or database query. */
final class BulkQuoteIcons
{
    public const SLOTS = [
        'product' => 'Product & Customisation',
        'contact' => 'Contact Information',
        'delivery' => 'Delivery Requirements',
        'attachments' => 'Attachments & Notes',
        'help' => 'Need a hand?',
    ];

    private const CONFIG_PATH = 'nextplay/bulk-quote-icons.json';

    private static ?array $loaded = null;

    public static function paths(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        $json = Storage::disk('local')->exists(self::CONFIG_PATH)
            ? Storage::disk('local')->get(self::CONFIG_PATH)
            : null;
        $stored = is_string($json) ? json_decode($json, true) : null;
        $stored = is_array($stored) ? $stored : [];
        $paths = [];
        foreach (self::SLOTS as $key => $_label) {
            $value = $stored[$key] ?? null;
            $paths[$key] = is_string($value) && preg_match('~^storefront/bulk-quote-icons/[a-zA-Z0-9._/-]+$~', $value)
                ? $value : null;
        }

        return self::$loaded = $paths;
    }

    public static function url(string $key): ?string
    {
        $path = self::paths()[$key] ?? null;

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path) : null;
    }

    public static function save(array $paths): void
    {
        if (! Storage::disk('local')->put(self::CONFIG_PATH, json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            throw new \RuntimeException('Unable to write bulk quote icon settings.');
        }
        self::$loaded = null;
    }
}
