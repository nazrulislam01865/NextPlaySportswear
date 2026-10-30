<x-layouts.storefront :seo="$seo">
    <section class="np-auth-proto" aria-labelledby="np-signup-title">
        <div class="np-auth-proto__card np-auth-proto__card--register" data-registration-wizard>
            <div class="np-auth-proto__steps" aria-label="Sign-up progress">
                @foreach ([1 => 'Email', 2 => 'Verify', 3 => 'Account'] as $number => $label)
                    @if ($number > 1)<span class="np-auth-proto__step-line {{ $step > $number - 1 ? 'is-complete' : '' }}" aria-hidden="true"></span>@endif
                    <div class="np-auth-proto__step {{ $number === $step ? 'is-current' : ($number < $step ? 'is-complete' : '') }}" @if($number === $step) aria-current="step" @endif>
                        <span class="np-auth-proto__step-dot">@if($number < $step)&#10003;@else {{ $number }} @endif</span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            @if ($step === 1)
                <h1 id="np-signup-title" class="np-auth-proto__title">Create your account</h1>
                <p class="np-auth-proto__subtitle">Start by verifying your email address.</p>
                <form method="POST" action="{{ route('register.email.send') }}" class="nps-form np-auth-proto__form" data-auth-inline-validation data-single-submit novalidate>
                    @csrf
                    <div class="np-auth-proto__field">
                        <label for="register_email">Email address</label>
                        <div class="np-auth-proto__input-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 7 9-7"/></svg>
                            <input id="register_email" name="email" type="email" data-auth-field autocomplete="email" placeholder="Enter your email address" value="{{ old('email') }}" required autofocus @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror>
                        </div>
                        <p id="register-email-error" class="np-auth-proto__error" data-auth-error-for="email" role="alert" @if(!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</p>
                        <p class="np-auth-proto__hint">We'll send you a 6-digit verification code.</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary">Send verification code</button>
                </form>
            @elseif ($step === 2)
                <h1 id="np-signup-title" class="np-auth-proto__title">Verify your email</h1>
                <p class="np-auth-proto__subtitle">Enter the 6-digit code we sent to</p>
                <div class="np-auth-proto__email-line"><strong>{{ $pendingEmail }}</strong>
                    <form action="{{ route('register.email.change') }}" method="POST">@csrf<button class="np-auth-proto__text-button" type="submit">Change email</button></form>
                </div>
                @if(session('status'))<p class="np-auth-proto__notice" role="status">{{ session('status') }}</p>@endif
                <form method="POST" action="{{ route('register.email.verify') }}" class="nps-form np-auth-proto__form" data-code-verification data-single-submit novalidate>
                    @csrf
                    <label class="np-auth-proto__code-label" for="signup-code-0">Verification code</label>
                    <div class="np-auth-proto__code-cells" role="group" aria-label="Six-digit verification code">
                        @for ($i = 0; $i < 6; $i++)
                            <input id="signup-code-{{ $i }}" type="text" inputmode="numeric" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" pattern="[0-9]" maxlength="1" aria-label="Digit {{ $i + 1 }}" data-code-digit {{ $i === 0 ? 'autofocus' : '' }}>
                        @endfor
                    </div>
                    <input type="hidden" name="code" data-code-value value="">
                    @error('code')<p class="np-auth-proto__error" role="alert">{{ $message }}</p>@enderror
                    @error('email')<p class="np-auth-proto__error" role="alert">{{ $message }}</p>@enderror
                    <p class="np-auth-proto__hint">Your code expires in 10 minutes.</p>
                    <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary" data-code-submit disabled>Verify email</button>
                </form>
                <div class="np-auth-proto__resend">
                    <p>Didn't receive a code? Check your spam folder.</p>
                    <form action="{{ route('register.email.send') }}" method="POST" data-registration-resend data-single-submit data-available-at="{{ $resendAfter }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $pendingEmail }}">
                        <button type="submit" class="np-auth-proto__text-button" data-resend-button disabled>Resend code in 00:45</button>
                    </form>
                    @error('email')<p class="np-auth-proto__error" role="alert">{{ $message }}</p>@enderror
                </div>
                <form method="POST" action="{{ route('register.email.change') }}" class="np-auth-proto__back">@csrf<button type="submit" class="np-auth-proto__text-button">‹&nbsp; Back</button></form>
            @else
                <h1 id="np-signup-title" class="np-auth-proto__title">Complete your account</h1>
                <div class="np-auth-proto__verified"><span aria-hidden="true">✓</span><strong>{{ $verifiedEmail }}</strong><em>Verified</em></div>
                <form method="POST" action="{{ route('register.store') }}" class="nps-form np-auth-proto__form np-auth-proto__complete-form" data-customer-registration novalidate data-single-submit>
                    @csrf
                    <input type="hidden" name="email" value="{{ $verifiedEmail }}">
                    <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">
                    <div class="np-auth-proto__field"><label for="signup-name">Full name</label><input id="signup-name" type="text" name="name" minlength="2" maxlength="120" autocomplete="name" placeholder="Enter your full name" value="{{ old('name') }}" required autofocus>@error('name')<p class="np-auth-proto__error">{{ $message }}</p>@enderror</div>
                    <div class="np-auth-proto__field"><label for="signup-password">Password</label><div class="np-auth-proto__password"><input id="signup-password" type="password" name="password" autocomplete="new-password" placeholder="Create a password" minlength="8" required aria-describedby="signup-password-hint"><button type="button" data-password-visibility="signup-password" aria-label="Show password" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><p id="signup-password-hint" class="np-auth-proto__hint @error('password') is-invalid @enderror" data-password-rule-feedback @error('password') data-server-error="{{ $message }}" @enderror data-default-hint="Use at least 8 characters with a letter, a number, and a special character." role="status" aria-live="polite">@error('password'){{ $message }}@else Use at least 8 characters with a letter, a number, and a special character. @enderror</p></div>
                    <div class="np-auth-proto__field"><label for="signup-confirm-password">Confirm password</label><div class="np-auth-proto__password"><input id="signup-confirm-password" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required><button type="button" data-password-visibility="signup-confirm-password" aria-label="Show password" aria-pressed="false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg></button></div><p class="np-customer-register-match" data-password-match-feedback role="status" aria-live="polite" hidden></p>@error('password_confirmation')<p class="np-auth-proto__error" role="alert">{{ $message }}</p>@enderror</div>
                    <x-storefront.auth.register-terms :short="true" />
                    <button type="submit" class="btn btn-primary btn-sm np-auth-proto__primary" disabled aria-disabled="true">Create account</button>
                </form>
            @endif

            @if ($step === 1 || $step === 3)
                <div class="np-auth-proto__footer">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
            @endif
        </div>
    </section>
</x-layouts.storefront>
