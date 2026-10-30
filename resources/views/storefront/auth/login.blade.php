<x-layouts.storefront :seo="$seo">
    <section class="np-auth-proto" aria-labelledby="np-login-title">
        <div class="np-auth-proto__card np-auth-proto__card--login">
            <h1 id="np-login-title" class="np-auth-proto__title">Sign in</h1>
            <p class="np-auth-proto__subtitle">Welcome back to NextPlay Sportswear.</p>
            @if (session('status'))<div class="np-auth-proto__notice" role="status">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div class="np-auth-proto__alert" role="alert"><span aria-hidden="true">!</span>{{ $errors->first() }}</div>
            @endif
            @if (! empty($checkoutIntended))<p class="np-auth-proto__notice">Sign in to continue to checkout. Your cart will be saved.</p>@endif
            <form method="POST" action="{{ route('login.store') }}" class="nps-form np-auth-proto__form" data-auth-inline-validation data-single-submit novalidate>
                @csrf
                @if (! empty($redirectUrl))<input type="hidden" name="redirect" value="{{ $redirectUrl }}">@endif
                <div class="np-auth-proto__field"><label for="login-email">Email address</label><input id="login-email" name="email" type="email" data-auth-field value="{{ old('email') }}" autocomplete="email" placeholder="Enter your email address" required autofocus><p id="login-email-error" class="np-auth-proto__error" data-auth-error-for="email" role="alert" hidden></p></div>
                <div class="np-auth-proto__field"><label for="login-password">Password</label><div class="np-auth-proto__password"><input id="login-password" name="password" type="password" data-auth-field autocomplete="current-password" placeholder="Enter your password" required><button type="button" data-password-visibility="login-password" aria-label="Show password" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><p id="login-password-error" class="np-auth-proto__error" data-auth-error-for="password" role="alert" hidden></p></div>
                <div class="np-auth-proto__login-extras"><label class="np-auth-proto__remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))>Remember me</label><a href="{{ route('password.request') }}">Forgot password?</a></div>
                <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary">Sign in</button>
            </form>
            <p class="np-auth-proto__legal">By signing in, you agree to our <a href="{{ route('terms') }}">Terms and Conditions</a> and <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>
            <div class="np-auth-proto__footer np-auth-proto__footer--stacked"><span>New to NextPlay?</span><a class="btn btn-sm np-auth-proto__secondary" href="{{ route('register', array_filter(['redirect' => $redirectUrl ?? null])) }}">Create account</a></div>
        </div>
    </section>
</x-layouts.storefront>
