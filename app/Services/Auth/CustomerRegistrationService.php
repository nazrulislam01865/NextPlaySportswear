<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CustomerRegistrationService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = new User();
            $user->forceFill([
                'name' => Str::squish(strip_tags($data['name'])),
                'email' => Str::lower(trim($data['email'])),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => null,
                'password' => $data['password'],
            ]);
            $user->save();

            return $user;
        });
    }
}
