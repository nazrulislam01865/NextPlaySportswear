/* NextPlay checkout prototype interactions. No payment data is collected here.
   Data is validated again by the Laravel checkout FormRequests. */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.np-checkout--prototype');
    if (!root) return;

    // One shared joined phone control: dial-code dropdown and national number,
    // while the server receives its existing single international phone field.
    const updatePhoneView = (rootPhone, fullPhone) => {
        const dial = rootPhone.querySelector('[data-phone-dial]');
        const local = rootPhone.querySelector('[data-phone-local]');
        const hidden = rootPhone.querySelector('[data-phone-full]');
        if (!dial || !local || !hidden) return;
        const raw = String(fullPhone || '').trim();
        const codes = Array.from(dial.options).map(option => option.value).filter(Boolean).sort((a,b) => b.length - a.length);
        const prefix = codes.find(code => raw.startsWith(code));
        if (prefix) dial.value = prefix;
        local.value = prefix ? raw.slice(prefix.length).trim() : raw;
        hidden.value = raw;
    };
    root.querySelectorAll('[data-phone-control]').forEach((control) => {
        const dial = control.querySelector('[data-phone-dial]');
        const local = control.querySelector('[data-phone-local]');
        const hidden = control.querySelector('[data-phone-full]');
        if (!dial || !local || !hidden) return;
        const sync = () => { hidden.value = `${dial.value} ${local.value.trim()}`.trim(); };
        dial.addEventListener('change', sync);
        local.addEventListener('input', sync);
        hidden.addEventListener('change', () => updatePhoneView(control, hidden.value));
        if (hidden.value.trim()) updatePhoneView(control, hidden.value);
        sync();
    });

    const setClientError = (field, message) => {
        const wrap = field.closest('.nps-field') || field.parentElement;
        if (!wrap) return;
        const existing = wrap.querySelector('.np-checkout__client-error');
        if (!message) {
            existing?.remove();
            if (!wrap.querySelector('.nps-error')) field.removeAttribute('aria-invalid');
            return;
        }
        if (wrap.querySelector('.nps-error:not(.np-checkout__client-error)')) return;
        let error = existing;
        if (!error) {
            error = document.createElement('p');
            error.className = 'nps-error np-checkout__client-error';
            error.setAttribute('role', 'alert');
            error.id = `${field.id || 'checkout-field'}-client-error`;
            wrap.append(error);
        }
        error.textContent = message;
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
    };

    for (const form of root.querySelectorAll('form[data-single-submit]')) {
        // Capture before the existing site-wide data-single-submit handler.
        form.addEventListener('submit', (event) => {
            const invalid = Array.from(form.elements).filter((field) =>
                typeof field.checkValidity === 'function' && !field.disabled && !field.checkValidity());
            if (!invalid.length) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            for (const field of invalid) {
                if (!field.matches('input,select,textarea')) continue;
                const message = field.validity.valueMissing
                    ? 'Complete this required field.'
                    : field.validity.typeMismatch ? 'Enter a valid value.'
                    : field.validity.rangeUnderflow ? 'Choose a date in the future.'
                    : 'Please check this value and try again.';
                setClientError(field, message);
            }
            invalid[0].scrollIntoView({behavior: 'smooth', block: 'center'});
            invalid[0].focus({preventScroll: true});
        }, true);
        form.addEventListener('input', (event) => {
            const field = event.target;
            if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
                if (field.checkValidity()) setClientError(field, '');
            }
        });
    }

    // Clicking anywhere inside a date field opens the native calendar.
    root.querySelectorAll('input[type="date"]').forEach((input) => {
        input.addEventListener('click', () => { try { input.showPicker?.(); } catch (_) { /* native fallback */ } });
    });

    const shipmentForm = root.querySelector('.np-checkout__shipment-form');
    if (shipmentForm) {
        // Saved address selection reuses the existing customer-address service.
        // Editing a selected address switches back to a new address so changes are not discarded.
        const primaryShipment = shipmentForm.querySelector('[data-shipment="0"]');
        const savedSelector = primaryShipment?.querySelector('[data-saved-address-select]');
        const savedDataNode = primaryShipment?.querySelector('[data-saved-address-data]');
        if (savedSelector && savedDataNode) {
            let addresses = [];
            try { addresses = JSON.parse(savedDataNode.textContent || '[]'); } catch (_) { addresses = []; }
            const choice = primaryShipment.querySelector('[data-address-choice]');
            let applying = false;
            const setChoice = () => {
                const selected = addresses.find(address => String(address.id) === savedSelector.value.replace('saved:', ''));
                if (choice) choice.value = selected ? savedSelector.value : 'new';
                if (!selected) return;
                const mainContact = primaryShipment.querySelector('[data-same-contact]');
                if (mainContact) mainContact.checked = false;
                applying = true;
                const name = [selected.first_name, selected.last_name].filter(Boolean).join(' ');
                const values = {
                    recipient_name:name, email:selected.email || '', phone:selected.phone || '',
                    company_name:selected.company_name || '', address_line_1:selected.address_line_1 || '',
                    address_line_2:selected.address_line_2 || '', city:selected.city || '',
                    state:selected.state || '', country:selected.country || '', postal_code:selected.postal_code || ''
                };
                for (const [key,value] of Object.entries(values)) {
                    const input = primaryShipment.querySelector(`[data-address-name="${key}"]`);
                    if (!input) continue;
                    input.value = value;
                    input.dispatchEvent(new Event('np-select-sync'));
                    input.dispatchEvent(new Event('change', {bubbles:true}));
                }
                applying = false;
                const save = primaryShipment.querySelector('[name="save_to_account"]');
                if (save) { save.checked = false; save.disabled = true; }
            };
            savedSelector.addEventListener('change', () => {
                const save = primaryShipment.querySelector('[name="save_to_account"]');
                if (save) save.disabled = savedSelector.value !== 'new';
                setChoice();
            });
            primaryShipment.querySelectorAll('[data-address-name]').forEach(input => {
                input.addEventListener('input', () => {
                    if (applying || savedSelector.value === 'new') return;
                    savedSelector.value = 'new';
                    savedSelector.dispatchEvent(new Event('np-select-sync'));
                    if (choice) choice.value = 'new';
                    const save = primaryShipment.querySelector('[name="save_to_account"]');
                    if (save) save.disabled = false;
                });
            });
            const phoneLocal = primaryShipment.querySelector('[data-phone-local]');
            const phoneDial = primaryShipment.querySelector('[data-phone-dial]');
            const markNew = () => {
                if (applying || savedSelector.value === 'new') return;
                savedSelector.value = 'new';
                if (choice) choice.value = 'new';
                const save = primaryShipment.querySelector('[name="save_to_account"]');
                if (save) save.disabled = false;
            };
            phoneLocal?.addEventListener('input', markNew);
            phoneDial?.addEventListener('change', markNew);
            if (savedSelector.value !== 'new') setChoice();
        }
        const main = shipmentForm.querySelector('[data-shipment="0"]');
        const field = (card, name) => card?.querySelector(`[data-address-name="${name}"]`);
        const addressNames = ['recipient_name', 'email', 'phone', 'company_name', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code'];
        const copyFromFirst = (card) => {
            for (const key of addressNames) {
                const to = field(card, key);
                const from = field(main, key);
                if (to && from) {
                    to.value = from.value;
                    to.dispatchEvent(new Event('np-select-sync'));
                    to.dispatchEvent(new Event('change', {bubbles: true}));
                }
            }
        };
        const setSameAsFirst = (card, same) => {
            const group = card.querySelector('[data-address-fields]');
            if (!group) return;
            if (same) copyFromFirst(card);
            group.hidden = same;
            for (const control of group.querySelectorAll('input,select,textarea')) control.disabled = same;
        };
        const primaryContact = main?.querySelector('[data-same-contact]');
        const infoName = shipmentForm.dataset.contactName || '';
        const infoEmail = shipmentForm.dataset.contactEmail || '';
        const infoPhone = shipmentForm.dataset.contactPhone || '';
        primaryContact?.addEventListener('change', () => {
            if (!primaryContact.checked) return;
            const contact = {recipient_name: infoName, email: infoEmail, phone: infoPhone};
            for (const [key, value] of Object.entries(contact)) {
                const target = field(main, key);
                if (target) { target.value = value; target.dispatchEvent(new Event('change', {bubbles:true})); }
            }
        });
        for (const key of ['recipient_name', 'email', 'phone']) {
            const input = key === 'phone' ? main?.querySelector('[data-phone-local]') : field(main,key);
            input?.addEventListener('input', () => {
                if (primaryContact) primaryContact.checked = false;
            });
        }
        shipmentForm.querySelectorAll('[data-shipment]').forEach((card) => {
            if (card === main) return;
            const same = card.querySelector('[data-same-shipment]');
            if (same) {
                setSameAsFirst(card, same.checked);
                same.addEventListener('change', () => setSameAsFirst(card, same.checked));
            }
            card.querySelector('[data-copy-shipment]')?.addEventListener('click', () => {
                if (same) same.checked = false;
                setSameAsFirst(card, false);
                copyFromFirst(card);
                field(card, 'recipient_name')?.focus();
            });
        });
        // Postal lookup is intentionally deferred until editing an address.
        // The existing checkout summary applies the authoritative remote price.
        const endpoint = shipmentForm.dataset.remoteStatusUrl;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (endpoint && token) {
            const timers = new WeakMap();
            const sequences = new WeakMap();
            const checkRemote = (card) => {
                clearTimeout(timers.get(card));
                const postal = field(card, 'postal_code')?.value.trim() || '';
                const country = field(card, 'country')?.value || '';
                if (postal.length < 3 || !country) return;
                const current = (sequences.get(card) || 0) + 1;
                sequences.set(card, current);
                const timer = setTimeout(async () => {
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST', credentials: 'same-origin',
                            headers: {'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':token},
                            body: JSON.stringify({postal_code:postal,country,city:field(card,'city')?.value || '',state:field(card,'state')?.value || ''}),
                        });
                        if (!response.ok || current !== sequences.get(card)) return;
                        const result = await response.json();
                        let note = card.querySelector('.np-checkout__remote-status');
                        if (!note && result.remote) {
                            note = document.createElement('p');
                            note.className = 'np-checkout__remote-status';
                            note.setAttribute('role','status');
                            card.querySelector('.np-checkout__address-fields')?.append(note);
                        }
                        if (note) {
                            note.hidden = !result.remote;
                            note.textContent = result.remote
                                ? 'This is a remote delivery area. The applicable surcharge is charged once per order and included in the final total.' : '';
                        }
                    } catch (_) { /* Server remains authoritative when offline. */ }
                }, 550);
                timers.set(card, timer);
            };
            shipmentForm.querySelectorAll('[data-shipment]').forEach((card) => {
                for (const key of ['postal_code','country','city']) {
                    field(card,key)?.addEventListener('change', () => checkRemote(card));
                }
            });
        }
    }

    const finalForm = root.querySelector('[data-checkout-review]');
    if (finalForm) {
        const toggle = finalForm.querySelector('[data-use-shipping-address]');
        const sameInput = finalForm.querySelector('#np-billing-same');
        const wrap = finalForm.querySelector('[data-billing-fields]');
        const stored = JSON.parse(wrap?.dataset.primaryAddress || '{}');
        toggle?.addEventListener('change', () => {
            sameInput.value = toggle.checked ? '1' : '0';
            if (!toggle.checked) return;
            for (const [name, value] of Object.entries(stored)) {
                const input = finalForm.elements.namedItem(name);
                if (input) { input.value = value; input.dispatchEvent(new Event('np-select-sync')); }
                if (input instanceof HTMLElement) setClientError(input, '');
            }
        });
        wrap?.querySelectorAll('input,select').forEach((input) => {
            input.addEventListener('input', () => {
                if (toggle) toggle.checked = false;
                sameInput.value = '0';
            });
            input.addEventListener('change', () => {
                if (toggle) toggle.checked = false;
                sameInput.value = '0';
            });
        });
        const terms = document.getElementById('np-checkout-terms');
        const button = root.querySelector('[data-checkout-pay-button]');
        if (terms && button) {
            const blocked = button.hasAttribute('data-price-blocked');
            const sync = () => {button.disabled = blocked || !terms.checked;};
            terms.addEventListener('change', sync);
            sync();
        }
    }
});
