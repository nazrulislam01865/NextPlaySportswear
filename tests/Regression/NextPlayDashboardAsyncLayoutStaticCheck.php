<?php

/** Lightweight source regression; runs without Laravel vendor dependencies. */
$root = dirname(__DIR__, 2);
$load = static fn (string $path): string => file_get_contents($root.'/'.$path) ?: '';
$controller = $load('app/Http/Controllers/Admin/DashboardController.php');
$blade = $load('resources/views/admin/partials/dashboard-content.blade.php');
$layout = $load('resources/views/admin/dashboard.blade.php');
$javascript = $load('public/js/nextplay-dashboard-prototype.js');
$css = $load('public/css/nextplay-dashboard-prototype.css');

$checks = [
    'dashboard reuses existing aggregate controller' => str_contains($controller, "return view(\$view, [")
        && str_contains($controller, "'admin.partials.dashboard-content'"),
    'dashboard partial does not duplicate admin shell' => !str_contains($blade, '<x-layouts.admin')
        && str_contains($layout, "@include('admin.partials.dashboard-content')"),
    'date filter no longer submits full page on change' => !str_contains($blade, 'this.form.submit()')
        && str_contains($blade, 'data-dashboard-period-form'),
    'one same-origin fragment request with cancellation' => str_contains($javascript, 'await fetch(url.toString()')
        && str_contains($javascript, 'AbortController')
        && str_contains($javascript, "'X-NextPlay-Dashboard': 'partial'")
        && str_contains($javascript, "credentials: 'same-origin'"),
    'date response replaces only dashboard' => str_contains($javascript, 'current.replaceWith(next)')
        && str_contains($javascript, 'initDashboard(next)'),
    'reporting period retains browser navigation history' => str_contains($javascript, 'history.pushState')
        && str_contains($javascript, "addEventListener('popstate'"),
    'date filter has no reload/navigation call' => !str_contains($javascript, 'window.location.reload')
        && !str_contains($javascript, 'window.location.href ='),
    'product revenue cannot be shortened to 75px' => str_contains($css, '.np-dashboard .np-dash-product-price')
        && str_contains($css, 'max-width:none;')
        && str_contains($css, 'font-variant-numeric:tabular-nums;'),
    'right utility rail has a readable width' => str_contains($css, 'minmax(292px,310px)')
        && str_contains($css, '.np-dashboard .np-dash-notification-list > a'),
    'cards equalize heights within each row' => str_contains($css, '.np-dashboard .np-dash-analytics-row > .np-dash-panel')
        && str_contains($css, '.np-dashboard .np-dash-bottom-row > .np-dash-panel'),
    'shared admin sidebar untouched' => str_contains($layout, "<x-layouts.admin title=\"Dashboard\">")
        && !str_contains($css, '#admin-sidebar'),
];

$failed = 0;
foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    if (!$passed) $failed++;
}
exit($failed === 0 ? 0 : 1);
