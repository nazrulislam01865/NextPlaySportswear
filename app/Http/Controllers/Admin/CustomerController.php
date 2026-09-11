<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->withCount(['orders', 'orderReturnRequests'])
            ->withMax('orders as last_order_at', 'placed_at');

        if ($search = trim((string) $request->query('q'))) {
            $customers->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%');
            });
        }

        if ($accountStatus = $request->query('account_status')) {
            if (in_array($accountStatus, ['active', 'inactive', 'suspended'], true)) {
                $customers->where('is_active', $accountStatus === 'active');
            }
        }

        if ($verification = $request->query('verification')) {
            if ($verification === 'verified') {
                $customers->whereNotNull('email_verified_at');
            } elseif ($verification === 'unverified') {
                $customers->whereNull('email_verified_at');
            }
        }

        if ($orders = $request->query('orders')) {
            if ($orders === 'with_orders') {
                $customers->has('orders');
            } elseif ($orders === 'without_orders') {
                $customers->doesntHave('orders');
            }
        }

        if ($marketing = $request->query('marketing')) {
            if (in_array($marketing, ['yes', 'no'], true)) {
                $customers->where('marketing_consent', $marketing === 'yes');
            }
        }

        match ((string) $request->query('sort', 'newest')) {
            'name' => $customers->orderBy('name')->orderBy('id'),
            'orders' => $customers->orderByDesc('orders_count')->latest('id'),
            'last_order' => $customers->orderByDesc('last_order_at')->latest('id'),
            default => $customers->latest('created_at')->latest('id'),
        };

        return view('admin.customers.index', [
            'customers' => $customers->paginate($this->adminPerPage(25))->withQueryString(),
            'stats' => [
                'total' => User::query()->where('role', 'customer')->count(),
                'active' => User::query()->where('role', 'customer')->where('is_active', true)->count(),
                'verified' => User::query()->where('role', 'customer')->whereNotNull('email_verified_at')->count(),
                'with_orders' => User::query()->where('role', 'customer')->has('orders')->count(),
            ],
        ]);
    }

    public function show(User $customer): View
    {
        abort_unless($customer->role === 'customer', 404);

        $customer->loadMissing([
            'suspendedBy:id,name,email',
            'reactivatedBy:id,name,email',
        ]);

        $orders = $customer->orders()
            ->latest('placed_at')
            ->paginate($this->adminPerPage(15))
            ->withQueryString();

        $addresses = $customer->customerAddresses()->get();
        $paymentMethods = $customer->customerPaymentMethods()->get();
        $recentReturns = $customer->orderReturnRequests()
            ->with('order:id,order_number')
            ->latest('requested_at')
            ->limit(8)
            ->get();

        $paidByCurrency = DB::table('order_payments')
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->where('orders.user_id', $customer->id)
            ->whereNull('orders.deleted_at')
            ->where('order_payments.status', 'paid')
            ->select('order_payments.currency', DB::raw('SUM(order_payments.amount) as total'))
            ->groupBy('order_payments.currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount): float => (float) $amount);

        $refundedByCurrency = DB::table('order_refunds')
            ->join('orders', 'orders.id', '=', 'order_refunds.order_id')
            ->where('orders.user_id', $customer->id)
            ->whereNull('orders.deleted_at')
            ->where('order_refunds.status', 'issued')
            ->select('order_refunds.currency', DB::raw('SUM(order_refunds.amount) as total'))
            ->groupBy('order_refunds.currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount): float => (float) $amount);

        $currencies = $paidByCurrency->keys()->merge($refundedByCurrency->keys())->unique()->sort()->values();
        $netSpend = $currencies->mapWithKeys(function (string $currency) use ($paidByCurrency, $refundedByCurrency): array {
            $paid = (float) ($paidByCurrency[$currency] ?? 0);
            $refunded = (float) ($refundedByCurrency[$currency] ?? 0);

            return [$currency => max(0, round($paid - $refunded, 2))];
        });

        $admin = auth('admin')->user();

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'addresses' => $addresses,
            'paymentMethods' => $paymentMethods,
            'recentReturns' => $recentReturns,
            'netSpend' => $netSpend,
            'canViewOrders' => (bool) $admin?->canAdmin('orders.view'),
            'canViewReturns' => (bool) $admin?->canAdmin('returns.view'),
            'canManageCustomer' => (bool) $admin?->canAdmin('customers.manage'),
            'stats' => [
                'orders' => $customer->orders()->count(),
                'open_orders' => $customer->orders()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'returns' => $customer->orderReturnRequests()->count(),
                'addresses' => $addresses->count(),
            ],
        ]);
    }

    public function suspend(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $validated = $request->validate([
            'suspension_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'suspension_reason.required' => 'Please provide a reason for suspending this customer.',
            'suspension_reason.min' => 'The suspension reason must be at least 5 characters.',
        ]);

        $admin = auth('admin')->user();

        $changed = DB::transaction(function () use ($customer, $admin, $validated): bool {
            $lockedCustomer = User::query()
                ->whereKey($customer->getKey())
                ->where('role', 'customer')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedCustomer->is_active) {
                return false;
            }

            $lockedCustomer->forceFill([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => trim((string) $validated['suspension_reason']),
                'suspended_by' => $admin?->id,
                'reactivated_at' => null,
                'reactivated_by' => null,
                'auth_session_version' => ((int) $lockedCustomer->auth_session_version) + 1,
                'remember_token' => null,
            ])->save();

            return true;
        });

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', $changed
                ? 'Customer suspended. Existing storefront sessions and future sign-ins are now blocked.'
                : 'This customer is already suspended.');
    }

    public function reactivate(User $customer): RedirectResponse
    {
        abort_unless($customer->role === 'customer', 404);

        $admin = auth('admin')->user();

        $changed = DB::transaction(function () use ($customer, $admin): bool {
            $lockedCustomer = User::query()
                ->whereKey($customer->getKey())
                ->where('role', 'customer')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCustomer->is_active) {
                return false;
            }

            $lockedCustomer->forceFill([
                'is_active' => true,
                'reactivated_at' => now(),
                'reactivated_by' => $admin?->id,
                'auth_session_version' => ((int) $lockedCustomer->auth_session_version) + 1,
                'remember_token' => null,
            ])->save();

            return true;
        });

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', $changed
                ? 'Customer reactivated. The customer can sign in again with their existing credentials.'
                : 'This customer account is already active.');
    }
}
