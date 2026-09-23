<x-layouts.admin title="Edit Homepage Slide">
    @include('admin.homepage-slides._form', [
        'action' => route('admin.homepage-slides.update', $slide),
        'method' => 'PUT',
    ])
</x-layouts.admin>
