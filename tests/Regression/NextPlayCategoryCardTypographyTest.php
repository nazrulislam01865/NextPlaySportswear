<?php

namespace Tests\Regression;

use Tests\TestCase;

class NextPlayCategoryCardTypographyTest extends TestCase
{
    public function test_category_hierarchy_cards_reuse_the_homepage_size_tokens(): void
    {
        $theme = file_get_contents(resource_path('css/storefront-theme.css'));
        $css = file_get_contents(resource_path('css/storefront-typography.css'));

        foreach ([
            '--nps-category-card-title: .96rem;',
            '--nps-category-card-title-weight: 800;',
            '--nps-category-card-description: .86rem;',
            '--nps-category-card-count: .72rem;',
            '--nps-category-card-link: .72rem;',
        ] as $token) {
            $this->assertStringContainsString($token, $theme);
        }
        foreach ([
            '.np-home-category-card__title,.np-all-category-card__title,.np-category-child-title',
            '.np-category-child-card .np-category-child-description',
            '.home-page #categories .home-featured-category-grid .np-home-category-card__title',
            '.home-page #categories .home-featured-category-grid .np-home-category-card__description',
            'font-size: var(--nps-category-card-title) !important;',
            'font-size: var(--nps-category-card-count) !important;',
            'font-size: var(--nps-category-card-description) !important;',
        ] as $expected) {
            $this->assertStringContainsString($expected, $css);
        }
        $this->assertStringNotContainsString('flex-basis: 17.5rem;', $css);
        $this->assertStringNotContainsString('.np-catalog-filter-', $css);
    }
}
