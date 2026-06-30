<?php

namespace App\Services\Payments;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PaymentMethodService
{
    public function availableMethods(array $summary, ?User $user = null): array
    {
        $total = max(0, (float) ($summary['total'] ?? 0));

        return $this->methodRecords()
            ->filter(function (PaymentMethod $method) use ($total): bool {
                if ($method->minimum_total !== null && $total < (float) $method->minimum_total) {
                    return false;
                }

                if ($method->maximum_total !== null && $total > (float) $method->maximum_total) {
                    return false;
                }

                return true;
            })
            ->map(fn (PaymentMethod $method): array => $this->toCheckoutOption($method, $total))
            ->values()
            ->all();
    }

    public function defaultMethod(array $methods): ?array
    {
        if ($methods === []) {
            return null;
        }

        $default = collect($methods)->firstWhere('is_default', true);

        return $default ?: $methods[0];
    }

    public function savedCardGateway(array $methods): ?array
    {
        return collect($methods)
            ->filter(fn (array $method): bool => (bool) ($method['allows_saved_methods'] ?? false))
            ->sortBy([['is_default', 'desc'], ['sort_order', 'asc']])
            ->first();
    }

    private function methodRecords(): Collection
    {
        if (! Schema::hasTable('payment_methods')) {
            return collect($this->fallbackMethods());
        }

        $records = PaymentMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $records->isNotEmpty() ? $records : collect($this->fallbackMethods());
    }

    private function toCheckoutOption(PaymentMethod $method, float $total): array
    {
        return [
            'id' => $method->id,
            'code' => $method->normalizedCode(),
            'method' => $method->normalizedCode(),
            'title' => (string) $method->name,
            'label' => (string) $method->name,
            'provider' => (string) $method->provider,
            'payment_type' => (string) $method->payment_type,
            'description' => (string) ($method->description ?: 'Complete payment using this method.'),
            'instructions' => (string) ($method->instructions ?: ''),
            'badge' => (string) ($method->badge ?: ucfirst((string) $method->payment_type)),
            'amount' => round($total, 2),
            'display_amount' => '$'.number_format($total, 2),
            'minimum_total' => $method->minimum_total === null ? null : (float) $method->minimum_total,
            'maximum_total' => $method->maximum_total === null ? null : (float) $method->maximum_total,
            'is_online' => (bool) $method->is_online,
            'requires_provider_redirect' => (bool) $method->requires_provider_redirect,
            'requires_manual_review' => (bool) $method->requires_manual_review,
            'allows_saved_methods' => (bool) $method->allows_saved_methods,
            'is_default' => (bool) $method->is_default,
            'is_active' => (bool) $method->is_active,
            'sort_order' => (int) $method->sort_order,
        ];
    }

    private function fallbackMethods(): array
    {
        return collect([
            new PaymentMethod([
                'name' => 'Credit / Debit Card',
                'code' => 'card',
                'provider' => 'stripe',
                'payment_type' => 'card',
                'badge' => 'Secure',
                'description' => 'Pay securely by card through a PCI-compliant hosted payment provider.',
                'instructions' => 'Raw card numbers and CVV are never stored in the application database.',
                'is_online' => true,
                'requires_provider_redirect' => true,
                'requires_manual_review' => false,
                'allows_saved_methods' => true,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 10,
            ]),
            new PaymentMethod([
                'name' => 'Request Invoice for Bulk Order',
                'code' => 'invoice',
                'provider' => 'manual',
                'payment_type' => 'invoice',
                'badge' => 'Invoice',
                'description' => 'Submit the order for manual invoice review.',
                'instructions' => 'Admin reviews and confirms payment terms before production starts.',
                'is_online' => false,
                'requires_provider_redirect' => false,
                'requires_manual_review' => true,
                'allows_saved_methods' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 20,
            ]),
        ])->map(fn (PaymentMethod $method): PaymentMethod => tap($method, fn ($m) => $m->exists = true))->all();
    }
}
