/* NextPlay bulk quotation editor: source-of-truth prices are recalculated by PHP on publish. */
(() => {
  'use strict';
  const root = document.getElementById('np-quote-editor');
  const form = document.getElementById('np-quote-form');
  const dataTag = document.getElementById('np-quote-model');
  if (!root || !form || !dataTag) return;
  let model;
  try { model = JSON.parse(dataTag.textContent); } catch (_) { return; }
  const byId = id => document.getElementById(id);
  const fmt = value => '$' + Math.max(0, Number(value) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const num = value => Math.max(0, Number.isFinite(Number(value)) ? Number(value) : 0);
  const read = (i, key) => {
    const node = form.querySelector(`[data-i="${i}"][data-field="${key}"]`);
    return node ? (node.tagName === 'SELECT' ? node.value : num(node.value)) : 0;
  };
  const field = (i, key) => form.querySelector(`[data-i="${i}"][data-field="${key}"]`);
  const text = (selector, value) => { const el = form.querySelector(selector) || root.querySelector(selector); if (el) el.textContent = value; };
  let lastCalculation = null;

  function tierPrice(tiers, quantity, fallback) {
    let matched = null, minimum = -1;
    for (const tier of tiers || []) {
      if (quantity >= num(tier.min) && (tier.max == null || quantity <= num(tier.max)) && num(tier.min) >= minimum) {
        matched = num(tier.unit); minimum = num(tier.min);
      }
    }
    return matched === null ? num(fallback) : matched;
  }

  function activeFabric(line, i) {
    const group = (line.groups || []).find(g => /fabric|material/i.test(g.label));
    if (!group) return null;
    const selector = form.querySelector(`select[name="lines[${i}][options][${group.id}]"]`);
    const picked = (group.values || []).find(v => v.id === (selector ? selector.value : group.selected));
    return (line.fabric_tiers || []).find(t => picked && String(t.label || '').toLowerCase() === String(picked.label || '').toLowerCase()) || null;
  }

  // Storefront price tables use one shipping charge per order for the selected quantity tier.
  function tableRate(line, i, method, quantity) {
    const fabric = activeFabric(line, i);
    const headers = fabric?.headers?.length ? fabric.headers : (line.table_headers || []);
    const rows = fabric?.rows?.length ? fabric.rows : (line.table_rows || []);
    const tiers = fabric?.tiers?.length ? fabric.tiers : (line.tiers || []);
    if (!headers.length || !rows.length) return null;
    const rx = method === 'express' ? /express|urgent|rush|priority/i : (method === 'remote' ? /remote|rural|outlying/i : /regular|standard|economy|normal/i);
    let col = method === 'unit'
      ? headers.findIndex((s, idx) => idx > 0 && /\b(unit|product)\b.*\bprice\b|\bprice\b.*\bunit\b/i.test(String(s)))
      : headers.findIndex((s, idx) => idx > 0 && rx.test(String(s)) && (method === 'remote' || /ship|deliver|freight/i.test(String(s))));
    if (col < 0 && method === 'regular') col = headers.findIndex((s, idx) => idx > 0 && /shipping/i.test(String(s)) && !/express|urgent|remote/i.test(String(s)));
    if (col < 0) return null;
    let idx = -1, bestMin = -1;
    tiers.forEach((t, j) => { if (quantity >= num(t.min) && (t.max == null || quantity <= num(t.max)) && num(t.min) >= bestMin) { idx = j; bestMin = num(t.min); } });
    if (idx < 0) {
      idx = rows.findIndex(row => {
        const match = String(row[0] ?? '').match(/\d[\d,]*/);
        return match && quantity >= Number(match[0].replace(/,/g, ''));
      });
      if (idx < 0) idx = 0;
    }
    const cell = String(rows[Math.min(idx, rows.length - 1)]?.[col] ?? '').trim();
    if (/^(free|included)$/i.test(cell)) return 0;
    const match = cell.match(/-?\d[\d,]*(?:\.\d+)?/);
    return match ? Math.max(0, Number(match[0].replace(/,/g, ''))) : null;
  }

  function options(line, i, kind = 'options') {
    let unit = 0, fixed = 0;
    const selectedLabels = [], missing = [];
    for (const group of line.groups || []) {
      const controls = Array.from(form.querySelectorAll(`[data-option-kind="${kind}"][data-option-group="${group.id}"][data-option-line="${i}"]`));
      const chosen = controls.length
        ? (group.type === 'checkbox' ? controls.filter(control => control.checked).map(control => control.value) : [controls[0].value])
        : (group.type === 'checkbox' ? (Array.isArray(group.selected) ? group.selected : []) : [group.selected]);
      const selected = group.values.filter(value => chosen.includes(value.id));
      if (group.required && selected.length === 0) missing.push(group.label);
      const wrapper = form.querySelector(`[data-option-kind="${kind}"][data-line-index="${i}"] [data-option-group="${group.id}"]`)?.closest('.np-quote-option-field');
      if (wrapper) { if (group.required && !selected.length) wrapper.dataset.unselected = '1'; else delete wrapper.dataset.unselected; }
      for (const choice of selected) {
        if (choice.charge_type === 'fixed_order') fixed += num(choice.price);
        else if (choice.charge_type !== 'included') unit += num(choice.price);
        selectedLabels.push(`${group.label}: ${choice.label}${num(choice.price) ? ' (+' + fmt(choice.price) + (choice.charge_type === 'fixed_order' ? '/order' : '/pc') + ')' : ''}`);
      }
    }
    return { unit, fixed, missing, labels: selectedLabels };
  }

  function calculate() {
    const shippingMethod = byId('np-quote-shipping-method').value;
    const sampleMethod = byId('np-quote-sample-ship').value;
    const sampleEnabled = byId('np-quote-sample-on').checked;
    const remote = num(byId('np-quote-remote')?.value);
    const privateAddress = num(byId('np-quote-private')?.value);
    const sampleRemote = num(byId('np-quote-sample-remote')?.value);
    const samplePrivate = num(byId('np-quote-sample-private')?.value);
    let bulk = 0, samples = 0, optionTotal = 0;
    const rows = [], missing = [];
    model.lines.forEach((line, idx) => {
      const i = line.index;
      const qty = num(read(i, 'quantity'));
      const unit = num(read(i, 'unit_price'));
      const choices = options(line, i);
      const sampleChoices = options(line, i, 'sample_options');
      const imprint = qty * num(read(i, 'imprint_unit')) + num(read(i, 'imprint_setup'));
      const extra = qty * choices.unit + choices.fixed + imprint;
      const shipping = num(read(i, shippingMethod === 'express' ? 'express_shipping' : 'regular_shipping'));
      const base = qty * unit;
      const lineTotal = base + extra + shipping;
      bulk += lineTotal;
      optionTotal += extra;
      text(`[data-line-total="${i}"]`, fmt(lineTotal + (idx === 0 ? remote + privateAddress : 0)));
      text(`[data-bulk-breakdown-total="${i}"]`, fmt(lineTotal));
      text(`[data-bulk-order-label="${i}"]`, `${qty} × ${line.name}`);
      text(`[data-option-total="${i}"]`, fmt(extra));
      text(`[data-option-summary="${i}"]`, choices.labels.join(' · ') || 'Included / none selected');
      // Keep the compact product label synchronized with any option edits.
      const basicOptions = choices.labels.map(value => value.replace(/ \(\+\$.*$/, ''));
      text(`[data-product-title="${i}"]`, [line.sku, line.name].filter(Boolean).join(' ') + (basicOptions.length ? ' · ' + basicOptions.join(' · ') : ''));
      missing.push(...choices.missing.map(v => `${line.name}: ${v}`));

      let sampleLine = 0;
      if (sampleEnabled) {
        const sampleQty = num(read(i, 'sample_quantity'));
        const sampleType = read(i, 'sample_type');
        const sampleProduct = sampleQty * num(read(i, 'sample_unit'));
        const sampleExtra = sampleType === 'printed' && sampleQty
          ? sampleQty * (num(read(i, 'sample_imprint_unit')) + sampleChoices.unit) + num(read(i, 'sample_setup')) + sampleChoices.fixed : 0;
        const sampleShipping = sampleQty ? num(read(i, sampleMethod === 'express' ? 'sample_express_shipping' : 'sample_regular_shipping')) : 0;
        sampleLine = sampleProduct + sampleExtra + sampleShipping;
        samples += sampleLine;
        text(`[data-sample-total="${i}"]`, fmt(sampleLine + (idx === 0 && sampleQty ? sampleRemote + samplePrivate : 0)));
        text(`[data-sample-type-label="${i}"]`, sampleType === 'blank' ? 'None (blank)' : (sampleChoices.labels.join(' · ') || 'Printed'));
        if (sampleType === 'printed' && sampleQty > 0) missing.push(...sampleChoices.missing.map(v => `${line.name} sample: ${v}`));
      }
      rows.push({ name: line.name, quantity: qty, base, options: extra, shipping, lineTotal, sample: sampleLine });
    });
    bulk += remote + privateAddress;
    if (sampleEnabled && rows.some((_, i) => num(read(i, 'sample_quantity')) > 0)) samples += sampleRemote + samplePrivate;
    const total = bulk + samples;
    for (const [id, amount] of [['np-quote-bulk-total', bulk], ['np-quote-sample-total', samples], ['np-quote-footer-bulk', bulk], ['np-quote-footer-sample', samples], ['np-quote-footer-grand', total]]) {
      if (byId(id)) byId(id).textContent = fmt(amount);
    }
    byId('np-quote-sample-fields').hidden = !sampleEnabled;
    byId('np-quote-sample-empty').hidden = sampleEnabled;
    byId('np-quote-sample-on-text').textContent = sampleEnabled ? 'Yes' : 'No';
    byId('np-quote-sample-ship').disabled = !sampleEnabled;
    lastCalculation = { bulk, samples, total, remote, privateAddress, sampleRemote, samplePrivate, rows, missing, optionTotal };
    return lastCalculation;
  }

  function refreshTierFor(i) {
    const line = model.lines.find(l => l.index === i);
    if (!line) return;
    const quantity = num(read(i, 'quantity'));
    const fabric = activeFabric(line, i);
    const tiers = fabric?.tiers?.length ? fabric.tiers : line.tiers;
    const recommended = tiers?.length ? tierPrice(tiers, quantity, line.base_price)
      : (tableRate(line, i, 'unit', quantity) ?? num(line.base_price));
    const price = field(i, 'unit_price');
    if (price && price.dataset.manual !== '1') price.value = recommended.toFixed(2);
    text(`[data-tier-label="${i}"]`, `Tier ${fmt(recommended)}/pc`);
    for (const method of ['regular', 'express']) {
      const el = field(i, method + '_shipping');
      if (el && el.dataset.manual !== '1') {
        const table = tableRate(line, i, method, quantity);
        if (table !== null) el.value = table.toFixed(2);
      }
    }
    const remote = byId('np-quote-remote');
    if (remote && remote.dataset.manual !== '1') {
      const rates = model.lines.map(l => tableRate(l, l.index, 'remote', num(read(l.index, 'quantity')))).filter(v => v !== null);
      if (rates.length) remote.value = Math.max(...rates).toFixed(2);
    }
  }

  form.querySelectorAll('[data-field]').forEach(input => {
    if (!['quantity', 'unit_price', 'regular_shipping', 'express_shipping'].includes(input.dataset.field)) return;
    const saved = model.lines.find(line => line.index === Number(input.dataset.i));
    if (saved?.draft && Object.prototype.hasOwnProperty.call(saved.draft, input.dataset.field) && input.dataset.field !== 'quantity') {
      input.dataset.manual = '1';
    }
  });
  form.addEventListener('input', ev => {
    const target = ev.target;
    if (target.dataset.i !== undefined && ['unit_price', 'regular_shipping', 'express_shipping'].includes(target.dataset.field)) target.dataset.manual = '1';
    if (target.id === 'np-quote-remote') target.dataset.manual = '1';
    if (target.dataset.field === 'quantity') refreshTierFor(Number(target.dataset.i));
    calculate();
  });
  form.addEventListener('change', ev => {
    const target = ev.target;
    if (target.dataset.optionLine !== undefined && target.dataset.optionKind === 'options') refreshTierFor(Number(target.dataset.optionLine));
    calculate();
  });
  // Do not override the requested / saved quote on initial render.
  model.lines.forEach(line => {
    const recommended = num(line.unit_price);
    text(`[data-tier-label="${line.index}"]`, `Tier ${fmt(recommended)}/pc`);
  });
  calculate();

  const paymentTerm = byId('np-quote-payment-term');
  const paymentLabel = byId('np-quote-payment-label');
  if (paymentTerm && paymentLabel) {
    const syncPayment = () => { paymentLabel.value = paymentTerm.selectedOptions[0]?.dataset.label || ''; };
    paymentTerm.addEventListener('change', syncPayment);
    syncPayment();
  }

  // The full upload field is a label target; keyboard and drop support the same input.
  // DataTransfer preserves the multipart files[] contract and selected-file removals.
  const fileInput = byId('np-quote-files');
  const fileDrop = byId('np-quote-file-drop');
  const chips = byId('np-quote-file-chips');
  const fileError = byId('np-quote-upload-error');
  let chosen = [];
  const accepted = name => /\.(pdf|xls|xlsx|png|jpe?g)$/i.test(name);
  function uploadError(message = '') {
    fileError.textContent = message;
    fileError.hidden = !message;
    fileInput.setAttribute('aria-invalid', message ? 'true' : 'false');
  }
  function setFiles(files) {
    const incoming = Array.from(files || []);
    let invalid = false;
    let tooMany = false;
    for (const file of incoming) {
      if (!accepted(file.name) || file.size > 10 * 1024 * 1024 || file.size <= 0) {
        invalid = true;
        continue;
      }
      if (chosen.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) continue;
      if (chosen.length >= 8) { tooMany = true; continue; }
      chosen.push(file);
    }
    uploadError(tooMany ? 'You can select a maximum of 8 quotation files.' : (invalid ? 'Choose PDF, JPG, PNG, XLS or XLSX files of 10 MB or less.' : ''));
    syncFiles();
  }
  function syncFiles() {
    try {
      const transfer = new DataTransfer();
      chosen.forEach(f => transfer.items.add(f));
      fileInput.files = transfer.files;
    } catch (_) { // Browsers without DataTransfer retain the native file selection.
      chosen = Array.from(fileInput.files || []);
    }
    chips.replaceChildren();
    if (!chosen.length) {
      const empty = document.createElement('small');
      empty.textContent = 'No new files selected.';
      chips.append(empty);
    }
    chosen.forEach((file, index) => {
      const tag = document.createElement('span'); tag.className = 'np-quote-file-chip';
      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('width', '16'); svg.setAttribute('height', '16');
      svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('fill', 'none'); svg.setAttribute('stroke', 'currentColor');
      svg.setAttribute('stroke-width', '1.8');
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6');
      svg.append(path);
      const label = document.createElement('span'); label.textContent = file.name;
      const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×';
      remove.setAttribute('aria-label', `Remove ${file.name}`);
      remove.addEventListener('click', () => { chosen.splice(index, 1); uploadError(); syncFiles(); });
      tag.append(svg, label, remove); chips.append(tag);
    });
  }
  fileDrop.addEventListener('keydown', ev => {
    if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); fileInput.click(); }
  });
  fileInput.addEventListener('change', () => setFiles(fileInput.files));
  ['dragenter', 'dragover'].forEach(type => fileDrop.addEventListener(type, ev => {
    ev.preventDefault(); if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'copy';
    fileDrop.classList.add('is-dragging');
  }));
  fileDrop.addEventListener('dragleave', ev => {
    if (!fileDrop.contains(ev.relatedTarget)) fileDrop.classList.remove('is-dragging');
  });
  fileDrop.addEventListener('drop', ev => {
    ev.preventDefault(); fileDrop.classList.remove('is-dragging'); setFiles(ev.dataTransfer?.files);
  });

  const preview = byId('np-quote-preview');
  const content = byId('np-quote-preview-content');
  function appendRow(parent, name, amount) {
    const row = document.createElement('div'); row.className = 'np-quote-preview-row';
    const label = document.createElement('span'); label.textContent = name;
    const value = document.createElement('strong'); value.textContent = fmt(amount);
    row.append(label, value); parent.append(row);
  }
  byId('np-quote-preview-btn').addEventListener('click', () => {
    const current = calculate();
    content.replaceChildren();
    current.rows.forEach(row => appendRow(content, `${row.name} (${row.quantity} pcs)`, row.lineTotal));
    if (current.remote) appendRow(content, 'Bulk remote area surcharge / shipment', current.remote);
    if (current.privateAddress) appendRow(content, 'Bulk private address / order', current.privateAddress);
    appendRow(content, 'Bulk total', current.bulk);
    if (byId('np-quote-sample-on').checked) appendRow(content, 'Samples including separate delivery', current.samples);
    appendRow(content, 'Grand total', current.total);
    if (current.missing.length) {
      const warning = document.createElement('p'); warning.className = 'np-quote-preview-warning';
      warning.textContent = 'Choose required product options before publishing: ' + current.missing.join('; ');
      content.prepend(warning);
    }
    preview.showModal();
  });
  byId('np-quote-preview-close').addEventListener('click', () => preview.close());
  preview.addEventListener('click', ev => { if (ev.target === preview) preview.close(); });
  form.addEventListener('submit', ev => {
    const isDraft = ev.submitter?.id === 'np-quote-save';
    if (isDraft) return;
    const current = calculate();
    if (current.missing.length) {
      ev.preventDefault();
      window.alert('Select the required product options: ' + current.missing.join('; '));
      const first = form.querySelector('.np-quote-option-field[data-unselected="1"]');
      if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); first.querySelector('select, input')?.focus(); }
      return;
    }
    if (current.total <= 0) { ev.preventDefault(); window.alert('The quotation total must be greater than zero.'); return; }
    if (!byId('np-quote-confirm-notify').checked) { ev.preventDefault(); window.alert('Confirm the customer notification before sending.'); return; }
    if (!fileInput.files.length && !form.querySelector('input[name="use_flowtrack_offer"]:checked')) {
      ev.preventDefault(); window.alert('Attach at least one quotation document before publishing.'); return;
    }
    const submit = byId('np-quote-send'); submit.disabled = true; submit.textContent = 'Publishing…';
  });
})();
