<?php

$root = dirname(__DIR__, 2);
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$form = (string) file_get_contents($root.'/resources/views/admin/products/_form.blade.php');
$adminJs = (string) file_get_contents($root.'/resources/js/admin.js');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($builder, '$productHighlights = collect($product[\'features\'] ?? [])'), 'Storefront highlights are sourced from product.features.');
$expect(str_contains($builder, '->take(4)'), 'Storefront limits product highlights to four items.');
$expect(str_contains($builder, 'aria-label="Product highlights"'), 'Storefront renders the strip as informational product highlights.');
$expect(! str_contains($builder, 'aria-label="Configurable product options"'), 'Storefront highlight strip is not presented as customization choices.');
$expect(! str_contains($builder, 'np-product-option-highlight'), 'Storefront highlight strip no longer uses selectable customization option cards.');

$expect(str_contains($form, "'features' => old('features', \$product->features ?? [])"), 'Admin product form loads product.features into Alpine state.');
$expect(str_contains($form, 'Product Highlights'), 'Admin product form exposes a Product Highlights editor.');
$expect(str_contains($form, ':name="`features[${index}]`"'), 'Admin product form submits feature rows through the existing features array.');
$expect(str_contains($form, '@click="addFeature()"'), 'Admin product form lets admins add highlight rows.');
$expect(str_contains($form, 'features.splice(index, 1)'), 'Admin product form lets admins remove highlight rows.');
$expect(str_contains($adminJs, "features: initial.features?.length ? initial.features : ['']"), 'Existing admin form state continues to own the product features array.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail managed-highlight regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail managed-highlight regression passed.\n";
