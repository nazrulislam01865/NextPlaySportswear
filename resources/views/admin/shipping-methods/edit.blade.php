<x-layouts.admin title="Edit Shipping Method" subtitle="Update checkout shipping rules and delivery estimate.">
    @include('admin.shipping-methods._form', [
        'method' => $method,
        'action' => route('admin.shipping-methods.update', $method),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
