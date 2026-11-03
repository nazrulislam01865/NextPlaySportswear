/* Payment Information admin table editor.
   Frontend-only editing: no requests until the existing Save form is submitted.
   All text is assigned as textContent/value, never injected as HTML. */
(() => {
    'use strict';

    const init = () => {
        const editor = document.getElementById('payment-status-table-editor');
        if (!editor || editor.dataset.editable !== '1') return;

        const grid = document.getElementById('payment-status-grid');
        const headings = document.getElementById('payment-status-headings');
        const tbody = document.getElementById('payment-status-rows');
        const addRowButton = document.getElementById('payment-add-row');
        const addColumnButton = document.getElementById('payment-add-column');
        if (!grid || !headings || !tbody || !addRowButton || !addColumnButton) return;

        const maxRows = Number(editor.dataset.maxRows) || 100;
        const maxColumns = Number(editor.dataset.maxColumns) || 10;
        const columns = Array.from(headings.querySelectorAll('[data-payment-column]'), input => input.value);
        const rows = Array.from(tbody.querySelectorAll('tr'), tr => ({
            cells: Array.from(tr.querySelectorAll('[data-payment-cell]'), input => input.value),
        }));

        const element = (tag, className = '', text = '') => {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text) node.textContent = text;
            return node;
        };

        const removeButton = (label, attribute, index, disabled) => {
            const button = element('button', 'np-payment-admin-remove', 'Remove');
            button.type = 'button';
            button.setAttribute(attribute, String(index));
            button.setAttribute('aria-label', `${label} ${index + 1}`);
            button.disabled = disabled;
            return button;
        };

        // Capture browser-autofilled values as well as typed input before a structural change.
        const syncFromInputs = () => {
            headings.querySelectorAll('[data-payment-column]').forEach(input => {
                columns[Number(input.dataset.paymentColumn)] = input.value;
            });
            tbody.querySelectorAll('[data-payment-cell]').forEach(input => {
                rows[Number(input.dataset.paymentRow)].cells[Number(input.dataset.paymentCell)] = input.value;
            });
        };

        const refreshButtons = () => {
            addRowButton.disabled = rows.length >= maxRows;
            addColumnButton.disabled = columns.length >= maxColumns;
        };

        // Only called for structural changes, so editing values does not lose focus.
        const render = () => {
            const headerRow = document.createDocumentFragment();
            columns.forEach((heading, columnIndex) => {
                const th = element('th');
                th.scope = 'col';
                const headingWrap = element('div', 'np-payment-admin-column-title');
                const headingLabel = element('label', '', `Column ${columnIndex + 1}`);
                headingLabel.htmlFor = `payment-column-${columnIndex}`;
                headingWrap.append(headingLabel, removeButton('Remove column', 'data-remove-column', columnIndex, columns.length === 1));
                const input = element('input', 'admin-input');
                input.id = `payment-column-${columnIndex}`;
                input.name = `status_table[columns][${columnIndex}]`;
                input.dataset.paymentColumn = String(columnIndex);
                input.value = heading;
                input.required = true;
                input.maxLength = 100;
                th.append(headingWrap, input);
                headerRow.append(th);
            });
            const actionsTh = element('th', 'np-payment-admin-actions-heading', 'Actions');
            actionsTh.scope = 'col';
            headerRow.append(actionsTh);
            headings.replaceChildren(headerRow);

            const fragment = document.createDocumentFragment();
            rows.forEach((row, rowIndex) => {
                const tr = element('tr');
                columns.forEach((heading, columnIndex) => {
                    const td = element('td');
                    const label = element('label', 'sr-only', `Row ${rowIndex + 1} · ${heading || `Column ${columnIndex + 1}`}`);
                    label.htmlFor = `payment-cell-${rowIndex}-${columnIndex}`;
                    const input = element('input', 'admin-input');
                    input.id = `payment-cell-${rowIndex}-${columnIndex}`;
                    input.name = `status_table[rows][${rowIndex}][cells][${columnIndex}]`;
                    input.dataset.paymentRow = String(rowIndex);
                    input.dataset.paymentCell = String(columnIndex);
                    input.value = row.cells[columnIndex] ?? '';
                    input.maxLength = 500;
                    input.required = columnIndex === 0;
                    td.append(label, input);
                    tr.append(td);
                });
                const actionsTd = element('td', 'np-payment-admin-row-action');
                actionsTd.append(removeButton('Remove row', 'data-remove-row', rowIndex, rows.length === 1));
                tr.append(actionsTd);
                fragment.append(tr);
            });
            tbody.replaceChildren(fragment);
            refreshButtons();
        };

        editor.addEventListener('input', event => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement)) return;
            if (input.hasAttribute('data-payment-column')) {
                columns[Number(input.dataset.paymentColumn)] = input.value;
            } else if (input.hasAttribute('data-payment-cell')) {
                rows[Number(input.dataset.paymentRow)].cells[Number(input.dataset.paymentCell)] = input.value;
            }
        });

        editor.addEventListener('click', event => {
            const button = event.target.closest('button');
            if (!button || button.disabled) return;
            syncFromInputs();
            if (button === addRowButton && rows.length < maxRows) {
                rows.push({cells: columns.map(() => '')});
                render();
                tbody.querySelector(`input[data-payment-row="${rows.length - 1}"]`)?.focus();
            } else if (button === addColumnButton && columns.length < maxColumns) {
                columns.push(`Column ${columns.length + 1}`);
                rows.forEach(row => row.cells.push(''));
                render();
                headings.querySelector(`input[data-payment-column="${columns.length - 1}"]`)?.focus();
            } else if (button.hasAttribute('data-remove-row') && rows.length > 1) {
                rows.splice(Number(button.dataset.removeRow), 1);
                render();
            } else if (button.hasAttribute('data-remove-column') && columns.length > 1) {
                const index = Number(button.dataset.removeColumn);
                columns.splice(index, 1);
                rows.forEach(row => row.cells.splice(index, 1));
                render();
            }
        });

        refreshButtons();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once: true});
    } else {
        init();
    }
})();
