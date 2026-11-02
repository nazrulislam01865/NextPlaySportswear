<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class ContentDesignSystemTest extends TestCase
{
    public function test_template_tokens_have_a_single_source(): void
    {
        $theme = file_get_contents(resource_path('css/storefront-theme.css'));
        $compiled = file_get_contents(public_path('css/storefront-typography.css'));
        $content = file_get_contents(public_path('css/nextplay-content-design-system.css'));

        foreach (['--np-content-font:', '--np-content-title-size:',
                  '--np-content-btn-height:', '--np-content-color-action:',
                  '--np-content-nav-size:'] as $token) {
            $this->assertStringContainsString($token, $theme);
            $this->assertStringContainsString($token, $compiled);
        }
        $this->assertStringContainsString('var(--np-content-btn-height)', $content);
        $this->assertStringContainsString('var(--np-content-nav-size)', $content);
    }

    public function test_managed_guides_use_reusable_cta_components(): void
    {
        foreach (['shipping', 'how-to-order', 'artwork-guidelines', 'size-guide'] as $page) {
            $view = file_get_contents(resource_path("views/storefront/content/{$page}.blade.php"));
            $this->assertStringContainsString('btn btn-secondary nps-template-cta', $view);
        }
    }
}
