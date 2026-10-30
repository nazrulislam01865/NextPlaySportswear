@props(['action', 'reference'])
@php
    $forwardDefaults = app(\App\Services\Email\ForwardingSettings::class)->get();
@endphp
<details class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" id="confirmation-forward" @if($errors->has("forward") || $errors->has("recipients") || $errors->has("message")) open @endif>
    <summary class="cursor-pointer text-sm font-bold text-brand-ink hover:text-brand-red">Forward for confirmation <span class="ml-1 text-xs font-normal text-slate-500">(email details and original files)</span></summary>
    <form method="POST" action="{{ $action }}" class="mt-4 grid gap-3" aria-label="Forward {{ $reference }} for confirmation">
        @csrf
        <label class="block">
            <span class="admin-label">Recipient email(s) <span class="text-red-600">*</span></span>
            <input type="text" name="recipients" class="admin-input w-full" value="{{ old('recipients', $forwardDefaults['recipients']) }}" placeholder="name@example.com" autocomplete="off" required maxlength="1300" aria-describedby="forward-recipient-help">
            <span id="forward-recipient-help" class="mt-1 block text-xs text-slate-500">Use commas to separate up to five addresses. Defaults are managed in Email Customization.</span>
        </label>
        <label class="block">
            <span class="admin-label">Confirmation message</span>
            <textarea name="message" class="admin-textarea w-full" rows="2" maxlength="1000">{{ old('message', $forwardDefaults['message']) }}</textarea>
        </label>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="text-xs text-slate-500">Includes request details, available original uploads and an invoice PDF for orders. E2A: up to 10 files / 9 MB; other providers: up to 30 files / 14 MB. No customer notification.</span>
            <button type="submit" class="btn btn-red">Send for confirmation</button>
        </div>
        @error('forward')<p class="text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
        @error('recipients')<p class="text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
        @error('message')<p class="text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
    </form>
</details>
