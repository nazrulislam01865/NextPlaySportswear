@php
    $reward = (float) ($offer['reward_amount'] ?? 0);
    $minimum = (float) ($offer['minimum_order'] ?? 0);
    $rewardLabel = number_format($reward, $reward == floor($reward) ? 0 : 2);
    $minimumLabel = number_format($minimum, $minimum == floor($minimum) ? 0 : 2);
@endphp
<x-layouts.storefront :seo="$seo">
    <section class="np-referral-register-page">
        <div class="site-container">
            <nav class="np-referral-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ url()->previous() }}">Referral offer</a><span>/</span><strong>Create account</strong>
            </nav>

            <div class="np-referral-register-grid">
                <section class="np-referral-register-card">
                    <h1 class="nps-form-page-title">CREATE YOUR ACCOUNT</h1>
                    <h2>Your friend's £{{ $rewardLabel }} offer is saved for this visit.</h2>
                    <p class="np-referral-register-card__intro">Verify your email and create an account to shop with your referral offer already linked to your order.</p>

                    @if ($errors->any() && $step === 3)
                        <div class="np-referral-form-alert" role="alert">{{ $errors->first() }}</div>
                    @endif

                    {{-- Referral signup shares the same verified-email lifecycle as regular signup. --}}
                    @if ($step === 1)
                        <p class="np-referral-register-card__intro">Verify your email address before completing your referral account.</p>
                        <form method="POST" action="{{ route('register.email.send') }}" class="nps-form np-referral-register-form" novalidate data-auth-inline-validation data-single-submit>
                            @csrf
                            <label>Email address
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="Enter your email address" required autofocus data-auth-field @error('email') aria-invalid="true" aria-describedby="referral-register-email-error" @enderror>
                            </label>
                            <p id="referral-register-email-error" class="np-referral-form-alert" data-auth-error-for="email" role="alert" @if(!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</p>
                            <button type="submit" class="btn btn-secondary np-referral-register-submit">SEND VERIFICATION CODE <x-storefront.referral.icon name="arrow-right" :size="20" /></button>
                        </form>
                    @elseif ($step === 2)
                        <p class="np-referral-register-card__intro">Enter the six-digit code sent to <strong>{{ $pendingEmail }}</strong>.</p>
                        @if(session('status'))<p class="np-referral-register-card__intro" role="status">{{ session('status') }}</p>@endif
                        <form method="POST" action="{{ route('register.email.verify') }}" class="nps-form np-referral-register-form" novalidate data-single-submit>
                            @csrf
                            <label>Verification code
                                <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code" required autofocus>
                            </label>
                            @error('code')<p class="np-referral-form-alert" role="alert">{{ $message }}</p>@enderror
                            <button type="submit" class="btn btn-secondary np-referral-register-submit">VERIFY EMAIL <x-storefront.referral.icon name="arrow-right" :size="20" /></button>
                        </form>
                        <form method="POST" action="{{ route('register.email.send') }}" data-registration-resend data-single-submit data-available-at="{{ $resendAfter }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $pendingEmail }}">
                            <button type="submit" class="np-auth-proto__text-button" data-resend-button disabled>Resend verification code</button>
                        </form>
                        <form method="POST" action="{{ route('register.email.change') }}">
                            @csrf
                            <button type="submit" class="np-auth-proto__text-button">Change email address</button>
                        </form>
                    @else
                        <p class="np-referral-register-card__intro">Email verified: <strong>{{ $verifiedEmail }}</strong></p>
                        <form method="POST" action="{{ route('register.store') }}" class="nps-form np-referral-register-form" novalidate data-single-submit>
                        @csrf
                        @if (! empty($redirectUrl))<input type="hidden" name="redirect" value="{{ $redirectUrl }}">@endif
                        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

                        <label>First name<input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name" autocomplete="given-name" required autofocus></label>
                        <label>Last name<input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" autocomplete="family-name" required></label>
                        <input type="hidden" name="email" value="{{ $verifiedEmail }}">
                        <label>Password
                            <span class="np-referral-password-wrap">
                                <input type="password" name="password" placeholder="Create a password" autocomplete="new-password" required data-referral-password>
                                <button type="button" aria-label="Show password" data-referral-password-toggle><x-storefront.referral.icon name="eye" :size="21" /></button>
                            </span>
                            <small>Use at least 8 characters with a mix of letters, numbers and symbols.</small>
                        </label>

                        <label class="np-referral-check"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required><span>I agree to the <a href="{{ route('terms') }}">Terms &amp; Conditions</a> and acknowledge the <a href="{{ route('privacy') }}">Privacy Policy</a>.</span></label>

                        <button type="submit" class="btn btn-secondary np-referral-register-submit">CREATE ACCOUNT &amp; CONTINUE <x-storefront.referral.icon name="arrow-right" :size="20" /></button>
                    </form>
                    @endif

                    <div class="np-referral-register-or"><span>OR</span></div>
                    <a href="{{ route('products.index') }}" class="btn btn-outline np-referral-register-guest">CONTINUE SHOPPING <x-storefront.referral.icon name="arrow-right" :size="20" /></a>
                    <p class="np-referral-register-card__fine">You can browse before registering, but the referral can only be redeemed by a new customer account created from this offer.</p>
                    <p class="np-referral-register-card__signin">Already have an account? <a href="{{ route('login', ['redirect' => route('products.index')]) }}">Sign in</a></p>
                </section>

                <aside class="np-referral-register-aside">
                    <div class="np-referral-register-aside__offer">
                        <span class="np-referral-register-aside__icon"><x-storefront.referral.icon name="gift" :size="48" /></span>
                        <div><small>YOUR REFERRAL OFFER</small><strong>£{{ $rewardLabel }} <span>off</span></strong><p>your first eligible order of £{{ $minimumLabel }} or more.</p></div>
                    </div>
                    <div class="np-referral-register-aside__rules">
                        <p><x-storefront.referral.icon name="user" :size="26" /> New customers only</p>
                        <p><x-storefront.referral.icon name="bag" :size="26" /> Eligible products only</p>
                        <p><x-storefront.referral.icon name="card" :size="26" /> Shown at checkout when your order qualifies.</p>
                    </div>
                    <div class="np-referral-register-aside__callout"><span><x-storefront.referral.icon name="link" :size="32" /></span><div><strong>No code to remember</strong><p>Your friend's offer is already attached to this visit.</p></div></div>
                    <div class="np-referral-register-aside__callout"><span><x-storefront.referral.icon name="cart" :size="32" /></span><div><strong>Prefer to shop first?</strong><p>Your offer stays linked while you browse; create your new account before checkout to redeem it.</p><a href="{{ route('products.index') }}">Continue shopping <x-storefront.referral.icon name="arrow-right" :size="18" /></a></div></div>
                </aside>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.querySelector('[data-referral-password]');
            const toggle = document.querySelector('[data-referral-password-toggle]');
            if (!input || !toggle) return;
            toggle.addEventListener('click', () => {
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                toggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            });
        });
    </script>
</x-layouts.storefront>
