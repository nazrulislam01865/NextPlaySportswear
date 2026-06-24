<x-layouts.admin title="Homepage Slider">
    <div class="mb-6 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Storefront content</p>
            <h2 class="mt-1 text-2xl font-black text-brand-dark">Homepage Slider</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Upload banner images, hide or customize text, control buttons, schedule visibility, and set the display order.</p>
        </div>
        <a href="{{ route('admin.homepage-slides.create') }}" class="btn btn-red">Add Slide</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Homepage slides table">
            <table class="admin-table min-w-[1050px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] font-black uppercase tracking-[.12em] text-slate-500">
                    <tr><th class="px-5 py-3">Slide</th><th class="px-5 py-3">Content</th><th class="px-5 py-3">Schedule</th><th class="px-5 py-3">Order</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($slides as $slide)
                        @php($image = $slide->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($slide->image_path) : $slide->image_url)
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <div class="flex min-w-[260px] items-center gap-4">
                                    <img src="{{ $image }}" alt="" class="h-20 w-32 rounded-xl object-cover" loading="lazy">
                                    <div class="min-w-0"><a href="{{ route('admin.homepage-slides.edit', $slide) }}" class="line-clamp-2 font-black text-brand-blue">{{ $slide->title ?: 'Image-only slide' }}</a><p class="mt-1 text-xs text-slate-400">Updated {{ $slide->updated_at?->diffForHumans() }}</p></div>
                                </div>
                            </td>
                            <td class="px-5 py-4"><span class="admin-status-pill px-2.5 py-1 text-xs font-black {{ $slide->show_content ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $slide->show_content ? 'Text visible' : 'Image only' }}</span><p class="mt-2 max-w-xs line-clamp-2 text-xs text-slate-500">{{ $slide->description }}</p></td>
                            <td class="px-5 py-4 text-xs leading-6 text-slate-600"><div>Start: {{ $slide->starts_at?->format('M j, Y g:i A') ?? 'Immediately' }}</div><div>End: {{ $slide->ends_at?->format('M j, Y g:i A') ?? 'No end date' }}</div></td>
                            <td class="px-5 py-4 font-black">{{ number_format($slide->sort_order) }}</td>
                            <td class="px-5 py-4"><span class="admin-status-pill px-2.5 py-1 text-xs font-black {{ $slide->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $slide->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.homepage-slides.edit', $slide) }}" class="admin-row-action border-slate-200 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('admin.homepage-slides.toggle', $slide) }}">@csrf @method('PATCH')<button class="admin-row-action border-slate-200 text-xs">{{ $slide->is_active ? 'Disable' : 'Enable' }}</button></form>
                                    <form method="POST" action="{{ route('admin.homepage-slides.destroy', $slide) }}" onsubmit="return confirm('Delete this homepage slide? This action cannot be undone.')">@csrf @method('DELETE')<button class="admin-row-action border-red-200 text-xs text-red-700 hover:bg-red-50">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500"><p class="font-black">No homepage slides yet.</p><p class="mt-1 text-sm">Add the first banner to display a slider on the storefront homepage.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($slides->hasPages())<div class="border-t border-slate-100 p-5">{{ $slides->links() }}</div>@endif
    </div>
</x-layouts.admin>
