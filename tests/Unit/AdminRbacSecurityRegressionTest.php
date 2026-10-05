<?php

namespace Tests\Unit;

use App\Support\AdminRbac;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminRbacSecurityRegressionTest extends TestCase
{
    #[DataProvider('routePermissions')]
    public function test_sensitive_admin_routes_resolve_to_expected_permission(string $routeName, string $method, string $permission): void
    {
        $request = Request::create('/admin/test', $method);

        $this->assertSame($permission, AdminRbac::permissionForRoute($routeName, $request));
    }

    /** @return array<string,array{string,string,string}> */
    public static function routePermissions(): array
    {
        return [
            'media gallery is read-only' => ['admin.media-library.index', 'GET', 'media.view'],
            'media upload page requires manage' => ['admin.media-library.upload', 'GET', 'media.manage'],
            'media store requires manage' => ['admin.media-library.store', 'POST', 'media.manage'],
            'world cup list requires customization view' => ['admin.world-cup-customization-options.type', 'GET', 'customization.view'],
            'world cup store requires customization manage' => ['admin.world-cup-customization-options.store', 'POST', 'customization.manage'],
            'fabric import requires customization manage' => ['admin.jersey-customization-options.import-fabrics', 'POST', 'customization.manage'],
            'bulk quote detail is order view' => ['admin.bulk-quotes.show', 'GET', 'orders.view'],
            'bulk quote update is order manage' => ['admin.bulk-quotes.update', 'PATCH', 'orders.manage'],
            'bulk quote retry is order manage' => ['admin.bulk-quotes.retry-sync', 'POST', 'orders.manage'],
            'country calling code master list is order view' => ['admin.country-calling-codes.index', 'GET', 'orders.view'],
            'country calling code master store is order manage' => ['admin.country-calling-codes.store', 'POST', 'orders.manage'],
            'bulk quote budget master list is order view' => ['admin.bulk-quote-budget-ranges.index', 'GET', 'orders.view'],
            'bulk quote budget master update is order manage' => ['admin.bulk-quote-budget-ranges.update', 'PUT', 'orders.manage'],
            'order sync retry is order manage' => ['admin.orders.retry-sync', 'POST', 'orders.manage'],
        ];
    }
}
