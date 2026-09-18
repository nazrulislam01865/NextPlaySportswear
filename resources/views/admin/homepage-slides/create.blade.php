<x-layouts.admin title="Add Hero Banner Slide">
    <div class="np-home-admin">
        <div class="mb-5">
            <a href="{{ route('admin.homepage.sections.edit', 'hero') }}" class="np-home-admin__secondary inline-flex">← Back to Hero Banner</a>
        </div>
    @include('admin.homepage-slides._form', [
        'action' => route('admin.homepage-slides.store'),
        'method' => 'POST',
    ])
    </div>
</x-layouts.admin>
