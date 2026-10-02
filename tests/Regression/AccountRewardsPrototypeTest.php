<?php

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root.'/routes/web.php');
$controller = file_get_contents($root.'/app/Http/Controllers/Storefront/Account/AccountController.php');
$service = file_get_contents($root.'/app/Services/Storefront/CustomerAccountService.php');
$referralService = file_get_contents($root.'/app/Services/Referrals/ReferralOfferService.php');
$rewardsView = file_get_contents($root.'/resources/views/storefront/account/rewards.blade.php');
$referralsView = file_get_contents($root.'/resources/views/storefront/account/referrals.blade.php');
$pageComponent = file_get_contents($root.'/resources/views/components/storefront/account/rewards/page.blade.php');
$sidebar = file_get_contents($root.'/resources/views/components/storefront/account/sidebar.blade.php');
$banner = file_get_contents($root.'/resources/views/components/storefront/account/rewards/referral-banner.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$js = file_get_contents($root.'/resources/js/storefront.js');

foreach (compact('routes', 'controller', 'service', 'referralService', 'rewardsView', 'referralsView', 'pageComponent', 'sidebar', 'banner', 'css', 'js') as $name => $contents) {
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$name} source.\n");
        exit(1);
    }
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($routes, "Route::get('/rewards', [AccountController::class, 'rewards'])->name('rewards');"), 'Rewards route exists inside the authenticated account group.');
$expect(str_contains($routes, "Route::get('/refer-a-friend', [AccountController::class, 'referrals'])->name('referrals');"), 'Refer-a-friend route exists inside the authenticated account group.');
$expect(str_contains($controller, "'rewards' => \$this->accountService->rewardsPage(\$request->user())"), 'Rewards controller uses the centralized account service.');
$expect(str_contains($controller, "'referrals' => \$this->accountService->referralsPage(\$request->user())"), 'Referral controller uses the centralized account service.');
$expect(str_contains($service, 'public function sidebarData(User $user): array'), 'Rewards and referral pages can render the shared account sidebar without loading dashboard statistics.');
$expect(str_contains($controller, "'navigation' => \$this->accountService->accountNavigation()"), 'Rewards and referrals reuse the canonical account navigation.');
$expect(str_contains($pageComponent, '<x-storefront.account.sidebar'), 'Rewards and referral pages reuse the canonical account sidebar component.');
$expect(substr_count($service, "'label' => 'My Rewards'") === 1, 'My Rewards exists only in the canonical account navigation definition.');
$expect(substr_count($service, "'label' => 'Refer a Friend'") === 1, 'Refer-a-friend exists only in the canonical account navigation definition.');
$expect(str_contains($referralService, 'Crypt::encryptString'), 'Referral share identifier is encrypted instead of exposing the database ID.');

$expect(str_contains($rewardsView, 'title="MY REWARDS"'), 'Rewards page title matches the approved prototype.');
$expect(str_contains($rewardsView, '<x-storefront.account.rewards.referral-banner'), 'Rewards page reuses the referral banner component.');
$expect(str_contains($rewardsView, 'REWARD ACTIVITY'), 'Rewards activity section is present.');
$expect(str_contains($rewardsView, "route('products.index')"), 'Shop Now action is wired to the product catalog.');
$expect(str_contains($rewardsView, "route('account.referrals')"), 'Refer-a-friend action is wired to the referral page.');
$expect(str_contains($rewardsView, "route('terms')"), 'Programme terms action is wired to the existing terms page.');

$expect(str_contains($referralsView, 'title="REFER A FRIEND"'), 'Referral page title matches the approved prototype.');
$expect(str_contains($referralsView, 'referralShareActions'), 'Referral share controls use the reusable share behavior.');
$expect(str_contains($referralsView, 'COPY LINK'), 'Copy-link control is present.');
$expect(str_contains($referralsView, 'SHARE BY EMAIL'), 'Email share control is present.');
$expect(str_contains($referralsView, 'SHARE LINK'), 'Native share control is present.');
$expect(str_contains($referralsView, '<x-storefront.account.rewards.status-pill'), 'Referral statuses use a reusable component.');

$expect(str_contains($pageComponent, '<x-layouts.storefront'), 'Rewards pages use the project storefront layout.');
$expect(str_contains($sidebar, '<x-storefront.account.icon'), 'Shared account sidebar uses the centralized account icon component.');
$expect(str_contains($banner, 'btn btn-secondary'), 'Prototype CTA reuses the centralized storefront button system.');
$expect(str_contains($css, 'NEXTPLAY_ACCOUNT_REWARDS_PROTOTYPE'), 'Prototype styles are centralized in storefront CSS.');
$expect(str_contains($css, 'font-family: var(--np-font-heading)'), 'Prototype typography resolves through centralized font tokens.');
$expect(str_contains($css, 'var(--np-color-primary)'), 'Prototype brand color resolves through centralized theme tokens.');
$expect(str_contains($js, 'window.referralShareActions'), 'Referral share behavior is centralized in storefront JavaScript.');
$expect(str_contains($js, "navigator.share"), 'Share Link uses the native share API when available.');
$expect(str_contains($js, "navigator.clipboard"), 'Copy Link uses the secure clipboard API with fallback behavior.');

if ($failures !== []) {
    fwrite(STDERR, "Account rewards prototype regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Account rewards prototype regression passed.\n";
