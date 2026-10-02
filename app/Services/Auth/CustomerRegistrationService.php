<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CustomerRegistrationService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data, ?int $referredByUserId = null): User
    {
        return DB::transaction(function () use ($data, $referredByUserId): User {
            if ($referredByUserId && $referredByUserId > 0) {
                $referrer = User::query()
                    ->whereKey($referredByUserId)
                    ->where('role', 'customer')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $referrer instanceof User) {
                    throw ValidationException::withMessages([
                        'referral' => 'This referral is no longer available. Please request a new referral link.',
                    ]);
                }
            }

            $user = new User();
            $user->forceFill([
                'name' => Str::squish(strip_tags($data['name'])),
                'email' => Str::lower(trim($data['email'])),
                'role' => 'customer',
                'is_active' => true,
                'referred_by_user_id' => $referredByUserId && $referredByUserId > 0 ? $referredByUserId : null,
                'email_verified_at' => config('security.email_verification.enabled', false)
                    ? null
                    : now(),
                'password' => $data['password'],
            ]);
            $user->save();

            return $user;
        });
    }
}
