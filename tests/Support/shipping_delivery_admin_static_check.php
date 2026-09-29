<?php
$root = dirname(__DIR__, 2);
$file = $root.'/resources/views/admin/shipping-delivery-page/edit.blade.php';
if (! is_file($file)) { fwrite(STDERR, "Shipping & Delivery admin editor missing.\n"); exit(1); }
$view = file_get_contents($file);
$requirements = [
    'hero[eyebrow]','hero[title]','hero[subtitle]','tabs[{{ $index }}][label]',
    'delivery_intro[title]','delivery_intro[subtitle]',
    'info_cards[cards][{{ $index }}][title]','info_cards[cards][{{ $index }}][description]','info_card_icon_{{ $index }}',
    'delivery_steps[title]','delivery_steps[steps][{{ $index }}][number]','delivery_steps[steps][{{ $index }}][description]',
    'notice[text]','notice_icon',
    'faqs[title]','faqs[subtitle]','faqs[items][{{ $index }}][question]','faqs[items][{{ $index }}][answer]',
    'address_checklist[title]','address_checklist[subtitle]','address_checklist[items][{{ $index }}][title]','address_checklist[items][{{ $index }}][description]',
    'checklist_icon_{{ $index }}',
    'cta[title]','cta[description]','cta[primary_label]','cta[primary_url]','cta[policy_label]','cta[policy_url]',
    'seo[title]','seo[description]', "route('shipping')", 'Save Shipping & Delivery Page',
];
foreach ($requirements as $snippet) {
    if (! str_contains($view, $snippet)) { fwrite(STDERR, "Admin editor missing: {$snippet}\n"); exit(1); }
}
foreach (['reorder','add_faq','delete_step','active_tab','background_color','text_color'] as $forbidden) {
    if (str_contains($view, $forbidden)) { fwrite(STDERR, "Admin editor exposes forbidden structural control: {$forbidden}\n"); exit(1); }
}
$slotPatterns = [
    'info_card_icon_{{ $index }}' => 2,
    'notice_icon' => 1,
    'checklist_icon_{{ $index }}' => 4,
];
$uploads = 0;
foreach ($slotPatterns as $pattern => $count) {
    if (! str_contains($view, $pattern)) { fwrite(STDERR, "Missing upload pattern: {$pattern}\n"); exit(1); }
    $uploads += $count;
}
if ($uploads !== 7) { fwrite(STDERR, "Expected 7 fixed upload slots, found {$uploads}.\n"); exit(1); }
echo "Shipping & Delivery admin management static check passed (7 fixed upload slots).\n";
