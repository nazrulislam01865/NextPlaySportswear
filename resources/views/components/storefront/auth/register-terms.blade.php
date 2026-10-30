@props([
    'name' => 'terms',
    'short' => false,
])

<div class="np-customer-register-terms">
    <label class="np-customer-register-terms__label" for="{{ $name }}">
        <input
            id="{{ $name }}"
            type="checkbox"
            name="{{ $name }}"
            value="1"
            class="np-customer-register-terms__checkbox"
            @checked(old($name))
            required
            aria-required="true"
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        >
        <span class="np-customer-register-terms__text">
            @if ($short)
                I agree to the <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms and Conditions</a>
                and <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
            @else
                By continuing, you agree to NextPlay Sportswear’s
                <strong><a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Terms and Conditions</a></strong>
                and
                <strong><a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a></strong>.
            @endif
        </span>
    </label>

    @error($name)
        <p id="{{ $name }}-error" class="np-customer-login-field__error">{{ $message }}</p>
    @enderror

    @error('website')
        <p class="np-customer-login-field__error">{{ $message }}</p>
    @enderror
</div>
