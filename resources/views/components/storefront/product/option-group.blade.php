@props(['group', 'showHeader' => true])
@php
    $id = $group['id'];
    $type = $group['type'] ?? 'select';
@endphp
<section class="np-proto-option-section" data-input-style="{{ $type }}">
    @if($showHeader)
        <div class="np-proto-option-section__head">
            <div>
                <h4>{{ $group['label'] }} @if($group['required'])<span class="text-brand-red">*</span>@endif</h4>
                @if($group['description'])<p>{{ $group['description'] }}</p>@endif
            </div>
        </div>
    @endif

    @if($type === 'image')
        <div class="np-proto-option-grid np-proto-option-grid--image" role="radiogroup" aria-label="{{ $group['label'] }}">
            @foreach($group['values'] as $value)
                <x-storefront.product.customizer.option-choice :group="$group" :value="$value" />
            @endforeach
        </div>
    @elseif($type === 'swatch')
        <div class="np-proto-swatch-grid" role="radiogroup" aria-label="{{ $group['label'] }}">
            @foreach($group['values'] as $value)
                @php
                    $images = collect($value['images'] ?? [])->map(fn ($image) => is_array($image) ? ($image['url'] ?? null) : $image)->filter()->values();
                    $preview = $images->first() ?: ($value['image'] ?? null);
                @endphp
                <button
                    type="button"
                    class="np-proto-swatch-choice"
                    @click="choose(@js($group), @js($value['id']))"
                    :class="selections[@js($id)] === @js($value['id']) ? 'is-selected' : ''"
                    :aria-checked="selections[@js($id)] === @js($value['id']) ? 'true' : 'false'"
                    role="radio"
                >
                    <span class="np-proto-swatch-choice__sample" aria-hidden="true">
                        @if(filled($value['color'] ?? null))
                            <span style="background-color: {{ $value['color'] }}"></span>
                        @elseif($preview)
                            <img src="{{ $preview }}" alt="" loading="lazy" decoding="async">
                        @else
                            <span class="is-empty"></span>
                        @endif
                    </span>
                    <span class="np-proto-swatch-choice__copy">
                        <strong>{{ $value['label'] }}</strong>
                        <small x-text="chargeLabel(@js($value))"></small>
                    </span>
                    <span class="np-proto-selected-check" aria-hidden="true">✓</span>
                </button>
            @endforeach
        </div>
    @elseif($type === 'buttons')
        <div class="np-proto-button-choice-grid" role="radiogroup" aria-label="{{ $group['label'] }}">
            @foreach($group['values'] as $value)
                <button
                    type="button"
                    class="np-proto-button-choice"
                    @click="choose(@js($group), @js($value['id']))"
                    :class="selections[@js($id)] === @js($value['id']) ? 'is-selected' : ''"
                    :aria-checked="selections[@js($id)] === @js($value['id']) ? 'true' : 'false'"
                    role="radio"
                >
                    <span>
                        <strong>{{ $value['label'] }}</strong>
                        @if(filled($value['description'] ?? null))<small>{{ $value['description'] }}</small>@endif
                    </span>
                    <small class="np-proto-option-charge" :class="chargeLabel(@js($value)) === 'Included' ? 'is-included' : ''" x-text="chargeLabel(@js($value))"></small>
                </button>
            @endforeach
        </div>
    @elseif($type === 'select')
        <div class="np-proto-select-wrap">
            <select
                class="np-proto-option-input np-proto-option-select"
                x-model="selections[@js($id)]"
                @change="chooseSelect(@js($group), $event.target.value)"
                @if($group['required']) required @endif
            >
                <option value="">Choose {{ $group['label'] }}</option>
                @foreach($group['values'] as $value)
                    <option value="{{ $value['id'] }}">{{ $value['label'] }}</option>
                @endforeach
            </select>
            <p class="np-proto-select-charge" x-show="selections[@js($id)]" x-cloak x-text="chargeLabel(optionValue(@js($group), selections[@js($id)]))"></p>
        </div>
    @elseif($type === 'checkbox')
        <div class="np-proto-checkbox-grid" role="group" aria-label="{{ $group['label'] }}">
            @foreach($group['values'] as $value)
                <label class="np-proto-checkbox-choice" :class="(multiSelections[@js($id)] || []).includes(@js($value['id'])) ? 'is-selected' : ''">
                    <input
                        type="checkbox"
                        :checked="(multiSelections[@js($id)] || []).includes(@js($value['id']))"
                        @change="toggle(@js($group), @js($value['id']))"
                    >
                    <span class="np-proto-checkbox-choice__mark" aria-hidden="true">✓</span>
                    <span class="np-proto-checkbox-choice__copy">
                        <strong>{{ $value['label'] }}</strong>
                        @if(filled($value['description'] ?? null))<small>{{ $value['description'] }}</small>@endif
                    </span>
                    <small class="np-proto-option-charge" :class="chargeLabel(@js($value)) === 'Included' ? 'is-included' : ''" x-text="chargeLabel(@js($value))"></small>
                </label>
            @endforeach
        </div>
    @elseif($type === 'textarea')
        <textarea x-model="inputs[@js($id)]" @input="sync()" @change="commitInput(@js($group), $event.target.value)" class="np-proto-option-input np-proto-option-textarea" placeholder="{{ $group['placeholder'] }}"></textarea>
    @elseif($type === 'file')
        <label class="np-proto-option-file"><strong>Upload {{ $group['label'] }}</strong><small>{{ $group['accepted_file_types'] ?: 'PDF, SVG, PNG, JPG' }} · max {{ $group['maximum_file_size_mb'] ?: 15 }} MB</small><input type="file" name="artwork_file" @change="inputs[@js($id)] = $event.target.files[0]?.name || ''; sync(); if (inputs[@js($id)]) notifyCustomization('Added', @js($group['label']).concat(' has been added to your product.'), 'input:'.concat(@js($id)))"></label>
    @else
        <input class="np-proto-option-input" type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}" x-model="inputs[@js($id)]" @input="sync()" @change="commitInput(@js($group), $event.target.value)" placeholder="{{ $group['placeholder'] }}">
    @endif
</section>
