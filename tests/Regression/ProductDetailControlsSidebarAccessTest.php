<?php

namespace Tests\Regression;

use Tests\TestCase;

class ProductDetailControlsSidebarAccessTest extends TestCase
{
    public function test_product_detail_controls_are_exposed_from_catalog_sidebar(): void
    {
        $blade = file_get_contents(resource_path('views/components/layouts/admin.blade.php'));

        $catalogPosition = strpos($blade, '>Catalog</p>');
        $controlsPosition = strpos($blade, '>Product Detail Controls</x-admin.sidebar-link>');
        $storePosition = strpos($blade, '>Store</p>');

        $this->assertNotFalse($catalogPosition);
        $this->assertNotFalse($controlsPosition);
        $this->assertGreaterThan($catalogPosition, $controlsPosition);
        $this->assertTrue($storePosition === false || $controlsPosition < $storePosition);
        $this->assertStringContainsString("\$canAdmin('product_detail_controls.view') || \$canAdmin('products.view')", $blade);
    }

    public function test_product_permissions_are_a_safe_fallback_for_centralized_controls(): void
    {
        $middleware = file_get_contents(app_path('Http/Middleware/EnsureAdminPermission.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/ProductDetailUiSettingsController.php'));

        $this->assertStringContainsString("'product_detail_controls.view' => 'products.view'", $middleware);
        $this->assertStringContainsString("'product_detail_controls.manage' => 'products.manage'", $middleware);
        $this->assertStringContainsString("canAdmin('products.manage')", $controller);
    }
}
