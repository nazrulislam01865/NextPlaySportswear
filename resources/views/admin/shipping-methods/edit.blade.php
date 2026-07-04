<x-layouts.admin title="Edit Shipping Method" subtitle="Update this reusable master shipping method.">
    @include('admin.shipping-methods._form', [
        'method' => $method,
        'action' => route('admin.shipping-methods.update', $method),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
