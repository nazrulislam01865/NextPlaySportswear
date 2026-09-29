<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingDeliveryPageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_super_admin_can_open_editor_and_content_manager_gets_default_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->get(route('admin.shipping-delivery-page.edit'))->assertOk();

        $content = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $this->assertTrue($content->canAdmin('shipping_delivery_page.view'));
        $this->assertTrue($content->canAdmin('shipping_delivery_page.manage'));
    }

    public function test_unrelated_manager_roles_do_not_gain_shipping_delivery_page_defaults(): void
    {
        foreach (['catalog_manager', 'order_manager', 'support_agent'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->assertFalse($user->canAdmin('shipping_delivery_page.view'));
            $this->assertFalse($user->canAdmin('shipping_delivery_page.manage'));
        }
    }

    public function test_view_only_content_manager_cannot_update(): void
    {
        $user = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $roleId = DB::table('admin_roles')->where('slug', 'content_manager')->value('id');
        $permissionId = DB::table('admin_permissions')->where('key', 'shipping_delivery_page.manage')->value('id');
        DB::table('admin_role_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->update(['allowed' => false]);

        $this->actingAs($user, 'admin')->get(route('admin.shipping-delivery-page.edit'))->assertOk();
        $this->actingAs($user, 'admin')->put(route('admin.shipping-delivery-page.update'), [])->assertForbidden();
    }


    public function test_admin_without_view_permission_cannot_open_editor(): void
    {
        $user = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $roleId = DB::table('admin_roles')->where('slug', 'content_manager')->value('id');
        $permissionId = DB::table('admin_permissions')->where('key', 'shipping_delivery_page.view')->value('id');
        DB::table('admin_role_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->update(['allowed' => false]);

        $this->actingAs($user, 'admin')->get(route('admin.shipping-delivery-page.edit'))->assertForbidden();
    }

    public function test_existing_shipping_permissions_still_map_to_shipping_methods(): void
    {
        $request = request();
        $this->assertSame('shipping.view', AdminRbac::permissionForRoute('admin.shipping-methods.index', $request));
        $this->assertSame('shipping.manage', AdminRbac::permissionForRoute('admin.shipping-methods.update', $request));
    }
}
