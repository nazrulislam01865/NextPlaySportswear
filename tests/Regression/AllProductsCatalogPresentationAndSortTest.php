<?php

$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/storefront/products/index.blade.php');
$css = file_get_contents($root.'/resources/css/storefront.css');
$service = file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$js = file_get_contents($root.'/resources/js/storefront.js');

foreach (compact('view', 'css', 'service', 'js') as $name => $contents) {
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
    ! preg_match('/product\{\{\s*\$products->total\(\)\s*===\s*1.*?found/s', $view),
    'The redundant "products found" count above the All Products heading is removed.'
);

$expect(
    str_contains($view, 'np-catalog-active-bar--plain'),
    'The All Products results/sort row uses the plain visual modifier.'
);
$expect(
    preg_match('/\.storefront-clean-ui\s+\.np-catalog-active-bar--plain\s*\{[^}]*border:\s*0[^}]*background:\s*transparent[^}]*box-shadow:\s*none/s', $css) === 1,
    'The All Products plain results/sort row has no border, background, or shadow.'
);

$expect(
    ! str_contains($view, 'mt-7 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-card'),
    'The pagination area no longer uses the white bordered card wrapper.'
);

$expect(
    str_contains($view, 'data-product-sort')
        && str_contains($js, "url.searchParams.set('sort', sortSelect.value)")
        && str_contains($js, "url.searchParams.delete('page')"),
    'Changing Sort By is handled centrally while preserving current filters and resetting pagination.'
);

$featuredSortPattern = '/default\s*=>\s*\$products\s*\n\s*->orderByDesc\(\'products\.is_featured\'\)\s*\n\s*->orderBy\(\'products\.sort_order\'\)\s*\n\s*->orderByDesc\(\'products\.published_at\'\)\s*\n\s*->orderBy\(\'products\.name\'\)\s*\n\s*->orderByDesc\(\'products\.id\'\)/s';
$expect(
    preg_match($featuredSortPattern, $service) === 1,
    'Featured sorting prioritizes featured products before secondary sort order.'
);

foreach (['featured', 'best-selling', 'newest', 'price-low', 'price-high', 'rating-high', 'name-asc'] as $sortValue) {
    $expect(
        str_contains($view, 'value="'.$sortValue.'"'),
        "Sort option {$sortValue} remains available on All Products."
    );
}

if ($failures !== []) {
    fwrite(STDERR, "All Products catalog presentation/sort regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "All Products catalog presentation/sort regression passed.\n";
