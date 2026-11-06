@php
    // Reuse one control for bulk and sample options. Keep selections independent.
    $sampleOptions = $optionKind === 'sample_options';
    $groupId = (string) $group['id'];
    $savedOptions = (array) ($stored[$optionKind] ?? []);
    $initialValue = $sampleOptions
        ? ($stored['options'][$groupId] ?? $group['selected'])
        : $group['selected'];
    $selectedOption = old("lines.$i.$optionKind.$groupId", $savedOptions[$groupId] ?? $initialValue);
    $selectedIds = array_values(array_filter(array_map('strval', (array) $selectedOption), fn ($value) => $value !== ''));
    $orderedValues = collect($group['values'])
        ->sortBy(fn ($choice) => in_array((string) $choice['id'], $selectedIds, true) ? 0 : 1)
        ->values();
    $isMissing = $group['required'] && count($selectedIds) === 0;
    $name = "lines[$i][$optionKind][$groupId]";
    $controlId = "np-quote-$optionKind-$i-$groupId";
@endphp
<div class="np-quote-option-field" data-option-kind="{{ $optionKind }}" data-line-index="{{ $i }}" data-unselected="{{ $isMissing ? '1' : '0' }}">
    <?php if ($group['type'] === 'checkbox'): ?>
        <span class="np-quote-option-group-label">
            {{ $group['label'] }}
            <?php if ($group['required']): ?>
                <span class="np-quote-star" aria-label="required">*</span>
            <?php endif; ?>
        </span>
        <details class="np-quote-multi-dropdown" data-option-multi="{{ $optionKind }}" data-option-multi-line="{{ $i }}" data-option-multi-group="{{ $groupId }}">
            <summary id="{{ $controlId }}" aria-label="Choose {{ $group['label'] }}">
                <span data-multi-summary>{{ count($selectedIds) ? count($selectedIds).' selected' : 'Choose '.$group['label'] }}</span>
            </summary>
            <div class="np-quote-multi-choices">
                <?php foreach ($orderedValues as $choice): ?>
                    <label class="np-quote-check">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $choice['id'] }}"
                            {{ in_array((string) $choice['id'], $selectedIds, true) ? 'checked' : '' }}
                            {{ $group['fixed'] ? 'disabled' : '' }}
                            data-option-kind="{{ $optionKind }}" data-option-group="{{ $groupId }}" data-option-line="{{ $i }}">
                        <span>
                            {{ $choice['label'] }}
                            <?php if ($choice['price']): ?>
                                <small>+${{ number_format($choice['price'], 2) }} {{ $choice['charge_type'] === 'fixed_order' ? '/order' : '/pc' }}</small>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </details>
        <?php if ($group['fixed']): ?>
            <?php foreach ($selectedIds as $id): ?>
                <input type="hidden" name="{{ $name }}[]" value="{{ $id }}">
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <label for="{{ $controlId }}">
            {{ $group['label'] }}
            <?php if ($group['required']): ?>
                <span class="np-quote-star" aria-label="required">*</span>
            <?php endif; ?>
        </label>
        <select class="admin-input np-quote-option-select" id="{{ $controlId }}" name="{{ $name }}"
            {{ $group['fixed'] ? 'disabled' : '' }}
            data-option-kind="{{ $optionKind }}" data-option-group="{{ $groupId }}" data-option-line="{{ $i }}">
            <?php if (!$group['fixed'] && count($selectedIds) === 0): ?>
                <option value="" selected>Choose {{ $group['label'] }}</option>
            <?php endif; ?>
            <?php foreach ($orderedValues as $choice): ?>
                <option value="{{ $choice['id'] }}" {{ (string) $selectedOption === (string) $choice['id'] ? 'selected' : '' }}>
                    {{ $choice['label'] }}
                    <?php if ($choice['price']): ?>
                        · +${{ number_format($choice['price'], 2) }} {{ $choice['charge_type'] === 'fixed_order' ? '/order' : '/pc' }}
                    <?php else: ?>
                        · Included
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
            <?php if (!$group['fixed'] && count($selectedIds) > 0): ?>
                <option value="">Clear selection</option>
            <?php endif; ?>
        </select>
        <?php if ($group['fixed']): ?>
            <input type="hidden" name="{{ $name }}" value="{{ $selectedOption }}">
        <?php endif; ?>
    <?php endif; ?>
</div>
