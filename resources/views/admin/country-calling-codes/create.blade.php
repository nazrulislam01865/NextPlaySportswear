<x-layouts.admin title="Add Country" eyebrow="Master Data / Countrys" subtitle="Add a country used across the storefront and phone calling codes.">
    @include('admin.country-calling-codes._form', [
        'action' => route('admin.country-calling-codes.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
