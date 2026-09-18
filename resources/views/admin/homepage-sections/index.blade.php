<x-layouts.admin title="Homepage Controls" eyebrow="Storefront" subtitle="Manage the approved Vue homepage section by section." :storefront-url="route('home')">
<div class="np-home-admin">
    <div class="np-home-admin__hero mb-6 rounded-2xl p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div><p class="np-home-admin__kicker">NEXTPLAY STOREFRONT</p><h2 class="text-2xl font-bold">Homepage Control Center</h2><p class="np-home-admin__muted mt-2 max-w-3xl text-sm">Edit only the sections used by the new Vue homepage. Product cards remain controlled by the product catalog.</p></div>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="np-home-admin__secondary">View Homepage ↗</a>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach($sections as $section)
            <article class="np-home-admin__section-card rounded-2xl p-5">
                <div class="flex gap-4">
                    @if($section['thumbnail'])
                        <img src="{{ $section['thumbnail'] }}" alt="" class="h-24 w-28 shrink-0 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                            <span class="np-home-admin__order">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="{{ $section['is_active'] ? 'np-home-admin__status--active' : 'np-home-admin__status--hidden' }} np-home-admin__status">{{ $section['is_active'] ? 'Visible' : 'Hidden' }}</span>
                        </div>
                        <h3 class="mt-3 text-lg font-bold">{{ $section['name'] }}</h3>
                        <p class="np-home-admin__muted mt-1 text-sm">{{ $section['title'] ?: 'Section settings and visibility' }}</p>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs">
                            @if($section['item_count'] !== null)<span>{{ $section['item_count'] }} configured item{{ $section['item_count'] === 1 ? '' : 's' }}</span>@endif
                            @if($section['slide_count'] !== null)<span>{{ $section['slide_count'] }} hero slide{{ $section['slide_count'] === 1 ? '' : 's' }}</span>@endif
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    @if($section['key'] === 'hero' && $canManageSlides)
                        <a href="{{ route('admin.homepage-slides.index') }}" class="np-home-admin__secondary">Manage Slides</a>
                    @endif
                    <a href="{{ route('admin.homepage.sections.edit', $section['key']) }}" class="np-home-admin__primary rounded-xl px-4 py-2 font-bold">Edit Section</a>
                </div>
            </article>
        @endforeach
    </div>
</div>
</x-layouts.admin>
