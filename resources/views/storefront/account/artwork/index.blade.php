<x-storefront.account.page :seo="$seo" title="Artwork Approvals" subtitle="" :account="$account" :navigation="$navigation" content-only>
    <div class="np-artwork">
        <x-storefront.account.artwork.breadcrumb />
        <header class="np-artwork-heading">
            <h1>Artwork Approvals</h1>
            <p>Review and approve your design proofs before production begins.</p>
            <p class="np-artwork-muted">Our design team will create your artwork based on your requirements. Once you're satisfied, approve the design to move forward.</p>
        </header>
        <div class="np-artwork-stats">
            <a href="{{ route('account.artwork.index', ['status' => 'pending_review']) }}" class="np-artwork-stat">
                <span class="np-artwork-stat__icon np-artwork-stat__icon--pending"><x-storefront.account.icon name="clock" :size="31" /></span>
                <span><strong>{{ $counts['pending_review'] ?? 0 }}</strong><b>Pending Review</b><small>Needs your approval</small></span>
            </a>
            <a href="{{ route('account.artwork.index', ['status' => 'approved']) }}" class="np-artwork-stat">
                <span class="np-artwork-stat__icon np-artwork-stat__icon--approved"><x-storefront.account.icon name="check-circle" :size="31" /></span>
                <span><strong>{{ $counts['approved'] ?? 0 }}</strong><b>Approved</b><small>Ready for production</small></span>
            </a>
            <a href="{{ route('account.artwork.index', ['status' => 'needs_revision']) }}" class="np-artwork-stat">
                <span class="np-artwork-stat__icon np-artwork-stat__icon--revision"><x-storefront.account.icon name="refresh" :size="31" /></span>
                <span><strong>{{ $counts['needs_revision'] ?? 0 }}</strong><b>Needs Revision</b><small>Action required</small></span>
            </a>
        </div>
        <div class="np-artwork-section-heading">
            <h2>Artwork Proofs <span>({{ $proofs->total() }})</span></h2>
            <form action="{{ route('account.artwork.index') }}" method="GET"><label class="sr-only" for="artwork-status">Filter artwork proofs</label>
                <select id="artwork-status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending_review" @selected(request('status') === 'pending_review')>Pending Review</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="needs_revision" @selected(request('status') === 'needs_revision')>Needs Revision</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </form>
        </div>
        <div class="np-artwork-list">
            @forelse($proofs as $proof)
                <x-storefront.account.artwork.list-card :proof="$proof" />
            @empty
                <div class="np-artwork-empty"><x-storefront.account.icon name="artwork" :size="38" />
                    <h3>No artwork proofs yet</h3><p>When our design team uploads a proof for your order, it will appear here for you to review.</p>
                    @if(request()->filled('status'))<a class="np-artwork-button" href="{{ route('account.artwork.index') }}">Clear Filter</a>@endif
                </div>
            @endforelse
        </div>
        @if($proofs->hasPages())<div class="np-artwork-pagination">{{ $proofs->links('pagination.nextplay') }}</div>@endif
    </div>
</x-storefront.account.page>
