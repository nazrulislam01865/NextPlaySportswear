<x-layouts.admin title="Navigation Menus">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-sm leading-6 text-slate-500">Manage the storefront header and footer navigation from one place. Header changes apply to both desktop and mobile after saving.</p>
        </div>
        <a class="btn btn-red shrink-0" href="{{ route('admin.menus.create') }}">+ Add Menu</a>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($menus as $menu)
            @php
                $isHeader = $menu->location === 'header-primary';
                $locationLabel = $locationOptions[$menu->location] ?? ($menu->location ?: 'Unassigned location');
            @endphp
            <article @class([
                'rounded-3xl border bg-white p-5 shadow-card',
                'border-blue-200 ring-1 ring-blue-100' => $isHeader,
                'border-slate-200' => ! $isHeader,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-lg font-black text-brand-ink">{{ $menu->name }}</h2>
                            @if($isHeader)
                                <span class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700">Header</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $locationLabel }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-1 text-xs font-bold {{ $menu->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $menu->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    {{ $menu->all_items_count }} {{ $menu->all_items_count === 1 ? 'menu item' : 'menu items' }}
                    @if($isHeader)
                        · controls desktop + mobile header
                    @endif
                </div>

                <div class="mt-5 flex gap-2">
                    <a class="btn {{ $isHeader ? 'btn-red' : 'btn-white' }} flex-1" href="{{ route('admin.menus.edit', $menu) }}">
                        {{ $isHeader ? 'Manage Header Menu' : 'Edit Menu' }}
                    </a>
                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('Delete this navigation menu?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-white text-red-700">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 md:col-span-2 xl:col-span-3">
                No navigation menus exist yet.
            </div>
        @endforelse
    </div>
</x-layouts.admin>
