<x-layouts.admin title="Edit Rural Surcharge" subtitle="Update surcharge rules used during checkout.">
    @include('admin.rural-area-surcharges._form', [
        'surcharge' => $surcharge,
        'action' => route('admin.rural-area-surcharges.update', $surcharge),
        'method' => 'PUT',
    ])
</x-layouts.admin>
