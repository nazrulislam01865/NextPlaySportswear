<?php

$root = dirname(__DIR__, 2);
$registry = file_get_contents($root.'/app/Support/HomepageSectionRegistry.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/HomepageSectionRequest.php');
$admin = file_get_contents($root.'/resources/views/admin/homepage-sections/edit.blade.php');
$view = file_get_contents($root.'/resources/views/components/storefront/home/best-selling-gear.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');

foreach (compact('registry', 'request', 'admin', 'view', 'css') as $name => $contents) {
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
    preg_match("/'key'\s*=>\s*'best_selling_gear'.*?'fields'\s*=>\s*\[[^\]]*'settings'[^\]]*\]/s", $registry) === 1,
    'Best-Selling Gear registry exposes section settings.'
);
$expect(
    preg_match("/'key'\s*=>\s*'best_selling_gear'.*?'settings'\s*=>\s*\[[^\]]*'background_color'\s*=>/s", $registry) === 1,
    'Best-Selling Gear registry defines a background color setting.'
);
$expect(str_contains($request, "'settings.background_color'"), 'Homepage request validates the background color.');
$expect(str_contains($request, "'best_selling_gear' =>"), 'Homepage request normalizes Best-Selling Gear settings.');
$expect(str_contains($admin, "name=\"settings[background_color]\""), 'Admin exposes a background color field.');
$expect(str_contains($admin, 'name="image_file"'), 'Admin exposes a background image upload.');
$expect(str_contains($admin, 'name="image_url"'), 'Admin exposes a background image URL.');
$expect(str_contains($admin, 'name="remove_image"'), 'Admin exposes background image removal.');
$expect(str_contains($admin, "\$section->key === 'best_selling_gear'"), 'Background controls are scoped to Best-Selling Gear.');

$expect(str_contains($view, 'PublicMedia::url'), 'Storefront resolves the section background through the shared public media helper.');
$expect(str_contains($view, "settings.background_color"), 'Storefront reads the configured background color.');
$expect(str_contains($view, 'np-best-gear-section--custom-background'), 'Storefront marks custom background mode explicitly.');
$expect(str_contains($view, '--np-best-gear-bg-image'), 'Storefront passes the custom image through a section-scoped CSS variable.');
$expect(str_contains($view, '--np-best-gear-bg-color'), 'Storefront passes the custom color through a section-scoped CSS variable.');

$expect(
    preg_match('/\.home-page\s+\.np-best-gear-section--custom-background\s*\{[^}]*background-color:\s*var\(--np-best-gear-bg-color/s', $css) === 1,
    'Custom background mode applies the configured color.'
);
$expect(
    preg_match('/\.home-page\s+\.np-best-gear-section--custom-background\.np-best-gear-section--has-image\s*\{[^}]*background-image:\s*var\(--np-best-gear-bg-image/s', $css) === 1,
    'Custom image mode applies the configured image.'
);
$expect(
    preg_match('/\.home-page\s+\.np-best-gear-section--custom-background::before\s*,\s*\.home-page\s+\.np-best-gear-section--custom-background::after\s*\{[^}]*display:\s*none/s', $css) === 1,
    'Decorative default overlays are disabled when an admin background is configured.'
);

if ($failures !== []) {
    fwrite(STDERR, "Best-Selling Gear background customization regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Best-Selling Gear background customization regression passed.\n";
