@props(['group'])
@php($id = $group['id'])
<section class="np-proto-option-section">
    <div class="np-proto-option-section__head">
        <div>
            <h4>{{ $group['label'] }} @if($group['required'])<span class="text-brand-red">*</span>@endif</h4>
            @if($group['description'])<p>{{ $group['description'] }}</p>@endif
        </div>
    </div>

    @if(in_array($group['type'], ['image', 'swatch', 'buttons', 'select', 'checkbox'], true))
        <div class="np-proto-option-grid" @if($group['type'] !== 'checkbox') role="radiogroup" aria-label="{{ $group['label'] }}" @endif>
            @foreach($group['values'] as $value)
                <x-storefront.product.customizer.option-choice
                    :group="$group"
                    :value="$value"
                    :multiple="$group['type'] === 'checkbox'"
                />
            @endforeach
        </div>
    @elseif($group['type'] === 'textarea')
        <textarea x-model="inputs[@js($id)]" @input="sync()" @change="commitInput(@js($group), $event.target.value)" class="np-proto-option-input np-proto-option-textarea" placeholder="{{ $group['placeholder'] }}"></textarea>
    @elseif($group['type'] === 'file')
        <label class="np-proto-option-file"><strong>Upload {{ $group['label'] }}</strong><small>{{ $group['accepted_file_types'] ?: 'PDF, SVG, PNG, JPG' }} · max {{ $group['maximum_file_size_mb'] ?: 15 }} MB</small><input type="file" name="artwork_file" @change="inputs[@js($id)] = $event.target.files[0]?.name || ''; sync(); if (inputs[@js($id)]) notifyCustomization('Added', @js($group['label']).concat(' has been added to your product.'), 'input:'.concat(@js($id)))"></label>
    @else
        <input class="np-proto-option-input" type="{{ $group['type'] === 'number' ? 'number' : ($group['type'] === 'date' ? 'date' : 'text') }}" x-model="inputs[@js($id)]" @input="sync()" @change="commitInput(@js($group), $event.target.value)" placeholder="{{ $group['placeholder'] }}">
    @endif
</section>
