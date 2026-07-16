@props(['table', 'embedded' => false])

@if(!empty($table['headers']) && !empty($table['rows']))
    @unless($embedded)
        <section class="section-padding bg-white" aria-labelledby="product-price-table-heading">
            <div class="site-container">
    @endunless

    <div
        x-data="{
            defaultTable: @js($table),
            table: @js($table),
            sourceLabel: '',
            init() {
                window.addEventListener('product-price-table-updated', (event) => {
                    const nextTable = event.detail?.table || this.defaultTable;
                    this.table = (Array.isArray(nextTable.rows) && nextTable.rows.length) ? nextTable : this.defaultTable;
                    this.sourceLabel = event.detail?.label || '';
                });
            },
            cell(row, index) {
                return Array.isArray(row) ? (row[index] || '—') : '—';
            },
            highlight(index) {
                return Number(this.table.highlight_column || -1) === Number(index);
            }
        }"
        @class([
            'overflow-hidden border border-slate-200 bg-white shadow-card',
            'rounded-[24px] sm:rounded-[28px]' => ! $embedded,
            'rounded-2xl' => $embedded,
        ])
    >
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
                <p x-show="sourceLabel" x-cloak class="mt-2 inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-black text-brand-red" x-text="`Showing ${sourceLabel}`"></p>
            </div>

            @unless($embedded)
                <a href="#configure-product" class="btn btn-light mobile-full">Configure This Product</a>
            @endunless
        </div>

        <div class="grid gap-3 p-4 sm:hidden">
            <template x-for="(row, rowIndex) in table.rows" :key="`mobile-price-row-${rowIndex}`">
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <dl class="grid gap-3">
                        <template x-for="(header, columnIndex) in table.headers" :key="`mobile-price-cell-${rowIndex}-${columnIndex}`">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-2 last:border-0 last:pb-0">
                                <dt class="text-xs font-black uppercase tracking-wide text-slate-500" x-text="header"></dt>
                                <dd
                                    class="text-right text-sm"
                                    :class="{
                                        'font-black text-brand-red': highlight(columnIndex),
                                        'font-bold text-brand-ink': Number(columnIndex) === 0
                                    }"
                                    x-text="cell(row, columnIndex)"
                                ></dd>
                            </div>
                        </template>
                    </dl>
                </article>
            </template>
        </div>

        <div class="touch-scroll-x hidden sm:block" tabindex="0" aria-label="Product quantity price table">
            <table class="w-full min-w-[600px] border-collapse text-sm">
                <thead>
                    <tr>
                        <template x-for="(header, headerIndex) in table.headers" :key="`price-header-${headerIndex}`">
                            <th class="bg-brand-dark px-4 py-3 text-left text-[10px] font-black uppercase tracking-[.12em] text-white" x-text="header"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, rowIndex) in table.rows" :key="`price-row-${rowIndex}`">
                        <tr class="hover:bg-slate-50">
                            <template x-for="(header, columnIndex) in table.headers" :key="`price-cell-${rowIndex}-${columnIndex}`">
                                <td
                                    class="px-4 py-3"
                                    :class="{
                                        'font-black text-brand-red': highlight(columnIndex),
                                        'font-bold': Number(columnIndex) === 0
                                    }"
                                    x-text="cell(row, columnIndex)"
                                ></td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div x-show="table.note" x-cloak class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500 sm:px-5" x-text="table.note"></div>

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
