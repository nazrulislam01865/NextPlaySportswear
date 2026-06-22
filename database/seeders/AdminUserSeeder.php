<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production') && blank($password)) {
            throw new \RuntimeException('ADMIN_PASSWORD must be set before seeding production.');
        }

        $admin = User::query()->firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@nextplay.test')]);
        $admin->forceFill([
            'name' => env('ADMIN_NAME', 'NextPlay Administrator'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ]);

        // Re-running seeders must not silently invalidate a real administrator's
        // credentials. Opt in explicitly when a password reset is intended.
        if (! $admin->exists || filter_var(env('ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOLEAN)) {
            $admin->password = Hash::make($password ?: 'ChangeMe123!');
        }

        $admin->save();
    }
}
