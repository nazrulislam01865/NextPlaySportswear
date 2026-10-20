<x-storefront.account.page :seo="$seo" title="Bulk Quotes" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-rq np-rq--list">
        <header class="np-rq-heading np-rq-heading--list">
            <div><h1>Bulk Quotes</h1><p>View your bulk quote requests, check quotation files, respond to our team, and convert to order.</p></div>
            <a href="{{ route('quote.request') }}" class="btn btn-red">Request New Quote</a>
        </header>
        <div class="np-rq-stats">
            @foreach([
                ['key' => 'all', 'name' => 'Total Quotes', 'caption' => 'All quote requests', 'icon' => 'quote'],
                ['key' => 'pending', 'name' => 'Pending Review', 'caption' => 'Awaiting our response', 'icon' => 'clock'],
                ['key' => 'discussion', 'name' => 'In Discussion', 'caption' => 'You’re chatting with us', 'icon' => 'message'],
                ['key' => 'confirmed', 'name' => 'Confirmed', 'caption' => 'Ready to convert', 'icon' => 'check-circle'],
                ['key' => 'closed', 'name' => 'Closed', 'caption' => 'Completed or expired', 'icon' => 'order-center'],
            ] as $stat)
                <a href="{{ route('account.bulk-quotes.index', ['status' => $stat['key']]) }}" class="np-rq-stat np-rq-stat--{{ $stat['key'] }}" aria-label="{{ $stat['name'] }}: {{ $counts[$stat['key']] }}">
                    <span class="np-rq-stat-icon"><x-storefront.account.icon :name="$stat['icon']" :size="24" /></span>
                    <span><strong>{{ $counts[$stat['key']] }}</strong><b>{{ $stat['name'] }}</b><small>{{ $stat['caption'] }}</small></span>
                </a>
            @endforeach
        </div>
        <nav class="np-rq-tabs" aria-label="Filter bulk quotations">
            @foreach(['all' => 'All', 'pending' => 'Pending', 'sent' => 'Quotation Sent', 'discussion' => 'In Discussion', 'confirmed' => 'Confirmed', 'closed' => 'Closed'] as $key => $label)
                <a class="{{ $filter === $key ? 'is-current' : '' }}" @if($filter === $key) aria-current="page" @endif href="{{ route('account.bulk-quotes.index', $key === 'all' ? [] : ['status' => $key]) }}">{{ $label }} ({{ $counts[$key] }})</a>
            @endforeach
        </nav>
        <div class="np-rq-table-scroll"><table class="np-rq-table">
            <thead><tr><th>Quote #</th><th>Submitted Date</th><th>Team Name</th><th>Requested Delivery</th><th>Files</th><th>Version</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($quotes as $quote)
                    <tr>
                        <td><a class="np-rq-reference" href="{{ route('account.bulk-quotes.show', $quote->reference) }}">{{ $quote->reference }}</a></td>
                        <td>{{ \App\Support\ViewerTime::render($quote->created_at, 'M j, Y') }}</td>
                        <td>{{ $quote->organization ?: '—' }}</td>
                        <td>{{ $quote->needed_by?->format('M j, Y') ?: 'To confirm' }}</td>
                        <td><span class="np-rq-file-counter">{{ $quote->current_files_count }} {{ \Illuminate\Support\Str::plural('file', $quote->current_files_count) }}</span></td>
                        <td>{{ $quote->quotation_version ? 'v'.$quote->quotation_version : '—' }}</td>
                        <td><x-storefront.account.bulk-quote.status :quote="$quote" /></td>
                        <td><div class="np-rq-table-actions">
                            <a class="btn btn-red" href="{{ route('account.bulk-quotes.show', $quote->reference) }}">{{ $quote->quotation_version ? 'Review Quotation' : 'View Request' }}</a>
                            @if($quote->quotation_version && $quote->quotation_sent_at)
                                <a class="btn btn-white" href="{{ route('account.bulk-quotes.show', $quote->reference) }}#np-rq-files">Download Files <x-storefront.account.icon name="chevron-right" :size="14" /></a>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="np-rq-empty">No bulk quotations in this category. <a href="{{ route('quote.request') }}">Request a new quote</a> to get started.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($quotes->hasPages())<div class="np-rq-pagination">{{ $quotes->links('pagination.nextplay') }}</div>@endif
    </div>
</x-storefront.account.page>
