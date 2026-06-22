<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            AdminUserSeeder::class,
            ProductSeeder::class,
            CatalogNavigationSeeder::class,
            HomepageSlideSeeder::class,
        ]);

        // Keep demo accounts out of production and make local/testing seeding idempotent.
        if (app()->environment(['local', 'testing'])) {
            $testUser = User::query()->firstOrNew([
                'email' => 'test@example.com',
            ]);

            $testUser->forceFill([
                'name' => 'Test User',
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => $testUser->email_verified_at ?? now(),
            ]);

            // Do not reset an existing test user's password on every seed run.
            if (! $testUser->exists) {
                $testUser->password = 'password';
            }

            $testUser->save();
        }
    }
}
