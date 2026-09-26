<x-layouts.admin title="Create Gender" subtitle="Add a reusable gender option for product setup.">
    @include('admin.genders._form', [
        'gender' => $gender,
        'action' => route('admin.genders.store'),
        'formMethod' => 'POST',
    ])
</x-layouts.admin>
