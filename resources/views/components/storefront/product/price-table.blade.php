@props(['table', 'embedded' => false])

@if(!empty($table['headers']) && !empty($table['rows']))
    @unless($embedded)
        <section class="section-padding bg-white" aria-labelledby="product-price-table-heading">
            <div class="site-container">
    @endunless

    <div @class([
        'overflow-hidden border border-slate-200 bg-white shadow-card',
        'rounded-[24px] sm:rounded-[28px]' => ! $embedded,
        'rounded-2xl' => $embedded,
    ])>
        <div @class([
            'flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-slate-50',
            'p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6' => ! $embedded,
            'p-4 sm:p-5' => $embedded,
        ])>
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Quantity pricing</p>
                <h2 id="product-price-table-heading" @class([
                    'mt-1 font-display font-bold uppercase leading-tight tracking-tight text-brand-ink',
                    'text-3xl sm:text-4xl' => ! $embedded,
                    'text-2xl sm:text-3xl' => $embedded,
                ])>Price Table</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Your unit price is selected from the total quantity across every chosen size.
                </p>
            </div>

            @unless($embedded)
                <a href="#configure-product" class="btn btn-light mobile-full">Configure This Product</a>
            @endunless
        </div>

        <div class="grid gap-3 p-4 sm:hidden">
            @foreach($table['rows'] as $row)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dl class="grid gap-3">
                        @foreach($table['headers'] as $columnIndex => $header)
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-2 last:border-0 last:pb-0">
                                <dt class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $header }}</dt>
                                <dd @class([
                                    'text-right text-sm',
                                    'font-black text-brand-red' => (int)($table['highlight_column'] ?? -1) === $columnIndex,
                                    'font-bold text-brand-ink' => $columnIndex === 0,
                                ])>{{ $row[$columnIndex] ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endforeach
        </div>

        <div class="touch-scroll-x hidden sm:block" tabindex="0" aria-label="Product quantity price table">
            <table class="w-full min-w-[600px] border-collapse text-sm">
                <thead>
                    <tr>
                        @foreach($table['headers'] as $header)
                            <th class="bg-brand-dark px-4 py-3 text-left text-[10px] font-black uppercase tracking-[.12em] text-white">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($table['rows'] as $row)
                        <tr class="hover:bg-slate-50">
                            @foreach($row as $columnIndex => $cell)
                                <td @class([
                                    'px-4 py-3',
                                    'font-black text-brand-red' => (int)($table['highlight_column'] ?? -1) === $columnIndex,
                                    'font-bold' => $columnIndex === 0,
                                ])>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(!empty($table['note']))
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 sm:px-5">{{ $table['note'] }}</div>
        @endif

        @if($embedded)
            <div class="grid gap-3 border-t border-slate-200 p-4 sm:grid-cols-2 sm:p-5">
                <a href="#configure-product" class="btn btn-red py-4">Start Customizing ↓</a>
                <a href="{{ route('quote.request') }}" class="btn btn-white py-4">Request Bulk Quote</a>
            </div>
        @endif
    </div>

    @unless($embedded)
            </div>
        </section>
    @endunless
@endif
