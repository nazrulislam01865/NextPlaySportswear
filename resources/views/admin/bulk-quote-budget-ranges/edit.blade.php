<x-layouts.admin title="Edit Bulk Quote Budget Range" eyebrow="Master Data / Bulk Quote Budget Ranges" subtitle="Update this reusable budget range.">
    @include('admin.bulk-quote-budget-ranges._form', [
        'action' => route('admin.bulk-quote-budget-ranges.update', $budgetRange),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
