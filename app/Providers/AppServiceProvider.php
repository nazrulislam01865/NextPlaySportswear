<?php

namespace App\Providers;

use App\Listeners\Auth\SendWelcomeEmailAfterVerification;
use App\Services\Cart\CartService;
use App\Services\Catalog\NavigationService;
use App\Services\Storefront\FooterPaymentMethodService;
use App\Services\Storefront\HomepageSliderService;
use App\Services\Storefront\HomepageSectionService;
use App\Services\Wishlist\WishlistHeaderService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationService::class);
        $this->app->singleton(FooterPaymentMethodService::class);
        $this->app->singleton(HomepageSliderService::class);
        $this->app->singleton(HomepageSectionService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.nextplay');
        Paginator::defaultSimpleView('pagination.nextplay-simple');

        $compiledViewPath = config('view.compiled');

        if (is_string($compiledViewPath) && $compiledViewPath !== '') {
            File::ensureDirectoryExists($compiledViewPath, 0755, true);
        }


        Event::listen(Verified::class, SendWelcomeEmailAfterVerification::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof \App\Models\User) {
                app(CartService::class)->claimSessionCart($event->user);
            }
        });

        View::composer('components.storefront.header', function ($view): void {
            $headerCart = app(CartService::class)->headerSummary(4);
            $customer = auth('web')->user();
            $headerWishlist = app(WishlistHeaderService::class)->summary($customer, 4);

            $view->with('cartItemCount', (int) ($headerCart['quantity'] ?? 0));
            $view->with('wishlistItemCount', (int) ($headerWishlist['total_items'] ?? 0));
            $view->with('wishlistAuthenticated', $customer?->isCustomer() ?? false);
            $view->with('headerWishlist', $headerWishlist);
            $view->with('headerCart', $headerCart);
            $view->with('storefrontMenus', app(NavigationService::class)->storefrontMenus());
        });

        View::composer('components.storefront.footer', function ($view): void {
            $view->with('storefrontMenus', app(NavigationService::class)->storefrontMenus());
            $view->with('footerPaymentMethods', app(FooterPaymentMethodService::class)->methods());
        });

        RateLimiter::for('admin-login', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $emailFingerprint = hash('sha256', substr($email, 0, 190));

            // Keep brute-force protection without blocking a legitimate admin
            // after a few accidental double-clicks or validation retries.
            return [
                Limit::perMinute(30)->by('admin-login-ip:'.$ip),
                Limit::perMinute(15)->by('admin-login-account:'.$ip.':'.$emailFingerprint),
            ];
        });

        RateLimiter::for('storefront-login', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $emailFingerprint = hash('sha256', substr($email, 0, 190));

            // Keep brute-force protection while avoiding false HTTP 429
            // responses from normal validation retries or accidental double
            // submissions. The account bucket is independent from the IP
            // bucket so users behind the same office/mobile network do not
            // block one another after only a few attempts.
            return [
                Limit::perMinute(60)->by('storefront-login-ip:'.$ip),
                Limit::perMinute(20)->by('storefront-login-account-minute:'.$emailFingerprint),
                Limit::perHour(100)->by('storefront-login-account-hour:'.$emailFingerprint),
            ];
        });

        RateLimiter::for('email-verification-send', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $userId = (string) ($request->user('web')?->getAuthIdentifier() ?: 'guest');

            return [
                Limit::perMinute(max(1, (int) config('security.email_verification.resend.ip_per_minute', 6)))
                    ->by('email-verification-ip-minute:'.$ip),
                Limit::perMinute(max(1, (int) config('security.email_verification.resend.account_per_minute', 3)))
                    ->by('email-verification-account-minute:'.$userId),
                Limit::perHour(max(1, (int) config('security.email_verification.resend.account_per_hour', 10)))
                    ->by('email-verification-account-hour:'.$userId),
            ];
        });

        RateLimiter::for('password-reset-link', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $fingerprint = hash('sha256', substr($email, 0, 190));

            return [
                Limit::perMinute(max(1, (int) config('security.password_reset.request.ip_per_minute', 3)))
                    ->by('password-reset-link-ip-minute:'.$ip),
                Limit::perHour(max(1, (int) config('security.password_reset.request.ip_per_hour', 12)))
                    ->by('password-reset-link-ip-hour:'.$ip),
                Limit::perMinute(max(1, (int) config('security.password_reset.request.email_per_minute', 2)))
                    ->by('password-reset-link-email-minute:'.$fingerprint),
                Limit::perHour(max(1, (int) config('security.password_reset.request.email_per_hour', 5)))
                    ->by('password-reset-link-email-hour:'.$fingerprint),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $fingerprint = hash('sha256', substr($email, 0, 190));

            return [
                Limit::perMinute(max(1, (int) config('security.password_reset.submit.ip_per_minute', 5)))
                    ->by('password-reset-submit-ip-minute:'.$ip),
                Limit::perHour(max(1, (int) config('security.password_reset.submit.ip_per_hour', 20)))
                    ->by('password-reset-submit-ip-hour:'.$ip),
                Limit::perMinute(max(1, (int) config('security.password_reset.submit.email_per_minute', 3)))
                    ->by('password-reset-submit-email-minute:'.$fingerprint),
                Limit::perHour(max(1, (int) config('security.password_reset.submit.email_per_hour', 10)))
                    ->by('password-reset-submit-email-hour:'.$fingerprint),
            ];
        });


        RateLimiter::for('contact', function (Request $request): array {
            $email = strtolower(trim((string) $request->input('email')));
            $emailFingerprint = hash('sha256', substr($email, 0, 190));
            $ip = (string) ($request->ip() ?: 'unknown');

            return [
                Limit::perMinute(3)->by('contact-ip-minute:'.$ip),
                Limit::perHour(20)->by('contact-ip-hour:'.$ip),
                Limit::perHour(10)->by('contact-email-hour:'.$emailFingerprint),
            ];
        });

        RateLimiter::for('bulk-quote', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $emailFingerprint = $email !== ''
                ? hash('sha256', substr($email, 0, 190))
                : null;

            $sessionId = $request->hasSession()
                ? (string) $request->session()->getId()
                : '';
            $customerId = (string) ($request->user('web')?->getAuthIdentifier() ?: '');
            $browserIdentity = $customerId !== ''
                ? 'user:'.$customerId
                : 'session:'.hash('sha256', $sessionId !== '' ? $sessionId : $ip);

            $tooManyRequestsResponse = static function (Request $request): \Illuminate\Http\RedirectResponse {
                return redirect()
                    ->back()
                    ->withInput($request->except(['_token', 'attachment', 'company']))
                    ->withErrors([
                        'bulk_quote_rate_limit' => 'You have submitted several quote requests in a short time. Please wait a moment and try again.',
                    ]);
            };

            // The old numeric throttle allowed only four POST requests per minute
            // and counted validation retries as attempts. That could lock a real
            // customer out while correcting the form and showed Laravel's raw
            // 429 page. These independent buckets keep abuse protection while
            // allowing normal validation retries and users behind shared networks.
            $limits = [
                Limit::perMinute(30)
                    ->by('bulk-quote-browser-minute:'.$browserIdentity)
                    ->response($tooManyRequestsResponse),
                Limit::perHour(120)
                    ->by('bulk-quote-browser-hour:'.$browserIdentity)
                    ->response($tooManyRequestsResponse),
                Limit::perHour(300)
                    ->by('bulk-quote-ip-hour:'.$ip)
                    ->response($tooManyRequestsResponse),
            ];

            if ($emailFingerprint !== null) {
                $limits[] = Limit::perHour(30)
                    ->by('bulk-quote-email-hour:'.$emailFingerprint)
                    ->response($tooManyRequestsResponse);
            }

            return $limits;
        });


        RateLimiter::for('checkout-step', function (Request $request): Limit {
            $identity = (string) ($request->user('web')?->getAuthIdentifier() ?: $request->ip() ?: 'guest');
            $routeName = (string) ($request->route()?->getName() ?: $request->path());

            // Each checkout step gets an independent bucket. This avoids the
            // previous nested numeric throttles counting every request twice
            // and incorrectly returning HTTP 429 during a normal checkout.
            return Limit::perMinute(20)->by('checkout-step:'.$identity.':'.$routeName);
        });

        RateLimiter::for('checkout-place-order', function (Request $request): Limit {
            $identity = (string) ($request->user('web')?->getAuthIdentifier() ?: $request->ip() ?: 'guest');

            return Limit::perMinute(4)->by('checkout-place-order:'.$identity);
        });

        RateLimiter::for('order-reorder', function (Request $request): array {
            $identity = (string) ($request->user('web')?->getAuthIdentifier() ?: $request->ip() ?: 'guest');
            $routeOrder = $request->route('order');
            $orderId = is_object($routeOrder) && method_exists($routeOrder, 'getKey')
                ? (string) $routeOrder->getKey()
                : (string) ($routeOrder ?: 'unknown');

            // Reorders get their own bucket instead of sharing the generic
            // numeric account throttle with unrelated profile/address writes.
            return [
                Limit::perMinute(30)->by('order-reorder-minute:'.$identity.':'.$orderId),
                Limit::perHour(120)->by('order-reorder-hour:'.$identity),
            ];
        });
    }
}
