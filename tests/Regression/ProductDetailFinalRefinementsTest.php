<?php
$root=dirname(__DIR__,2);
$builder=(string)file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$signals=(string)file_get_contents($root.'/resources/views/components/storefront/product/purchase-signals.blade.php');
$css=(string)file_get_contents($root.'/resources/css/storefront.css');
$failures=[];$expect=static function(bool $ok,string $msg)use(&$failures){if(!$ok)$failures[]=$msg;};
$expect(str_contains($signals,'np-product-sku-inline') && str_contains($signals,'copySku'), 'SKU is rendered beside shopper activity with copy action.');
$expect(!str_contains($builder,'<span class="np-product-sku">SKU:'), 'Old far-right SKU placement is removed from builder title meta.');
$expect(str_contains($builder,'np-product-material-card') && str_contains($css,'.np-product-material-media img'), 'Material option image styling remains explicit.');
$expect(str_contains($css,'width: 92px') || str_contains($css,'width: 96px') || str_contains($css,'width: 100px'), 'Material image is enlarged.');
$expect(str_contains($css,'.np-proto-size-image') && (str_contains($css,'width: 58px') || str_contains($css,'width: 64px')), 'Size-row sample image is enlarged.');
$expect(!str_contains($builder,'<th>Preview</th>'), 'Roster preview column header is hidden.');
$expect(!str_contains($builder,'np-proto-roster-preview'), 'Roster preview cells are hidden.');
if($failures){fwrite(STDERR,"Product detail final refinements regression failed:\n - ".implode("\n - ",$failures)."\n");exit(1);} echo "Product detail final refinements regression passed.\n";
