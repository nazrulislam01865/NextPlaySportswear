<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Catalog\NavigationService;
use App\Services\Storefront\HomepageSliderService;
use App\Services\Storefront\HomepageSectionService;
use App\Services\Wishlist\WishlistHeaderService;
use Illuminate\Auth\Events\Login;
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

        RateLimiter::for('password-reset-link', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $fingerprint = hash('sha256', substr($email, 0, 190));

            return [
                Limit::perMinute(3)->by('password-reset-link-ip:'.$ip),
                Limit::perHour(8)->by('password-reset-link-account:'.$fingerprint),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): array {
            $ip = (string) ($request->ip() ?: 'unknown');
            $email = strtolower(trim((string) $request->input('email')));
            $fingerprint = hash('sha256', substr($email, 0, 190));

            return [
                Limit::perMinute(5)->by('password-reset-ip:'.$ip),
                Limit::perHour(15)->by('password-reset-account:'.$fingerprint),
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
