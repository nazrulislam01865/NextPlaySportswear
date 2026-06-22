@props(['table'])
@if(!empty($table['headers']) && !empty($table['rows']))
<section class="section-padding bg-white" aria-labelledby="product-price-table-heading">
    <div class="site-container">
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-slate-50 p-6 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Quantity pricing</p><h2 id="product-price-table-heading" class="mt-1 font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">Price Table</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Pricing columns and rows are controlled separately for this product from the admin panel.</p></div>
                <a href="#configure-product" class="btn btn-light">Configure This Product</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full border-collapse text-sm">
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
