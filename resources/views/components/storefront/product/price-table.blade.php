@props(['table'])
@if(!empty($table['headers']) && !empty($table['rows']))
<section class="section-padding bg-white" aria-labelledby="product-price-table-heading">
    <div class="site-container">
        <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-card sm:rounded-[28px]">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-slate-50 p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Quantity pricing</p>
                    <h2 id="product-price-table-heading" class="mt-1 font-display text-3xl font-bold uppercase leading-tight tracking-tight text-brand-ink sm:text-4xl">Price Table</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pricing columns and rows are controlled separately for this product from the admin panel.</p>
                </div>
                <a href="#configure-product" class="btn btn-light mobile-full">Configure This Product</a>
            </div>

            <div class="grid gap-3 p-4 sm:hidden">
                @foreach($table['rows'] as $row)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <dl class="grid gap-3">
                            @foreach($table['headers'] as $columnIndex => $header)
                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-2 last:border-0 last:pb-0">
                                    <dt class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $header }}</dt>
                                    <dd @class(['text-right text-sm', 'font-black text-brand-red' => (int)($table['highlight_column'] ?? -1) === $columnIndex, 'font-bold text-brand-ink' => $columnIndex === 0])>{{ $row[$columnIndex] ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @endforeach
            </div>

            <div class="touch-scroll-x hidden sm:block" tabindex="0" aria-label="Product quantity price table">
                <table class="w-full min-w-[680px] border-collapse text-sm">
                    <thead><tr>@foreach($table['headers'] as $header)<th class="bg-brand-dark px-5 py-4 text-left text-[10px] font-black uppercase tracking-[.12em] text-white">{{ $header }}</th>@endforeach</tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($table['rows'] as $row)
                            <tr class="hover:bg-slate-50">@foreach($row as $columnIndex => $cell)<td @class(['px-5 py-4', 'font-black text-brand-red' => (int)($table['highlight_column'] ?? -1) === $columnIndex, 'font-bold' => $columnIndex === 0])>{{ $cell }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(!empty($table['note']))<div class="border-t border-slate-200 bg-slate-50 px-5 py-4 text-xs leading-5 text-slate-500">{{ $table['note'] }}</div>@endif
        </div>
    </div>
</section>
@endif
