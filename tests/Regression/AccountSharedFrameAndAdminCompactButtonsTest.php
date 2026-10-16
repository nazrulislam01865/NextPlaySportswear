<?php

// Source-level guard for the shared account frame and compact admin action sizing.
$root = dirname(__DIR__, 2);
$read = static function (string $file) use ($root): string {
    $contents = file_get_contents($root.'/'.$file);
    if ($contents === false) {
        throw new RuntimeException('Missing file: '.$file);
    }
    return $contents;
};

$frame = $read('resources/views/components/storefront/account/frame.blade.php');
$page = $read('resources/views/components/storefront/account/page.blade.php');
$shell = $read('resources/views/components/storefront/account/shell.blade.php');
$rewards = $read('resources/views/components/storefront/account/rewards/page.blade.php');
$orders = $read('resources/views/components/storefront/account/orders/page.blade.php');
$adminCss = $read('resources/css/admin.css');
$storefrontCss = $read('resources/css/storefront.css');

$assert = static function (bool $valid, string $message): void {
    if (! $valid) throw new RuntimeException($message);
};

$assert(str_contains($frame, 'np-account-prototype-hero'), 'Shared frame must render the account hero.');
$assert(str_contains($frame, 'np-account-prototype-layout'), 'Shared frame must render the consistent account grid.');
$assert(substr_count($frame, '<x-storefront.account.sidebar') === 1, 'Shared frame must have one sidebar.');
$assert(str_contains($frame, 'np-account-square-cards'), 'All account content uses the approved square card scope.');
$assert(str_contains($page, '<x-storefront.account.frame'), 'Dashboard and order history use the frame.');
$assert(str_contains($shell, '<x-storefront.account.frame'), 'Profile, addresses, payments and order subpages use the frame.');
$assert(str_contains($rewards, '<x-storefront.account.frame'), 'Rewards and referrals use the same frame.');
$assert(! str_contains($orders, '<h2 class="mt-2'), 'Order subpages do not duplicate the main hero title.');
$assert(! str_contains($orders, '$errors->any()'), 'Order subpages do not duplicate the shared error summary.');
$assert(str_contains($adminCss, 'NEXTPLAY_ADMIN_COMPACT_ACTIONS'), 'Admin buttons have a scoped centralized compact style.');
$assert(str_contains($adminCss, 'height: 36px !important'), 'Admin full-size buttons have consistent 36px heights.');
$assert(str_contains($adminCss, '.product-add-button') && str_contains($adminCss, '.admin-modern-button'), 'Compact styling includes legacy and reusable buttons.');
$assert(str_contains($storefrontCss, 'np-account-order-tools'), 'Shared account CSS includes compact order actions.');

echo "Shared account layout and compact admin button regression passed.\n";
