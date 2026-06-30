<x-layouts.admin title="Add Coupon">
    @include('admin.coupons._form', [
        'action' => route('admin.coupons.store'),
        'method' => 'POST',
    ])
</x-layouts.admin>
