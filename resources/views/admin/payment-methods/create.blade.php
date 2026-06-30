<x-layouts.admin title="Create Payment Method" subtitle="Add a dynamic checkout payment option.">
    @include('admin.payment-methods._form', [
        'method' => $method,
        'action' => route('admin.payment-methods.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
