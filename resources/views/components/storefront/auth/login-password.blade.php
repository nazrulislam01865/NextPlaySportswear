@props([
    'name' => 'password',
    'label' => 'Password',
    'placeholder' => 'Enter your password',
    'autocomplete' => 'current-password',
    'required' => false,
    'forgotUrl' => null,
    'forgotLabel' => 'Forgot password?',
])

<div class="np-customer-login-field" x-data="{ visible: false }">
    <label for="{{ $name }}" class="np-customer-login-field__label">
        {{ $label }}
        @if ($required)
            <span class="np-customer-login-field__required" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="np-customer-login-password">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="password"
            :type="visible ? 'text' : 'password'"
            value=""
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required aria-required="true" @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            class="np-customer-login-field__input np-customer-login-password__input"
            data-password-input
        >

        <button
            type="button"
            class="np-customer-login-password__toggle"
            @click="visible = ! visible"
            aria-label="Show password"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            aria-pressed="false"
            :aria-pressed="visible.toString()"
            aria-controls="{{ $name }}"
            data-password-toggle
        >
            <svg
                x-show="! visible"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M2.06 12.35a1 1 0 0 1 0-.7C3.72 7.73 7.16 5 12 5s8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7C20.28 16.27 16.84 19 12 19s-8.28-2.73-9.94-6.65Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>

            <svg
                x-cloak
                x-show="visible"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c4.84 0 8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7 11.05 11.05 0 0 1-1.43 2.49"></path>
                <path d="M6.61 6.61A11.03 11.03 0 0 0 2.06 11.65a1 1 0 0 0 0 .7C3.72 16.27 7.16 19 12 19a10.8 10.8 0 0 0 5.39-1.39"></path>
                <path d="m3 3 18 18"></path>
            </svg>
        </button>
    </div>

    @error($name)
        <p id="{{ $name }}-error" class="np-customer-login-field__error">{{ $message }}</p>
    @enderror

    @if ($forgotUrl)
        <div class="np-customer-login-password__forgot-row">
            <a href="{{ $forgotUrl }}" class="np-customer-login-password__forgot">
                {{ $forgotLabel }}
            </a>
        </div>
    @endif
</div>
