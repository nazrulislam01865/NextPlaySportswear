<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PREVIOUS = [
        ['Choose Product', 'Pick the product, sport, category, or apparel type.'],
        ['Share Custom Details', 'Send your logo, colors, names, numbers, size list, and quantity.'],
        ['Review Mockup', 'We prepare or review the artwork before production.'],
        ['Confirm Order', 'Approve the final details, price, and timeline.'],
        ['Production & Shipping', 'Your order goes into production and ships to your address.'],
    ];

    private const UPDATED = [
        ['Choose your kit', 'Find the right style for your team.', 'NextPlay navy custom jerseys on display'],
        ['Make it yours', 'Add colours, logos, names and numbers.', 'Customize a NextPlay jersey on a laptop'],
        ['Approve your artwork', 'Review your design before production.', 'Front and back jersey artwork ready for approval'],
        ['We make your kit', 'Your approved design goes into production.', 'NextPlay jersey being stitched in production'],
        ['Delivered to you', 'Packed with care and sent to your door.', 'Folded NextPlay jersey packed for delivery'],
    ];

    public function up(): void
    {
        $this->transform(false);
    }

    public function down(): void
    {
        $this->transform(true);
    }

    private function transform(bool $reverse): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $section = DB::table('homepage_sections')->where('key', 'process')->first(['id', 'items']);
        if (! $section) {
            return;
        }

        $items = json_decode((string) $section->items, true);
        if (! is_array($items) || count($items) !== 5) {
            // Never replace an admin-customized number of cards.
            return;
        }

        foreach ($items as $index => $item) {
            $expected = $reverse ? self::UPDATED[$index] : self::PREVIOUS[$index];
            if (! is_array($item) || ($item['title'] ?? '') !== $expected[0] || ($item['description'] ?? '') !== $expected[1]) {
                // Never overwrite edited titles or descriptions.
                return;
            }
        }

        foreach ($items as $index => &$item) {
            $replacement = $reverse ? self::PREVIOUS[$index] : self::UPDATED[$index];
            $item['title'] = $replacement[0];
            $item['description'] = $replacement[1];
            $defaultUrl = sprintf('/images/ordering-process/step-%02d.webp', $index + 1);

            if ($reverse) {
                if (($item['image_url'] ?? null) === $defaultUrl) {
                    unset($item['image_url']);
                }
                if (($item['image_alt'] ?? null) === self::UPDATED[$index][2]) {
                    unset($item['image_alt']);
                }
            } elseif (empty($item['image_path']) && empty($item['image_url'])) {
                $item['image_url'] = $defaultUrl;
                $item['image_alt'] = $item['image_alt'] ?? $replacement[2];
            }
        }
        unset($item);

        DB::table('homepage_sections')->where('id', $section->id)->update([
            'items' => json_encode(array_values($items), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
};
