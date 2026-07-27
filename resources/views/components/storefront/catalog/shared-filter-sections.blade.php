@props([
    'filters' => [],
    'options' => [],
    'idPrefix' => 'catalog-filter',
])

@php
    $selected = static fn (string $key): array => array_values((array) ($filters[$key] ?? []));
    $hasSelection = static fn (string $key): bool => count((array) ($filters[$key] ?? [])) > 0;
@endphp

@if(($options['sports'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('sports')) open @endif>
        <summary class="np-catalog-filter-title"><span>Sport</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['sports'] as $option)
                @php($fieldId = $idPrefix.'-sport-'.$option['id'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="sports[]" value="{{ $option['id'] }}" @checked(in_array((int) $option['id'], array_map('intval', $selected('sports')), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['product_types'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('product_types')) open @endif>
        <summary class="np-catalog-filter-title"><span>Product type</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['product_types'] as $option)
                @php($fieldId = $idPrefix.'-type-'.\Illuminate\Support\Str::slug($option['value']))
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="product_types[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('product_types'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif


@if(($options['colors'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('colors')) open @endif>
        <summary class="np-catalog-filter-title"><span>Color</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-color-grid">
            @foreach($options['colors'] as $option)
                @php($fieldId = $idPrefix.'-color-'.$option['value'])
                <label class="np-catalog-color-option" for="{{ $fieldId }}" title="{{ $option['label'] }}">
                    <input id="{{ $fieldId }}" type="checkbox" name="colors[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('colors'), true))>
                    <span class="np-catalog-color-swatch" style="background: {{ $option['color_hex'] ?: '#e2e8f0' }}"></span>
                    <span>{{ $option['label'] }}</span>
                    <small>{{ $option['count'] }}</small>
                </label>
            @endforeach
        </div>
    </details>
@endif

<details class="np-catalog-filter-section" @if(($filters['min_price'] ?? null) !== null || ($filters['max_price'] ?? null) !== null) open @endif>
    <summary class="np-catalog-filter-title"><span>Starting unit price</span><span aria-hidden="true">+</span></summary>
    <div class="np-catalog-price-grid">
        <label>
            <span>Minimum</span>
            <div class="np-catalog-money-input"><span>$</span><input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" min="0" step="0.01" placeholder="{{ $options['price_floor'] ?? 0 }}"></div>
        </label>
        <label>
            <span>Maximum</span>
            <div class="np-catalog-money-input"><span>$</span><input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" min="0" step="0.01" placeholder="{{ $options['price_ceiling'] ?? 100 }}"></div>
        </label>
    </div>
    <p class="np-catalog-filter-help">Filters the displayed starting price before quantity and customization choices.</p>
</details>

@if(($options['moq'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('moq')) open @endif>
        <summary class="np-catalog-filter-title"><span>Minimum order quantity</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['moq'] as $option)
                @php($fieldId = $idPrefix.'-moq-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="moq[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('moq'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['customization'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('customization')) open @endif>
        <summary class="np-catalog-filter-title"><span>Customization</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['customization'] as $option)
                @php($fieldId = $idPrefix.'-customization-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="customization[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('customization'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['artwork_methods'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('artwork_methods')) open @endif>
        <summary class="np-catalog-filter-title"><span>Decoration / branding</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['artwork_methods'] as $option)
                @php($fieldId = $idPrefix.'-artwork-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="artwork_methods[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('artwork_methods'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['materials'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('materials')) open @endif>
        <summary class="np-catalog-filter-title"><span>Fabric / material</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['materials'] as $option)
                @php($fieldId = $idPrefix.'-material-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="materials[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('materials'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['availability'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if($hasSelection('availability')) open @endif>
        <summary class="np-catalog-filter-title"><span>Availability</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['availability'] as $option)
                @php($fieldId = $idPrefix.'-availability-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="availability[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected('availability'), true))>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endif

@if(($options['rating_options'] ?? []) !== [])
    <details class="np-catalog-filter-section" @if(($filters['min_rating'] ?? null) !== null) open @endif>
        <summary class="np-catalog-filter-title"><span>Customer rating</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($options['rating_options'] as $option)
                @php($fieldId = $idPrefix.'-rating-'.$option['value'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="radio" name="min_rating" value="{{ $option['value'] }}" @checked((int) ($filters['min_rating'] ?? 0) === (int) $option['value'])>
                        <span class="np-catalog-stars" aria-hidden="true">
                            <span>{{ str_repeat('★', (int) $option['value']) }}</span><span class="np-catalog-stars-muted">{{ str_repeat('★', 5 - (int) $option['value']) }}</span>
                        </span>
                        <span>{{ $option['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                </label>
            @endforeach
            @if(($filters['min_rating'] ?? null) !== null)
                <label class="np-catalog-filter-option">
                    <span class="np-catalog-filter-option__main"><input type="radio" name="min_rating" value="">Any rating</span>
                </label>
            @endif
        </div>
    </details>
@endif

@foreach(($options['attributes'] ?? []) as $attribute)
    <details class="np-catalog-filter-section" @if(count((array) ($filters['attributes'][$attribute['slug']] ?? [])) > 0) open @endif>
        <summary class="np-catalog-filter-title"><span>{{ $attribute['name'] }}</span><span aria-hidden="true">+</span></summary>
        <div class="np-catalog-filter-options">
            @foreach($attribute['values'] as $value)
                @php($fieldId = $idPrefix.'-attribute-'.$attribute['slug'].'-'.$value['id'])
                <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                    <span class="np-catalog-filter-option__main">
                        <input id="{{ $fieldId }}" type="checkbox" name="attributes[{{ $attribute['slug'] }}][]" value="{{ $value['slug'] }}" @checked(in_array($value['slug'], (array) ($filters['attributes'][$attribute['slug']] ?? []), true))>
                        <span>{{ $value['label'] }}</span>
                    </span>
                    <span class="np-catalog-filter-count">{{ $value['count'] }}</span>
                </label>
            @endforeach
        </div>
    </details>
@endforeach
