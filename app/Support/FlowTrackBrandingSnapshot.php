<?php

namespace App\Support;

/** Read the storefront's published design tokens rather than duplicating its palette. */
final class FlowTrackBrandingSnapshot
{
    public static function current(): array
    {
        $css = (string) @file_get_contents(resource_path('css/storefront-theme.css'));
        $read = static function (string $name, string $fallback) use ($css): string {
            return preg_match('/'.preg_quote($name, '/').'\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $css, $matches)
                ? strtoupper($matches[1]) : $fallback;
        };

        return [
            'name' => (string) config('storefront.name', 'NextPlay Sportswear'),
            'logo_url' => StorefrontBranding::logoUrl(),
            'favicon_url' => StorefrontBranding::faviconUrl(),
            'primary_color' => $read('--np-color-primary', '#061F44'),
            'secondary_color' => $read('--np-color-secondary', '#CF5D38'),
            'body_color' => $read('--np-color-body', '#334155'),
            'muted_color' => $read('--np-color-muted', '#64748B'),
            'border_color' => $read('--np-color-border', '#E2E8F0'),
            'font_family' => 'Expose',
            'font_base_url' => rtrim(url('/fonts/expose'), '/'),
            'button_radius' => '6.4px',
        ];
    }
}
