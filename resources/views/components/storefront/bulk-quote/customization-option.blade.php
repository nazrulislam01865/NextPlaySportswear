@props([
    'value',
    'label',
    'icon',
])

<label class="bulk-quote-custom-option">
    <input
        type="checkbox"
        name="customization_types[]"
        value="{{ $value }}"
        @checked(in_array($value, old('customization_types', []), true))
    >
    <span class="bulk-quote-custom-check" aria-hidden="true"></span>
    <span class="bulk-quote-custom-icon" aria-hidden="true">
        <x-storefront.bulk-quote.icon :name="$icon" :size="20" />
    </span>
    <span>{{ $label }}</span>
</label>
