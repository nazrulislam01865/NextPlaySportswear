<x-layouts.admin title="Create Shipping Method" subtitle="Add a dynamic checkout shipping option.">
    @include('admin.shipping-methods._form', [
        'method' => $method,
        'action' => route('admin.shipping-methods.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
