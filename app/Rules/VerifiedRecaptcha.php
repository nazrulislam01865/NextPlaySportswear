<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VerifiedRecaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siteKey = (string) config('services.recaptcha.site_key');
        $secretKey = (string) config('services.recaptcha.secret_key');

        // Never allow submissions to bypass CAPTCHA when credentials are missing.
        if ($siteKey === '' || $secretKey === '') {
            $fail('Quote requests are temporarily unavailable. Please try again later.');

            return;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(6)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                ]);
        } catch (ConnectionException) {
            $fail('CAPTCHA verification is unavailable. Please try again.');

            return;
        }

        if (! $response->ok()
            || $response->json('success') !== true
            || ! hash_equals(strtolower(request()->getHost()), strtolower((string) $response->json('hostname', '')))) {
            $fail('Please complete the CAPTCHA verification and try again.');
        }
    }
}
