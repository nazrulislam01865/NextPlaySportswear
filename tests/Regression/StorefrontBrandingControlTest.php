<?php

$root = dirname(__DIR__, 2);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$files = [
    'model' => $root.'/app/Models/StorefrontBrandingSetting.php',
    'support' => $root.'/app/Support/StorefrontBranding.php',
    'controller' => $root.'/app/Http/Controllers/Admin/StorefrontBrandingController.php',
    'request' => $root.'/app/Http/Requests/Admin/StorefrontBrandingRequest.php',
    'component' => $root.'/resources/views/components/storefront/brand-logo.blade.php',
    'admin_view' => $root.'/resources/views/admin/storefront-branding/edit.blade.php',
];

foreach ($files as $label => $path) {
    $expect(file_exists($path), "Missing {$label} file: {$path}");
}

$routes = file_get_contents($root.'/routes/web.php') ?: '';
$rbac = file_get_contents($root.'/app/Support/AdminRbac.php') ?: '';
$header = file_get_contents($root.'/resources/views/components/storefront/header.blade.php') ?: '';
$footer = file_get_contents($root.'/resources/views/components/storefront/footer.blade.php') ?: '';
$layout = file_get_contents($root.'/resources/views/components/layouts/storefront.blade.php') ?: '';
$error = file_get_contents($root.'/resources/views/errors/error.blade.php') ?: '';
$invoice = file_get_contents($root.'/resources/views/storefront/orders/invoice.blade.php') ?: '';
$accountInvoice = file_get_contents($root.'/resources/views/storefront/account/orders/invoice.blade.php') ?: '';
$creditNote = file_get_contents($root.'/resources/views/storefront/account/returns/credit-note.blade.php') ?: '';
$adminLayout = file_get_contents($root.'/resources/views/components/layouts/admin.blade.php') ?: '';

$expect(str_contains($routes, "name('storefront-branding.edit')"), 'Admin branding edit route is missing.');
$expect(str_contains($routes, "name('storefront-branding.update')"), 'Admin branding update route is missing.');
$expect(str_contains($rbac, "'storefront_branding.view'"), 'Storefront branding view permission is missing.');
$expect(str_contains($rbac, "'storefront_branding.manage'"), 'Storefront branding manage permission is missing.');
$expect(str_contains($rbac, "if (Str::startsWith(\$name, 'storefront-branding.'))"), 'Storefront branding routes need explicit view/manage permission mapping so the edit page is viewable without manage permission.');
$expect(str_contains($adminLayout, "route('admin.storefront-branding.edit')"), 'Storefront Branding admin navigation link is missing.');

foreach ([
    'header' => $header,
    'footer' => $footer,
    'error page' => $error,
    'order invoice' => $invoice,
    'account invoice' => $accountInvoice,
    'credit note' => $creditNote,
] as $label => $source) {
    $expect(str_contains($source, '<x-storefront.brand-logo'), "{$label} does not use the shared storefront brand-logo component.");
}

$expect(str_contains($layout, 'StorefrontBranding::logoUrl()'), 'Structured data does not use the centralized storefront logo resolver.');

$migrationMatches = glob($root.'/database/migrations/*create_storefront_branding_settings_table.php') ?: [];
$expect(count($migrationMatches) === 1, 'Expected exactly one storefront branding settings migration.');

if ($failures !== []) {
    fwrite(STDERR, "Storefront branding control regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Storefront branding control regression passed.\n";
