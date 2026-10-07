<x-layouts.admin title="Create Production Method" subtitle="Add a reusable production method for product setup.">
    @include('admin.production-methods._form', [
        'method' => $method,
        'action' => route('admin.production-methods.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
