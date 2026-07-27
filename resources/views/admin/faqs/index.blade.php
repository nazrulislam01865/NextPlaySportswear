<x-layouts.admin title="FAQ Master Data" subtitle="Create reusable frequently asked questions and assign them from product add/edit pages.">
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-[minmax(240px,1fr)_180px_auto]">
            <label class="admin-label">Search FAQs
                <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search question or answer">
            </label>
            <label class="admin-label">Status
                <select class="admin-input" name="status">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </label>
            <button class="btn btn-white self-end" type="submit">Filter</button>
        </form>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-red shrink-0">+ Add FAQ</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="touch-scroll-x" tabindex="0" aria-label="FAQ master data table">
            <table class="admin-table min-w-[900px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">FAQ</th>
                        <th class="px-5 py-3">Assigned products</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Order</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($faqs as $faq)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-black text-brand-ink">{{ $faq->question }}</p>
                                <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($faq->answer, 180) }}</p>
                            </td>
                            <td class="px-5 py-4 font-black text-brand-blue">{{ number_format($faq->products_count) }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wide',
                                    'bg-emerald-50 text-emerald-700' => $faq->is_active,
                                    'bg-slate-100 text-slate-500' => ! $faq->is_active,
                                ])>{{ $faq->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-600">{{ $faq->sort_order }}</td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions justify-end">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.faqs.edit', $faq) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ? It will also be removed from every assigned product, but the products will not be deleted.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No FAQs found. Create one to make it available in product setup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($faqs->hasPages())
        <div class="admin-pagination mt-5">{{ $faqs->links('pagination.nextplay', ['itemName' => 'FAQ']) }}</div>
    @endif
</x-layouts.admin>
