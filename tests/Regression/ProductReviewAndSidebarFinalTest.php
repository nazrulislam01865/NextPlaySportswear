<?php
$root=dirname(__DIR__,2);
$builder=(string)file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$summary=(string)file_get_contents($root.'/resources/views/components/storefront/product/customizer/order-summary.blade.php');
$failures=[];$expect=static function(bool $ok,string $msg)use(&$failures){if(!$ok)$failures[]=$msg;};
foreach(['Selected Options','Player Names & Numbers','Artwork','Production & Shipping','Shipping (Estimated)','Remote Area Surcharge'] as $text){$expect(str_contains($builder,$text),"Review includes {$text}.");}
$expect(str_contains($builder,"class=\"btn btn-secondary btn-xl np-review-add-to-cart\""), 'Review Add to Cart uses friendly primary action hook.');
$expect(str_contains($builder,':disabled="!canAddToCart()"'), 'Review Add to Cart reflects valid configuration or enables validation on the review step.');
$expect(str_contains($builder,'np-review-save-later'), 'Review Save for Later uses dedicated secondary action hook.');
$expect(str_contains($builder, '$reviewOptionGroups') && str_contains($builder, '@foreach($reviewOptionGroups as $group)'), 'Review includes fixed and customer-facing non-material option details.');
foreach(['np-custom-order-pricing','Product Price','Shipping (Estimated)','Remote Area Surcharge','Total','Save as Quote'] as $text){$expect(str_contains($summary,$text),"Sidebar includes {$text}.");}
$expect(str_contains($summary,':disabled="!canAddToCart()"'), 'Sidebar Add to Cart tracks validation readiness without blocking complete configurations.');
$expect(str_contains($summary,'np-custom-order-add-to-cart'), 'Sidebar has dedicated Add to Cart action.');
$expect(str_contains($builder,'canAddToCart()'), 'Builder exposes side-effect-free completion predicate.');
$expect(str_contains($builder,'completedCustomizerStep') && str_contains($builder,'advanceCustomizerStep'), 'Customizer continues tracking sequential step progress for Completed states.');
$expect(str_contains($builder, 'Number(this.activeCustomizerStep) === Number(this.maxCustomizerStep)'), 'The review step is dynamic for products with and without a roster.');
$expect(str_contains($builder, '|| this.validationIssue() === null'), 'Completed configurations can be added regardless of the last accordion clicked.');
$expect(!str_contains($builder,'this.completedCustomizerStep >= 5 && this.activeCustomizerStep === 6 && baseCanAddToCart.call(this)'), 'Button enablement no longer pre-validates earlier steps.');
$expect(str_contains($builder,'@submit="if(!validate()) $event.preventDefault()"'), 'Actual cart submission still validates required data and displays errors on a review-step submission.');
if($failures){fwrite(STDERR,"Product review/sidebar final regression failed:\n - ".implode("\n - ",$failures)."\n");exit(1);} echo "Product review/sidebar final regression passed.\n";
