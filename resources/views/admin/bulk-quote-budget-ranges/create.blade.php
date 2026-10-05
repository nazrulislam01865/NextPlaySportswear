<x-layouts.admin title="Add Bulk Quote Budget Range" eyebrow="Master Data / Bulk Quote Budget Ranges" subtitle="Add a reusable budget range for the Bulk Quote form.">
    @include('admin.bulk-quote-budget-ranges._form', [
        'action' => route('admin.bulk-quote-budget-ranges.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
