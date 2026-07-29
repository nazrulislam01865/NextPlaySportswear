<?php

namespace Tests\Unit;

use App\Support\AdminPagination;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AdminPaginationTest extends TestCase
{
    public function test_it_accepts_supported_items_per_page_values(): void
    {
        $request = Request::create('/admin/products', 'GET', ['per_page' => 60]);

        $this->assertSame(60, AdminPagination::resolve($request, 25));
    }

    public function test_it_falls_back_to_the_page_default_for_unsupported_values(): void
    {
        $request = Request::create('/admin/products', 'GET', ['per_page' => 999]);

        $this->assertSame(25, AdminPagination::resolve($request, 25));
    }
}
