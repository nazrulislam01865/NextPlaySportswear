<x-layouts.admin title="Edit Time Zone" eyebrow="Master Data / Time Zones" subtitle="Update this reusable promotion scheduling time zone.">
    @include('admin.time-zones._form', [
        'action' => route('admin.time-zones.update', $timeZone),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
