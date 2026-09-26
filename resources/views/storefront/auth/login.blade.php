<x-layouts.storefront :seo="$seo">
    <x-storefront.auth.login-shell title="Sign In">
        @if (session('status'))
            <div class="np-customer-login-alert np-customer-login-alert--success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if (! empty($checkoutIntended))
            <div class="np-customer-login-alert np-customer-login-alert--notice" role="status">
                Please sign in or create a customer account to continue secure checkout. Your cart will stay saved and will be linked to your account after login.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="np-customer-login-form"
            novalidate
            data-single-submit
        >
            @csrf

            @if (! empty($redirectUrl))
                <input type="hidden" name="redirect" value="{{ $redirectUrl }}">
            @endif

            <x-storefront.auth.login-input
                name="email"
                label="Email address"
                type="email"
                placeholder="you@example.com"
                autocomplete="email"
                required
                autofocus
            />

            <x-storefront.auth.login-password
                name="password"
                label="Password"
                placeholder="Enter your password"
                autocomplete="current-password"
                :forgot-url="route('password.request')"
                required
            />

            <button type="submit" class="btn btn-primary btn-lg np-customer-login__submit">
                Sign In
            </button>
        </form>

        <div class="np-customer-login__register">
            <span>New to NextPlay?</span>
            <a
                href="{{ route('register', array_filter(['redirect' => $redirectUrl ?? null])) }}"
                class="np-customer-login__register-link"
            >
                Create an account
            </a>
        </div>
    </x-storefront.auth.login-shell>
</x-layouts.storefront>
