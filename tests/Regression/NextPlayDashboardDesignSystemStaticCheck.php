<?php

/** Run with: php tests/Regression/NextPlayDashboardDesignSystemStaticCheck.php */
$root = dirname(__DIR__, 2);
$layout = file_get_contents($root.'/resources/views/components/layouts/admin.blade.php');
$css = file_get_contents($root.'/public/css/nextplay-dashboard-prototype.css');
$brand = file_get_contents($root.'/resources/views/components/admin/brand-identity.blade.php');
$theme = file_get_contents($root.'/resources/css/storefront-theme.css');

$checks = [
    'shared nav used exactly once' => substr_count($layout, 'data-admin-sidebar-nav') === 1,
    'no dashboard replacement nav' => ! str_contains($layout, '<x-admin.dashboard-navigation'),
    'no hidden all-admin-tools menu' => ! str_contains($layout, 'All admin tools'),
    'original sidebar resize retained' => str_contains($layout, 'admin-sidebar-resizer'),
    'original sidebar scroll retained' => str_contains($layout, '<x-admin.sidebar-scroll-restore'),
    'sidebar does not have dashboard-only CSS' => ! str_contains($css, '#admin-sidebar') && ! str_contains($css, '--admin-sidebar-width'),
    'dashboard uses semantic Expose font variable' => str_contains($css, 'font-family:var(--nps-font'),
    'dashboard uses semantic heading token' => str_contains($css, '--nps-h5'),
    'dashboard uses semantic caption token' => str_contains($css, '--nps-caption'),
    'dashboard uses shared color tokens' => str_contains($css, '--nps-navy') && str_contains($css, '--nps-link'),
    'font faces defined by existing design system' => str_contains($theme, 'font-family: "Expose"'),
    'brand retains website configured footer logo' => str_contains($brand, 'StorefrontBranding::footerLogoUrl()'),
    'broken logo has fallback' => str_contains($brand, 'data-logo-fallback=') && str_contains($brand, 'data-logo-default='),
    'unused duplicate nav component is removed' => ! file_exists($root.'/resources/views/components/admin/dashboard-navigation.blade.php'),
];
$failed = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (! $ok) { ++$failed; }
}
exit($failed > 0 ? 1 : 0);
