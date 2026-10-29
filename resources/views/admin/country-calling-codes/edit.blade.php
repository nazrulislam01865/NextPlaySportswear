<x-layouts.admin title="Edit Country" eyebrow="Master Data / Countrys" subtitle="Edit storefront country, calling code and region label.">
    @include('admin.country-calling-codes._form', [
        'action' => route('admin.country-calling-codes.update', $countryCallingCode),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
