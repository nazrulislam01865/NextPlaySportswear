@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/admin-bulk-quote-detail.css') }}?v=20261029legible2">
@endpush
<x-layouts.admin title="Bulk Quotation Request" :bulk-quote-detail="true">
    @php
        $adminUser = auth('admin')->user();
        $canViewCustomers = (bool) ($adminUser?->canAdmin('customers.view') ?? false);
        $canManage = (bool) ($adminUser?->canAdmin('orders.manage') ?? false);
        $customerAccount = (array) $bulkQuote->customer_account;
        $attachment = (array) $bulkQuote->attachment;
        $flowTrackResponse = $canManage && is_array($bulkQuote->flowtrack_response) ? $bulkQuote->flowtrack_response : [];
        $offerReady = $canManage && filled($bulkQuote->flowtrack_offer_manifest_hash)
            && hash_equals((string) $bulkQuote->flowtrack_offer_manifest_hash, (string) $bulkQuote->flowtrack_offer_ready_hash);
        $transferredOffers = $offerReady ? $guestFiles->where('category', 'flowtrack_offer') : collect();
        $workflowSteps = [
            ['label' => 'New', 'active' => true],
            ['label' => 'In Review', 'active' => !in_array($bulkQuote->status, ['new'], true)],
            ['label' => 'Quoted', 'active' => $bulkQuote->quotation_version > 0],
            ['label' => 'Converted', 'active' => $bulkQuote->converted_order_id !== null],
        ];
        $currentStep = $bulkQuote->converted_order_id ? 4 : ($bulkQuote->quotation_version > 0 ? 3 : ($bulkQuote->status === 'new' ? 1 : 2));
        $hasQuotation = $bulkQuote->quotation_version > 0;
    @endphp
    <div class="np-bq" x-data="{ tab: (new URLSearchParams(window.location.search)).get('tab') === 'messages' ? 'messages' : ((new URLSearchParams(window.location.search)).get('tab') === 'activity' ? 'activity' : 'details'), syncOpen: false }">
        <div class="np-bq-topline">
            <div class="np-bq-heading-group">
                <nav class="np-bq-breadcrumb" aria-label="Breadcrumb"><button type="button" class="np-bq-mobile-menu lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation">☰</button><a href="{{ route('admin.bulk-quotes.index') }}">Bulk Quote Requests</a><span>/</span><span>Request Details</span></nav>
                <h1>Bulk Quotation Request</h1>
                <p class="np-bq-subtitle">{{ $bulkQuote->reference }} <span>·</span> Submitted {{ \App\Support\ViewerTime::render($bulkQuote->created_at, 'd M Y, g:i A') ?: '—' }}</p>
            </div>
            <div class="np-bq-header-actions">
                <a class="np-bq-back" href="{{ route('admin.bulk-quotes.index') }}"><x-admin.bulk-quote-icon name="arrow" /> Back to requests</a>
                <a href="{{ route('admin.bulk-quotes.download-document', $bulkQuote) }}" class="btn btn-red np-bq-export"><x-admin.bulk-quote-icon name="download" /> Download Request PDF</a>
                <span>Includes request details + all attachments</span>
            </div>
        </div>

        @if($errors->any())
            <div class="np-bq-error" role="alert">
                <strong>Unable to complete the request.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="np-bq-workflow" aria-label="Request workflow">
            <div class="np-bq-workflow-left">
                <div @class(['np-bq-overview', 'np-bq-is-new' => $bulkQuote->status === 'new'])>
                    <div><span class="np-bq-small-label">Current status</span><x-admin.status-pill :status="$bulkQuote->status" /></div>
                    <div><span class="np-bq-small-label">Priority</span><strong>{{ ucfirst((string) $bulkQuote->priority) }}</strong></div>
                    <div><span class="np-bq-small-label">Assigned to</span>
                        @if($canManage)
                            <form class="np-bq-quick-assignee" method="POST" action="{{ route('admin.bulk-quotes.update', $bulkQuote) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $bulkQuote->status }}">
                                <input type="hidden" name="priority" value="{{ $bulkQuote->priority }}">
                                <input type="hidden" name="quote_currency" value="{{ $bulkQuote->quote_currency ?: 'USD' }}">
                                <input type="hidden" name="quoted_amount" value="{{ $bulkQuote->quoted_amount }}">
                                <input type="hidden" name="admin_note" value="{{ $bulkQuote->admin_note }}">
                                <input type="hidden" name="last_contacted_at" value="{{ $bulkQuote->last_contacted_at?->format('Y-m-d\TH:i') }}">
                                <select name="assigned_to" aria-label="Assign request to" onchange="this.form.requestSubmit()">
                                    <option value="">Unassigned</option>
                                    @foreach($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((string) $bulkQuote->assigned_to === (string) $assignee->id)>{{ $assignee->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span>{{ $bulkQuote->assignedAdmin?->name ?: 'Unassigned' }}</span>
                        @endif
                    </div>
                </div>
                <div class="np-bq-actions"><strong>Update request status</strong><div class="np-bq-action-list">
                    @if($canManage)
                        @foreach([['under_review', 'Mark In Review'], ['quotation_sent', 'Mark Quoted'], ['converted', 'Convert to Order'], ['rejected', 'Reject'], ['closed', 'Close Request']] as [$status, $label])
                            @if($status === 'quotation_sent' && !$hasQuotation)
                                <button type="button" class="btn btn-outline np-bq-status-action np-bq-action-{{ str_replace('_', '-', $status) }}" @click="tab='messages'">{{ $label }}</button>
                            @elseif($status === 'converted' && !$bulkQuote->converted_order_id)
                                <button type="button" class="btn btn-outline np-bq-status-action np-bq-action-{{ str_replace('_', '-', $status) }}" disabled title="An order is created after the customer accepts the quotation">{{ $label }}</button>
                            @else
                                <form method="POST" action="{{ route('admin.bulk-quotes.update', $bulkQuote) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <input type="hidden" name="priority" value="{{ $bulkQuote->priority }}">
                                    <input type="hidden" name="assigned_to" value="{{ $bulkQuote->assigned_to }}">
                                    <input type="hidden" name="quote_currency" value="{{ $bulkQuote->quote_currency ?: 'USD' }}">
                                    <input type="hidden" name="quoted_amount" value="{{ $bulkQuote->quoted_amount }}">
                                    <input type="hidden" name="admin_note" value="{{ $bulkQuote->admin_note }}">
                                    <input type="hidden" name="last_contacted_at" value="{{ $bulkQuote->last_contacted_at?->format('Y-m-d\TH:i') }}">
                                    <button class="btn btn-outline np-bq-status-action np-bq-action-{{ str_replace('_', '-', $status) }}" type="submit" @disabled($bulkQuote->status === $status)>{{ $label }}</button>
                                </form>
                            @endif
                        @endforeach
                    @else
                        <span class="np-bq-readonly">View-only access</span>
                    @endif
                </div></div>
            </div>
            <ol class="np-bq-timeline" aria-label="Quotation progress">
                @foreach($workflowSteps as $index => $step)
                    <li class="{{ $index + 1 === $currentStep ? 'is-current' : '' }} {{ $step['active'] ? 'is-complete' : '' }}"><span>{{ $index + 1 }}</span><small>{{ $step['label'] }}</small></li>
                @endforeach
            </ol>
        </section>

        <div class="np-bq-tabs" role="tablist" aria-label="Request sections">
            <button type="button" role="tab" :aria-selected="tab === 'details'" :class="{ 'is-active': tab === 'details' }" @click="tab='details'">Request Details</button>
            <button type="button" role="tab" :aria-selected="tab === 'messages'" :class="{ 'is-active': tab === 'messages' }" @click="tab='messages'">Quotation &amp; Messages</button>
            <button type="button" role="tab" :aria-selected="tab === 'activity'" :class="{ 'is-active': tab === 'activity' }" @click="tab='activity'">Activity Log</button>
        </div>

        <div x-show="tab === 'details'" role="tabpanel" class="np-bq-details">
            <div class="np-bq-column">
                <x-admin.bulk-quote-card title="Product Requirements" icon="cube">
                    <x-admin.bulk-quote-field label="Product">
                        @if(!empty($bulkQuote->product_information))
                            @php
                                $productInfo = $bulkQuote->product_information;
                            @endphp
                            <details class="np-bq-product-extra">
                                <summary aria-label="View selected product information" title="View selected product information">{{ $bulkQuote->product_type }}</summary>
                                <div class="np-bq-extra-fields">
                                    <strong>Selected Product Information</strong>
                                    <div class="np-bq-extra-fields-list">
                        <x-admin.bulk-quote-field label="Product">{{ $productInfo['product_name'] ?? '—' }}</x-admin.bulk-quote-field>
                        <x-admin.bulk-quote-field label="SKU">{{ $productInfo['sku'] ?? '—' }}</x-admin.bulk-quote-field>
                        @foreach(($productInfo['options'] ?? []) as $option)
                            <x-admin.bulk-quote-field :label="$option['label'] ?? 'Option'">{{ implode(', ', (array) ($option['values'] ?? [$option['value'] ?? '—'])) }}</x-admin.bulk-quote-field>
                        @endforeach
                        @foreach(($productInfo['size_groups'] ?? []) as $group)
                            <x-admin.bulk-quote-field :label="'Size & Quantity'.(!empty($group['label']) ? ' — '.$group['label'] : '')">{{ collect($group['sizes'] ?? [])->map(fn ($size) => ($size['size'] ?? 'Size').': '.($size['quantity'] ?? 0))->implode(' · ') ?: '—' }}</x-admin.bulk-quote-field>
                        @endforeach
                        @if(filled($productInfo['notes'] ?? null))
                            <x-admin.bulk-quote-field label="Product Notes">{{ $productInfo['notes'] }}</x-admin.bulk-quote-field>
                        @endif
                        
                                    </div>
                                </div>
                            </details>
                        @else
                            {{ $bulkQuote->product_type }}
                        @endif
                    </x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Quantity">{{ $bulkQuote->estimatedQuantityLabel() }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Size">{{ $bulkQuote->sizes_needed ?: '—' }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Budget">{{ $bulkQuote->budgetRangeLabel() }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Customization">{{ implode(', ', $bulkQuote->customizationLabels()) ?: '—' }}</x-admin.bulk-quote-field>
                    @php
                        // Only accept a literal hex code already supplied in the customer's wording.
                        $artworkColor = preg_match('/#[0-9a-fA-F]{6}\b/', (string) $bulkQuote->artwork_details, $artworkColorMatch)
                            ? $artworkColorMatch[0] : null;
                    @endphp
                    <div class="np-bq-artwork-detail"><strong>Artwork / Logo Details</strong><span class="np-bq-artwork-copy">
                        @if($artworkColor)
                            <i class="np-bq-color-chip" style="background-color: {{ $artworkColor }}" aria-label="Artwork color {{ $artworkColor }}"></i>
                        @endif
                        {{ $bulkQuote->artwork_details ?: '—' }}
                    </span></div>
                </x-admin.bulk-quote-card>

                <x-admin.bulk-quote-card title="Delivery Requirements" icon="truck">
                    <x-admin.bulk-quote-field label="Needed by">{{ $bulkQuote->needed_by?->format('d M Y') ?: '—' }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Event date">{{ $bulkQuote->event_date?->format('d M Y') ?: '—' }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Shipping method">{{ $bulkQuote->preferredShippingMethodLabel() }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Destination">{{ $bulkQuote->country }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="State / Province">{{ $bulkQuote->state_province ?: '—' }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="ZIP / Postal code">{{ $bulkQuote->postal_code ?: '—' }} <x-admin.remote-area-flag :remote="$bulkQuote->is_remote_area" /></x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Shipping address">{{ $bulkQuote->shipping_address ?: '—' }}</x-admin.bulk-quote-field>
                </x-admin.bulk-quote-card>

                <x-admin.bulk-quote-card title="Attachments & Customer Notes" icon="paperclip">
                    <div class="np-bq-attachments-label">Attachments</div>
                    @if(!empty($attachment['path']))
                        <div class="np-bq-file"><div class="np-bq-file-symbol">{{ strtoupper(substr(pathinfo($attachment['original_name'] ?? 'file', PATHINFO_EXTENSION), 0, 4)) ?: 'FILE' }}</div><div class="np-bq-file-name"><strong>{{ $attachment['original_name'] ?? 'Customer attachment' }}</strong><small>{{ strtoupper(pathinfo($attachment['original_name'] ?? 'file', PATHINFO_EXTENSION)) }}
                                @if(!empty($attachment['size']))
                                    · {{ number_format((int) $attachment['size'] / 1048576, 2) }} MB
                                @endif
                            </small></div><div class="np-bq-file-actions"><a class="btn btn-white" href="{{ route('admin.bulk-quotes.attachment', $bulkQuote) }}?preview=1" target="_blank" rel="noopener"><x-admin.bulk-quote-icon name="eye" /> Preview</a><a class="btn btn-white" href="{{ route('admin.bulk-quotes.attachment', $bulkQuote) }}"><x-admin.bulk-quote-icon name="download" /> Download</a></div></div>
                        <p class="np-bq-info"><span class="np-bq-info-mark" aria-hidden="true">i</span>Request PDF includes all request information and attached files.</p>
                    @else
                        <p class="np-bq-muted">No attachment submitted.</p>
                    @endif
                    <div class="np-bq-notes"><strong>Customer notes</strong><p>{{ $bulkQuote->additional_notes ?: 'No additional notes provided.' }}</p></div>
                </x-admin.bulk-quote-card>
            </div>

            <div class="np-bq-column">
                <x-admin.bulk-quote-card title="Customer" icon="user">
                    <div class="np-bq-customer-tag">{{ !empty($customerAccount['is_registered']) || $bulkQuote->user ? 'Registered customer' : 'Guest customer' }}</div>
                    <x-admin.bulk-quote-field label="Name">{{ $bulkQuote->full_name }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Organization">{{ $bulkQuote->organization ?: '—' }}</x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Email"><a href="mailto:{{ $bulkQuote->email }}">{{ $bulkQuote->email }}</a></x-admin.bulk-quote-field>
                    <x-admin.bulk-quote-field label="Phone"><a href="tel:{{ $bulkQuote->phone }}">{{ $bulkQuote->phone ?: '—' }}</a></x-admin.bulk-quote-field>
                    @if(!empty($customerAccount['company_name']))
                        <x-admin.bulk-quote-field label="Account company">{{ $customerAccount['company_name'] }}</x-admin.bulk-quote-field>
                    @endif
                    @if(!empty($customerAccount['preferred_sport']))
                        <x-admin.bulk-quote-field label="Preferred sport">{{ $customerAccount['preferred_sport'] }}</x-admin.bulk-quote-field>
                    @endif
                    @if($canViewCustomers && $bulkQuote->user)
                        <a class="np-bq-inline-link" href="{{ route('admin.customers.show', $bulkQuote->user) }}">View customer account</a>
                    @endif
                </x-admin.bulk-quote-card>

                <x-admin.bulk-quote-card title="Internal Management" icon="settings">
                    @if($canManage)
                        <form method="POST" action="{{ route('admin.bulk-quotes.update', $bulkQuote) }}" class="np-bq-management-form">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $bulkQuote->status }}">
                            <input type="hidden" name="quote_currency" value="{{ $bulkQuote->quote_currency ?: 'USD' }}">
                            <input type="hidden" name="quoted_amount" value="{{ $bulkQuote->quoted_amount }}">
                            <div class="np-bq-form-row"><label><span>Assigned to</span><select class="admin-input" name="assigned_to"><option value="">Unassigned</option>
                                @foreach($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $bulkQuote->assigned_to) === (string) $assignee->id)>{{ $assignee->name }}</option>
                                @endforeach
                            </select></label>
                            <label><span>Priority</span><select class="admin-input" name="priority">
                                @foreach($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $bulkQuote->priority) === $value)>{{ $label }}</option>
                                @endforeach
                            </select></label></div>
                            <div class="np-bq-last-contact" x-data="{ editContact: false }">
                                <span>Last contacted</span>
                                <button type="button" class="np-bq-contact-display" @click="editContact = !editContact" :aria-expanded="editContact" title="Edit last contacted date">{{ \App\Support\ViewerTime::render($bulkQuote->last_contacted_at, 'd M Y, g:i A') ?: 'Not contacted' }}</button>
                                <input x-show="editContact" x-cloak class="admin-input" type="datetime-local" data-np-utc-input name="last_contacted_at" value="{{ old('last_contacted_at', $bulkQuote->last_contacted_at?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <label class="np-bq-full-label"><span>Internal note</span><textarea class="admin-textarea" name="admin_note" maxlength="5000" rows="3" placeholder="Add a note for your team...">{{ old('admin_note', $bulkQuote->admin_note) }}</textarea></label>
                            <button type="submit" class="btn btn-red">Save Changes</button>
                        </form>
                    @else
                        <x-admin.bulk-quote-field label="Assigned to">{{ $bulkQuote->assignedAdmin?->name ?: 'Unassigned' }}</x-admin.bulk-quote-field>
                        <x-admin.bulk-quote-field label="Priority">{{ ucfirst((string) $bulkQuote->priority) }}</x-admin.bulk-quote-field>
                        <x-admin.bulk-quote-field label="Last contacted">{{ \App\Support\ViewerTime::render($bulkQuote->last_contacted_at, 'd M Y, g:i A') ?: 'Not contacted' }}</x-admin.bulk-quote-field>
                        <x-admin.bulk-quote-field label="Internal note">{{ $bulkQuote->admin_note ?: '—' }}</x-admin.bulk-quote-field>
                    @endif
                </x-admin.bulk-quote-card>

                <x-admin.bulk-quote-card title="Quotation" icon="file" class="np-bq-quotation-card">
                    @if(!$hasQuotation)
                        <div class="np-bq-empty-quote">
                            <span class="np-bq-empty-icon"><x-admin.bulk-quote-icon name="file" /></span>
                            <strong>No quotation sent yet.</strong>
                            <p>Create and send a quotation from the Quotation &amp; Messages tab.</p>
                            @if($canManage)
                                <button type="button" @click="tab='messages'" class="btn btn-outline">Prepare Quotation</button>
                            @endif
                        </div>
                    @else
                        <div class="np-bq-empty-quote">
                            <span class="np-bq-empty-icon"><x-admin.bulk-quote-icon name="file" /></span>
                            <strong>Quotation version {{ $bulkQuote->quotation_version }}</strong>
                            <p>Sent {{ \App\Support\ViewerTime::render($bulkQuote->quotation_sent_at, 'd M Y, g:i A') ?: '—' }}</p>
                            <button type="button" @click="tab='messages'" class="btn btn-outline">View Quotation</button>
                            @if($bulkQuote->convertedOrder)
                                <a class="np-bq-inline-link" href="{{ route('admin.orders.show', $bulkQuote->convertedOrder) }}">Open order {{ $bulkQuote->convertedOrder->order_number }}</a>
                            @endif
                        </div>
                    @endif
                </x-admin.bulk-quote-card>
            </div>
            <div class="np-bq-bottom-row">
                <section class="np-bq-collapsible"><button type="button" @click="syncOpen=!syncOpen" :aria-expanded="syncOpen"><span><x-admin.bulk-quote-icon name="refresh" /><strong>FlowTrack Sync</strong><x-admin.status-pill :status="$bulkQuote->flowtrack_sync_status" /></span><x-admin.bulk-quote-icon name="chevron" /></button><div x-show="syncOpen" x-cloak class="np-bq-sync-content"><x-admin.integration-sync-panel title="FlowTrack Inquiry Sync" description="Delivery state for the connected inquiry." :status="$bulkQuote->flowtrack_sync_status" :attempts="$bulkQuote->flowtrack_sync_attempts" :remote-number="$bulkQuote->flowtrack_inquiry_number" remote-number-label="FlowTrack Inquiry Number" :remote-id="$bulkQuote->flowtrack_inquiry_id" remote-id-label="FlowTrack Inquiry ID" :last-attempt-at="$bulkQuote->flowtrack_last_attempt_at" :synced-at="$bulkQuote->flowtrack_synced_at" :error="$bulkQuote->flowtrack_sync_error" :response="$flowTrackResponse" :retry-action="route('admin.bulk-quotes.retry-sync', $bulkQuote)" :can-retry="$canManage" retry-label="Retry Inquiry Sync" /></div></section>
                <button type="button" class="np-bq-activity-strip" @click="tab='activity'">
                    <span>
                        <x-admin.bulk-quote-icon name="clock" />
                        <strong>Recent Activity</strong>
                        @if($activities->isNotEmpty())
                            <span class="np-bq-activity-dot"></span>
                            <small>{{ str($activities->first()->action)->headline() }} · {{ \App\Support\ViewerTime::render($activities->first()->occurred_at, 'd M Y, g:i A') }}</small>
                        @else
                            <small>Request submitted · {{ \App\Support\ViewerTime::render($bulkQuote->created_at, 'd M Y, g:i A') }}</small>
                        @endif
                    </span>
                    <span>View full activity →</span>
                </button>
            </div>
        </div>

        <div x-show="tab === 'messages'" x-cloak class="np-bq-tab-content" role="tabpanel">

            @if($bulkQuote->guest_decision)
                <x-admin.section-card title="Customer Decision" description="The customer's latest response to your published quotation.">
                    <p class="text-sm font-semibold text-brand-dark">{{ str($bulkQuote->guest_decision)->headline() }}</p>
                    @if($bulkQuote->converted_order_id)
                        <a class="btn btn-white mt-3" href="{{ route('admin.orders.show', $bulkQuote->convertedOrder) }}">View Created Order {{ $bulkQuote->convertedOrder?->order_number }}</a>
                        <p class="mt-2 text-xs text-slate-500">This is a manual quotation invoice order. Payment has not been collected yet.</p>
                    @endif
                </x-admin.section-card>
            @endif
            @if($canManage)
                <x-admin.section-card title="Prepare & Send Quotation" description="Upload your finalized quotation and publish it to the customer quote pages. Each publication creates a new version; previous files remain in the admin history.">
                    <form action="{{ route('admin.bulk-quotes.publish', $bulkQuote) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @php
                            $quoteDetails = (array) $bulkQuote->quotation_details;
                        @endphp
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <label><span class="admin-label">Final quantity *</span><input class="admin-input" type="number" min="1" name="quantity" required value="{{ old('quantity', $quoteDetails['quantity'] ?? '') }}"></label>
                            <label><span class="admin-label">Subtotal (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="subtotal" required value="{{ old('subtotal', $quoteDetails['subtotal'] ?? '') }}"></label>
                            <label><span class="admin-label">Customization (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="customization" required value="{{ old('customization', $quoteDetails['customization'] ?? '0') }}"></label>
                            <label><span class="admin-label">Shipping (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="shipping" required value="{{ old('shipping', $quoteDetails['shipping'] ?? '0') }}"></label>
                            <label><span class="admin-label">Valid Until *</span><input class="admin-input" type="date" name="valid_until" required min="{{ now()->toDateString() }}" value="{{ old('valid_until', $bulkQuote->quotation_valid_until?->toDateString() ?? now()->addDays(30)->toDateString()) }}"></label>
                            <label><span class="admin-label">Shipping Method</span><input class="admin-input" name="shipping_method" maxlength="160" value="{{ old('shipping_method', $quoteDetails['shipping_method'] ?? '') }}"></label>
                            <label class="md:col-span-2"><span class="admin-label">Payment Terms *</span><input class="admin-input" name="payment_terms" required maxlength="1000" value="{{ old('payment_terms', $quoteDetails['payment_terms'] ?? '') }}" placeholder="Example: 50% deposit; balance before dispatch"></label>
                            <label class="md:col-span-2"><span class="admin-label">Quotation Notes</span><textarea class="admin-textarea min-h-[74px]" name="quotation_notes" maxlength="2500">{{ old('quotation_notes', $quoteDetails['quotation_notes'] ?? '') }}</textarea></label>
                            <div class="md:col-span-2 space-y-2">
                                @if($canManage && filled($bulkQuote->flowtrack_offer_manifest_hash) && ! $offerReady)
                                    <p class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">FlowTracker quotation files are being transferred. You can publish after all files arrive, or upload a quotation manually.</p>
                                @endif
                                @if($transferredOffers->isNotEmpty())
                                    <label class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-brand-dark">
                                        <input type="checkbox" name="use_flowtrack_offer" value="1" @checked(old('use_flowtrack_offer', '1') === '1')>
                                        <span><strong>Use offer files received from FlowTracker ({{ $transferredOffers->count() }})</strong>
                                            <span class="mt-1 block text-xs text-slate-600">These private files came from the completed “Send Offer to Client” task. They will be copied into the quotation when you publish, without another upload.</span>
                                        </span>
                                    </label>
                                    <div class="space-y-1 pl-3 text-xs text-slate-600">
                                        @foreach($transferredOffers as $offer)
                                            <a class="block underline" href="{{ route('admin.bulk-quotes.file', [$bulkQuote, $offer]) }}">{{ $offer->original_name }}</a>
                                        @endforeach
                                    </div>
                                @endif
                                <label><span class="admin-label">{{ $transferredOffers->isNotEmpty() ? 'Optional additional quotation files' : 'Quotation Files *' }} (1–8 PDF, images or Excel files, 10 MB each)</span>
                                    <input class="admin-input" type="file" name="files[]" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" multiple
                                        @if($transferredOffers->isEmpty())
                                            required
                                        @endif
                                    >
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4"><p class="text-xs text-slate-500">Current version: {{ $bulkQuote->quotation_version }} · New version: {{ $bulkQuote->quotation_version + 1 }} · Publish notifies the customer's original email.</p><button class="btn btn-red" type="submit">Publish Quotation & Notify Customer</button></div>
                    </form>
                </x-admin.section-card>
            @endif
            <x-admin.section-card title="Quotation Documents & Customer Messages" description="Private quotation files and customer feedback are linked to this request, visible to authorized administrators only.">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 font-semibold text-brand-dark">Documents</h3>
                        <div class="space-y-2">
                            @forelse($guestFiles as $file)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 p-3 text-sm">
                                    <div>
                                        <p class="font-medium">{{ $file->original_name }}</p>
                                        <small class="text-slate-500">{{ ucfirst($file->category) }} · Version {{ $file->version }} · {{ number_format($file->size / 1024) }} KB</small>
                                    </div>
                                    <a class="btn btn-white" href="{{ route('admin.bulk-quotes.file', [$bulkQuote, $file]) }}">Download</a>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No quotation files have been uploaded.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold text-brand-dark">Conversation</h3>
                        <div class="max-h-96 space-y-2 overflow-y-auto">
                            @forelse($guestMessages as $message)
                                <div class="rounded-lg border border-slate-200 p-3 text-sm">
                                    <strong>{{ $message->sender === 'admin' ? ($message->actor?->name ?: 'NextPlay Sales') : ($message->actor?->name ?: ($bulkQuote->user_id ? 'Registered Customer' : 'Guest Customer')) }}</strong>
                                    <small class="ml-2 text-slate-500">{{ \App\Support\ViewerTime::render($message->created_at, 'M j, Y · g:i A') }}</small>
                                    <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                                    @if($message->change_types)
                                        <p class="mt-1 text-slate-500">Change types: {{ implode(', ', $message->change_types) }}</p>
                                    @endif
                                    @foreach($message->files as $file)
                                        <a class="mt-1 block text-blue-700 underline" href="{{ route('admin.bulk-quotes.file', [$bulkQuote, $file]) }}">{{ $file->original_name }}</a>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">No messages yet.</p>
                            @endforelse
                        </div>
                        @if($canManage)
                            <form class="mt-3" method="POST" action="{{ route('admin.bulk-quotes.message', $bulkQuote) }}">
                                @csrf
                                <label>
                                    <span class="admin-label">Reply to Customer</span>
                                    <textarea class="admin-textarea min-h-[86px]" name="body" maxlength="3000" required placeholder="Your message will appear in the customer's quote history and trigger an email notification."></textarea>
                                </label>
                                <button class="btn btn-red mt-2" type="submit">Send Message</button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-admin.section-card>
        </div>
        <div x-show="tab === 'activity'" x-cloak class="np-bq-tab-content" role="tabpanel">
            <x-admin.bulk-quote-card title="Workflow Activity" icon="clock">
                <x-admin.bulk-quote-field label="Reference">{{ $bulkQuote->reference }}</x-admin.bulk-quote-field>
                <x-admin.bulk-quote-field label="Submitted">{{ \App\Support\ViewerTime::render($bulkQuote->created_at, 'd M Y, g:i A') ?: '—' }}</x-admin.bulk-quote-field>
                <x-admin.bulk-quote-field label="Last updated">{{ \App\Support\ViewerTime::render($bulkQuote->updated_at, 'd M Y, g:i A') ?: '—' }}</x-admin.bulk-quote-field>
                @foreach($activities as $activity)
                    <div class="np-bq-activity-entry">
                        <strong>{{ str($activity->action)->headline() }}</strong>
                        <small>{{ $activity->actor?->name ?: 'System' }} · {{ \App\Support\ViewerTime::render($activity->occurred_at, 'd M Y, g:i A') }}</small>
                        @if($activity->from_status && $activity->from_status !== $activity->to_status)
                            <p>Changed from {{ str($activity->from_status)->headline() }} to {{ str($activity->to_status)->headline() }}.</p>
                        @endif
                        @if($activity->note)
                            <p>{{ $activity->note }}</p>
                        @endif
                    </div>
                @endforeach
            </x-admin.bulk-quote-card>
        </div>
    </div>
</x-layouts.admin>
