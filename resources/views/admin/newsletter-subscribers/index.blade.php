<x-layouts.admin
    title="Newsletter Subscribers"
    subtitle="View emails collected from the homepage newsletter signup section."
>
    @php
        $hasActiveFilters = filled($filters['q'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['source'] ?? null);

        $statCards = [
            ['label' => 'Total Emails', 'value' => $stats['total'] ?? 0, 'note' => 'All stored subscribers', 'icon' => '@', 'class' => 'bg-blue-50 text-brand-blue'],
            ['label' => 'Active', 'value' => $stats['active'] ?? 0, 'note' => 'Currently subscribed', 'icon' => '✓', 'class' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Unsubscribed', 'value' => $stats['unsubscribed'] ?? 0, 'note' => 'Opted out records', 'icon' => '−', 'class' => 'bg-slate-100 text-slate-600'],
            ['label' => 'Today', 'value' => $stats['today'] ?? 0, 'note' => 'New emails today', 'icon' => '+', 'class' => 'bg-rose-50 text-brand-red'],
        ];
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($statCards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-lg font-black {{ $card['class'] }}">{{ $card['icon'] }}</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold leading-4 text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-black leading-none text-brand-ink">{{ number_format((int) $card['value']) }}</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-slate-400">{{ $card['note'] }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mb-5 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <form method="GET" class="grid min-w-0 flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(240px,1fr)_180px_220px_auto_auto]">
            <label class="admin-label">
                Search
                <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search email or source" autocomplete="off">
            </label>

            <label class="admin-label">
                Status
                <select class="admin-input" name="status">
                    <option value="">All statuses</option>
                    <option value="{{ \App\Models\NewsletterSubscriber::STATUS_ACTIVE }}" @selected(($filters['status'] ?? '') === \App\Models\NewsletterSubscriber::STATUS_ACTIVE)>Active</option>
                    <option value="{{ \App\Models\NewsletterSubscriber::STATUS_UNSUBSCRIBED }}" @selected(($filters['status'] ?? '') === \App\Models\NewsletterSubscriber::STATUS_UNSUBSCRIBED)>Unsubscribed</option>
                </select>
            </label>

            <label class="admin-label">
                Source
                <select class="admin-input" name="source">
                    <option value="">All sources</option>
                    @foreach($sources as $source)
                        <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ $source }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-col justify-end">
                <button class="btn btn-navy h-11 w-full whitespace-nowrap sm:w-auto">◇ Filter</button>
            </div>

            <div class="flex flex-col justify-end">
                @if($hasActiveFilters)
                    <a href="{{ route('admin.newsletter-subscribers.index') }}" class="btn btn-white h-11 w-full whitespace-nowrap sm:w-auto">Clear</a>
                @else
                    <span class="hidden lg:block"></span>
                @endif
            </div>
        </form>

        <a href="{{ route('admin.newsletter-subscribers.export', request()->query()) }}" class="btn btn-red h-11 shrink-0 whitespace-nowrap">Export CSV</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Newsletter subscribers table">
            <table class="admin-table min-w-[920px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Email</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Source</th>
                        <th class="px-5 py-4">Subscribed</th>
                        <th class="px-5 py-4">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($subscribers as $subscriber)
                        <tr>
                            <td class="px-5 py-4">
                                <a class="font-black text-brand-ink hover:text-brand-red" href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'admin-status-pill px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-700' => $subscriber->status === \App\Models\NewsletterSubscriber::STATUS_ACTIVE,
                                    'bg-slate-100 text-slate-600' => $subscriber->status !== \App\Models\NewsletterSubscriber::STATUS_ACTIVE,
                                ])>{{ \Illuminate\Support\Str::of($subscriber->status)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $subscriber->source ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                <strong class="block text-brand-ink">{{ $subscriber->subscribed_at?->format('M d, Y') ?: '—' }}</strong>
                                <span class="text-xs text-slate-500">{{ $subscriber->subscribed_at?->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <strong class="block text-brand-ink">{{ $subscriber->updated_at?->format('M d, Y') ?: '—' }}</strong>
                                <span class="text-xs text-slate-500">{{ $subscriber->updated_at?->format('H:i') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No newsletter emails found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $subscribers->links() }}</div>
</x-layouts.admin>
