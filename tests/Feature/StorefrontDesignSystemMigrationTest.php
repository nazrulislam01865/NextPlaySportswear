<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontDesignSystemMigrationTest extends TestCase
{
    public function test_storefront_uses_the_approved_central_brand_and_expose_type_system(): void
    {
        $themeCss = file_get_contents(resource_path('css/storefront-theme.css'));
        $fontAssets = file_get_contents(resource_path('views/components/storefront/font-assets.blade.php'));
        $layout = file_get_contents(resource_path('views/components/layouts/storefront.blade.php'));

        $this->assertStringContainsString('--np-color-primary: #061F44;', $themeCss);
        $this->assertStringContainsString('--np-color-secondary: #CF5D38;', $themeCss);
        $this->assertStringContainsString('--np-color-orange: #CF5D38;', $themeCss);
        $this->assertStringContainsString('--np-font-body: "Expose"', $themeCss);
        $this->assertStringContainsString('--np-title-1-size: 48px;', $themeCss);
        $this->assertStringContainsString('--np-body-1-size: 18px;', $themeCss);
        $this->assertStringContainsString('--np-cta-all-caps-regular-size: 16px;', $themeCss);
        $this->assertStringNotContainsString('fonts.googleapis.com', $fontAssets);
        $this->assertStringContainsString('content="#061F44"', $layout);

        foreach (['Regular', 'Medium', 'Bold', 'Black'] as $weight) {
            $this->assertFileExists(resource_path("fonts/expose/Expose-{$weight}.woff2"));
        }
    }
    public function test_tailwind_and_buttons_resolve_through_the_central_theme(): void
    {
        $tailwind = file_get_contents(base_path('tailwind.config.js'));
        $css = file_get_contents(resource_path('css/storefront.css'));

        $this->assertStringContainsString("red: themeColor('--np-color-secondary')", $tailwind);
        $this->assertStringContainsString("navy: themeColor('--np-color-primary')", $tailwind);
        $this->assertStringContainsString('font-size: var(--np-cta-all-caps-regular-size);', $css);
        $this->assertStringContainsString('.btn-primary,', $css);
        $this->assertStringContainsString('background: var(--np-color-primary);', $css);
        $this->assertStringContainsString('.btn-secondary,', $css);
        $this->assertStringContainsString('background: var(--np-color-secondary);', $css);
    }

    public function test_shared_storefront_css_consumes_semantic_type_and_color_tokens(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));
        $pagination = file_get_contents(resource_path('css/pagination.css'));

        $this->assertStringNotContainsString('Inter', $css);
        $this->assertStringNotContainsString('Oswald', $css);
        $this->assertStringContainsString('font-size: var(--np-title-2-size);', $css);
        $this->assertStringContainsString('font-size: var(--np-title-4-size);', $css);
        $this->assertStringContainsString('color: var(--np-color-primary);', $pagination);
        $this->assertStringNotContainsString('#15345d', strtolower($pagination));
        $this->assertStringNotContainsString('#0d2545', strtolower($pagination));
    }

    public function test_customer_facing_views_do_not_bypass_the_central_font_or_legacy_palette(): void
    {
        $roots = [
            resource_path('views/components/storefront'),
            resource_path('views/storefront'),
            resource_path('views/errors'),
        ];
        $legacyColors = ['#e91d33', '#c9182b', '#15345d', '#0d2545', '#2467b7'];

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }
                $content = file_get_contents($file->getPathname());
                if (str_contains($file->getPathname(), '/testimonials.blade.php')) {
                    $content = preg_replace('/\$colors\s*=\s*\[[^;]+;/s', '', $content) ?? $content;
                }
                $this->assertDoesNotMatchRegularExpression('/\b(?:Inter|Oswald)\b/', $content, $file->getPathname());
                $this->assertStringNotContainsString('fonts.googleapis.com', $content, $file->getPathname());
                foreach ($legacyColors as $color) {
                    $this->assertStringNotContainsString($color, strtolower($content), $file->getPathname());
                }
            }
        }
    }

    public function test_compiled_storefront_asset_matches_the_centralized_theme(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $asset = $manifest['resources/css/storefront.css']['file'] ?? null;

        $this->assertNotNull($asset);
        $this->assertFileExists(public_path('build/'.$asset));

        $compiled = file_get_contents(public_path('build/'.$asset));
        $this->assertMatchesRegularExpression('/\bExpose\b/', $compiled);
        $this->assertStringContainsString('#061f44', strtolower($compiled));
        $this->assertStringContainsString('#cf5d38', strtolower($compiled));
        $this->assertStringContainsString('.btn-primary', $compiled);
        $this->assertDoesNotMatchRegularExpression('/\b(?:Inter|Oswald)\b/', $compiled);

        foreach (['#e91d33', '#c9182b', '#15345d', '#0d2545', '#2467b7'] as $legacyColor) {
            $this->assertStringNotContainsString($legacyColor, strtolower($compiled));
        }
    }

    public function test_storefront_source_has_no_known_legacy_brand_color_bypasses(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));
        $legacyBrandColors = [
            '#ed102b', '#f1122e', '#ff233c', '#d81e35', '#c80d24', '#d80e27',
            '#ef2028', '#ef233c', '#c91832', '#b20c20', '#2f67c7', '#285caa',
            '#0b63ce', '#0e4f9f', '#061e40', '#062042', '#071a35', '#08182f',
            '#081d3f', '#2f6fbd', '#00c2ff',
        ];

        foreach ($legacyBrandColors as $legacyColor) {
            $this->assertStringNotContainsString($legacyColor, strtolower($css));
        }
    }

}
