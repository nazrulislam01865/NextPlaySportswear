<x-layouts.admin
    title="Contact Requests"
    eyebrow="Commerce"
    subtitle="Review customer enquiries, attachments and follow-up status in one place."
    :compact-header="true"
>
    @php
        $cards = [
            ['label' => 'All requests', 'value' => $stats->total ?? 0, 'hint' => 'Every Contact Us submission', 'symbol' => '✉', 'tone' => 'bg-blue-50 text-brand-blue'],
            ['label' => 'New', 'value' => $stats->new_count ?? 0, 'hint' => 'Waiting for review', 'symbol' => '●', 'tone' => 'bg-rose-50 text-brand-red'],
            ['label' => 'In review', 'value' => $stats->in_review_count ?? 0, 'hint' => 'Being handled', 'symbol' => '◷', 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Resolved / closed', 'value' => $stats->finished_count ?? 0, 'hint' => 'Handled enquiries', 'symbol' => '✓', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ];
        $hasFilters = filled($filters['q']) || filled($filters['status']) || filled($filters['topic']);
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <div class="flex items-center gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-lg font-black {{ $card['tone'] }}">{{ $card['symbol'] }}</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-black leading-tight text-brand-ink">{{ number_format((int) $card['value']) }}</p>
                        <p class="text-xs text-slate-500">{{ $card['hint'] }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <x-admin.filter-bar label="Contact requests filters">
        <div class="admin-filter-bar__search">
            <x-admin.filter-search name="q" :value="$filters['q']" label="Search contact requests" placeholder="Name, email, phone or order number" :maxlength="100" />
        </div>
        <div class="admin-filter-bar__field">
            <x-admin.searchable-select id="contact-requests-filter-status" name="status" label="Contact request status">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </x-admin.searchable-select>
        </div>
        <div class="admin-filter-bar__field">
            <x-admin.searchable-select id="contact-requests-filter-topic" name="topic" label="Contact request topic">
                <option value="">All topics</option>
                @foreach($topics as $value => $label)
                    <option value="{{ $value }}" @selected($filters['topic'] === $value)>{{ $label }}</option>
                @endforeach
            </x-admin.searchable-select>
        </div>
        <div class="admin-filter-bar__actions">
            <button class="btn btn-red" type="submit">Filter</button>
            @if($hasFilters)
                <a class="btn btn-white" href="{{ route('admin.contact-requests.index') }}">Clear</a>
            @endif
        </div>
    </x-admin.filter-bar>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-black text-brand-ink">Contact Us Inbox</h2>
                <p class="mt-1 text-sm text-slate-500">Newest requests first. Open a request to read the full message and download submitted files.</p>
            </div>
            <span class="text-xs font-bold text-slate-500">Private admin records</span>
        </div>
        <div class="admin-table-scroll" tabindex="0" aria-label="Contact Us requests table">
            <table class="admin-table min-w-[1000px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Reference / received</th>
                        <th class="px-5 py-4">Customer</th>
                        <th class="px-5 py-4">Topic</th>
                        <th class="px-5 py-4">Order reference</th>
                        <th class="px-5 py-4">Attachment</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $contact)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 align-top">
                                <a class="font-black text-brand-blue hover:underline" href="{{ route('admin.contact-requests.show', $contact) }}">#{{ $contact->id }}</a>
                                <p class="mt-1 text-xs text-slate-500">{{ \App\Support\ViewerTime::render($contact->created_at, 'd M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="max-w-[240px] break-words font-bold text-brand-ink">{{ $contact->name }}</p>
                                <p class="mt-1 max-w-[250px] break-all text-xs text-slate-500">{{ $contact->email }}</p>
                                @if($contact->phone)<p class="mt-1 text-xs text-slate-500">{{ $contact->phone }}</p>@endif
                            </td>
                            <td class="px-5 py-4 align-top font-semibold text-slate-700">{{ $topics[$contact->topic] ?? str($contact->topic)->headline() }}</td>
                            <td class="px-5 py-4 align-top text-slate-600">{{ $contact->order_number ?: '—' }}</td>
                            <td class="px-5 py-4 align-top text-slate-600">
                                @if($contact->attachment_name)<span class="inline-flex items-center gap-1 text-xs font-semibold text-brand-blue">▧ Attached</span>
                                @else<span class="text-slate-400">None</span>@endif
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span @class([
                                    'admin-status-pill inline-flex rounded-full px-3 py-1 text-xs font-bold',
                                    'bg-blue-50 text-blue-800' => $contact->status === \App\Models\ContactMessage::STATUS_NEW,
                                    'bg-amber-50 text-amber-800' => $contact->status === \App\Models\ContactMessage::STATUS_IN_REVIEW,
                                    'bg-emerald-50 text-emerald-800' => $contact->status === \App\Models\ContactMessage::STATUS_RESOLVED,
                                    'bg-slate-100 text-slate-700' => ! in_array($contact->status, [\App\Models\ContactMessage::STATUS_NEW, \App\Models\ContactMessage::STATUS_IN_REVIEW, \App\Models\ContactMessage::STATUS_RESOLVED], true),
                                ])>{{ $statuses[$contact->status] ?? str($contact->status)->headline() }}</span>
                            </td>
                            <td class="px-5 py-4 align-top"><a class="btn btn-white whitespace-nowrap" href="{{ route('admin.contact-requests.show', $contact) }}">View details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No Contact Us requests match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-5">{{ $requests->links('pagination.nextplay', ['itemName' => 'request']) }}</div>
</x-layouts.admin>
