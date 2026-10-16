<?php

// Dependency-free check for the admin layout's contextual alert ownership.
$root = dirname(__DIR__, 2);
$layout = file_get_contents($root.'/resources/views/components/layouts/admin.blade.php');

if (! is_string($layout)) {
    fwrite(STDERR, "Could not read admin layout.\n");
    exit(1);
}

$extractPatterns = static function (string $variable) use ($layout): array {
    $pattern = '/\$'.preg_quote($variable, '/').'\s*=\s*request\(\)->routeIs\((.*?)\);/s';
    if (preg_match($pattern, $layout, $matches) !== 1) {
        throw new RuntimeException("Missing admin alert owner list: {$variable}");
    }
    preg_match_all("/'([^']+)'/", $matches[1], $routes);
    return $routes[1];
};

$matchesRoute = static function (string $route, array $patterns): bool {
    foreach ($patterns as $pattern) {
        if (fnmatch($pattern, $route)) {
            return true;
        }
    }
    return false;
};

$statusPatterns = $extractPatterns('hasPageStatusAlert');
$validationPatterns = $extractPatterns('hasPageValidationSummary');

$localStatusRoutes = [
    'admin.coupons.index',
    'admin.promotions.sales.index',
    'admin.storefront-branding.edit',
    'admin.about-page.edit',
    'admin.shipping-delivery-page.edit',
    'admin.sustainability-page.edit',
    'admin.email-customization.templates',
    'admin.holiday-calendars.index',
];
$localValidationRoutes = [
    'admin.coupons.create',
    'admin.coupons.edit',
    'admin.storefront-branding.edit',
    'admin.about-page.edit',
    'admin.shipping-delivery-page.edit',
    'admin.sustainability-page.edit',
    'admin.categories.create',
    'admin.users.index',
    'admin.role-matrix.index',
    'admin.bulk-quotes.show',
    'admin.email-customization.edit',
    'admin.holiday-calendars.edit',
    'admin.products.edit',
];
$failures = [];
foreach ($localStatusRoutes as $route) {
    if (! $matchesRoute($route, $statusPatterns)) {
        $failures[] = "Duplicate status alert remains possible: {$route}";
    }
}
foreach ($localValidationRoutes as $route) {
    if (! $matchesRoute($route, $validationPatterns)) {
        $failures[] = "Duplicate validation summary remains possible: {$route}";
    }
}
foreach (['admin.dashboard', 'admin.coupons.edit', 'admin.categories.index'] as $route) {
    if ($matchesRoute($route, $statusPatterns)) {
        $failures[] = "Global status incorrectly suppressed: {$route}";
    }
}
foreach (['admin.dashboard', 'admin.coupons.index', 'admin.categories.index'] as $route) {
    if ($matchesRoute($route, $validationPatterns)) {
        $failures[] = "Global validation summary incorrectly suppressed: {$route}";
    }
}
if (! str_contains($layout, "session('status') && ! \$hasPageStatusAlert")
    || ! str_contains($layout, '$errors->any() && ! $hasPageValidationSummary')
) {
    $failures[] = 'Shared alerts must honor the contextual alert owner lists.';
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures)."\n");
    exit(1);
}

echo "Admin alert deduplication checks passed.\n";
