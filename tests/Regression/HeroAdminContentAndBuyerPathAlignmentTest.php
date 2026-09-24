<?php

$root = dirname(__DIR__, 2);
$registry = file_get_contents($root.'/app/Support/HomepageSectionRegistry.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/HomepageSectionRequest.php');
$admin = file_get_contents($root.'/resources/views/admin/homepage-sections/edit.blade.php');
$hero = file_get_contents($root.'/resources/views/components/storefront/home/hero.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');

foreach (compact('registry', 'request', 'admin', 'hero', 'css') as $name => $contents) {
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

foreach ([
    'trustline',
    'badge_design_support',
    'badge_bulk_pricing',
    'badge_shipping',
    'stat_value',
    'stat_subtitle',
] as $key) {
    $expect(str_contains($registry, "'{$key}' =>"), "Hero registry defines {$key}.");
    $expect(str_contains($request, "'settings.{$key}'"), "Hero request validates settings.{$key}.");
    $expect(str_contains($admin, "name=\"settings[{$key}]\""), "Hero admin exposes settings[{$key}].");
}

$expect(
    preg_match("/'fields'\s*=>\s*\[[^\]]*'text'[^\]]*'buttons'[^\]]*'hero_slides'[^\]]*'items'[^\]]*'settings'[^\]]*\]/s", $registry) === 1,
    'Hero registry enables settings in its editable field list.'
);

foreach ([
    'Serving teams, clubs, businesses, and event organizers across the USA.',
    'Custom Design Support',
    'Bulk Pricing Available',
    'USA Shipping',
    '500+ Teams',
    'Trusted across the USA',
] as $hardcodedText) {
    $expect(! str_contains($hero, $hardcodedText), "Hero storefront no longer hardcodes: {$hardcodedText}");
}

$expect(str_contains($hero, "data_get(\$section, 'settings'"), 'Hero storefront reads trust/highlight content from section settings.');

$expect(
    preg_match('/\.home-page\s+\.path-card\s*\{[^}]*display:\s*flex\s*;[^}]*flex-direction:\s*column\s*;[^}]*height:\s*100%\s*;/s', $css) === 1,
    'Buyer path cards use a full-height flex-column layout.'
);
$expect(
    preg_match('/\.home-page\s+\.path-card\s+\.link-red\s*\{[^}]*margin-top:\s*auto\s*;/s', $css) === 1,
    'Buyer path CTA is anchored to the bottom with auto top margin.'
);

if ($failures !== []) {
    fwrite(STDERR, "Hero admin / buyer path alignment regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Hero admin / buyer path alignment regression passed.\n";
