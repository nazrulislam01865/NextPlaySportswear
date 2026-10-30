/* Purely presentational summary: reads form fields and selected product already on page.
   Does not fetch, mutate quote data, or interfere with the existing picker/submission. */
(() => {
    'use strict';
    const form = document.getElementById('bulk-quote-request-form');
    if (!form) return;
    const productType = document.getElementById('bulk-product-type');
    const selectedProduct = document.getElementById('bulk-quote-selected-product');
    const selectedName = document.getElementById('bulk-quote-selected-name');
    const quantity = document.getElementById('bulk-quantity');
    const neededBy = document.getElementById('bulk-needed-by');
    const productSummary = document.getElementById('bulk-quote-summary-product');
    const quantitySummary = document.getElementById('bulk-quote-summary-quantity');
    const dateSummary = document.getElementById('bulk-quote-summary-date');
    if (!productSummary || !quantitySummary || !dateSummary) return;

    const update = () => {
        productSummary.textContent = (!selectedProduct?.hidden && selectedName?.textContent?.trim())
            || productType?.value.trim() || '—';
        quantitySummary.textContent = quantity?.selectedOptions?.[0]?.value
            ? quantity.selectedOptions[0].textContent.trim() : '—';
        if (!neededBy?.value) { dateSummary.textContent = '—'; return; }
        const parts = neededBy.value.split('-');
        dateSummary.textContent = parts.length === 3
            ? `${parts[2]}/${parts[1]}/${parts[0]}` : neededBy.value;
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    if (selectedProduct) {
        new MutationObserver(update).observe(selectedProduct, {
            attributes: true, childList: true, characterData: true, subtree: true,
        });
    }
    // The product picker restores optional product details after scripts have loaded.
    update();
})();
