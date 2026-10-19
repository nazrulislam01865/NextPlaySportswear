<div class="grid min-w-0 gap-3 xl:grid-cols-2">
    <div class="min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h4 class="text-sm font-semibold text-brand-ink">Latest proof · v{{ $proof->version }}</h4>
            <span class="text-xs text-slate-500">{{ $proof->created_at?->format('M j, Y · g:i A') }}</span>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            <a class="btn btn-white" href="{{ route('admin.orders.artwork-proofs.file', [$order, $proof]) }}" target="_blank" rel="noopener noreferrer">View Proof</a>
            <a class="btn btn-white" href="{{ route('admin.orders.artwork-proofs.file', [$order, $proof]) }}?download=1">Download Proof</a>
            @if($proof->reference_path)
                <a class="btn btn-white" href="{{ route('admin.orders.artwork-proofs.file', [$order, $proof]) }}?type=reference&amp;download=1">Customer Reference</a>
            @endif
        </div>
        @if(in_array($proof->status, ['needs_revision', 'rejected'], true))
            <div class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                {{ $proof->status === 'rejected' ? 'Customer rejected this version. Review their feedback before publishing a replacement.' : 'Customer requested a revision. Review their feedback before publishing the next version.' }}
            </div>
        @endif
        <div class="mt-3 space-y-2 text-sm">
            @if($proof->customer_feedback)
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <h5 class="mb-1 font-semibold text-brand-ink">Customer Feedback</h5>
                    <p class="whitespace-pre-wrap break-words leading-6 text-slate-700">{{ $proof->customer_feedback }}</p>
                </div>
            @else
                <p class="text-slate-500">No customer feedback submitted for this version.</p>
            @endif
            @if($proof->priority)
                <p class="text-slate-600"><strong>Priority:</strong> {{ ucfirst($proof->priority) }}@if($proof->change_types) · <strong>Requested changes:</strong> {{ implode(', ', $proof->change_types) }}@endif</p>
            @endif
            @if($proof->designer_notes)
                <div><h5 class="font-semibold text-brand-ink">Notes sent with proof</h5><p class="mt-1 whitespace-pre-wrap break-words text-slate-600">{{ $proof->designer_notes }}</p></div>
            @endif
        </div>
    </div>

    <div class="min-w-0 rounded-lg border border-slate-200 bg-white p-3" aria-label="Customer and design team messages">
        <h4 class="text-sm font-semibold text-brand-ink">Conversation</h4>
        <p class="mt-1 text-xs text-slate-500">Customer-visible messages for version {{ $proof->version }}.</p>
        <div class="mt-2 max-h-56 space-y-2 overflow-y-auto" aria-live="polite">
            @forelse($proof->messages as $message)
                <div @class([
                    'rounded-lg border px-3 py-2 text-sm',
                    'border-blue-100 bg-blue-50' => $message->sender_role === 'designer',
                    'border-slate-200 bg-slate-50' => $message->sender_role !== 'designer',
                ])>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <strong class="text-xs text-brand-ink">{{ $message->sender_role === 'designer' ? 'Design Team' : 'Customer' }}</strong>
                        <span class="text-xs text-slate-500">{{ $message->created_at?->format('M j, Y · g:i A') }}</span>
                    </div>
                    <p class="mt-1 whitespace-pre-wrap break-words leading-5 text-slate-700">{{ $message->message }}</p>
                </div>
            @empty
                <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">No messages for this version yet.</p>
            @endforelse
        </div>
        @if($canManageOrders)
            <form method="POST" action="{{ route('admin.orders.artwork-proofs.reply', [$order, $proof]) }}" class="mt-2 border-t border-slate-200 pt-2">
                @csrf
                <label for="proof-reply-{{ $proof->id }}" class="admin-label">Reply to customer</label>
                <textarea id="proof-reply-{{ $proof->id }}" class="admin-textarea" name="message" rows="2" minlength="5" maxlength="2000" required placeholder="Write a clear response to the customer's feedback..." style="min-height: 70px">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p>@enderror
                <div class="mt-2 flex justify-end"><button type="submit" class="btn btn-red">Send Reply</button></div>
            </form>
        @endif
    </div>
</div>
