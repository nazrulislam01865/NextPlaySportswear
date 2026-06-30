<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use App\Services\Catalog\NavigationService;
use App\Services\Storefront\HomepageSliderService;
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
            $view->with('cartItemCount', app(CartService::class)->count());
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
