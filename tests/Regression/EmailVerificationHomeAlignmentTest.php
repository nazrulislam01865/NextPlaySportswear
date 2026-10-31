<?php

/** Regression contract runnable without Composer dependencies. */
$root = dirname(__DIR__, 2);
$read = static fn (string $path): string => file_get_contents($root.'/'.$path) ?: '';
$registration = $read('app/Http/Controllers/Storefront/Auth/RegisteredUserController.php');
$verification = $read('app/Http/Controllers/Storefront/Auth/EmailVerificationController.php');
$referral = $read('resources/views/storefront/referral/register.blade.php');
$routes = $read('routes/web.php');
$layout = $read('resources/views/components/layouts/storefront.blade.php');
$redirect = $read('app/Support/StorefrontRedirect.php');
$source = $read('resources/css/storefront-typography.css');
$published = $read('public/css/storefront-typography.css');

$rules = [
    'Auth continuation never points back to the old email verification notice' => str_contains($redirect, "'/email/verify', '/email/verified'"),
    'Normal and referral signup require email code verification' => str_contains($registration, 'if ($preverifiedEmail === null || ! hash_equals('),
    'Registration saves the verified timestamp' => str_contains($registration, "forceFill(['email_verified_at' => now()])->saveQuietly()"),
    'Successful registration never redirects to legacy notice' => ! str_contains($registration, "redirect()->route('verification.notice')"),
    'Referral signup uses shared email OTP send' => str_contains($referral, "route('register.email.send')"),
    'Referral signup uses shared OTP verification' => str_contains($referral, "route('register.email.verify')"),
    'Referral signup completes with session-verified email' => str_contains($referral, 'name="email" value="{{ $verifiedEmail }}"'),
    'Legacy email route validates signed links without requiring a browser login' => str_contains($routes, "->middleware(['signed', 'throttle:30,1'])"),
    'Verification resolves the signed account, not the browser user' => str_contains($verification, "->whereKey(\$id)"),
    'Verification compares the email ownership hash' => str_contains($verification, "hash_equals(sha1(\$customer->getEmailForVerification()), \$hash)"),
    'Cross-browser verification never silently authenticates' => str_contains($verification, "return redirect()->route('login')"),
    'FAQ and Quick Help share a parent heading row' => str_contains($source, 'grid-template-rows: max-content auto'),
    'Shared row applies only to the two-column homepage FAQ' => str_contains($source, 'np-home-faq-layout:not(.np-home-faq-layout--single)'),
    'Home layout uses a new versioned stylesheet' => str_contains($layout, '?v=20261031faqsharedrow2'),
    'Compiled static stylesheet includes the shared heading row' => str_contains($published, 'grid-template-rows: max-content auto'),
];

$bad = array_keys(array_filter($rules, static fn ($v): bool => ! $v));
if ($bad) {
    fwrite(STDERR, "Verification/FAQ alignment regression failed:\n - ".implode("\n - ", $bad)."\n");
    exit(1);
}
echo count($rules)." email-verification and FAQ-alignment contracts passed.\n";
