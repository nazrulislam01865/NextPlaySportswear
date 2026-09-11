<x-layouts.admin
    title="Bulk Quote Requests"
    eyebrow="Commerce"
    subtitle="Review, assign, qualify, quote, and monitor customer bulk requests from one workflow."
    :compact-header="true"
>
    <x-admin.filter-bar label="Bulk quote filters">
        <div class="admin-filter-bar__search">
            <input
                class="admin-input"
                name="q"
                value="{{ request('q') }}"
                placeholder="Reference, customer, email, product..."
                aria-label="Search bulk quote requests"
            >
        </div>

        <div class="admin-filter-bar__field">
            <select class="admin-input" name="status" aria-label="Request status">
                <option value="">All statuses</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__field admin-filter-bar__field--sm">
            <select class="admin-input" name="priority" aria-label="Priority">
                <option value="">All priorities</option>
                @foreach($priorityOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__field admin-filter-bar__field--md">
            <select class="admin-input" name="assigned_to" aria-label="Assigned admin">
                <option value="">All assignees</option>
                @foreach($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((string) request('assigned_to') === (string) $assignee->id)>{{ $assignee->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__field">
            <select class="admin-input" name="sync_status" aria-label="FlowTrack sync status">
                <option value="">All FlowTrack</option>
                @foreach($syncStatusOptions as $syncStatus)
                    <option value="{{ $syncStatus }}" @selected(request('sync_status') === $syncStatus)>{{ str($syncStatus)->headline() }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__field">
            <select class="admin-input" name="country" aria-label="Country">
                <option value="">All countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-filter-bar__actions">
            <button class="btn btn-red" type="submit">Filter</button>
            @if(request()->hasAny(['q', 'status', 'priority', 'assigned_to', 'sync_status', 'country']))
                <a class="btn btn-white" href="{{ route('admin.bulk-quotes.index') }}">Clear</a>
            @endif
        </div>
    </x-admin.filter-bar>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-black">Bulk Quote Pipeline</h2>
                <p class="text-sm text-slate-500">Workflow status, ownership, customer deadline, and FlowTrack Inquiry state.</p>
            </div>
            <a class="btn btn-white" href="{{ route('admin.orders.index') }}">Orders</a>
        </div>

        <div class="admin-table-scroll" tabindex="0" aria-label="Bulk quote requests table">
            <table class="admin-table min-w-[1180px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Request</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Requirement</th>
                        <th class="px-5 py-3">Workflow</th>
                        <th class="px-5 py-3">Owner</th>
                        <th class="px-5 py-3">FlowTrack</th>
                        <th class="px-5 py-3">Needed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($quotes as $quote)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 align-top">
                                <a class="font-black text-brand-blue hover:underline" href="{{ route('admin.bulk-quotes.show', $quote) }}">{{ $quote->reference }}</a>
                                <p class="mt-1 text-xs text-slate-400">{{ $quote->created_at?->format('M d, Y · g:i A') }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="font-black text-brand-dark">{{ $quote->full_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $quote->organization }}</p>
                                <p class="text-xs text-slate-400">{{ $quote->email }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="max-w-[240px] font-semibold text-slate-800">{{ $quote->product_type }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $quote->estimatedQuantityLabel() }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    <x-admin.status-pill :status="$quote->status" />
                                    <x-admin.status-pill :status="$quote->priority" />
                                </div>
                                @if($quote->quoted_amount !== null)
                                    <p class="mt-2 text-xs font-bold text-slate-600">{{ $quote->quote_currency }} {{ number_format((float) $quote->quoted_amount, 2) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="font-semibold text-slate-700">{{ $quote->assignedAdmin?->name ?: 'Unassigned' }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <x-admin.status-pill :status="$quote->flowtrack_sync_status" />
                                @if($quote->flowtrack_inquiry_number)
                                    <p class="mt-2 text-xs font-semibold text-slate-500">{{ $quote->flowtrack_inquiry_number }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="font-semibold text-slate-700">{{ $quote->needed_by?->format('M d, Y') ?: '—' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ collect([$quote->state_province, $quote->country])->filter()->join(', ') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">No bulk quote requests match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $quotes->links('pagination.nextplay') }}</div>
</x-layouts.admin>
