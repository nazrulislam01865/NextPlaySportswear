<x-layouts.admin title="Add Country Calling Code" eyebrow="Master Data / Country Calling Codes" subtitle="Add a reusable country calling code for the Bulk Quote form.">
    @include('admin.country-calling-codes._form', [
        'action' => route('admin.country-calling-codes.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
