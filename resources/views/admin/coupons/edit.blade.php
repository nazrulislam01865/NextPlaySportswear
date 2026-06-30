<x-layouts.admin title="Edit Coupon">
    @include('admin.coupons._form', [
        'action' => route('admin.coupons.update', $coupon),
        'method' => 'PUT',
    ])
</x-layouts.admin>
