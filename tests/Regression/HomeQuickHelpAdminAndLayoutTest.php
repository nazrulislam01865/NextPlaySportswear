<?php

$root = dirname(__DIR__, 2);
$files = [
    'registry' => 'app/Support/HomepageSectionRegistry.php',
    'request' => 'app/Http/Requests/Admin/HomepageSectionRequest.php',
    'controller' => 'app/Http/Controllers/Admin/HomepageSectionController.php',
    'media' => 'app/Services/Catalog/HomepageSectionMediaService.php',
    'faq' => 'resources/views/components/storefront/home/faq.blade.php',
    'cards' => 'resources/views/components/storefront/home/quick-help.blade.php',
    'icons' => 'resources/views/components/storefront/home/quick-help-icon.blade.php',
    'admin' => 'resources/views/admin/homepage-sections/edit.blade.php',
    'css' => 'resources/css/storefront.css',
    'routes' => 'routes/web.php',
];
foreach ($files as $name => $path) {
    $files[$name] = file_get_contents($root.'/'.$path);
    if ($files[$name] === false) {
        fwrite(STDERR, "Could not read {$name}\n");
        exit(1);
    }
}

$errors = [];
$test = static function ($pass, $label) use (&$errors): void {
    if (! $pass) $errors[] = $label;
};
foreach (['Size Guide', 'Artwork Guidelines', 'Track Your Order', 'Contact Us'] as $label) {
    $test(str_contains($files['registry'], "'title' => '{$label}'"), "Missing Quick Help default: {$label}");
}
foreach (['/size-guide', '/artwork-guidelines', '/track-order', '/contact-us'] as $url) {
    $test(str_contains($files['registry'], "'url' => '{$url}'"), "Missing Quick Help link: {$url}");
    $test(str_contains($files['routes'], "'{$url}'"), "Missing route: {$url}");
}
$test(str_contains($files['faq'], '<x-storefront.home.quick-help'), 'FAQ must render reusable Quick Help component');
$test(str_contains($files['cards'], '<x-storefront.home.section-heading'), 'Quick Help uses centralized heading component');
$test(str_contains($files['cards'], '<x-storefront.home.quick-help-icon'), 'Quick Help uses reusable SVG icon component');
$test(str_contains($files['cards'], 'PublicMedia::url'), 'Uploaded icon URLs use shared media resolver');
$test(str_contains($files['request'], "'settings.quick_help_cards' => ['sometimes', 'array', 'size:4']"), 'Validate exactly four cards');
$test(str_contains($files['request'], 'new SafePublicUrl()'), 'Validate card destinations');
$test(str_contains($files['request'], 'normalizedQuickHelpSettings'), 'Sanitize text and boolean settings');
$test(str_contains($files['media'], 'syncQuickHelpIcons'), 'Store custom card icons using existing public media service');
$test(str_contains($files['media'], 'unset($card[\'remove_icon\'])'), 'Do not persist temporary removal flag');
$test(str_contains($files['controller'], '$existingSettings'), 'Preserve existing images during admin update');
foreach (['quick_help_title', 'quick_help_description', 'show_quick_help', 'icon_file', 'remove_icon', '[enabled]'] as $field) {
    $test(str_contains($files['admin'], $field), "Missing admin control: {$field}");
}
$test(str_contains($files['css'], 'grid-template-columns: repeat(2, minmax(0, 1fr))'), 'Quick Help retains reference 2x2 card design');
$test(str_contains($files['css'], 'grid-template-columns: minmax(0, 1fr) minmax(0, 436px)'), 'FAQ and Quick Help use reference column proportions');
$test(str_contains($files['css'], 'min-height: 196px;'), 'Quick Help cards match 196px prototype minimum height');
$test(str_contains($files['css'], 'width: 94%;'), 'Desktop FAQ column is slightly narrower than before');
$test(str_contains($files['css'], 'color: var(--np-color-secondary);'), 'Arrow uses centralized orange theme token');
$test(str_contains($files['css'], 'max-width: 470px'), 'Mobile cards adapt without horizontal overflow');
$test(str_contains($files['css'], '--np-color-primary'), 'Colors use centralized theme variables');
$test(str_contains($files['css'], 'background: transparent'), 'Card icons do not receive unwanted background');
$manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true);
$cssAsset = $root.'/public/build/'.$manifest['resources/css/storefront.css']['file'];
$test(is_file($cssAsset) && str_contains(file_get_contents($cssAsset), 'np-home-quick-help-card'), 'Prebuilt storefront CSS contains Quick Help styles');

if ($errors) {
    fwrite(STDERR, "Quick Help regression failed:\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}
echo "Quick Help content, admin, validation, media, layout and build-asset checks passed.\n";
