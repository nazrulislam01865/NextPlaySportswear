<?php

namespace Tests\Unit;

use App\Support\AdminRbac;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class AdminRbacCategoryProductAssignmentTest extends TestCase
{
    public function test_removing_a_category_product_assignment_requires_category_management_not_record_delete_permission(): void
    {
        $request = Request::create('/admin/categories/1/products/2', 'DELETE');
        $route = (new Route('DELETE', '/admin/categories/{category}/products/{product}', static fn () => null))
            ->name('admin.categories.products.destroy');
        $request->setRouteResolver(static fn () => $route);

        $this->assertSame('categories.manage', AdminRbac::permissionForRoute($route->getName(), $request));
        $this->assertFalse(AdminRbac::requiresDeletePermission($request));
    }
}
