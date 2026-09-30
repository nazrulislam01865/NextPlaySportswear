<?php

$root = dirname(__DIR__, 2);
$builder = file_get_contents($root.'/resources/views/components/storefront/product/builder.blade.php');
$stepHeader = file_get_contents($root.'/resources/views/components/storefront/product/customizer/step-header.blade.php');
$navigation = file_get_contents($root.'/resources/views/components/storefront/product/customizer/navigation.blade.php');
$optionGroup = file_get_contents($root.'/resources/views/components/storefront/product/option-group.blade.php');
$optionChoicePath = $root.'/resources/views/components/storefront/product/customizer/option-choice.blade.php';
$css = file_get_contents($root.'/resources/css/storefront.css');

foreach (compact('builder', 'stepHeader', 'navigation', 'optionGroup', 'css') as $name => $contents) {
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$name} source.\n");
        exit(1);
    }
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(! str_contains($builder, '<x-storefront.product.customizer.stepper'), 'Customizer does not render a separate horizontal stepper above the accordion.');
$expect(substr_count($builder, 'class="np-proto-step-card"') >= 6, 'All six customization steps remain rendered as stacked accordion cards.');
foreach (range(1, 6) as $step) {
    $expect(
        str_contains($builder, 'class="np-proto-step-expanded" x-show="isCustomizerStepOpen('.$step.')"'),
        "Step {$step} uses independent open-state visibility while its header stays visible."
    );
}
$expect(str_contains($builder, 'openCustomizerSteps: { 1: true, 2: true, 3: true, 4: true, 5: true, 6: true }'), 'All six customization steps are expanded on initial load.');
$expect(! str_contains($builder, 'np-proto-review-completed'), 'Review step no longer renders a separate duplicate completed-step list.');

$expect(str_contains($stepHeader, '@click="toggleCustomizerStep({{ $number }})"'), 'Reusable step header independently toggles the selected accordion step.');
$expect(str_contains($stepHeader, 'completedCustomizerStep >= {{ $number }}'), 'Reusable step header exposes completed state only for steps that were actually advanced through.');
$expect(str_contains($stepHeader, 'activeCustomizerStep === {{ $number }}'), 'Reusable step header exposes the active in-progress state.');
$expect(str_contains($stepHeader, ':aria-expanded="isCustomizerStepOpen({{ $number }}) ? \'true\' : \'false\'"'), 'Accordion headers expose accessible independent expanded state.');

$expect(is_file($optionChoicePath), 'Reusable option-choice component exists for product selections.');
if (is_file($optionChoicePath)) {
    $optionChoice = file_get_contents($optionChoicePath);
    $expect(str_contains($optionChoice, 'np-proto-option-card'), 'Reusable option choice uses the compact prototype option card.');
    $expect(str_contains($optionChoice, 'np-proto-option-media'), 'Reusable option choice includes the compact preview area.');
    $expect(str_contains($optionChoice, 'np-proto-selected-check'), 'Reusable option choice keeps the selected check state.');
}

$expect(substr_count($builder, '<x-storefront.product.customizer.option-choice') >= 1, 'Fabric options use the reusable option-choice component.');
$expect(str_contains($optionGroup, "['image', 'swatch', 'buttons', 'select', 'checkbox']") && str_contains($optionGroup, '<x-storefront.product.customizer.option-choice'), 'Image, swatch, button/select, and checkbox options reuse the same compact option-card presentation.');
$expect(! str_contains($optionGroup, 'aspect-square w-full'), 'Selectable option groups no longer render oversized square option images.');

$refinementPos = strpos($css, 'NEXTPLAY_PRODUCT_CUSTOMIZER_ACCORDION_REFINEMENT');
$refinementCss = $refinementPos === false ? '' : substr($css, $refinementPos);
$expect($refinementCss !== '', 'Customizer accordion/readability refinement CSS block exists.');
$expect(str_contains($refinementCss, '.np-proto-step-toggle'), 'Accordion header styling is centralized.');
$expect(str_contains($refinementCss, '.np-proto-option-grid'), 'Unified option-card grid styling is centralized.');
$expect(str_contains($refinementCss, 'grid-template-columns: repeat(2, minmax(0, 1fr))'), 'Selectable options use compact two-column fabric-style cards on desktop.');
$expect(str_contains($refinementCss, 'font-size: 14px'), 'Customizer body copy has a readable 14px baseline.');
$expect(str_contains($refinementCss, 'font-size: 16px'), 'Important customizer labels use a readable 16px size.');
foreach (['font-size: 8px', 'font-size: 9px', 'font-size: 10px', 'font-size: 11px'] as $tinyFont) {
    $expect(! str_contains($refinementCss, $tinyFont), "Refinement block does not reintroduce unreadably small {$tinyFont} text.");
}

if ($failures !== []) {
    fwrite(STDERR, "Product detail accordion/option-card regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail accordion/option-card regression passed.\n";
