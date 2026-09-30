<?php
$root = dirname(__DIR__, 2);
$files = [
    'migration' => $root.'/database/migrations/2026_09_30_000300_add_media_to_production_and_shipping_methods.php',
    'service' => $root.'/app/Services/Catalog/MasterMethodMediaService.php',
    'productionModel' => $root.'/app/Models/ProductionMethod.php',
    'shippingModel' => $root.'/app/Models/ShippingMethod.php',
    'productionRequest' => $root.'/app/Http/Requests/Admin/ProductionMethodRequest.php',
    'shippingRequest' => $root.'/app/Http/Requests/Admin/ShippingMethodRequest.php',
    'productionController' => $root.'/app/Http/Controllers/Admin/ProductionMethodController.php',
    'shippingController' => $root.'/app/Http/Controllers/Admin/ShippingMethodController.php',
    'productionForm' => $root.'/resources/views/admin/production-methods/_form.blade.php',
    'shippingForm' => $root.'/resources/views/admin/shipping-methods/_form.blade.php',
    'mediaComponent' => $root.'/resources/views/components/admin/master-method-media.blade.php',
];
$failures = [];
$expect = static function (bool $ok, string $message) use (&$failures): void { if (! $ok) $failures[] = $message; };
foreach ($files as $key => $path) $expect(is_file($path), "Missing {$key}: {$path}");
$read = static fn(string $key): string => is_file($files[$key]) ? (string) file_get_contents($files[$key]) : '';
$migration = $read('migration');
$expect(substr_count($migration, "nullable()->after") >= 4 || (str_contains($migration, "image_path") && str_contains($migration, "image_url")), 'Migration adds nullable image_path/image_url to both master tables.');
foreach (['productionModel','shippingModel'] as $key) {
    $src = $read($key);
    $expect(str_contains($src, "'image_path'") && str_contains($src, "'image_url'"), "{$key} fillable includes media fields.");
    $expect(str_contains($src, 'function imageUrl()') && str_contains($src, 'PublicMedia::url'), "{$key} resolves media through PublicMedia.");
}
$service = $read('service');
$expect(str_contains($service, 'master-data/production-methods') && str_contains($service, 'master-data/shipping-methods'), 'Media service restricts owned directories.');
$expect(str_contains($service, 'Storage::disk(\'public\')'), 'Media service uses public disk.');
$expect(str_contains($service, 'remove_image'), 'Media service supports removal.');
foreach (['productionRequest','shippingRequest'] as $key) {
    $src = $read($key);
    $expect(str_contains($src, "'image_file'") && str_contains($src, "'image_url'") && str_contains($src, "'remove_image'"), "{$key} validates media controls.");
}
foreach (['productionController','shippingController'] as $key) {
    $src = $read($key);
    $expect(str_contains($src, 'MasterMethodMediaService'), "{$key} uses shared media service.");
}
foreach (['productionForm','shippingForm'] as $key) {
    $src = $read($key);
    $expect(str_contains($src, 'enctype="multipart/form-data"'), "{$key} is multipart.");
    $expect(str_contains($src, '<x-admin.master-method-media :method="$method" />'), "{$key} reuses the master media component.");
}
$mediaComponent = $read('mediaComponent');
$expect(str_contains($mediaComponent, 'name="image_file"') && str_contains($mediaComponent, 'name="image_url"') && str_contains($mediaComponent, 'name="remove_image"'), 'Reusable master media component exposes upload, URL, and remove fields.');
if ($failures) { fwrite(STDERR, "Master method media persistence regression failed:\n - ".implode("\n - ", $failures)."\n"); exit(1); }
echo "Master method media persistence regression passed.\n";
