<x-layouts.admin title="Edit Country Calling Code" eyebrow="Master Data / Country Calling Codes" subtitle="Update this reusable country calling code.">
    @include('admin.country-calling-codes._form', [
        'action' => route('admin.country-calling-codes.update', $countryCallingCode),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
