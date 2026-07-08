<x-layouts.admin
    title="Homepage Sections"
    eyebrow="Storefront"
    subtitle="Edit the homepage section by section without touching code."
    :storefront-url="route('home')"
>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.24em] text-brand-red">Homepage</p>
                    <h2 class="mt-1 text-2xl font-black text-brand-ink">Manage homepage sections</h2>
                    <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">Each section has only the content it needs: heading, short description, buttons, image, and simple list items where required. Product and category cards still come from their existing catalog settings.</p>
                </div>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-white">View Homepage ↗</a>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach($sections as $section)
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-[.16em] text-slate-500">{{ $loop->iteration }}</span>
                                <span @class([
                                    'rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-[.16em]',
                                    'bg-emerald-50 text-emerald-700' => $section['is_active'],
                                    'bg-slate-100 text-slate-500' => ! $section['is_active'],
                                ])>{{ $section['is_active'] ? 'Active' : 'Hidden' }}</span>
                            </div>
                            <h3 class="mt-3 truncate text-xl font-black text-brand-ink">{{ $section['name'] }}</h3>
                            <p class="mt-2 line-clamp-2 text-sm font-medium leading-6 text-slate-600">{{ $section['title'] ?: $section['description'] ?: 'This section controls display and order only.' }}</p>
                        </div>
                        <div class="shrink-0 rounded-2xl bg-brand-dark px-4 py-3 text-center text-white">
                            <span class="block text-[10px] font-black uppercase tracking-[.18em] text-slate-300">Order</span>
                            <strong class="text-lg">{{ $section['sort_order'] }}</strong>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs font-bold text-slate-500">
                            {{ count($section['items'] ?? []) }} item{{ count($section['items'] ?? []) === 1 ? '' : 's' }}
                        </div>
                        <a href="{{ route('admin.homepage.sections.edit', $section['key']) }}" class="btn btn-red">Edit Section</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
