<?php

$root = dirname(__DIR__, 2);

$viewPath = $root.'/resources/views/storefront/categories/show.blade.php';
$indexPath = $root.'/resources/views/storefront/categories/index.blade.php';
$controllerPath = $root.'/app/Http/Controllers/Storefront/CategoryController.php';
$cardPath = $root.'/resources/views/components/storefront/sport-index-card.blade.php';
$cssPath = $root.'/resources/css/storefront.css';
$catalogServicePath = $root.'/app/Services/Storefront/CategoryCatalogService.php';
$adminFormPath = $root.'/resources/views/admin/categories/_form.blade.php';
$adminControllerPath = $root.'/app/Http/Controllers/Admin/CategoryController.php';
$mediaServicePath = $root.'/app/Services/Catalog/CategoryMediaService.php';
$builtCssPaths = glob($root.'/public/build/assets/storefront-*.css') ?: [];

$view = file_get_contents($viewPath);
$index = file_get_contents($indexPath);
$controller = file_get_contents($controllerPath);
$card = file_get_contents($cardPath);
$css = file_get_contents($cssPath);
$catalogService = file_get_contents($catalogServicePath);
$adminForm = file_get_contents($adminFormPath);
$adminController = file_get_contents($adminControllerPath);
$mediaService = file_get_contents($mediaServicePath);
$builtCss = $builtCssPaths !== [] ? file_get_contents($builtCssPaths[0]) : '';

if (
    $view === false || $index === false || $controller === false || $card === false
    || $css === false || $catalogService === false || $adminForm === false
    || $adminController === false || $mediaService === false || $builtCss === false
) {
    fwrite(STDERR, "Unable to read category storefront sources.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(
    ! str_contains($view, 'Related categories')
        && ! str_contains($view, '$relatedCategories'),
    'Category detail page removes the old Related categories section.'
);

$expect(
    ! str_contains($view, 'Sport-specific')
        && ! str_contains($index, 'Sport-specific')
        && str_contains($view, 'Shop by Sport')
        && str_contains($view, 'Looking for sport-specific uniforms or gear? Start with your sport and find matching products faster.'),
    'Both category pages remove the Sport-specific eyebrow while keeping the Shop by Sport heading and description.'
);

$expect(
    str_contains($card, 'aspect-square')
        && str_contains($card, 'style="aspect-ratio: 1 / 1;"')
        && ! str_contains($card, 'min-h-[260px]')
        && ! str_contains($card, 'rounded-[')
        && ! str_contains($card, 'rounded-lg')
        && ! str_contains($card, 'rounded-xl')
        && ! str_contains($card, 'rounded-2xl'),
    'The shared sport-index-card is a sharp-cornered 1:1 square without the old minimum-height sizing.'
);

$expect(
    preg_match('/\.np-sport-index-card\s*\{[^}]*aspect-ratio:\s*1\s*\/\s*1\s*;[^}]*\}/s', $css) === 1
        && ! preg_match('/\.np-sport-index-card\s*\{[^}]*min-height\s*:/s', $css),
    'Storefront CSS preserves a true 1:1 sport card and does not override it with a minimum height.'
);

$expect(
    $builtCss === ''
        || (! str_contains($builtCss, '.np-sport-index-card{aspect-ratio:4/5;min-height:260px}')
            && ! str_contains($builtCss, '.np-sport-index-card{aspect-ratio:1;min-height:245px}')),
    'Existing production storefront CSS does not retain the old non-square/minimum-height sport card rules.'
);

$expect(
    str_contains($catalogService, "'image' => \$category->thumbnailUrl()")
        && str_contains($adminForm, 'name="thumbnail_file"')
        && str_contains($adminForm, 'title="Category Image"')
        && str_contains($adminForm, 'Shop by Sport card image')
        && str_contains($adminController, '$this->mediaService->sync($category, $request);')
        && str_contains($mediaService, '$uploaded->store("categories/')
        && str_contains($mediaService, '", \'public\')'),
    'Shop by Sport images stay connected to the editable admin Category Image thumbnail flow.'
);

$expect(
    str_contains($view, '<x-storefront.sport-index-card :sport="$sport" />'),
    'Category detail page reuses the existing sport-index-card component.'
);

$expect(
    str_contains($controller, "'sports' => \$this->catalog->sports()")
        && ! str_contains($controller, "'relatedCategories' => \$this->catalog->relatedCategories(\$category)"),
    'Category detail controller supplies sports and stops loading related categories.'
);

$indexSportSectionStart = strpos($index, '<section class="bg-[#f3f5f7] py-[66px]" id="sports"');
$expect(
    $indexSportSectionStart !== false
        && str_contains($view, 'class="bg-[#f3f5f7] py-[66px]"')
        && str_contains($view, 'grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4'),
    'Category detail Shop by Sport section matches the existing category-index layout classes.'
);

if ($failures !== []) {
    fwrite(STDERR, "Category detail Shop by Sport reuse regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Category detail Shop by Sport reuse regression passed.\n";
