<?php

$root = dirname(__DIR__, 2);
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$css = (string) file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($builder, '$productHighlights = collect($product[\'features\'] ?? [])'), 'Hero highlight strip is sourced from admin-managed product features.');
$expect(str_contains($builder, '->take(4)'), 'Hero highlight strip is limited to four product highlights.');
$expect(str_contains($builder, 'aria-label="Product highlights"'), 'Hero presents the strip as informational product highlights.');
$expect(! str_contains($builder, 'aria-label="Configurable product options"'), 'Hero highlights are separate from customer customization option groups.');

$expect(str_contains($builder, '$sizeRangeItems = collect($product[\'size_groups\']'), 'Size summary is built from every configured size group.');
$expect(str_contains($builder, "->implode(' · ')"), 'Multiple size-group ranges are presented together in the product size summary.');
$expect(str_contains($builder, 'np-size-chart-tabs'), 'Size-guide modal includes navigation for multiple configured size charts.');
$expect(str_contains($builder, '@click="activeChartGroup = @js($group[\'id\'])"'), 'Each size chart tab switches the active chart group.');
$expect(str_contains($css, '.np-size-chart-tabs'), 'Multiple size-chart navigation is styled in centralized storefront CSS.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail multi-size-guide/managed-highlight regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail multi-size-guide/managed-highlight regression passed.\n";
