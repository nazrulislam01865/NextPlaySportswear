<x-layouts.admin title="Edit Payment Method" subtitle="Update checkout payment rules and provider behavior.">
    @include('admin.payment-methods._form', [
        'method' => $method,
        'action' => route('admin.payment-methods.update', $method),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
