<x-layouts.admin title="Add Remote Area Surcharge" subtitle="Create one structured remote/extended-area rule.">
    @include('admin.rural-area-surcharges._form', [
        'surcharge' => $surcharge,
        'action' => route('admin.rural-area-surcharges.store'),
        'method' => 'POST',
    ])
</x-layouts.admin>
