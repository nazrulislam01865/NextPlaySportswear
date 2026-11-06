@php
    $draft = (array) ($quoteEditor['draft'] ?? []);
    $published = (array) ($bulkQuote->quotation_details ?? []);
    $lines = $quoteEditor['lines'];
    $delivery = $quoteEditor['delivery'];
    $shippingSelected = old('shipping_method', $draft['shipping_method'] ?? $delivery['preferred']);
    $sampleSelected = old('sample_shipping_method', $draft['sample_shipping_method'] ?? 'regular');
    $sampleEnabled = (bool) old('sample_enabled', $draft['sample_enabled'] ?? false);
    $remoteAmount = old('remote_charge', $draft['remote_charge'] ?? $quoteEditor['remote_charge']);
@endphp
<div class="np-quote-ui" id="np-quote-editor">
    <div class="np-quote-head">
        <div>
            <p class="np-quote-overline"><a href="{{ route('admin.bulk-quotes.index') }}">Bulk quote requests</a> <span>/</span> <a href="{{ route('admin.bulk-quotes.show', $bulkQuote) }}?tab=details">{{ $bulkQuote->reference }}</a></p>
            <h2>Prepare bulk quotation</h2>
            <p>{{ $bulkQuote->full_name }} @if($bulkQuote->organization) · {{ $bulkQuote->organization }} @endif</p>
            <div class="np-quote-info" role="note"><span aria-hidden="true">ⓘ</span> Client selections and saved prices loaded automatically. Edit any value before sending.</div>
        </div>
        <div class="np-quote-delivery">
            <div class="np-quote-delivery-label">
                <svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h11v11H3zM14 10h4l3 4v3h-7z"/><circle cx="7.5" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                <strong>Delivery: {{ ($delivery['address'] ?: collect([$delivery['state'], $delivery['postal']])->filter()->implode(', ')) ?: 'Address to be confirmed' }}</strong>
                <span class="np-quote-pill">{{ $quoteEditor['remote'] ? 'Remote area' : 'Standard area' }}</span>
            </div>
            <a href="#np-quote-shipping" class="np-quote-edit-link">Edit</a>
            <div class="np-quote-shipping" id="np-quote-shipping">
                <label for="np-quote-shipping-method">Client selected shipping</label>
                <select class="admin-input" id="np-quote-shipping-method" name="shipping_method" form="np-quote-form">
                    <option value="regular" @selected($shippingSelected === 'regular')>Regular</option>
                    <option value="express" @selected($shippingSelected === 'express')>Express</option>
                </select>
                <p>Both rates are shown. Only the selected method is included in totals.</p>
            </div>
        </div>
    </div>

    @if(filled($draft['updated_at'] ?? null))
        <p class="np-quote-draft-notice">Draft last saved {{ \Carbon\Carbon::parse($draft['updated_at'])->format('d M Y, g:i A') }}. Draft prices have not been sent to the customer.</p>
    @endif

    <form id="np-quote-form" method="POST" action="{{ route('admin.bulk-quotes.publish', $bulkQuote) }}" enctype="multipart/form-data" data-remote="{{ $quoteEditor['remote'] ? '1' : '0' }}">
        @csrf
        <section class="np-quote-panel" aria-labelledby="np-quote-bulk-title">
            <header class="np-quote-panel-head">
                <div><span class="np-quote-step">1</span><h3 id="np-quote-bulk-title">BULK ORDER</h3></div>
                <small>Shared address surcharge applied once per shipment.</small>
            </header>
            <div class="np-quote-table-scroller">
                <table class="np-quote-pricing-table" id="np-quote-bulk-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Product / unit</th><th>Regular shipping / order</th><th>Express shipping / order</th><th>Remote area / order</th><th>Private address / order</th><th>Product + selected delivery</th></tr></thead>
                    <tbody>
                        @foreach($lines as $line)
                            @php
                                $i = $line['index'];
                                $stored = (array) $line['draft'];
                                $qty = old("lines.$i.quantity", $stored['quantity'] ?? $line['quantity']);
                                $unit = old("lines.$i.unit_price", $stored['unit_price'] ?? $line['unit_price']);
                                $reg = old("lines.$i.regular_shipping", $stored['regular_shipping'] ?? $line['shipping_rates']['regular']);
                                $exp = old("lines.$i.express_shipping", $stored['express_shipping'] ?? $line['shipping_rates']['express']);
                            @endphp
                            <tr class="np-quote-product-row" data-line="{{ $i }}">
                                <td class="np-quote-product-cell">
                                    <strong data-product-title="{{ $i }}">{{ $line['sku'] ? $line['sku'].' ' : '' }}{{ $line['name'] }}@if($line['selected_options']) · {{ collect($line['selected_options'])->map(fn ($selected) => $selected['value'] ?? implode(', ', (array) ($selected['values'] ?? [])))->filter()->implode(' · ') }}@endif</strong>
                                    @unless($line['product_id']) <small class="np-quote-manual-note">Not a catalogue product — enter the negotiated unit price.</small> @endunless
                                </td>
                                <td><input class="admin-input np-quote-number" aria-label="{{ $line['name'] }} quantity" type="number" name="lines[{{ $i }}][quantity]" min="1" max="10000000" step="1" value="{{ $qty }}" data-field="quantity" data-i="{{ $i }}" required></td>
                                <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" aria-label="{{ $line['name'] }} unit price" type="number" name="lines[{{ $i }}][unit_price]" min="0" max="99999999" step="0.01" value="{{ $unit }}" data-field="unit_price" data-i="{{ $i }}" required></span><small class="np-quote-tier-hint" data-tier-label="{{ $i }}"></small></td>
                                <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" aria-label="{{ $line['name'] }} regular shipping" type="number" name="lines[{{ $i }}][regular_shipping]" min="0" step="0.01" value="{{ $reg }}" data-field="regular_shipping" data-i="{{ $i }}" required></span></td>
                                <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" aria-label="{{ $line['name'] }} express shipping" type="number" name="lines[{{ $i }}][express_shipping]" min="0" step="0.01" value="{{ $exp }}" data-field="express_shipping" data-i="{{ $i }}" required></span></td>
                                <td>
                                    @if($loop->first && $quoteEditor['remote'])
                                        <span class="np-quote-money-field"><input class="admin-input np-quote-number" aria-label="Remote area surcharge once per bulk shipment" type="number" name="remote_charge" min="0" step="0.01" value="{{ $remoteAmount }}" id="np-quote-remote"></span>
                                    @else
                                        <span class="np-quote-not-applicable">{{ $quoteEditor['remote'] ? 'Applied once above' : 'Not applicable' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($loop->first)
                                        <span class="np-quote-money-field"><input class="admin-input np-quote-number" aria-label="Private address charge per bulk order" type="number" name="private_address_charge" min="0" step="0.01" value="{{ old('private_address_charge', $draft['private_address_charge'] ?? 0) }}" id="np-quote-private"></span>
                                    @else <span class="np-quote-not-applicable">Shared charge</span> @endif
                                </td>
                                <td class="np-quote-bold-money" data-line-total="{{ $i }}">$0.00</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="np-quote-breakdown-title">&nbsp;</div>
            <div class="np-quote-table-scroller">
                <table class="np-quote-pricing-table np-quote-imprint-table np-quote-option-breakdown" aria-label="Bulk order product options and charges">
                    <thead><tr><th>Order line</th><th>Product option</th><th>Extra / unit</th><th>Setup / order</th><th>Option total</th><th>Line total</th></tr></thead>
                    <tbody>
                        @foreach($lines as $line)
                            @php $i = $line['index']; $stored = (array) $line['draft']; @endphp
                            @foreach($line['groups'] as $group)
                                <tr data-option-group-row="options" data-option-line="{{ $i }}" data-group-id="{{ $group['id'] }}">
                                    <td>@if($loop->first)<span data-bulk-order-label="{{ $i }}">{{ $line['quantity'] }} × {{ $line['name'] }}</span>@endif</td>
                                    <td>@include('admin.bulk-quotes.partials.quotation-options', ['optionKind' => 'options', 'group' => $group, 'i' => $i, 'stored' => $stored])</td>
                                    <td data-option-unit-amount>$0.00</td>
                                    <td data-option-fixed-amount>$0.00</td>
                                    <td data-option-row-total class="np-quote-bold-money">$0.00</td>
                                    <td></td>
                                </tr>
                            @endforeach
                            <tr data-option-adjustment="options" data-option-line="{{ $i }}">
                                <td>@if(!count($line['groups']))<span data-bulk-order-label="{{ $i }}">{{ $line['quantity'] }} × {{ $line['name'] }}</span>@endif</td>
                                <td><span class="np-quote-adjustment-label">Additional customization</span></td>
                                <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][imprint_unit]" min="0" step="0.01" aria-label="{{ $line['name'] }} additional customization per unit" value="{{ old("lines.$i.imprint_unit", $stored['imprint_unit'] ?? 0) }}" data-field="imprint_unit" data-i="{{ $i }}"></span></td>
                                <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][imprint_setup]" min="0" step="0.01" aria-label="{{ $line['name'] }} additional customization setup per order" value="{{ old("lines.$i.imprint_setup", $stored['imprint_setup'] ?? 0) }}" data-field="imprint_setup" data-i="{{ $i }}"></span></td>
                                <td class="np-quote-bold-money" data-option-total="{{ $i }}">$0.00</td>
                                <td class="np-quote-bold-money" data-bulk-breakdown-total="{{ $i }}">$0.00</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <footer class="np-quote-panel-footer"><span class="np-quote-example-note">Example editable rates</span><strong>Bulk total <span id="np-quote-bulk-total">$0.00</span></strong></footer>
        </section>

        <section class="np-quote-panel" aria-labelledby="np-quote-sample-title">
            <header class="np-quote-panel-head">
                <div><span class="np-quote-step">2</span><h3 id="np-quote-sample-title">SAMPLE</h3><label class="np-quote-switch-label">Sample requested <input type="checkbox" name="sample_enabled" value="1" id="np-quote-sample-on" @checked($sampleEnabled)><span class="np-quote-switch" aria-hidden="true"></span><span id="np-quote-sample-on-text">{{ $sampleEnabled ? 'Yes' : 'No' }}</span></label></div>
                <div class="np-quote-sample-shipping"><label for="np-quote-sample-ship">Sample shipping:</label><select class="admin-input" name="sample_shipping_method" id="np-quote-sample-ship"><option value="regular" @selected($sampleSelected === 'regular')>Regular</option><option value="express" @selected($sampleSelected === 'express')>Express</option></select><small>All rates are editable; setup may be zero. Samples total separate from bulk.</small></div>
            </header>
            <div id="np-quote-sample-fields" @if(!$sampleEnabled) hidden @endif>
                <div class="np-quote-table-scroller">
                    <table class="np-quote-pricing-table"><thead><tr><th>Product</th><th>Qty</th><th>Sample type</th><th>Product / unit</th><th>Regular shipping / order</th><th>Express shipping / order</th><th>Remote area / order</th><th>Private address / order</th></tr></thead>
                        <tbody>
                            @foreach($lines as $line)
                                @php $i = $line['index']; $stored = (array) $line['draft']; @endphp
                                <tr data-sample-line="{{ $i }}">
                                    <td class="np-quote-product-cell"><strong>{{ $line['name'] }}</strong>
                                    </td>
                                    <td><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_quantity]" min="0" max="1000" step="1" value="{{ old("lines.$i.sample_quantity", $stored['sample_quantity'] ?? 1) }}" data-field="sample_quantity" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample quantity"></td>
                                    <td><select class="admin-input" name="lines[{{ $i }}][sample_type]" data-field="sample_type" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample type"><option value="printed" @selected(old("lines.$i.sample_type", $stored['sample_type'] ?? 'printed') === 'printed')>Printed</option><option value="blank" @selected(old("lines.$i.sample_type", $stored['sample_type'] ?? 'printed') === 'blank')>Blank</option></select></td>
                                    <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_unit]" min="0" step="0.01" value="{{ old("lines.$i.sample_unit", $stored['sample_unit'] ?? $line['sample_default']) }}" data-field="sample_unit" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample unit price"></span></td>
                                    <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_regular_shipping]" min="0" step="0.01" value="{{ old("lines.$i.sample_regular_shipping", $stored['sample_regular_shipping'] ?? 0) }}" data-field="sample_regular_shipping" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample regular shipping"></span></td>
                                    <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_express_shipping]" min="0" step="0.01" value="{{ old("lines.$i.sample_express_shipping", $stored['sample_express_shipping'] ?? 0) }}" data-field="sample_express_shipping" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample express shipping"></span></td>
                                    <td>
                                        @if($loop->first && $quoteEditor['remote'])
                                            <span class="np-quote-money-field"><input class="admin-input np-quote-number" id="np-quote-sample-remote" type="number" min="0" step="0.01" name="sample_remote_charge" value="{{ old('sample_remote_charge', $draft['sample_remote_charge'] ?? $quoteEditor['remote_charge']) }}" aria-label="Sample remote-area charge per shipment"></span>
                                        @else <span class="np-quote-not-applicable">{{ $quoteEditor['remote'] ? 'Shared charge' : 'Not applicable' }}</span> @endif
                                    </td>
                                    <td>
                                        @if($loop->first)
                                            <span class="np-quote-money-field"><input class="admin-input np-quote-number" id="np-quote-sample-private" type="number" min="0" step="0.01" name="sample_private_charge" value="{{ old('sample_private_charge', $draft['sample_private_charge'] ?? 0) }}" aria-label="Sample private-address charge per shipment"></span>
                                        @else <span class="np-quote-not-applicable">Shared charge</span> @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="np-quote-sample-explanation">Samples share one shipment; shipping and address charge applied once.</p>
                <div class="np-quote-table-scroller">
                    @foreach($lines as $line)<input type="hidden" name="lines[{{ $line['index'] }}][sample_options][_submitted]" value="1">@endforeach
                    <table class="np-quote-pricing-table np-quote-imprint-table np-quote-option-breakdown" aria-label="Sample product options and charges">
                        <thead><tr><th>Sample</th><th>Sample option</th><th>Extra / unit</th><th>Setup / order</th><th>Sample line total</th></tr></thead>
                        <tbody>
                            @foreach($lines as $line)
                                @php $i = $line['index']; $stored = (array) $line['draft']; @endphp
                                @foreach($line['groups'] as $group)
                                    <tr data-option-group-row="sample_options" data-option-line="{{ $i }}" data-group-id="{{ $group['id'] }}">
                                        <td>@if($loop->first)<span data-sample-order-label="{{ $i }}">{{ $line['name'] }}</span>@endif</td>
                                        <td>@include('admin.bulk-quotes.partials.quotation-options', ['optionKind' => 'sample_options', 'group' => $group, 'i' => $i, 'stored' => $stored])</td>
                                        <td data-option-unit-amount>$0.00</td>
                                        <td data-option-fixed-amount>$0.00</td>
                                        <td class="np-quote-bold-money" data-option-row-total>$0.00</td>
                                    </tr>
                                @endforeach
                                <tr data-option-adjustment="sample_options" data-option-line="{{ $i }}">
                                    <td>@if(!count($line['groups']))<span data-sample-order-label="{{ $i }}">{{ $line['name'] }}</span>@endif</td>
                                    <td><span class="np-quote-adjustment-label">Additional customization</span></td>
                                    <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_imprint_unit]" min="0" step="0.01" value="{{ old("lines.$i.sample_imprint_unit", $stored['sample_imprint_unit'] ?? 0) }}" data-field="sample_imprint_unit" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample customization per unit"></span></td>
                                    <td><span class="np-quote-money-field"><input class="admin-input np-quote-number" type="number" name="lines[{{ $i }}][sample_setup]" min="0" step="0.01" value="{{ old("lines.$i.sample_setup", $stored['sample_setup'] ?? 0) }}" data-field="sample_setup" data-i="{{ $i }}" aria-label="{{ $line['name'] }} sample setup per order"></span></td>
                                    <td data-sample-total="{{ $i }}" class="np-quote-bold-money">$0.00</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p id="np-quote-sample-empty" class="np-quote-empty" @if($sampleEnabled) hidden @endif>Sample not requested. Turn on the switch to configure sample pricing.</p>
            <footer class="np-quote-panel-footer"><span>Blank samples have no option or customization charges. Turn samples off to exclude this section.</span><strong>Sample total <span id="np-quote-sample-total">$0.00</span></strong></footer>
        </section>

        <section class="np-quote-panel np-quote-documents" aria-labelledby="np-quote-document-title">
            <header class="np-quote-panel-head"><div><span class="np-quote-step">3</span><h3 id="np-quote-document-title">DOCUMENTS &amp; SEND</h3></div></header>
            <div class="np-quote-upload-area" aria-labelledby="np-quote-upload-heading">
                <label for="np-quote-files" class="np-quote-dropzone" id="np-quote-file-drop" role="button" tabindex="0" aria-describedby="np-quote-file-help">
                    <span class="np-quote-dropzone-icon" aria-hidden="true"><svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m-4 4 4-4 4 4"/><path d="M5 16.5a4.5 4.5 0 0 0 .5 9h13a4 4 0 0 0 1.5-7.7 6.5 6.5 0 0 0-12.5-1.3" transform="translate(0 -3)"/></svg></span>
                    <span class="np-quote-dropzone-copy"><strong id="np-quote-upload-heading">Upload quotation or supporting documents</strong><span>Drag and drop files here, or browse your device</span><small id="np-quote-file-help">PDF, JPG, PNG, XLS or XLSX · Maximum 8 files, 10 MB each</small></span>
                    <span class="np-quote-browse-files" aria-hidden="true">Browse files</span>
                </label>
                <input class="np-quote-visually-hidden-file" id="np-quote-files" type="file" name="files[]" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" multiple aria-describedby="np-quote-file-help np-quote-upload-error">
                <p class="np-quote-upload-error" id="np-quote-upload-error" role="alert" hidden></p>
                <div class="np-quote-selected-files"><span class="np-quote-upload-caption">Selected documents</span><div class="np-quote-file-chips" id="np-quote-file-chips" role="status" aria-live="polite"><small>No new files selected.</small></div></div>
            </div>
            @if($canManage && filled($bulkQuote->flowtrack_offer_manifest_hash) && !$offerReady)
                <p class="np-quote-help">FlowTracker quotation files are still transferring. Upload your own files, or publish once the transfer finishes.</p>
            @endif
            @if($transferredOffers->isNotEmpty())
                <div class="np-quote-included-files">
                    <label class="np-quote-forward-choice"><input type="checkbox" name="use_flowtrack_offer" value="1" @checked(old('use_flowtrack_offer', $draft['use_flowtrack_offer'] ?? true))><span>Include {{ $transferredOffers->count() }} verified FlowTracker offer file(s)</span></label>
                    <div class="np-quote-file-chips np-quote-existing-file-chips">@foreach($transferredOffers as $offer)<a href="{{ route('admin.bulk-quotes.file', [$bulkQuote, $offer]) }}" class="np-quote-file-chip np-quote-file-chip--existing" title="{{ $offer->original_name }}"><svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg><span>{{ $offer->original_name }}</span></a>@endforeach</div>
                </div>
            @endif
            <div class="np-quote-doc-grid">
                @php
                    $validDays = [1, 3, 7, 15, 21, 30, 60];
                    $savedValidity = old('valid_until', $draft['valid_until'] ?? $bulkQuote->quotation_valid_until?->toDateString() ?? now()->addDays(30)->toDateString());
                    $paymentValue = old('payment_terms', $draft['payment_terms'] ?? $published['payment_terms'] ?? '');
                    $paymentMatch = $paymentTermOptions->first(fn ($term) => $term->label === $paymentValue);
                    $validDates = collect($validDays)->map(fn ($days) => now()->startOfDay()->addDays($days)->toDateString())->all();
                    if (!in_array($savedValidity, $validDates, true)) $savedValidity = now()->addDays(30)->toDateString();
                @endphp
                <label for="np-quote-valid-until"><span>Valid until <b>*</b></span>
                    <select class="admin-input" id="np-quote-valid-until" name="valid_until" required>
                        @foreach($validDays as $days)
                            @php $dateValue = now()->startOfDay()->addDays($days)->toDateString(); @endphp
                            <option value="{{ $dateValue }}" @selected($savedValidity === $dateValue)>{{ $days }} {{ $days === 1 ? 'day' : 'days' }} · {{ \Carbon\Carbon::parse($dateValue)->format('d M Y') }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="np-quote-payment-term"><span>Payment terms <b>*</b></span>
                    <select class="admin-input" id="np-quote-payment-term" name="payment_term_id" required>
                        <option value="">Choose payment terms</option>
                        @foreach($paymentTermOptions as $term)
                            <option value="{{ $term->id }}" data-label="{{ $term->label }}" @selected($paymentMatch?->id === $term->id || (!$paymentValue && $loop->first))>{{ $term->label }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="payment_terms" id="np-quote-payment-label" value="{{ $paymentValue }}">
                </label>
                <label><span>Recipient</span><input class="admin-input" type="email" readonly value="{{ $bulkQuote->email }}" aria-label="Customer email recipient"></label>
                <label class="np-quote-notes-field"><span>Notes (optional)</span><textarea class="admin-textarea" rows="1" maxlength="2500" name="quotation_notes" placeholder="Add a note to the client (optional)">{{ old('quotation_notes', $draft['quotation_notes'] ?? $published['quotation_notes'] ?? '') }}</textarea></label>
            </div>

            <label class="np-quote-forward-choice"><input type="checkbox" name="confirm_notify" id="np-quote-confirm-notify" value="1" checked required><span>Email client a link to download, comment and approve the quotation</span></label>
        </section>
        <div class="np-quote-bottom-bar">
            <div><span>Bulk total <strong id="np-quote-footer-bulk">$0.00</strong></span><span class="np-quote-divider"></span><span>Sample total <strong id="np-quote-footer-sample">$0.00</strong></span><span class="np-quote-grand-visually-hidden">Grand total <strong id="np-quote-footer-grand">$0.00</strong></span></div>
            <div class="np-quote-bottom-actions"><button type="submit" formnovalidate formaction="{{ route('admin.bulk-quotes.draft', $bulkQuote) }}" class="btn btn-white" id="np-quote-save">Save draft</button><button class="btn btn-white" type="button" id="np-quote-preview-btn">Preview quotation</button><button class="btn btn-red" type="submit" id="np-quote-send">Submit &amp; send quotation</button></div>
        </div>
    </form>
    <dialog class="np-quote-preview" id="np-quote-preview"><div class="np-quote-preview-inner"><header><h3>Quotation preview</h3><button type="button" id="np-quote-preview-close" class="btn btn-white" aria-label="Close preview">Close</button></header><p class="np-quote-preview-recipient">{{ $bulkQuote->full_name }} · {{ $bulkQuote->email }} · {{ $bulkQuote->reference }}</p><div id="np-quote-preview-content"></div><p class="np-quote-preview-disclaimer">This is an on-screen pricing preview. Publishing requires quotation files and notifies the customer.</p></div></dialog>
</div>
<script type="application/json" id="np-quote-model">{!! json_encode($quoteEditor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
