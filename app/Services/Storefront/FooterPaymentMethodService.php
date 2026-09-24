<?php

namespace App\Services\Storefront;

use App\Models\PaymentMethod;
use App\Support\PublicMedia;
use Illuminate\Support\Facades\Cache;

final class FooterPaymentMethodService
{
    private const CACHE_KEY = 'storefront.footer.payment-methods.v1';

    /** @return array<int, array<string, mixed>> */
    public function methods(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function (): array {
            return PaymentMethod::query()
                ->where('is_active', true)
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'code',
                    'provider',
                    'footer_icon_path',
                    'footer_icon_alt',
                    'sort_order',
                ])
                ->map(static fn (PaymentMethod $method): array => [
                    'id' => (int) $method->id,
                    'name' => (string) $method->name,
                    'code' => (string) $method->code,
                    'provider' => (string) $method->provider,
                    'icon_url' => PublicMedia::url($method->footer_icon_path),
                    'icon_alt' => (string) ($method->footer_icon_alt ?: $method->name),
                    'sort_order' => (int) $method->sort_order,
                ])
                ->values()
                ->all();
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
