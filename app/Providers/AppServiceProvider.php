<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Catalog\NavigationService;
use App\Services\Storefront\HomepageSliderService;
use App\Services\Storefront\HomepageSectionService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
            $cartService = app(CartService::class);
            $cartSummary = $cartService->summary();
            $cartItems = collect($cartSummary['items'] ?? [])->values();

            $view->with('cartItemCount', (int) ($cartSummary['quantity'] ?? 0));
            $view->with('headerCart', [
                'items' => $cartItems->take(4)->all(),
                'total_items' => $cartItems->count(),
                'remaining_items' => max(0, $cartItems->count() - 4),
                'quantity' => (int) ($cartSummary['quantity'] ?? 0),
                'subtotal' => (float) ($cartSummary['subtotal'] ?? 0),
                'total' => (float) ($cartSummary['total'] ?? 0),
                'is_empty' => (bool) ($cartSummary['is_empty'] ?? true),
                'checkout_ready' => (bool) ($cartSummary['checkout_ready'] ?? false),
            ]);
            $view->with('storefrontMenus', app(NavigationService::class)->storefrontMenus());
        });

        View::composer('components.storefront.footer', function ($view): void {
            $view->with('storefrontMenus', app(NavigationService::class)->storefrontMenus());
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
    }
}
