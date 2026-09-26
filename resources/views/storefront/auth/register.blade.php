<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.register-shell title="Create Account">
        @if (session('status'))
            <div class="np-customer-login-alert np-customer-login-alert--success" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('register.store') }}"
            class="np-customer-login-form np-customer-register-form"
            novalidate
            data-single-submit
        >
            @csrf

            @if (! empty($redirectUrl))
                <input type="hidden" name="redirect" value="{{ $redirectUrl }}">
            @endif

            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

            <x-storefront.auth.login-input
                name="name"
                label="Full name"
                placeholder="Enter your full name"
                autocomplete="name"
                required
                autofocus
            />

            <x-storefront.auth.login-input
                name="email"
                label="Email address"
                type="email"
                placeholder="you@example.com"
                autocomplete="email"
                required
            />

            <x-storefront.auth.login-password
                name="password"
                label="Password"
                placeholder="Enter your password"
                autocomplete="new-password"
                required
            />

            <x-storefront.auth.login-password
                name="password_confirmation"
                label="Confirm password"
                placeholder="Confirm your password"
                autocomplete="new-password"
                required
            />

            <x-storefront.auth.register-terms />

            <button type="submit" class="btn btn-primary btn-lg np-customer-login__submit">
                Create Account
            </button>
        </form>

        <div class="np-customer-login__register np-customer-register__signin">
            <span>Already have an account?</span>
            <a
                href="{{ route('login', array_filter(['redirect' => $redirectUrl ?? null])) }}"
                class="np-customer-login__register-link"
            >
                Sign in
            </a>
        </div>
    </x-storefront.auth.register-shell>
</x-layouts.storefront>
