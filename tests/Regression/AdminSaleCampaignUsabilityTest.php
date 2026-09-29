<?php

$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/admin/promotions/sales/create.blade.php');
$css = file_get_contents($root.'/public/css/admin-sale-campaign.css');
$js = file_get_contents($root.'/public/js/admin-sale-campaign.js');
$partial = file_get_contents($root.'/resources/views/admin/promotions/_banner-placement-fields.blade.php');

foreach (compact('view', 'css', 'js', 'partial') as $name => $contents) {
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

$expect(
    str_contains($view, 'np-sale-banner-media-grid')
        && str_contains($view, 'np-sale-banner-device-card')
        && str_contains($view, 'np-sale-banner-placement'),
    'Campaign banner media and placement use dedicated responsive presentation wrappers.'
);

$expect(
    str_contains($view, '24:5 ratio')
        && str_contains($view, '5:4 ratio')
        && str_contains($view, 'Where should this banner appear?'),
    'Campaign banner media presents concise ratio guidance and a clear placement heading.'
);

$expect(
    str_contains($partial, 'np-banner-check-list'),
    'Campaign keeps using the shared placement field partial.'
);

$expect(
    preg_match('/\.np-sale-banner-placement\s+\.np-banner-check-list\s*\{[^}]*grid-template-columns:/s', $css) === 1
        && preg_match('/\.np-sale-banner-placement\s+\.np-banner-check-list\s+label\s*\{[^}]*border:/s', $css) === 1,
    'Campaign placement choices render as a responsive grid of card-like options.'
);

$expect(
    str_contains($view, 'x-ref="excludePickerRoot"')
        && str_contains($view, '@click.outside="closeExcludePicker()"')
        && str_contains($view, '@blur="handleExcludeBlur($event)"')
        && str_contains($view, '@keydown.escape.stop.prevent="closeExcludePicker()"'),
    'Exclude product picker closes through explicit outside, blur and Escape interactions.'
);

$expect(
    str_contains($view, '@mousedown.prevent')
        && str_contains($view, '@click="addExcludedProduct(option)"'),
    'Exclude result selection remains clickable while blur handling is enabled.'
);

$expect(
    str_contains($js, 'closeExcludePicker()')
        && str_contains($js, 'handleExcludeBlur(event)')
        && str_contains($js, 'this.excludePickerOpen = false;')
        && str_contains($js, 'this.excludeResults = [];')
        && str_contains($js, 'this.excludeRequestId += 1;'),
    'Exclude picker lifecycle clears stale results and invalidates in-flight searches when it closes.'
);

if ($failures !== []) {
    fwrite(STDERR, "Admin Sale Campaign usability regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Admin Sale Campaign usability regression passed.\n";
