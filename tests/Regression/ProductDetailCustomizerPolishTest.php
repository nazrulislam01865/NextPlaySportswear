<?php

$root = dirname(__DIR__, 2);
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$optionChoice = file_get_contents($root.'/resources/views/components/storefront/product/customizer/option-choice.blade.php');
$details = file_get_contents($root.'/resources/views/components/storefront/product/details.blade.php');
$js = file_get_contents($root.'/resources/js/storefront.js');
$css = file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(! str_contains($builder, 'Add individual player/item details'), 'Player step no longer requires a manual enable checkbox.');
$expect(str_contains($builder, 'Use the same name &amp; number for all items'), 'Player step offers same-name-and-number-for-all control.');
$expect(str_contains($builder, 'np-roster-shared-fields'), 'Player step exposes shared roster fields when same-for-all is enabled.');
$expect(str_contains($js, 'rosterSameForAll:'), 'Product builder tracks same-for-all roster state.');
$expect(str_contains($js, 'setRosterSameForAll('), 'Product builder has a same-for-all roster toggle method.');
$expect(str_contains($js, 'updateRosterSharedField('), 'Product builder can synchronize shared roster values to every generated row.');
$expect(str_contains($js, 'if (rosterSettings.enabled && this.totalQuantity() > 0) this.rosterEnabled = true;'), 'Roster automatically opens once a size/quantity is selected.');

$expect(str_contains($optionChoice, "'is-included'"), 'Included option charge receives a dedicated subdued style hook.');
$expect(! str_contains($details, '<span aria-hidden="true">◉</span>'), 'Product Information title no longer renders the orange circle.');
$expect(str_contains($details, "'is-active'") && ! str_contains($details, 'shadow-[inset_0_-3px_0_currentColor]'), 'Product Information tabs use a reusable active class rather than inline inset-shadow utilities.');

$marker = 'NEXTPLAY_PRODUCT_DETAIL_CUSTOMIZER_POLISH';
$pos = strpos($css, $marker);
$polishCss = $pos === false ? '' : substr($css, $pos);
$expect($polishCss !== '', 'Customizer polish CSS block exists.');
$expect(str_contains($polishCss, '.np-proto-option-card.is-selected'), 'Selected option card style is centralized in the polish block.');
$expect(str_contains($polishCss, 'border: 1px solid var(--np-color-secondary)'), 'Selected option cards use a thin 1px orange border.');
$expect(str_contains($polishCss, 'background: rgb(var(--np-color-secondary-rgb) / .055)'), 'Selected option cards use a light orange background.');
$expect(str_contains($polishCss, '.np-proto-selected-check'), 'Selected option check styling is refined.');
$expect(str_contains($polishCss, 'width: 16px') && str_contains($polishCss, 'height: 16px'), 'Selected option check is smaller.');
$expect(str_contains($polishCss, '.np-proto-option-charge.is-included'), 'Included option label is visually subdued.');
$expect(str_contains($polishCss, '.np-proto-size-summary-fabric'), 'Selected fabric summary has overflow protection.');
$expect(str_contains($polishCss, 'min-width: 0'), 'Fabric summary content is allowed to shrink without overflowing.');
$expect(str_contains($polishCss, '.np-proto-choice-card.is-selected'), 'Production/shipping selected cards use the common thin selected treatment.');
$expect(str_contains($polishCss, '.np-product-detail-tab.is-active'), 'Product information active tab underline is centralized.');
$expect(str_contains($polishCss, 'height: 2px'), 'Product information active underline matches the thin menu underline.');

if ($failures) {
    fwrite(STDERR, "Product detail customizer polish regression failed:\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "Product detail customizer polish regression passed.\n";
