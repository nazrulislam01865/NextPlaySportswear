<x-layouts.admin title="Confirmation Forwarding" subtitle="Automatically email new orders and bulk quotes to your saved recipients, including request details and files.">
    <div class="max-w-3xl space-y-5">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.email-customization.templates.index') }}" class="text-sm font-bold text-brand-ink hover:text-brand-red">← Email Customization</a>
        </div>
        @if($providerWarning)
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900" role="alert">{{ $providerWarning }}</p>
        @endif
        @if(session('status'))<p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('status') }}</p>@endif
        @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.email-customization.forwarding.update') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            @csrf @method('PUT')
            <div><h2 class="text-lg font-black text-brand-ink">Forward for confirmation</h2>
                <p class="mt-1 text-sm text-slate-600">Once recipients are saved, each newly submitted order or bulk quote is automatically forwarded for confirmation with its details and available files. You can also forward existing requests manually from their details pages.</p></div>
            <label class="block">
                <span class="admin-label">Default recipient email(s)</span>
                <textarea class="admin-textarea w-full" name="recipients" rows="2" maxlength="1300" placeholder="production@example.com, manager@example.com">{{ old('recipients', $settings['recipients']) }}</textarea>
                <span class="mt-1 block text-xs text-slate-500">Enter up to five addresses, separated by commas. Every new order and bulk quote will be sent to these addresses. Leave blank to turn automatic forwarding off.</span>
            </label>
            <label class="block"><span class="admin-label">Default confirmation message</span>
                <textarea class="admin-textarea w-full" name="message" rows="3" maxlength="1000" required>{{ old('message', $settings['message']) }}</textarea></label>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500">Stored in private application file storage. No database tables or migrations.</p>
                <button type="submit" class="btn btn-red">Save forwarding defaults</button>
            </div>
        </form>
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
            <p class="font-bold text-brand-ink">Manual forwarding for existing requests</p>
            <p class="mt-1">New requests are forwarded automatically to the saved addresses. To resend an existing request or choose different recipients, open its details page and select <strong>Send for confirmation</strong>.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-bold text-brand-ink underline hover:text-brand-red">View orders</a>
                <a href="{{ route('admin.bulk-quotes.index') }}" class="text-sm font-bold text-brand-ink underline hover:text-brand-red">View bulk quotes</a>
            </div>
        </div>
    </div>
</x-layouts.admin>
