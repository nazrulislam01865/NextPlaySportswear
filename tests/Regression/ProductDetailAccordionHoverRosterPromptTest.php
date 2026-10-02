<?php

$root = dirname(__DIR__, 2);
$builder = (string) file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$css = (string) file_get_contents($root.'/resources/css/storefront.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) $failures[] = $message;
};

$expect(
    ! str_contains($builder, 'title="Material pricing and availability are configured for this product.">?</span>'),
    'Material Option no longer renders the question-mark help icon.'
);
$expect(
    str_contains($builder, 'Select a quantity from the Sizes &amp; Quantities section to add player names and numbers.'),
    'Player Names & Numbers shows guidance before any size quantity is selected.'
);
$expect(
    str_contains($builder, 'x-show="totalQuantity() <= 0"'),
    'The player quantity guidance is tied to an empty total quantity.'
);
$expect(
    str_contains($builder, 'x-show="rosterEnabled && totalQuantity() > 0"'),
    'Roster inputs only replace the guidance after quantity selection.'
);

$marker = 'NEXTPLAY_CUSTOMIZER_STEP_HOVER_AND_ROSTER_EMPTY_STATE_FIX';
$pos = strpos($css, $marker);
$fixCss = $pos === false ? '' : substr($css, $pos);
$expect($fixCss !== '', 'Customizer step hover/roster empty-state fix CSS block exists.');
$expect(str_contains($fixCss, '.np-proto-step-header:hover'), 'Hover styling is applied to the complete step header rather than only the toggle button.');
$expect(str_contains($fixCss, 'gap: 0;'), 'Step header removes the empty grid gap that caused the unpainted strip.');
$expect(str_contains($fixCss, '.np-proto-step-toggle:hover') && str_contains($fixCss, 'background: transparent;'), 'Toggle hover stays transparent so the parent header provides one continuous hover surface.');
$expect(str_contains($fixCss, '.np-proto-player-quantity-message'), 'The empty player state has dedicated presentation styling.');

if ($failures !== []) {
    fwrite(STDERR, "Product detail accordion hover/roster prompt regression failed:\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

echo "Product detail accordion hover/roster prompt regression passed.\n";
