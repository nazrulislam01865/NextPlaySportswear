<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontButtonCascadeTest extends TestCase
{
    public function test_broad_page_link_rules_do_not_override_centralized_button_text_colors(): void
    {
        $css = file_get_contents(resource_path('css/storefront.css'));

        $this->assertStringContainsString('.home-page a:not(.btn) {', $css);
        $this->assertStringNotContainsString(".home-page a {\n    color: inherit;", $css);
        $this->assertStringContainsString('.bulk-quote-page a:not(.btn) { color: inherit; text-decoration: none; }', $css);
        $this->assertStringNotContainsString('.bulk-quote-page a { color: inherit; text-decoration: none; }', $css);
    }
}
