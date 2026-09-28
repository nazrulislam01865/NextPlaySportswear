<x-layouts.admin title="Promotions" subtitle="Manage sale campaigns and their storefront schedules.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Published campaigns appear here and become visible on the storefront only while their schedule and weekday rules are active.</p>
        </div>
        <a href="{{ route('admin.promotions.sales.create') }}" class="btn btn-red">+ Create Sale Campaign</a>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">{{ session('status') }}</div>
    @endif

    <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:grid-cols-[1fr_180px_auto]" method="GET">
        <input class="admin-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search campaign name or code">
        <select class="admin-input" name="status">
            <option value="">All statuses</option>
            <option value="live" @selected(($filters['status'] ?? '') === 'live')>Published</option>
            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
        </select>
        <button class="btn btn-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Sale campaigns table">
            <table class="admin-table min-w-[1040px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Campaign</th>
                        <th class="px-5 py-4">Discount</th>
                        <th class="px-5 py-4">Applies to</th>
                        <th class="px-5 py-4">Schedule</th>
                        <th class="px-5 py-4">Storefront</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaigns as $campaign)
                        @php
                            $now = now('UTC');
                            $isLive = $campaign->status === 'live';
                            $isScheduled = $isLive && $campaign->starts_at && $campaign->starts_at->isFuture();
                            $isEnded = $isLive && $campaign->ends_at && $campaign->ends_at->isPast();
                            $isWithinDates = $isLive && !$isScheduled && !$isEnded;
                            $localNow = $now->copy()->timezone($campaign->timezone ?: 'UTC');
                            $weekdayActive = !$campaign->repeat_weekdays || in_array($localNow->format('D'), (array) $campaign->weekdays, true);
                            $isActiveNow = $isWithinDates && $weekdayActive;
                            $statusLabel = $campaign->status === 'draft'
                                ? 'Draft'
                                : ($isScheduled ? 'Scheduled' : ($isEnded ? 'Ended' : ($isActiveNow ? 'Active' : 'Paused today')));
                            $statusClass = $isActiveNow
                                ? 'bg-emerald-50 text-emerald-700'
                                : ($campaign->status === 'draft' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700');
                            $targetCount = $campaign->applies_to === 'products'
                                ? $campaign->products_count
                                : ($campaign->applies_to === 'all' ? null : $campaign->categories_count);
                            $discountLabel = $campaign->discount_type === 'fixed'
                                ? '£'.number_format((float) $campaign->discount_value, 2).' off'
                                : rtrim(rtrim(number_format((float) $campaign->discount_value, 2), '0'), '.').'% off';
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if(filled($campaign->banner_image_path))
                                        <img src="{{ \App\Support\PublicMedia::storedPathUrl($campaign->banner_image_path) }}" alt="" class="h-11 w-20 rounded-lg border border-slate-200 object-cover">
                                    @endif
                                    <div class="min-w-0">
                                        <strong class="block truncate text-brand-ink">{{ $campaign->name }}</strong>
                                        @if(filled($campaign->internal_code))
                                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-wide text-slate-700">{{ $campaign->internal_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <strong class="text-brand-ink">{{ $discountLabel }}</strong>
                                @if($campaign->maximum_discount)
                                    <span class="block text-xs text-slate-500">Max £{{ number_format((float) $campaign->maximum_discount, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <strong class="block text-brand-ink">{{ str($campaign->applies_to)->replace('_', ' ')->headline() }}</strong>
                                @if($targetCount !== null)
                                    <span class="text-xs text-slate-500">{{ number_format($targetCount) }} selected</span>
                                @endif
                                @if($campaign->excluded_products_count > 0)
                                    <span class="block text-xs text-slate-500">{{ number_format($campaign->excluded_products_count) }} excluded</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600">
                                <span class="block">{{ $campaign->starts_at?->timezone($campaign->timezone)->format('d M Y, H:i') }}</span>
                                <span class="block">to {{ $campaign->ends_at?->timezone($campaign->timezone)->format('d M Y, H:i') }}</span>
                                @if($campaign->repeat_weekdays)
                                    <span class="block font-semibold text-slate-500">{{ implode(', ', (array) $campaign->weekdays) }}</span>
                                @endif
                                <span class="block text-slate-400">{{ $campaign->timezone }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs leading-5 text-slate-600">
                                <span class="block">Badge: {{ $campaign->show_sale_badge ? 'Yes' : 'No' }}</span>
                                <span class="block">Sale page: {{ $campaign->show_sale_page ? 'Yes' : 'No' }}</span>
                                <span class="block">Priority: {{ $campaign->priority }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.promotions.sales.edit', $campaign) }}" class="btn btn-white">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No sale campaigns found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $campaigns->links('pagination.nextplay') }}</div>
</x-layouts.admin>
