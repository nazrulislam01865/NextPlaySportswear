<x-layouts.storefront :seo="$seo">
    @if (session('status'))
        <x-storefront.system-card title="Check Your Email" icon="mail-check">
            <x-slot:message>
                If an account exists for that email, we’ll send a reset link.<br>
                Check your inbox and spam folder.
            </x-slot:message>

            <form method="POST" action="{{ route('password.email') }}" data-single-submit>
                @csrf
                <input type="hidden" name="email" value="{{ old('email') }}">

                <button type="submit" class="np-system-card__primary" data-submitting-label="Resending…">
                    Resend Link
                </button>
            </form>

            <x-slot:footer>
                <a href="{{ route('login') }}" class="np-system-card__secondary-link">
                    Back to Sign In
                </a>
            </x-slot:footer>
        </x-storefront.system-card>
    @else
        <x-storefront.system-card title="Forgot Password?">
            <x-slot:message>
                Enter your email and we’ll send you a reset link.
            </x-slot:message>

            <form method="POST" action="{{ route('password.email') }}" class="np-system-card__form" data-single-submit>
                @csrf

                <x-storefront.auth.login-input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                    autofocus
                />

                <button type="submit" class="np-system-card__primary" data-submitting-label="Sending…">
                    Send Reset Link
                </button>
            </form>

            <x-slot:footer>
                <a href="{{ route('login') }}" class="np-system-card__secondary-link">
                    Back to Sign In
                </a>
            </x-slot:footer>
        </x-storefront.system-card>
    @endif
</x-layouts.storefront>
