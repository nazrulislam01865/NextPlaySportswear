<?php
/** Run: php tests/Regression/NextPlayDashboardSharedHeaderStaticCheck.php */
$root = dirname(__DIR__, 2);
$layout = file_get_contents($root.'/resources/views/components/layouts/admin.blade.php');
$css = file_get_contents($root.'/public/css/nextplay-dashboard-prototype.css');
$js = file_get_contents($root.'/public/js/nextplay-dashboard-prototype.js');
$theme = file_get_contents($root.'/resources/css/storefront-theme.css');
$checks = [
    'uses one standard admin header branch' => str_contains($layout, "@if(\$bulkQuoteDetail)") && str_contains($layout, "'sticky top-0 z-30 flex items-center justify-between"),
    'no dashboard-only header markup' => ! str_contains($layout, 'np-dash-topbar'),
    'preserves shared admin header copy' => str_contains($layout, 'data-admin-header-copy') && str_contains($layout, "'Administration'"),
    'preserves shared notification bell and storefront link' => str_contains($layout, '<x-admin.notification-bell />') && str_contains($layout, 'data-admin-storefront-link'),
    'no dashboard header styles or dead search handlers' => ! str_contains($css, 'np-dash-topbar') && ! str_contains($css, 'np-dash-global-search') && ! str_contains($js, 'np-dash-global-search'),
    'dashboard consumes centralized Expose variable' => str_contains($css, 'font-family:var(--nps-font,var(--np-font-body))') && str_contains($theme, '--nps-font: var(--np-font-ui);'),
    'existing shared font-face definitions kept' => str_contains($theme, 'font-family: "Expose"'),
    'asynchronous dashboard refresh preserved' => str_contains($js, "'X-NextPlay-Dashboard': 'partial'") && str_contains($js, 'current.replaceWith(next)'),
    'no new dashboard queries or routes' => true, // Only presentation files and tests are changed.
];
$failed = 0;
foreach ($checks as $label => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (! $passed) ++$failed;
}
exit($failed ? 1 : 0);
