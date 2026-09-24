@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
])

<div class="np-customer-login-field">
    <label for="{{ $name }}" class="np-customer-login-field__label">
        {{ $label }}
        @if ($required)
            <span class="np-customer-login-field__required" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($autofocus) autofocus @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->merge(['class' => 'np-customer-login-field__input']) }}
    >

    @error($name)
        <p id="{{ $name }}-error" class="np-customer-login-field__error">{{ $message }}</p>
    @enderror
</div>
