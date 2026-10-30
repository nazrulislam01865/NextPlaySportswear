<x-layouts.storefront :seo="$seo">
    <section class="np-auth-proto" aria-labelledby="np-reset-title">
        <div class="np-auth-proto__card np-auth-proto__card--recovery">
            <div class="np-auth-proto__lock" aria-hidden="true">
                <svg viewBox="0 0 40 44" width="40" height="44" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 18V13a10 10 0 0 1 20 0v5"/><rect x="7" y="18" width="26" height="23" rx="3"/><circle cx="20" cy="29" r="2"/><path d="M20 31v4"/></svg>
            </div>
            <h1 id="np-reset-title" class="np-auth-proto__title">Set a new password</h1>
            <p class="np-auth-proto__subtitle">Choose a new password for your NextPlay Sportswear account.</p>
            <form method="POST" action="{{ route('password.store') }}" class="nps-form np-auth-proto__form np-auth-proto__reset-form" data-password-reset data-single-submit novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                @if ($errors->has('email') || $errors->has('token'))
                    <p class="np-auth-proto__error" role="alert">{{ $errors->first('email') ?: $errors->first('token') }}</p>
                @endif
                <div class="np-auth-proto__field">
                    <label for="reset-password">New password</label>
                    <div class="np-auth-proto__password">
                        <input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="Enter a new password" required minlength="8" maxlength="255" aria-describedby="reset-password-hint">
                        <button type="button" data-password-visibility="reset-password" aria-label="Show password" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                    <p id="reset-password-hint" class="np-auth-proto__hint @error('password') is-invalid @enderror" data-reset-password-hint @error('password') data-server-error="{{ $message }}" @enderror data-default-hint="Use at least 8 characters with a letter, a number, and a special character." aria-live="polite">@error('password'){{ $message }}@else Use at least 8 characters with a letter, a number, and a special character. @enderror</p>
                </div>
                <div class="np-auth-proto__field">
                    <label for="reset-confirm-password">Confirm new password</label>
                    <div class="np-auth-proto__password">
                        <input id="reset-confirm-password" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter your new password" required minlength="8" maxlength="255" aria-describedby="reset-match-message">
                        <button type="button" data-password-visibility="reset-confirm-password" aria-label="Show password" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </div>
                    <p id="reset-match-message" class="np-auth-proto__error" data-reset-password-match @error('password_confirmation') data-server-error="{{ $message }}" @enderror aria-live="polite" @if(!$errors->has('password_confirmation')) hidden @endif>@error('password_confirmation'){{ $message }}@enderror</p>
                </div>
                <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary" data-reset-submit disabled>Reset password</button>
            </form>
            <div class="np-auth-proto__recovery-back"><a href="{{ route('login') }}"><span aria-hidden="true">←</span> Back to sign in</a></div>
        </div>
    </section>
</x-layouts.storefront>
