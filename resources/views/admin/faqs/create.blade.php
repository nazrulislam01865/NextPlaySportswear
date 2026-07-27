<x-layouts.admin title="Create FAQ" subtitle="Add a reusable FAQ to Master Data.">
    @include('admin.faqs._form', [
        'action' => route('admin.faqs.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
