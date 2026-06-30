<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'active' => (string) $request->query('active', ''),
        ];

        $coupons = Coupon::query()
            ->withCount('redemptions')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($search) use ($filters): void {
                    $search->where('name', 'like', '%'.$filters['q'].'%')
                        ->orWhere('code', 'like', '%'.strtoupper($filters['q']).'%');
                });
            })
            ->when($filters['active'] !== '', fn ($query) => $query->where('is_active', $filters['active'] === '1'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons', 'filters'));
    }

    public function create(): View
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'discount_type' => 'percentage',
                'minimum_subtotal' => 0,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        Coupon::query()->create($request->validated());

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->validated());

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Coupon deleted successfully.');
    }
}
