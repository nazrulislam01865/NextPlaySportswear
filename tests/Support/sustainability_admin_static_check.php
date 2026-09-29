<?php
$root = dirname(__DIR__, 2);
$file = $root.'/resources/views/admin/sustainability-page/edit.blade.php';
if (! is_file($file)) { fwrite(STDERR, "Missing admin editor\n"); exit(1); }
$view = file_get_contents($file);
foreach (['Hero','Our Approach','Materials & Waste','People & Partners','Packaging & Delivery','Keeping You Informed','Bottom CTA','SEO','feature_image_{{ $index }}','remove_feature_image_{{ $index }}','info_card_icon_{{ $index }}','remove_info_card_icon_{{ $index }}','image_alt','icon_alt','admin.sustainability-page.update','Preview Sustainability','features[{{ $index }}][id]','informed[cards][{{ $index }}][id]'] as $needle) {
    if (! str_contains($view, $needle)) { fwrite(STDERR, "Admin editor missing {$needle}\n"); exit(1); }
}
if (substr_count($view, 'data-sustainability-upload-slot') !== 2) { fwrite(STDERR, "Expected the two looped upload-slot templates\n"); exit(1); }
foreach (['sortable','drag handle','custom css','layout_direction'] as $forbidden) {
    if (stripos($view, $forbidden) !== false) { fwrite(STDERR, "Forbidden structural control {$forbidden}\n"); exit(1); }
}
echo "sustainability admin static check: PASS\n";
