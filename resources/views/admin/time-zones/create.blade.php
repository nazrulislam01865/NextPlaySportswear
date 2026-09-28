<x-layouts.admin title="Add Time Zone" eyebrow="Master Data / Time Zones" subtitle="Add a reusable time zone for promotion scheduling.">
    @include('admin.time-zones._form', [
        'action' => route('admin.time-zones.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
