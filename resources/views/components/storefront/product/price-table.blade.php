@props(['table', 'fabricTables' => [], 'embedded' => false])

@php
    $defaultTable = is_array($table ?? null) ? $table : [];
    $fabricTabs = collect($fabricTables ?? [])
        ->filter(fn ($fabricTable) => is_array($fabricTable) && !empty($fabricTable['headers']) && !empty($fabricTable['rows']))
        ->unique(fn ($fabricTable) => (string) ($fabricTable['key'] ?? $fabricTable['fabric_code'] ?? $fabricTable['label'] ?? spl_object_id((object) $fabricTable)))
        ->values()
        ->map(function ($fabricTable, $index) {
            $label = trim((string) ($fabricTable['label'] ?? ''));
            $code = trim((string) ($fabricTable['fabric_code'] ?? ''));
            $key = trim((string) ($fabricTable['key'] ?? ''));

            if ($label === '') {
                $label = $code !== '' ? $code : 'Fabric '.($index + 1);
            }

            return [
                'id' => $key !== '' ? $key : 'fabric-table-'.$index,
                'label' => $label,
                'code' => $code,
                'table' => $fabricTable,
            ];
        })
        ->values()
        ->all();

    $defaultPriceTab = (!empty($defaultTable['headers']) && !empty($defaultTable['rows']))
        ? [[
            'id' => 'default-price-table',
            'label' => 'Default Price Table',
            'code' => 'Standard',
            'table' => $defaultTable,
            'is_default' => true,
        ]]
        : [];

    $priceTableTabs = collect($defaultPriceTab)
        ->merge($fabricTabs)
        ->values()
        ->all();

    $hasVisiblePriceTable = count($priceTableTabs) > 0;
@endphp

@if($hasVisiblePriceTable)
    @unless($embedded)
        <section class="section-padding bg-white" aria-labelledby="product-price-table-heading">
            <div class="site-container">
    @endunless

    <div
        x-data="{
            defaultTable: @js($defaultTable),
            tabs: @js($priceTableTabs),
            activeTab: @js($priceTableTabs[0]['id'] ?? null),
            runtimeTable: null,
            sourceLabel: '',
            init() {
                window.addEventListener('product-price-table-updated', (event) => {
                    const nextTable = event.detail?.table || this.defaultTable;
                    const incomingKey = String(event.detail?.key || nextTable?.key || '').trim();
                    const incomingLabel = String(event.detail?.label || nextTable?.label || '').trim();

                    if (this.tabs.length) {
                        const defaultTab = this.tabs.find((tab) => tab.is_default) || this.tabs[0];
                        const matchedTab = this.tabs.find((tab) => {
                            const tabKey = String(tab.id || tab.table?.key || '').trim();
                            const tableKey = String(tab.table?.key || '').trim();
                            const tabLabel = String(tab.label || tab.table?.label || '').trim();
                            const tableLabel = String(tab.table?.label || '').trim();

                            return ! tab.is_default && (
                                (incomingKey && (incomingKey === tabKey || incomingKey === tableKey))
                                || (incomingLabel && (incomingLabel === tabLabel || incomingLabel === tableLabel || incomingLabel.includes(tabLabel)))
                            );
                        });

                        this.activeTab = matchedTab?.id || defaultTab?.id || this.activeTab;
                        return;
                    }

                    this.runtimeTable = (Array.isArray(nextTable.rows) && nextTable.rows.length) ? nextTable : this.defaultTable;
                    this.sourceLabel = event.detail?.label || '';
                });
            },
            get table() {
                if (this.tabs.length) {
                    const selectedTab = this.tabs.find((tab) => tab.id === this.activeTab) || this.tabs[0];
                    return selectedTab?.table || this.defaultTable;
                }

                return this.runtimeTable || this.defaultTable;
            },
            activeTabLabel() {
                const selectedTab = this.tabs.find((tab) => tab.id === this.activeTab) || this.tabs[0];
                return selectedTab?.label || '';
            },
            selectTab(id) {
                this.activeTab = id;
                this.sourceLabel = '';
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
                <p x-show="! tabs.length && sourceLabel" x-cloak class="mt-2 inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-black text-brand-red" x-text="`Showing ${sourceLabel}`"></p>
            </div>

            @unless($embedded)
                <a href="#configure-product" class="btn btn-light mobile-full">Configure This Product</a>
            @endunless
        </div>

        <div x-show="tabs.length > 1" x-cloak class="border-b border-slate-200 bg-white">
            <div class="touch-scroll-x flex overflow-x-auto" role="tablist" aria-label="Product price tables">
                <template x-for="tab in tabs" :key="tab.id">
                    <button
                        type="button"
                        role="tab"
                        class="min-w-[150px] flex-1 border-r border-slate-200 px-3 py-2.5 text-center text-xs font-black transition last:border-r-0 sm:min-w-[180px] sm:px-5 sm:py-3 sm:text-sm"
                        :class="activeTab === tab.id ? 'bg-white text-brand-red shadow-[inset_0_-3px_0_0_#ef1737]' : 'bg-slate-50 text-slate-600 hover:bg-white hover:text-brand-ink'"
                        :aria-selected="activeTab === tab.id ? 'true' : 'false'"
                        @click="selectTab(tab.id)"
                    >
                        <span class="block truncate leading-tight" x-text="tab.label"></span>
                        <small x-show="tab.code" class="mt-0.5 block truncate text-[9px] font-bold uppercase tracking-[.1em] text-slate-400 sm:text-[10px]" x-text="tab.code"></small>
                    </button>
                </template>
            </div>
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
