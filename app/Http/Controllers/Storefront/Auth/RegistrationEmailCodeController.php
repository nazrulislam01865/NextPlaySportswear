<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Data\EmailMessage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Email\CentralEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Verify ownership before any customer account is created. No database schema changes. */
final class RegistrationEmailCodeController extends Controller
{
    public const SESSION_KEY = 'storefront.registration.email_code';
    private const CODE_SECONDS = 600;
    private const VERIFIED_SECONDS = 1200;
    private const RESEND_SECONDS = 45;
    private const MAX_ATTEMPTS = 5;

    /** The test code is available ONLY with an explicit local/testing preview flag. */
    public static function localPreviewEnabled(): bool
    {
        return app()->environment('local', 'testing')
            && (bool) config('security.registration_otp_preview', false);
    }

    public function send(Request $request, CentralEmailService $emails): RedirectResponse
    {
        $email = strtolower(trim((string) $request->input('email')));
        Validator::make(['email' => $email], [
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
        ])->validate();

        $existing = $request->session()->get(self::SESSION_KEY);
        if (is_array($existing) && (int) ($existing['resend_after'] ?? 0) > time()) {
            throw ValidationException::withMessages(['email' => 'Please wait before requesting another verification code.']);
        }

        $preview = self::localPreviewEnabled();
        if (! $preview && ! $emails->enabled()) {
            throw ValidationException::withMessages(['email' => 'Email verification is temporarily unavailable. Please try again later.']);
        }

        $code = $preview ? '123456' : (string) random_int(100000, 999999);
        if (! $preview) {
            try {
                // In normal mode, show step 2 only after transport acceptance.
                $emails->sendNow(new EmailMessage(
                key: 'customer.pre-registration-code',
                recipients: [['email' => $email]],
                subject: 'Your NextPlay Sportswear verification code',
                heading: 'Verify your email address',
                introLines: [
                    'Use this six-digit code to continue creating your NextPlay Sportswear account.',
                    'Do not share this code with anyone.',
                ],
                details: [
                    'Verification Code' => $code,
                    'Code Expires' => '10 minutes',
                ],
                outroLines: ['If you did not request this code, you can safely ignore this email.'],
                ));
            } catch (Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages(['email' => 'We could not send your verification code. Please try again shortly.']);
            }
        }

        $request->session()->put(self::SESSION_KEY, [
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => time() + self::CODE_SECONDS,
            'resend_after' => time() + self::RESEND_SECONDS,
            'attempts' => 0,
            'verified_at' => null,
        ]);

        return $preview
            ? redirect()->route('register')
            : redirect()->route('register')->with('status', 'We sent a six-digit code to '.$email.'.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $state = $request->session()->get(self::SESSION_KEY);
        if (! is_array($state) || empty($state['code_hash']) || (int) ($state['expires_at'] ?? 0) < time()) {
            $request->session()->forget(self::SESSION_KEY);
            throw ValidationException::withMessages(['code' => 'Your code has expired. Request a new verification code.']);
        }
        if ((int) ($state['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Change your email to start again.']);
        }

        if (! Hash::check((string) $data['code'], (string) $state['code_hash'])) {
            $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;
            $request->session()->put(self::SESSION_KEY, $state);
            throw ValidationException::withMessages(['code' => 'Incorrect verification code. Please try again.']);
        }

        $state['verified_at'] = time();
        unset($state['code_hash']);
        $request->session()->put(self::SESSION_KEY, $state);

        return redirect()->route('register');
    }

    public function change(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('register');
    }

    public static function verifiedEmail(Request $request): ?string
    {
        $state = $request->session()->get(self::SESSION_KEY);
        if (! is_array($state) || empty($state['verified_at'])
            || (int) $state['verified_at'] + self::VERIFIED_SECONDS < time()) {
            return null;
        }
        $email = $state['email'] ?? null;

        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function step(Request $request): int
    {
        if (self::verifiedEmail($request) !== null) {
            return 3;
        }
        $state = $request->session()->get(self::SESSION_KEY);

        return is_array($state) && ! empty($state['code_hash']) && (int) ($state['expires_at'] ?? 0) >= time() ? 2 : 1;
    }
}
