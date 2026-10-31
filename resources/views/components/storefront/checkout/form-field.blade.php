@props([
    'label', 'name', 'type' => 'text', 'placeholder' => '', 'value' => null,
    'full' => false, 'options' => [], 'textarea' => false,
    'required' => false, 'hint' => null, 'autocomplete' => null,
])
<div class="nps-field {{ $full ? 'nps-full' : '' }}">
    <label class="nps-label" for="{{ $name }}">
        {{ $label }}
        @if($required)
            <span class="nps-required" aria-hidden="true">*</span>
        @else
            <span class="nps-optional">(optional)</span>
        @endif
    </label>
    @if($textarea)
        <textarea id="{{ $name }}" name="{{ $name }}" placeholder="{{ $placeholder }}" @if($required) required @endif aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if($errors->has($name)) aria-describedby="{{ $name }}-error" @endif>{{ old($name, $value) }}</textarea>
    @elseif($options)
        <select id="{{ $name }}" name="{{ $name }}" @if($required) required @endif aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}">
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif @if($required) required @endif aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if($errors->has($name)) aria-describedby="{{ $name }}-error" @endif>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="nps-error" role="alert">{{ $message }}</p>
    @else
        @if($hint)
            <p class="nps-help">{{ $hint }}</p>
        @endif
    @enderror
</div>
