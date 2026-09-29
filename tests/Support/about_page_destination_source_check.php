<?php

$root = dirname(__DIR__, 2);
$service = file_get_contents($root . '/app/Services/Storefront/AboutPageService.php');
$request = file_get_contents($root . '/app/Http/Requests/Admin/AboutPageRequest.php');

$failures = [];

if (! str_contains($service, 'use App\\Support\\PublicUrl;') || ! str_contains($service, 'PublicUrl::isAllowed($value)')) {
    $failures[] = 'AboutPageService must delegate destination safety to PublicUrl.';
}

if (! str_contains($request, 'use App\\Support\\PublicUrl;') || ! str_contains($request, 'PublicUrl::isAllowed($value)')) {
    $failures[] = 'AboutPageRequest must delegate destination safety to PublicUrl.';
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "about destination source check passed\n";
