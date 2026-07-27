<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_customer_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => $customer->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo($customer, ResetPassword::class);
    }

    public function test_admin_account_does_not_receive_customer_password_reset_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => $admin->email,
        ])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_customer_can_reset_the_password_with_a_valid_token(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('OldPassword123'),
        ]);
        $token = Password::broker('users')->createToken($customer);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword123', $customer->fresh()->password));
    }
}
