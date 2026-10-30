<x-layouts.storefront :seo="$seo">
    <section class="np-auth-proto" aria-labelledby="np-forgot-title">
        <div class="np-auth-proto__card np-auth-proto__card--recovery">
            <div class="np-auth-proto__lock" aria-hidden="true">
                <svg viewBox="0 0 40 44" width="40" height="44" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 18V13a10 10 0 0 1 20 0v5"/><rect x="7" y="18" width="26" height="23" rx="3"/><circle cx="20" cy="29" r="2"/><path d="M20 31v4"/></svg>
            </div>
            <h1 id="np-forgot-title" class="np-auth-proto__title">Forgot password?</h1>
            <p class="np-auth-proto__subtitle">Enter the email address linked to your account. We’ll send you a link to reset your password.</p>

            @if (session('status'))
                <p class="np-auth-proto__notice np-auth-proto__notice--success" role="status">If an account exists for that email, check your inbox and spam folder for a reset link.</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="nps-form np-auth-proto__form" data-auth-inline-validation data-single-submit novalidate>
                @csrf
                <div class="np-auth-proto__field">
                    <label for="recovery-email">Email address</label>
                    <input id="recovery-email" name="email" type="email" data-auth-field autocomplete="email" placeholder="Enter your email address" value="{{ old('email') }}" required autofocus @error('email') aria-invalid="true" aria-describedby="recovery-email-error" @enderror>
                    <p id="recovery-email-error" class="np-auth-proto__error" data-auth-error-for="email" role="alert" @if(!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</p>
                </div>
                <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary">Send reset link</button>
            </form>
            <div class="np-auth-proto__recovery-back"><a href="{{ route('login') }}"><span aria-hidden="true">←</span> Back to sign in</a></div>
        </div>
    </section>
</x-layouts.storefront>
