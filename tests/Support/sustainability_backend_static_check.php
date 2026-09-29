<?php
$root = dirname(__DIR__, 2);
$mustExist = [
    'app/Models/SustainabilityPageSetting.php',
    'app/Services/Storefront/SustainabilityPageService.php',
    'app/Services/Catalog/SustainabilityPageMediaService.php',
    'app/Http/Requests/Admin/SustainabilityPageRequest.php',
    'app/Http/Controllers/Admin/SustainabilityPageController.php',
];
foreach ($mustExist as $file) {
    if (! is_file($root.'/'.$file)) { fwrite(STDERR, "Missing {$file}\n"); exit(1); }
}
$service = file_get_contents($root.'/app/Services/Storefront/SustainabilityPageService.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/SustainabilityPageRequest.php');
$media = file_get_contents($root.'/app/Services/Catalog/SustainabilityPageMediaService.php');
$rbac = file_get_contents($root.'/app/Support/AdminRbac.php');
$route = file_get_contents($root.'/routes/web.php');
foreach (['materials_waste','people_partners','packaging_delivery','materials','partners','packaging','PublicUrl::isAllowed','sustainability-page/'] as $needle) {
    if (! str_contains($service.$request.$media, $needle)) { fwrite(STDERR, "Missing backend contract: {$needle}\n"); exit(1); }
}
foreach (['sustainability_page.view','sustainability_page.manage'] as $needle) {
    if (! str_contains($rbac, $needle)) { fwrite(STDERR, "Missing RBAC {$needle}\n"); exit(1); }
}
if (! str_contains($route, "name('sustainability')") || ! str_contains($route, 'sustainability-page')) { fwrite(STDERR, "Missing Sustainability routes\n"); exit(1); }
echo "sustainability backend static check: PASS\n";
