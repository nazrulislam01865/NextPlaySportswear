<x-layouts.admin title="Edit Gender" subtitle="Update this reusable product gender option.">
    @include('admin.genders._form', [
        'gender' => $gender,
        'action' => route('admin.genders.update', $gender),
        'formMethod' => 'PUT',
    ])
</x-layouts.admin>
