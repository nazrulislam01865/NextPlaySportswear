<?php
namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SustainabilityPageAccessTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); AdminRbac::syncDefaults(true); }
    public function test_super_admin_can_open_and_content_manager_gets_defaults(): void { $admin=User::factory()->create(['role'=>'super_admin','is_active'=>true]); $this->actingAs($admin,'admin')->get(route('admin.sustainability-page.edit'))->assertOk(); $content=User::factory()->create(['role'=>'content_manager','is_active'=>true]); $this->assertTrue($content->canAdmin('sustainability_page.view')); $this->assertTrue($content->canAdmin('sustainability_page.manage')); }
    public function test_view_only_user_cannot_update(): void { $u=User::factory()->create(['role'=>'content_manager','is_active'=>true]); $role=DB::table('admin_roles')->where('slug','content_manager')->value('id'); $perm=DB::table('admin_permissions')->where('key','sustainability_page.manage')->value('id'); DB::table('admin_role_permissions')->where('role_id',$role)->where('permission_id',$perm)->update(['allowed'=>false]); $this->actingAs($u,'admin')->get(route('admin.sustainability-page.edit'))->assertOk(); $this->actingAs($u,'admin')->put(route('admin.sustainability-page.update'),[])->assertForbidden(); }

    public function test_user_without_view_permission_cannot_open_editor(): void { $u=User::factory()->create(['role'=>'content_manager','is_active'=>true]); $role=DB::table('admin_roles')->where('slug','content_manager')->value('id'); $perm=DB::table('admin_permissions')->where('key','sustainability_page.view')->value('id'); DB::table('admin_role_permissions')->where('role_id',$role)->where('permission_id',$perm)->update(['allowed'=>false]); $this->actingAs($u,'admin')->get(route('admin.sustainability-page.edit'))->assertForbidden(); }
    public function test_shipping_method_permissions_remain_unchanged(): void { $this->assertSame('shipping.view',AdminRbac::permissionForRoute('admin.shipping-methods.index',request())); $this->assertSame('shipping.manage',AdminRbac::permissionForRoute('admin.shipping-methods.update',request())); }
}
