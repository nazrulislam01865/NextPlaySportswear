<x-layouts.admin title="Create Shipping Method" subtitle="Add a reusable master shipping method for products and checkout.">
    @include('admin.shipping-methods._form', [
        'method' => $method,
        'action' => route('admin.shipping-methods.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
