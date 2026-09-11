<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_customer_with_correct_password_receives_suspension_message(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
            'email' => 'suspended@example.test',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This customer account is currently suspended. Please contact support if you believe this is a mistake.',
        ]);
        $this->assertGuest('web');
    }

    public function test_existing_suspended_customer_session_is_forced_out_on_next_request(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
            'auth_session_version' => 3,
        ]);

        $response = $this->actingAs($customer, 'web')->get(route('home'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'This customer account is currently suspended. Please contact support if you believe this is a mistake.',
        ]);
        $this->assertGuest('web');
    }
}
