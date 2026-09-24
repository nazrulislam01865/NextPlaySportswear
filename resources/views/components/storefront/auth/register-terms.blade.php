@props([
    'name' => 'terms',
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
            I agree to the
            <span class="np-customer-register-terms__links">
                <a href="{{ route('terms') }}">Terms</a>
                <span aria-hidden="true"> &amp; </span>
                <a href="{{ route('privacy') }}">Privacy Policy.</a>
            </span>
        </span>
    </label>

    @error($name)
        <p id="{{ $name }}-error" class="np-customer-login-field__error">{{ $message }}</p>
    @enderror

    @error('website')
        <p class="np-customer-login-field__error">{{ $message }}</p>
    @enderror
</div>
