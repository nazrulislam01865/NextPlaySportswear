<?php

namespace Tests\Regression;

use Tests\TestCase;

class NextPlayProductsAccountTypographyTest extends TestCase
{
    public function test_all_products_and_sale_share_centralized_type_without_touching_filters(): void
    {
        $css = file_get_contents(resource_path('css/storefront-typography.css'));
        $allProducts = file_get_contents(resource_path('views/storefront/products/index.blade.php'));
        $sale = file_get_contents(resource_path('views/storefront/products/sale.blade.php'));
        $newRules = explode('ALL PRODUCTS / SALE PRODUCTS — typography template v1.1', $css, 2)[1] ?? '';
        $catalogRules = explode('MY ACCOUNT — shared customer account typography template v1.1', $newRules, 2)[0];

        $this->assertStringContainsString('np-products-typography', $allProducts);
        $this->assertStringContainsString('np-products-typography', $sale);
        foreach (['np-products-sale-title', 'np-products-sale-heading', 'np-products-sale-lead',
            'np-products-sale-eyebrow', 'np-products-result-count'] as $selector) {
            $this->assertStringContainsString($selector, $sale);
            $this->assertStringContainsString($selector, $catalogRules);
        }
        foreach (['.np-product-card', '.np-filter-shell', '.np-filter-drawer', '.np-catalog-sort',
            '.np-catalog-active-summary', '.np-filter-mobile-open'] as $protected) {
            $this->assertStringNotContainsString($protected.' {', $catalogRules);
        }
        $this->assertSame(2, substr_count($sale, '<x-storefront.product.category-filter-panel'));
        $this->assertSame(2, substr_count($allProducts, '<x-storefront.product.category-filter-panel'));
        $this->assertStringContainsString("@include('storefront.products._sale-results'", $sale);
        $this->assertStringContainsString("@include('storefront.products._results'", $allProducts);
    }

    public function test_shared_customer_account_frame_uses_the_existing_design_tokens(): void
    {
        $css = file_get_contents(resource_path('css/storefront-typography.css'));
        $frame = file_get_contents(resource_path('views/components/storefront/account/frame.blade.php'));
        $accountSection = file_get_contents(resource_path('views/storefront/account/section.blade.php'));
        $theme = file_get_contents(resource_path('css/storefront-theme.css'));

        $this->assertSame(2, substr_count($frame, 'np-account-typography'));
        $this->assertStringContainsString('np-account-unavailable', $accountSection);
        foreach (['--nps-font:', '--nps-h1:', '--nps-h2:', '--nps-h3:', '--nps-body:',
            '--nps-small:', '--nps-button-size:', '--nps-control:'] as $token) {
            $this->assertStringContainsString($token, $theme);
        }
        foreach (['np-account-prototype-hero h1', 'np-account-prototype-nav__link',
            'np-account-prototype-content h2', 'np-account-prototype-content h3',
            'np-account-welcome-card__description', 'np-account-unavailable .btn',
            'np-account-prototype-content label:not(.sr-only)'] as $selector) {
            $this->assertStringContainsString($selector, $css);
        }
        $this->assertStringContainsString('not([type="checkbox"])', $css);
        $this->assertStringContainsString('not([type="radio"])', $css);
        $this->assertMatchesRegularExpression('/\?v=20[0-9a-z]+/',
            file_get_contents(resource_path('views/components/layouts/storefront.blade.php')));
    }
}
