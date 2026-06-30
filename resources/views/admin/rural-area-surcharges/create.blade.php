<x-layouts.admin title="Add Rural Surcharge" subtitle="Create a ZIP/postal-code based delivery surcharge.">
    @include('admin.rural-area-surcharges._form', [
        'surcharge' => $surcharge,
        'action' => route('admin.rural-area-surcharges.store'),
        'method' => 'POST',
    ])
</x-layouts.admin>
