<x-layouts.admin
    :title="'Contact Request #'.$contact->id"
    eyebrow="Commerce / Contact Requests"
    subtitle="Review the complete customer enquiry and manage its status."
    :compact-header="true"
>
    @php
        $tone = match ($contact->status) {
            \App\Models\ContactMessage::STATUS_NEW => 'bg-blue-50 text-blue-800',
            \App\Models\ContactMessage::STATUS_IN_REVIEW => 'bg-amber-50 text-amber-800',
            \App\Models\ContactMessage::STATUS_RESOLVED => 'bg-emerald-50 text-emerald-800',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.contact-requests.index') }}" class="btn btn-white">← Back to Contact Requests</a>
        <span class="admin-status-pill inline-flex rounded-full px-4 py-2 text-sm font-bold {{ $tone }}">{{ $statuses[$contact->status] ?? str($contact->status)->headline() }}</span>
    </div>

    <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
        <div class="min-w-0 space-y-5">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-black text-brand-ink">Customer Message</h2>
                    <p class="mt-1 text-xs text-slate-500">Submitted {{ \App\Support\ViewerTime::render($contact->created_at, 'd M Y, H:i') }} · Reference #{{ $contact->id }}</p>
                </div>
                <div class="p-5 sm:p-6">
                    <p class="mb-2 text-xs font-black uppercase tracking-wider text-slate-500">Message</p>
                    <div class="whitespace-pre-wrap break-words rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-7 text-slate-800">{{ $contact->message }}</div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                <h2 class="mb-5 text-lg font-black text-brand-ink">Submitted File</h2>
                @if($contact->attachment_path)
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="min-w-0">
                            <p class="break-all font-bold text-brand-ink">{{ $contact->attachment_name ?: 'Customer attachment' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $contact->attachment_mime ?: 'File' }}@if($contact->attachment_size) · {{ number_format($contact->attachment_size / 1024, 1) }} KB @endif</p>
                        </div>
                        <a href="{{ route('admin.contact-requests.attachment', $contact) }}" class="btn btn-navy">Download file</a>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Attachments are kept in private storage and require admin permission to download.</p>
                @else
                    <p class="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">No file was attached to this request.</p>
                @endif
            </section>
        </div>

        <div class="min-w-0 space-y-5">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                <h2 class="mb-5 text-lg font-black text-brand-ink">Contact &amp; Request Information</h2>
                <dl class="space-y-4 text-sm">
                    <div class="border-b border-slate-100 pb-3"><dt class="text-xs font-semibold text-slate-500">Customer</dt><dd class="mt-1 break-words font-bold text-brand-ink">{{ $contact->name }}</dd></div>
                    <div class="border-b border-slate-100 pb-3"><dt class="text-xs font-semibold text-slate-500">Email address</dt><dd class="mt-1 break-all font-medium text-brand-blue"><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></dd></div>
                    <div class="border-b border-slate-100 pb-3"><dt class="text-xs font-semibold text-slate-500">Phone</dt><dd class="mt-1 font-semibold text-brand-ink">{{ $contact->phone ?: 'Not supplied' }}</dd></div>
                    <div class="border-b border-slate-100 pb-3"><dt class="text-xs font-semibold text-slate-500">Topic</dt><dd class="mt-1 font-semibold text-brand-ink">{{ \App\Models\ContactMessage::topics()[$contact->topic] ?? str($contact->topic)->headline() }}</dd></div>
                    <div class="border-b border-slate-100 pb-3"><dt class="text-xs font-semibold text-slate-500">Related order</dt><dd class="mt-1 font-semibold text-brand-ink">{{ $contact->order_number ?: 'Not supplied' }}</dd></div>
                    <div><dt class="text-xs font-semibold text-slate-500">Last updated</dt><dd class="mt-1 font-semibold text-brand-ink">{{ \App\Support\ViewerTime::render($contact->updated_at, 'd M Y, H:i') }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a class="btn btn-white" href="mailto:{{ $contact->email }}?subject={{ rawurlencode('Re: Your NextPlay enquiry #'.$contact->id) }}">Reply by email</a>
                </div>
                <p class="mt-2 text-xs text-slate-500">Opens your email application. Sending a reply is not automatic.</p>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                <h2 class="text-lg font-black text-brand-ink">Review &amp; Follow-up</h2>
                <p class="mb-5 mt-1 text-sm text-slate-500">Keep an internal status and note for your support team.</p>
                @if($canManage)
                    <form method="POST" action="{{ route('admin.contact-requests.update', $contact) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="admin-label" for="contact-request-status">Request status
                            <select class="admin-input" id="contact-request-status" name="status" required>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $contact->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="admin-label" for="contact-request-note">Internal note
                            <textarea class="admin-textarea block min-h-[130px] w-full resize-y" id="contact-request-note" name="internal_note" maxlength="5000" rows="5" placeholder="Add an internal follow-up note (not visible to customers)">{{ old('internal_note', $contact->internal_note) }}</textarea>
                        </label>
                        @error('status')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        @error('internal_note')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-navy w-full sm:w-auto">Save request</button>
                    </form>
                @else
                    <p class="text-sm font-semibold text-slate-700">Status: {{ $statuses[$contact->status] ?? str($contact->status)->headline() }}</p>
                    @if($contact->internal_note)
                        <p class="mt-4 text-xs font-bold text-slate-500">Internal note</p>
                        <p class="mt-1 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-4 text-sm text-slate-700">{{ $contact->internal_note }}</p>
                    @endif
                    <p class="mt-3 text-xs text-slate-500">Read-only access. Ask an administrator for permission to manage requests.</p>
                @endif
            </section>
        </div>
    </div>
</x-layouts.admin>
