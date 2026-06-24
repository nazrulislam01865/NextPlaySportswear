<x-storefront.account.orders.page
    :seo="$seo"
    :account="$account"
    :navigation="$navigation"
    title="Order Downloads"
    subtitle="Securely access digital products, approved artwork packages, templates, and private order documents with expiry and download-limit controls."
    eyebrow="Digital fulfillment"
>
    <x-slot:actions>
        <a class="btn btn-white" href="{{ route('account.orders.index') }}">Order History</a>
    </x-slot:actions>

    <div class="rounded-3xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-800">
        <b>Private access:</b> every file request validates account ownership, active entitlement, expiry, usage limit, and a short-lived signed link. Storage paths are never exposed.
    </div>

    <section class="mt-6 rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
        <h3 class="text-xl font-black">Your Order Downloads</h3>
        <div class="mt-5 grid gap-4">
            @forelse($downloads as $download)
                <article class="grid gap-4 rounded-2xl border border-slate-200 p-4 md:grid-cols-[64px_1fr_auto] md:items-center">
                    <div class="grid h-14 w-14 place-items-center rounded-xl bg-brand-navy text-xs font-black text-white">
                        {{ strtoupper(pathinfo($download->original_name, PATHINFO_EXTENSION)) }}
                    </div>
                    <div>
                        <h4 class="font-black">{{ $download->title }}</h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Order {{ $download->order->order_number }} · {{ $download->original_name }}
                            @if($download->expires_at)
                                · Expires {{ $download->expires_at->format('M d, Y') }}
                            @endif
                            @if($download->remainingDownloads() !== null)
                                · {{ $download->remainingDownloads() }} downloads remaining
                            @endif
                        </p>
                        @if($download->license_note)
                            <p class="mt-2 text-xs leading-5 text-slate-500">{{ $download->license_note }}</p>
                        @endif
                    </div>
                    @if($download->isAvailable())
                        <a
                            class="btn btn-red"
                            href="{{ URL::temporarySignedRoute('account.downloads.download', now()->addMinutes(10), ['download' => $download]) }}"
                        >Download</a>
                    @else
                        <x-storefront.account.orders.status-pill status="expired" />
                    @endif
                </article>
            @empty
                <div class="rounded-2xl bg-slate-50 p-8 text-center">
                    <h4 class="font-black">No digital downloads available</h4>
                    <p class="mt-2 text-sm text-slate-500">This page is used only when an order includes downloadable files or customer-owned deliverables.</p>
                </div>
            @endforelse
        </div>
    </section>

    <div class="mt-6">{{ $downloads->links() }}</div>
</x-storefront.account.orders.page>
