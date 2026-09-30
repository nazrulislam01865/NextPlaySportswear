<?php
$root = dirname(__DIR__, 2);
$src = (string) file_get_contents($root.'/app/Services/Storefront/ProductCatalogService.php');
$failures=[]; $expect=static function(bool $ok,string $msg)use(&$failures){if(!$ok)$failures[]=$msg;};
$expect(str_contains($src, "'shippingMethods.shippingMethod'"), 'Full product query eager-loads master shipping relation.');
$expect(substr_count($src, "'image' =>") >= 2, 'Production and shipping payloads expose image keys.');
$expect(str_contains($src, '$method?->imageUrl()'), 'Production payload inherits master image.');
$expect(str_contains($src, '$masterMethod?->imageUrl()'), 'Shipping payload inherits master image.');
if($failures){fwrite(STDERR,"Product method media payload regression failed:\n - ".implode("\n - ",$failures)."\n");exit(1);} echo "Product method media payload regression passed.\n";
