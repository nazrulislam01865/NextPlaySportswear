<?php

$root = dirname(__DIR__, 2);
$files = [
    'service' => $root.'/app/Services/Storefront/CustomerAccountService.php',
    'controller' => $root.'/app/Http/Controllers/Storefront/Account/OrderCenterController.php',
    'dashboard' => $root.'/resources/views/storefront/account/dashboard.blade.php',
    'orders' => $root.'/resources/views/storefront/account/orders/index.blade.php',
    'page' => $root.'/resources/views/components/storefront/account/page.blade.php',
    'shell' => $root.'/resources/views/components/storefront/account/shell.blade.php',
    'sidebar' => $root.'/resources/views/components/storefront/account/sidebar.blade.php',
    'orderCard' => $root.'/resources/views/components/storefront/account/orders/history-card.blade.php',
    'css' => $root.'/resources/css/storefront.css',
];

$contents = [];
foreach ($files as $key => $path) {
    $contents[$key] = file_get_contents($path);
    if ($contents[$key] === false) {
        fwrite(STDERR, "Unable to read {$key}.\n");
        exit(1);
    }
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(str_contains($contents['dashboard'], 'title="MY ACCOUNT"'), 'Dashboard uses the approved My Account prototype title.');
$expect(str_contains($contents['orders'], 'title="MY ORDERS"'), 'Order history uses the approved My Orders prototype title.');
$expect(str_contains($contents['dashboard'], '<x-storefront.account.page'), 'Dashboard uses the reusable account page shell.');
$expect(str_contains($contents['orders'], '<x-storefront.account.page'), 'Order history uses the reusable account page shell.');
$expect(str_contains($contents['page'], '<x-storefront.account.sidebar'), 'Prototype account page reuses the centralized sidebar component.');
$expect(str_contains($contents['shell'], '<x-storefront.account.sidebar'), 'Legacy account pages reuse the same centralized sidebar component.');
$expect(str_contains($contents['page'], 'np-account-square-cards'), 'Prototype account content uses the centralized square-card scope.');
$expect(str_contains($contents['shell'], 'np-account-square-cards'), 'Legacy account content uses the centralized square-card scope.');
$expect(str_contains($contents['sidebar'], '<x-storefront.account.icon'), 'Account sidebar uses centralized account icons.');
$expect(str_contains($contents['dashboard'], 'btn btn-secondary'), 'Dashboard primary action uses the canonical button system.');
$expect(str_contains($contents['orders'], 'np-orders-filter-bar'), 'Order prototype filter bar is present.');
$expect(str_contains($contents['controller'], "'30' => 'Last 30 Days'"), 'Date range control is backed by server-side filtering.');
$expect(str_contains($contents['controller'], "'recent' => 'Most Recent'"), 'Sort control is backed by server-side ordering.');
$expect(str_contains($contents['orderCard'], '$order->items'), 'Order card uses the already eager-loaded items relationship.');
$expect(str_contains($contents['controller'], "->with('items')"), 'Order history eager-loads product items to avoid N+1 queries.');
$expect(str_contains($contents['service'], 'selectRaw("COUNT(*) as total_orders")'), 'Account order metrics are aggregated in one order query.');
$expect(str_contains($contents['service'], '->reorder()'), 'Account aggregate metrics remove the default order relationship sorting before aggregation.');
$expect(str_contains($contents['css'], 'NEXTPLAY_ACCOUNT_DASHBOARD_ORDER_HISTORY_PROTOTYPE'), 'Prototype styles are centralized in storefront CSS.');
$expect(str_contains($contents['css'], 'var(--np-color-primary)'), 'Prototype consumes centralized theme colors.');
$expect(str_contains($contents['css'], 'var(--np-font-heading)'), 'Prototype consumes centralized font tokens.');

if ($failures !== []) {
    fwrite(STDERR, "Account dashboard/order history prototype regression failed:\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "Account dashboard/order history prototype regression passed.\n";
