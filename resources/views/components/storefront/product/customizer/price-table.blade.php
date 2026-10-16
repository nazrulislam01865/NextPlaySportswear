<div class="np-proto-price-table" x-data="{ table() { return activePriceTable() || {}; } }">
    <div class="np-proto-data-table-wrap touch-scroll-x" tabindex="0" aria-label="Product quantity price table">
        <table class="np-proto-data-table">
            <thead>
                <tr>
                    <template x-for="(header, headerIndex) in (table().headers || [])" :key="`price-head-${headerIndex}`">
                        <th>
                            <span x-text="header"></span>
                        </th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, rowIndex) in (table().rows || [])" :key="`price-row-${rowIndex}`">
                    <tr>
                        <template x-for="(cell, cellIndex) in row" :key="`price-cell-${rowIndex}-${cellIndex}`">
                            <td :class="cellIndex === Number(table().highlight_column ?? 1) ? 'is-price' : (cellIndex === 0 ? 'is-quantity' : '')"
                                x-data="{ offer() { return priceTableCellOffer(cell, cellIndex, rowIndex); } }">
                                <span x-text="offer().label || '—'"></span>
                                <del x-show="offer().discounted" x-cloak class="ml-2 whitespace-nowrap text-sm font-normal text-slate-500" x-text="offer().originalLabel"></del>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div x-show="table().note" x-cloak class="np-proto-info-note np-proto-price-table-note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p x-text="table().note"></p>
    </div>

    <div class="np-proto-info-note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
        <p>Final pricing follows the selected fabric price table and your total quantity across all selected sizes.</p>
    </div>
</div>
