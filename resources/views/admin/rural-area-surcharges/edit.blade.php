<x-layouts.admin title="Edit Remote Area Surcharge" subtitle="Update the remote-area match and extra charge used during checkout.">
    @include('admin.rural-area-surcharges._form', [
        'surcharge' => $surcharge,
        'action' => route('admin.rural-area-surcharges.update', $surcharge),
        'method' => 'PUT',
    ])
</x-layouts.admin>
