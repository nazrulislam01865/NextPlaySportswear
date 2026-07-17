<x-layouts.admin title="Edit Production Method" subtitle="Update this reusable master production timeline.">
    @include('admin.production-methods._form', [
        'method' => $method,
        'action' => route('admin.production-methods.update', $method),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
