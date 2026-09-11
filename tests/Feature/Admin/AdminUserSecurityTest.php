<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_update_cannot_promote_a_customer_through_direct_route_binding(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.update', $customer), [
                'name' => 'Promoted Customer',
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertNotFound();

        $this->assertSame('customer', $customer->fresh()->role);
    }

    public function test_non_super_admin_cannot_modify_super_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.update', $superAdmin), [
                'name' => 'Changed Owner',
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('super_admin', $superAdmin->fresh()->role);
    }
}
