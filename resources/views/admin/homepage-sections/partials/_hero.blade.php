<x-admin.homepage.section-panel title="Hero Banner" description="Hero content and images are managed as slides so text, buttons, ordering and scheduling stay together.">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <strong class="text-lg">{{ $slideCount ?? 0 }} configured slides</strong>
            <p class="np-home-admin__muted mt-1 text-sm">Manage slide text, one responsive banner image, buttons and order.</p>
        </div>
        @if($canManageSlides)
            <a href="{{ route('admin.homepage-slides.index') }}" class="np-home-admin__primary">Manage Slides</a>
        @endif
    </div>
    <div class="mt-5"><x-admin.homepage.visibility-field :active="$viewSection['is_active']" /></div>
</x-admin.homepage.section-panel>
