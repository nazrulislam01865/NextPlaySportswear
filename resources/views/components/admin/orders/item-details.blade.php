@props(['item', 'order'])

@php
    $customization = (array) $item->customization;
    $fulfillment = (array) ($customization['fulfillment'] ?? []);
    $selectedSizes = collect($item->selectedSizes());
    $rosterRows = collect($item->rosterRows());
    $rosterFields = collect($item->rosterFields());
    $artworkFiles = collect($item->artworkFiles());
    $rosterEnabled = (bool) data_get($customization, 'configuration.roster_enabled', false);
@endphp

<article class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
    <div class="grid min-w-0 grid-cols-[72px_minmax(0,1fr)] gap-4 border-b border-slate-200 p-4 sm:grid-cols-[92px_minmax(0,1fr)] sm:p-5">
        <img
            src="{{ \App\Support\PublicMedia::url(null, $item->image_url, '/images/product-placeholder.svg') }}"
            alt="{{ $item->product_name }}"
            class="h-[72px] w-[72px] rounded-2xl bg-white object-contain sm:h-[92px] sm:w-[92px]"
            loading="lazy"
        >
        <div class="min-w-0">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                <div>
                    <h4 class="text-base font-black text-brand-ink sm:text-lg">{{ $item->product_name }}</h4>
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $item->sku ?: 'Custom product' }} · Quantity {{ $item->quantity }}</p>
                </div>
                <strong class="text-sm font-black text-brand-ink">${{ number_format((float) $item->line_total, 2) }}</strong>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                @if(!empty($customization['design_option']))
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-700">{{ $customization['design_option'] }}</span>
                @endif
                @if(is_array($fulfillment['production'] ?? null))
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-blue-700">Production: {{ data_get($fulfillment, 'production.label', 'Standard') }}</span>
                @endif
                @if(is_array($fulfillment['shipping'] ?? null))
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700">Shipping: {{ data_get($fulfillment, 'shipping.label', 'Selected') }}</span>
                @endif
            </div>

            @if(!empty($customization['notes']))
                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600"><b class="text-slate-900">Customer notes:</b> {{ $customization['notes'] }}</p>
            @endif
        </div>
    </div>

    <div class="grid gap-5 p-4 sm:p-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
            <div class="flex items-center justify-between gap-3">
                <h5 class="font-black text-brand-ink">Selected Sizes</h5>
                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500">{{ $selectedSizes->sum('quantity') ?: $item->quantity }} pieces</span>
            </div>

            @if($selectedSizes->isNotEmpty())
                <div class="mt-3 grid gap-2">
                    @foreach($selectedSizes as $size)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-sm">
                            <span class="min-w-0"><b>{{ data_get($size, 'size_label', 'Size') }}</b>@if(data_get($size, 'group_label')) <span class="text-xs text-slate-500">· {{ data_get($size, 'group_label') }}</span>@endif</span>
                            <strong>× {{ (int) data_get($size, 'quantity', 0) }}</strong>
                        </div>
                    @endforeach
                </div>
                @if(!empty($customization['size_summary']) && strcasecmp((string) $customization['size_summary'], 'Sizes selected in configuration') !== 0)
                    <p class="mt-3 text-xs leading-5 text-slate-500"><b class="text-slate-700">Saved summary:</b> {{ $customization['size_summary'] }}</p>
                @endif
            @elseif(!empty($customization['size_summary']))
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $customization['size_summary'] }}</p>
            @else
                <p class="mt-3 text-sm text-slate-500">No size selection was captured for this item.</p>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
            <div class="flex items-center justify-between gap-3">
                <h5 class="font-black text-brand-ink">Uploaded Artwork</h5>
                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500">{{ $artworkFiles->count() }} file(s)</span>
            </div>

            @if($artworkFiles->isNotEmpty())
                <div class="mt-3 grid gap-3">
                    @foreach($artworkFiles as $artwork)
                        @php
                            $mimeType = strtolower((string) data_get($artwork, 'mime_type', ''));
                            $extension = strtolower(pathinfo((string) data_get($artwork, 'original_name', ''), PATHINFO_EXTENSION));
                            $isImage = \Illuminate\Support\Str::startsWith($mimeType, 'image/') || in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'], true);
                            $viewUrl = route('admin.orders.artwork.show', [$order, $item, $loop->index]);
                            $sizeBytes = (int) data_get($artwork, 'size', 0);
                            $sizeLabel = $sizeBytes > 0 ? number_format($sizeBytes / 1024, 1).' KB' : 'Size unavailable';
                        @endphp
                        <div class="flex min-w-0 items-center gap-3 rounded-xl bg-white p-3">
                            @if($isImage)
                                <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="shrink-0">
                                    <img src="{{ $viewUrl }}" alt="{{ data_get($artwork, 'original_name', 'Artwork') }}" class="h-14 w-14 rounded-lg border border-slate-200 bg-white object-contain" loading="lazy">
                                </a>
                            @else
                                <div class="grid h-14 w-14 shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-black uppercase text-slate-500">{{ $extension ?: 'FILE' }}</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-slate-900">{{ data_get($artwork, 'original_name', 'Artwork file') }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $extension ? strtoupper($extension) : ($mimeType ?: 'File') }} · {{ $sizeLabel }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="text-xs font-black text-brand-red hover:underline">View</a>
                                    <a href="{{ $viewUrl }}?download=1" class="text-xs font-black text-slate-700 hover:underline">Download</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">No artwork file was uploaded with this order item.</p>
            @endif
        </section>
    </div>

    @if($rosterEnabled || $rosterRows->isNotEmpty())
        <section class="border-t border-slate-200 p-4 sm:p-5">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h5 class="font-black text-brand-ink">Roster Details</h5>
                    <p class="text-xs text-slate-500">Per-piece size, player name, number, and any additional roster fields captured at checkout.</p>
                </div>
                <span class="w-fit rounded-full bg-brand-dark px-3 py-1.5 text-xs font-black text-white">{{ $rosterRows->count() }} roster row(s)</span>
            </div>

            @if($rosterRows->isNotEmpty())
                <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Size</th>
                                @foreach($rosterFields as $field)
                                    <th class="px-4 py-3">{{ data_get($field, 'label', 'Field') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($rosterRows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-black text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <b>{{ data_get($row, 'size_label', data_get($row, 'size_code', '—')) }}</b>
                                        @if(data_get($row, 'size_group_label'))<span class="block text-xs text-slate-500">{{ data_get($row, 'size_group_label') }}</span>@endif
                                    </td>
                                    @foreach($rosterFields as $field)
                                        @php($value = data_get($row, 'values.'.data_get($field, 'key')))
                                        <td class="px-4 py-3 font-semibold text-slate-700">{{ filled($value) ? $value : '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Roster was enabled, but no roster rows were captured for this item.</p>
            @endif
        </section>
    @endif
</article>
