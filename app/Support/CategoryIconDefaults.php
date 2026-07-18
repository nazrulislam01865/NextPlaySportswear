<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CategoryIconDefaults
{
    public static function key(?string $label): string
    {
        $name = Str::of((string) $label)->lower()->squish()->toString();

        if (str_contains($name, 'hardware') || str_contains($name, 'tool')) {
            return 'hardware';
        }

        if (str_contains($name, 'bag')) {
            return 'bag';
        }

        if (str_contains($name, 'cap') || str_contains($name, 'head')) {
            return 'headwear';
        }

        // Keep the existing filter icons, but do not let "Gear" force the
        // hardware/accessory icon for labels like "Fan & Event Gear".
        if (str_contains($name, 'sport') || str_contains($name, 'event') || str_contains($name, 'fan')) {
            return 'sport-event';
        }

        if (str_contains($name, 'accessor') || str_contains($name, 'gear')) {
            return 'accessory';
        }

        return 'apparel';
    }

    public static function url(?string $label): string
    {
        return asset('images/category-icons/'.self::key($label).'.svg').'?v=20260718f';
    }

    public static function svgPaths(?string $label): string
    {
        return match (self::key($label)) {
            'bag' => '<path d="M6 8h12l-1 11H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
            'headwear' => '<path d="M3.2 14.1c1.8-5.1 5.7-8.4 11.3-8.4h1.1c3.3 0 5.8 2.8 6 6.4c.1 1.4-.9 2.5-2.3 2.5h-2.6c-4.4 0-8.8-1.5-13.5-.5Z"/><path d="M3.2 14.1C2 15 1.2 16.3.8 18c-.2.8.6 1.4 1.3 1.1l4.4-1.8c1.9-.8 4-.7 5.9.1c2.8 1.2 6.1.9 8.8-.8"/><path d="M8.6 13.6c.8-2.8 2.1-5.4 4-7.8"/><path d="M16 14.5c.6-3 .4-6.1-.6-8.8"/><path d="M14.3 5.7c.2-1 1-1.7 2-1.7s1.8.7 2 1.7"/><path d="M4 14c3.5-.8 7.5.5 12 1"/>',
            'sport-event' => '<path d="M4 5l8 4l8-4v12l-8 4l-8-4V5Z"/><path d="M12 9v12"/><path d="M4 5l8 4l8-4"/>',
            'hardware' => '<g stroke-width="1.25"><path d="M4.4 4.3l3.2 3.2l-2.3 2.3l-3.2-3.2a4.9 4.9 0 0 0 6.1 6.2l8 8a1.8 1.8 0 0 0 2.6-2.6l-8-8a4.9 4.9 0 0 0-6.4-5.9Z"/><path d="M13.6 7.1l5-4.7l3 3l-4.7 5"/><path d="M15.1 8.6l4.9-4.7"/><path d="M13.1 10.9l-7.9 7.9"/><path d="M3.5 17.1l3.4 3.4"/><path d="M4.1 16.5l3.4 3.4"/></g>',
            'accessory' => '<path d="M5 9h14v10H5z"/><path d="M8 9V7a4 4 0 0 1 8 0v2"/>',
            default => '<path d="M9 4h6l3 3l-2 2v11H8V9L6 7l3-3Z"/><path d="M9 4c.7 1.2 1.7 1.8 3 1.8S14.3 5.2 15 4"/>',
        };
    }
}
