<x-layouts.admin title="Edit FAQ" subtitle="Update this reusable FAQ and every product using it.">
    @include('admin.faqs._form', [
        'action' => route('admin.faqs.update', $faq),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
