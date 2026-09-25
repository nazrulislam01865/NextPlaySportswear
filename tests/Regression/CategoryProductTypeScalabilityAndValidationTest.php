<?php

$root = dirname(__DIR__, 2);

$paths = [
    'request' => $root.'/app/Http/Requests/Admin/CategoryFormRequest.php',
    'controller' => $root.'/app/Http/Controllers/Admin/CategoryController.php',
    'form' => $root.'/resources/views/admin/categories/_form.blade.php',
    'options' => $root.'/app/Support/ProductTypeOptions.php',
];

$contents = [];
foreach ($paths as $name => $path) {
    $contents[$name] = is_file($path) ? file_get_contents($path) : false;
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(
    $contents['options'] !== false
        && str_contains($contents['options'], 'final class ProductTypeOptions')
        && str_contains($contents['options'], 'public static function all(): array'),
    'Product type choices must come from one shared master-data provider.'
);

$expect(
    $contents['request'] !== false
        && str_contains($contents['request'], 'ProductTypeOptions::all()')
        && ! str_contains($contents['request'], "'filter_product_types' => ['nullable', 'array', 'max:100']")
        && str_contains($contents['request'], "'max:'.count(\$productTypeOptions)")
        && str_contains($contents['request'], 'Rule::in($productTypeOptions)'),
    'Category validation must scale to the current master-data product type count instead of failing above 100 selections.'
);

$expect(
    $contents['controller'] !== false
        && str_contains($contents['controller'], 'ProductTypeOptions::all()')
        && ! str_contains($contents['controller'], 'private function productTypeOptions(): array'),
    'The category controller must reuse the same product type provider as validation so the option list and validation cannot drift.'
);

$expect(
    $contents['form'] !== false
        && substr_count($contents['form'], 'Please correct the highlighted information') === 0,
    'The category form must not duplicate the centralized admin validation summary.'
);

if ($failures !== []) {
    fwrite(STDERR, "Category product-type scalability/validation regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Category product-type scalability/validation regression passed.\n";
