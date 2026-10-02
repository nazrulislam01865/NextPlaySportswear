<?php

$root = dirname(__DIR__, 2);
$files = [
    'migration' => 'database/migrations/2026_10_02_000002_add_referral_attribution_to_users_table.php',
    'referrals' => 'app/Services/Referrals/ReferralOfferService.php',
    'rewards' => 'app/Services/Rewards/RewardService.php',
    'checkout' => 'app/Services/Checkout/CheckoutService.php',
    'request' => 'app/Http/Requests/Storefront/Checkout/ReferralCheckoutRequest.php',
    'registration' => 'app/Http/Controllers/Storefront/Auth/RegisteredUserController.php',
];

$source = [];
foreach ($files as $key => $path) {
    $contents = file_get_contents($root.'/'.$path);
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$path}.\n");
        exit(1);
    }
    $source[$key] = $contents;
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($source['migration'], "foreignId('referred_by_user_id')"), 'Referral attribution is persisted on the new customer account.');
$expect(str_contains($source['referrals'], "getAttribute('referred_by_user_id')"), 'Eligibility requires the customer to be attributed to the same referrer.');
$expect(str_contains($source['referrals'], '$customer->orders()->exists()'), 'Existing customers and customers with an earlier order are rejected.');
$expect(str_contains($source['referrals'], 'assertEligibleForCheckout'), 'Referral eligibility has a final server-side checkout validator.');
$expect(str_contains($source['referrals'], "hash_equals("), 'Checkout email is compared securely with the attributed customer account.');
$expect(str_contains($source['checkout'], 'lockForUpdate()'), 'Order placement locks the customer row before one-time referral validation.');
$expect(substr_count($source['checkout'], 'assertEligibleForCheckout(') >= 2, 'Referral eligibility is checked before and inside the order transaction.');
$expect(str_contains($source['rewards'], 'where(\'referred_user_id\', $user->id)->exists()'), 'A referred customer cannot create a second referral redemption.');
$expect(str_contains($source['rewards'], "\$order->status !== 'completed'"), 'Referrer reward issuance requires a completed order.');
$expect(str_contains($source['rewards'], "\$order->payment_status !== 'paid'"), 'Referrer reward issuance requires successful payment.');
$expect(str_contains($source['request'], 'Rule::in([$accountEmail])'), 'Referral checkout email must match the new account email.');
$expect(str_contains($source['request'], "'phone' => ['required'"), 'Referral checkout collects the phone field required by checkout completion.');
$expect(str_contains($source['registration'], 'attachNewCustomer($user)'), 'New-account registration persists referral attribution.');

if ($failures !== []) {
    fwrite(STDERR, "Dynamic referral flow regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Dynamic referral flow regression passed.\n";
