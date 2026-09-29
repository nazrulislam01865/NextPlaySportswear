<?php

$root = dirname(__DIR__, 2);
$files = [
    'request' => file_get_contents($root.'/app/Http/Requests/Admin/AboutPageRequest.php'),
    'media' => file_get_contents($root.'/app/Services/Catalog/AboutPageMediaService.php'),
    'service' => file_get_contents($root.'/app/Services/Storefront/AboutPageService.php'),
    'admin' => file_get_contents($root.'/resources/views/admin/about-page/edit.blade.php'),
    'storefront' => file_get_contents($root.'/resources/views/storefront/content/about.blade.php'),
    'rbac' => file_get_contents($root.'/app/Support/AdminRbac.php'),
    'routes' => file_get_contents($root.'/routes/web.php'),
    'service_card' => file_get_contents($root.'/resources/views/components/storefront/about/service-card.blade.php'),
    'process_step' => file_get_contents($root.'/resources/views/components/storefront/about/process-step.blade.php'),
    'controller' => file_get_contents($root.'/app/Http/Controllers/Admin/AboutPageController.php'),
];

$requirements = [
    'request' => [
        'imageRules(10240)', 'imageRules(2048)', 'image/jpeg,image/png,image/webp,image/avif',
        "SERVICE_IDS = ['custom-teamwear', 'sportswear-gear', 'bulk-orders']",
        "PROCESS_IDS = ['choose-product', 'personalise', 'review-details', 'place-order']",
        "GALLERY_IDS = ['team', 'fabric', 'number', 'celebration']",
        "use App\\Support\\PublicUrl;",
        "PublicUrl::isAllowed(\$value)",
    ],
    'media' => [
        "about-page/introduction", "about-page/what-we-do/icons", "about-page/how-we-work/icons", "about-page/gallery", "about-page/help/icons",
        'commitCleanup', 'rollback', 'isAboutOwnedPath',
    ],
    'admin' => [
        'name="introduction_image"', 'name="service_icon_{{ $index }}"', 'name="process_icon_{{ $index }}"', 'name="gallery_image_{{ $index }}"', 'name="help_icon"',
        'name="seo[title]"', 'name="seo[description]"', 'Save About Page',
    ],
    'storefront' => [
        "data_get(\$about, 'hero.title')", "data_get(\$about, 'what_we_do.cards', [])", "data_get(\$about, 'how_we_work.steps', [])", "data_get(\$about, 'gallery.items', [])",
        "data_get(\$about, 'cta.primary_url')", "data_get(\$about, 'help.button_url')",
    ],
    'rbac' => ["'about_page.view'", "'about_page.manage'", "'admin.about-page.edit'", "'admin.about-page.update'"],
    'routes' => ["/about-page", "name('about-page.edit')", "name('about-page.update')"],
];

foreach ($requirements as $fileKey => $snippets) {
    foreach ($snippets as $snippet) {
        if (! str_contains($files[$fileKey], $snippet)) {
            fwrite(STDERR, "Missing {$fileKey} requirement: {$snippet}\n");
            exit(1);
        }
    }
}

foreach (['reorder_section', 'add_section', 'delete_section', 'section_visibility'] as $forbidden) {
    if (str_contains($files['admin'], $forbidden)) {
        fwrite(STDERR, "Forbidden About structural control found: {$forbidden}\n");
        exit(1);
    }
}

if (substr_count($files['admin'], 'type="file"') !== 5) {
    fwrite(STDERR, "Expected five file-input templates (1 intro + 3 loop templates + 1 help).\n");
    exit(1);
}

// At render time those loop templates expand to 1 + 3 + 4 + 4 + 1 = 13 upload slots.
$renderedUploadSlots = 1 + 3 + 4 + 4 + 1;
if ($renderedUploadSlots !== 13) {
    fwrite(STDERR, "About upload slot count changed unexpectedly.\n");
    exit(1);
}

$inlineIconRequirements = [
    'service_card' => 'width:54px;height:54px;object-fit:contain',
    'process_step' => 'width:42px;height:42px;object-fit:contain',
    'storefront' => 'width:48px;height:48px;object-fit:contain',
];
foreach ($inlineIconRequirements as $fileKey => $snippet) {
    if (! str_contains($files[$fileKey], $snippet)) {
        fwrite(STDERR, "Uploaded icon sizing must be self-contained in {$fileKey}: {$snippet}\n");
        exit(1);
    }
}

$tryPosition = strpos($files['controller'], 'try {');
$preparePosition = strpos($files['controller'], '$this->media->prepare');
if ($tryPosition === false || $preparePosition === false || $preparePosition < $tryPosition) {
    fwrite(STDERR, "About upload staging must be inside the guarded save flow.\n");
    exit(1);
}

echo "About admin management static check passed (13 fixed upload slots).\n";
