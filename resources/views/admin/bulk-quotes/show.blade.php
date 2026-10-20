<x-layouts.admin
    title="{{ $bulkQuote->reference }}"
    eyebrow="Bulk Quote Request"
    subtitle="Review the complete customer request, manage the sales workflow, and monitor FlowTrack delivery."
    :compact-header="true"
>
    @php
        $adminUser = auth('admin')->user();
        $canViewCustomers = (bool) ($adminUser?->canAdmin('customers.view') ?? false);
        $canManage = (bool) ($adminUser?->canAdmin('orders.manage') ?? false);
        $customerAccount = is_array($bulkQuote->customer_account) ? $bulkQuote->customer_account : [];
        $attachment = is_array($bulkQuote->attachment) ? $bulkQuote->attachment : [];
        $flowTrackResponse = $canManage && is_array($bulkQuote->flowtrack_response) ? $bulkQuote->flowtrack_response : [];
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <x-admin.status-pill :status="$bulkQuote->status" />
            <x-admin.status-pill :status="$bulkQuote->priority" />
            <x-admin.status-pill :status="$bulkQuote->flowtrack_sync_status" />
        </div>
        <div class="responsive-actions [&_.btn]:w-full sm:[&_.btn]:w-auto">
            @if($bulkQuote->user && $canViewCustomers)
                <a class="btn btn-white" href="{{ route('admin.customers.show', $bulkQuote->user) }}">Open Customer</a>
            @endif
            <a class="btn btn-white" href="{{ route('admin.bulk-quotes.index') }}">Back to Bulk Quotes</a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-black">Please correct the highlighted workflow information.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-5">
        @if($canManage)
            <x-admin.section-card title="Quote Workflow" description="Assign ownership and move the request through a controlled quotation pipeline without changing the original customer submission.">
                <form method="POST" action="{{ route('admin.bulk-quotes.update', $bulkQuote) }}">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label>
                            <span class="admin-label">Status</span>
                            <select class="admin-input" name="status" required>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $bulkQuote->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="admin-label">Priority</span>
                            <select class="admin-input" name="priority" required>
                                @foreach($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $bulkQuote->priority) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="admin-label">Assigned To</span>
                            <select class="admin-input" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $bulkQuote->assigned_to) === (string) $assignee->id)>{{ $assignee->name }} · {{ $assignee->adminRoleLabel() }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span class="admin-label">Last Contacted</span>
                            <input class="admin-input" type="datetime-local" data-np-utc-input name="last_contacted_at" value="{{ old('last_contacted_at', $bulkQuote->last_contacted_at?->format('Y-m-d\TH:i')) }}">
                        </label>

                        <label>
                            <span class="admin-label">Quoted Amount</span>
                            <input class="admin-input" type="number" name="quoted_amount" min="0" step="0.01" value="{{ old('quoted_amount', $bulkQuote->quoted_amount) }}" placeholder="0.00">
                        </label>

                        <label>
                            <span class="admin-label">Currency</span>
                            <input class="admin-input uppercase" name="quote_currency" maxlength="3" minlength="3" value="{{ old('quote_currency', $bulkQuote->quote_currency ?: 'USD') }}" required>
                        </label>

                        <label class="md:col-span-2">
                            <span class="admin-label">Internal Note</span>
                            <textarea class="admin-textarea min-h-[96px]" name="admin_note" maxlength="5000" placeholder="Internal context, quotation notes, or next action...">{{ old('admin_note', $bulkQuote->admin_note) }}</textarea>
                        </label>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                        <p class="text-xs leading-5 text-slate-500">Rejected, Converted, and Closed statuses automatically record who closed the workflow and when.</p>
                        <button class="btn btn-red" type="submit">Save Workflow</button>
                    </div>
                </form>
            </x-admin.section-card>
        @else
            <x-admin.section-card title="Quote Workflow" description="Current operational state. Your role has view-only access.">
                <dl class="admin-detail-grid">
                    <x-admin.detail-field label="Status"><x-admin.status-pill :status="$bulkQuote->status" /></x-admin.detail-field>
                    <x-admin.detail-field label="Priority"><x-admin.status-pill :status="$bulkQuote->priority" /></x-admin.detail-field>
                    <x-admin.detail-field label="Assigned To">{{ $bulkQuote->assignedAdmin?->name ?: 'Unassigned' }}</x-admin.detail-field>
                    <x-admin.detail-field label="Quoted Amount">{{ $bulkQuote->quoted_amount !== null ? $bulkQuote->quote_currency.' '.number_format((float) $bulkQuote->quoted_amount, 2) : '—' }}</x-admin.detail-field>
                    <x-admin.detail-field label="Last Contacted">{{ \App\Support\ViewerTime::render($bulkQuote->last_contacted_at, 'M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
                    <x-admin.detail-field label="Closed At">{{ \App\Support\ViewerTime::render($bulkQuote->closed_at, 'M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
                    <x-admin.detail-field label="Internal Note" :wide="true"><div class="whitespace-pre-line">{{ $bulkQuote->admin_note ?: '—' }}</div></x-admin.detail-field>
                </dl>
            </x-admin.section-card>
        @endif

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
                        @php($quoteDetails = (array) $bulkQuote->quotation_details)
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <label><span class="admin-label">Final quantity *</span><input class="admin-input" type="number" min="1" name="quantity" required value="{{ old('quantity', $quoteDetails['quantity'] ?? '') }}"></label>
                            <label><span class="admin-label">Subtotal (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="subtotal" required value="{{ old('subtotal', $quoteDetails['subtotal'] ?? '') }}"></label>
                            <label><span class="admin-label">Customization (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="customization" required value="{{ old('customization', $quoteDetails['customization'] ?? '0') }}"></label>
                            <label><span class="admin-label">Shipping (USD) *</span><input class="admin-input" type="number" step="0.01" min="0" name="shipping" required value="{{ old('shipping', $quoteDetails['shipping'] ?? '0') }}"></label>
                            <label><span class="admin-label">Valid Until *</span><input class="admin-input" type="date" name="valid_until" required min="{{ now()->toDateString() }}" value="{{ old('valid_until', $bulkQuote->quotation_valid_until?->toDateString() ?? now()->addDays(30)->toDateString()) }}"></label>
                            <label><span class="admin-label">Shipping Method</span><input class="admin-input" name="shipping_method" maxlength="160" value="{{ old('shipping_method', $quoteDetails['shipping_method'] ?? '') }}"></label>
                            <label class="md:col-span-2"><span class="admin-label">Payment Terms *</span><input class="admin-input" name="payment_terms" required maxlength="1000" value="{{ old('payment_terms', $quoteDetails['payment_terms'] ?? '') }}" placeholder="Example: 50% deposit; balance before dispatch"></label>
                            <label class="md:col-span-2"><span class="admin-label">Quotation Notes</span><textarea class="admin-textarea min-h-[74px]" name="quotation_notes" maxlength="2500">{{ old('quotation_notes', $quoteDetails['quotation_notes'] ?? '') }}</textarea></label>
                            <label class="md:col-span-2"><span class="admin-label">Quotation Files * (1–8 PDF, images or Excel files, 10 MB each)</span><input class="admin-input" type="file" name="files[]" accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" multiple required></label>
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

        <x-admin.section-card title="Request Summary" description="Primary identifiers and timing for this bulk quotation request.">
            <dl class="admin-detail-grid">
                <x-admin.detail-field label="Reference">{{ $bulkQuote->reference }}</x-admin.detail-field>
                <x-admin.detail-field label="Request Status"><x-admin.status-pill :status="$bulkQuote->status" /></x-admin.detail-field>
                <x-admin.detail-field label="Submitted">{{ \App\Support\ViewerTime::render($bulkQuote->created_at, 'M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
                <x-admin.detail-field label="Last Updated">{{ \App\Support\ViewerTime::render($bulkQuote->updated_at, 'M d, Y · g:i A') ?: '—' }}</x-admin.detail-field>
            </dl>
        </x-admin.section-card>

        <x-admin.section-card title="Customer" description="Contact and registered-account information captured when the request was submitted.">
            <dl class="admin-detail-grid">
                <x-admin.detail-field label="Full Name">{{ $bulkQuote->full_name }}</x-admin.detail-field>
                <x-admin.detail-field label="Organization">{{ $bulkQuote->organization }}</x-admin.detail-field>
                <x-admin.detail-field label="Email"><a class="text-brand-blue hover:underline" href="mailto:{{ $bulkQuote->email }}">{{ $bulkQuote->email }}</a></x-admin.detail-field>
                <x-admin.detail-field label="Phone"><a class="text-brand-blue hover:underline" href="tel:{{ $bulkQuote->phone }}">{{ $bulkQuote->phone }}</a></x-admin.detail-field>
                <x-admin.detail-field label="Registered Account">{{ !empty($customerAccount['is_registered']) || $bulkQuote->user ? 'Yes' : 'No' }}</x-admin.detail-field>
                <x-admin.detail-field label="Source User ID">{{ $customerAccount['source_user_id'] ?? $bulkQuote->user_id ?? '—' }}</x-admin.detail-field>
                @if(!empty($customerAccount['company_name']))
                    <x-admin.detail-field label="Account Company">{{ $customerAccount['company_name'] }}</x-admin.detail-field>
                @endif
                @if(!empty($customerAccount['preferred_sport']))
                    <x-admin.detail-field label="Preferred Sport">{{ $customerAccount['preferred_sport'] }}</x-admin.detail-field>
                @endif
            </dl>
        </x-admin.section-card>

        <x-admin.section-card title="Product Requirements" description="The product, quantity, sizing, budget, artwork, and requested customization details.">
            <dl class="admin-detail-grid">
                <x-admin.detail-field label="Items / Product Type">{{ $bulkQuote->product_type }}</x-admin.detail-field>
                <x-admin.detail-field label="Estimated Quantity">{{ $bulkQuote->estimatedQuantityLabel() }}</x-admin.detail-field>
                <x-admin.detail-field label="Sizes Needed">{{ $bulkQuote->sizes_needed }}</x-admin.detail-field>
                <x-admin.detail-field label="Budget Range">{{ $bulkQuote->budgetRangeLabel() }}</x-admin.detail-field>
                <x-admin.detail-field label="Customization Types" :wide="true">
                    @if($bulkQuote->customizationLabels() !== [])
                        <div class="flex flex-wrap gap-2">
                            @foreach($bulkQuote->customizationLabels() as $customization)
                                <span class="admin-status-pill border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-700">{{ $customization }}</span>
                            @endforeach
                        </div>
                    @else
                        —
                    @endif
                </x-admin.detail-field>
                <x-admin.detail-field label="Artwork / Logo / Customization Details" :wide="true"><div class="whitespace-pre-line">{{ $bulkQuote->artwork_details }}</div></x-admin.detail-field>
            </dl>
        </x-admin.section-card>

        <x-admin.section-card title="Delivery Requirements" description="Destination, shipping preference, and dates supplied by the customer.">
            <dl class="admin-detail-grid">
                <x-admin.detail-field label="Country">{{ $bulkQuote->country }}</x-admin.detail-field>
                <x-admin.detail-field label="State / Province">{{ $bulkQuote->state_province ?: '—' }}</x-admin.detail-field>
                <x-admin.detail-field label="ZIP / Postal Code">{{ $bulkQuote->postal_code ?: '—' }}</x-admin.detail-field>
                <x-admin.detail-field label="Preferred Shipping Method">{{ $bulkQuote->preferredShippingMethodLabel() }}</x-admin.detail-field>
                <x-admin.detail-field label="Needed By">{{ $bulkQuote->needed_by?->format('M d, Y') ?: '—' }}</x-admin.detail-field>
                <x-admin.detail-field label="Event Date">{{ $bulkQuote->event_date?->format('M d, Y') ?: '—' }}</x-admin.detail-field>
                <x-admin.detail-field label="Shipping Address" :wide="true"><div class="whitespace-pre-line">{{ $bulkQuote->shipping_address }}</div></x-admin.detail-field>
            </dl>
        </x-admin.section-card>

        <x-admin.section-card title="Attachment & Customer Notes" description="Original customer-provided file information and additional notes.">
            <dl class="admin-detail-grid">
                <x-admin.detail-field label="Attachment" :wide="true">
                    @if(!empty($attachment['path']))
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <p class="font-medium text-brand-dark">{{ $attachment['original_name'] ?? 'Bulk quote attachment' }}</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $attachment['mime_type'] ?? 'Unknown type' }}
                                    @if(!empty($attachment['size']))
                                        · {{ number_format(((int) $attachment['size']) / 1024, 1) }} KB
                                    @endif
                                </p>
                            </div>
                            <a class="btn btn-white" href="{{ route('admin.bulk-quotes.attachment', $bulkQuote) }}">Download Attachment</a>
                        </div>
                    @else
                        No attachment submitted.
                    @endif
                </x-admin.detail-field>
                <x-admin.detail-field label="Additional Notes" :wide="true"><div class="whitespace-pre-line">{{ $bulkQuote->additional_notes ?: '—' }}</div></x-admin.detail-field>
            </dl>
        </x-admin.section-card>

        <x-admin.integration-sync-panel
            title="FlowTrack Inquiry Sync"
            description="Persistent delivery state for the Inquiry created from this NextPlay bulk quote request."
            :status="$bulkQuote->flowtrack_sync_status"
            :attempts="$bulkQuote->flowtrack_sync_attempts"
            :remote-number="$bulkQuote->flowtrack_inquiry_number"
            remote-number-label="FlowTrack Inquiry Number"
            :remote-id="$bulkQuote->flowtrack_inquiry_id"
            remote-id-label="FlowTrack Inquiry ID"
            :last-attempt-at="$bulkQuote->flowtrack_last_attempt_at"
            :synced-at="$bulkQuote->flowtrack_synced_at"
            :error="$bulkQuote->flowtrack_sync_error"
            :response="$flowTrackResponse"
            :retry-action="route('admin.bulk-quotes.retry-sync', $bulkQuote)"
            :can-retry="$canManage"
            retry-label="Retry Inquiry Sync"
        />

        <x-admin.section-card title="Workflow Activity" description="A compact audit trail of admin workflow changes for this request.">
            <div class="grid gap-3">
                @forelse($activities as $activity)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                            <div>
                                <p class="font-black text-brand-dark">{{ str($activity->action)->headline() }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $activity->actor?->name ?: 'System' }} · {{ \App\Support\ViewerTime::render($activity->occurred_at, 'M d, Y · g:i A') }}</p>
                            </div>
                            @if($activity->to_status)
                                <x-admin.status-pill :status="$activity->to_status" />
                            @endif
                        </div>
                        @if($activity->from_status && $activity->from_status !== $activity->to_status)
                            <p class="mt-3 text-sm text-slate-600">Status changed from <strong>{{ str($activity->from_status)->headline() }}</strong> to <strong>{{ str($activity->to_status)->headline() }}</strong>.</p>
                        @endif
                        @if($activity->note)
                            <p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ $activity->note }}</p>
                        @endif
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No workflow changes have been recorded yet.</p>
                @endforelse
            </div>
        </x-admin.section-card>
    </div>
</x-layouts.admin>
