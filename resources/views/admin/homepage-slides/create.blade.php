<x-layouts.admin title="Add Homepage Slide">
    @include('admin.homepage-slides._form', [
        'action' => route('admin.homepage-slides.store'),
        'method' => 'POST',
    ])
</x-layouts.admin>
