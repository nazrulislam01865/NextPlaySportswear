@php
    $isEdit = ($formMethod ?? 'POST') !== 'POST';
    $isActive = (bool) old('is_active', $method->is_active ?? true);
    $isDefault = (bool) old('is_default', $method->is_default ?? false);
    $isOnline = (bool) old('is_online', $method->is_online ?? false);
    $requiresRedirect = (bool) old('requires_provider_redirect', $method->requires_provider_redirect ?? false);
    $requiresManualReview = (bool) old('requires_manual_review', $method->requires_manual_review ?? false);
    $allowsSaved = (bool) old('allows_saved_methods', $method->allows_saved_methods ?? false);
@endphp

<form method="POST" action="{{ $action }}" class="grid gap-6" novalidate>
    @csrf
    @if($isEdit)
        @method($formMethod)
    @endif

    <x-admin.section-card title="Method Details" description="This information is shown to customers on the checkout payment step.">
        <div class="grid gap-5 lg:grid-cols-4">
            <label class="admin-label lg:col-span-2">
                Method name
                <input type="text" name="name" value="{{ old('name', $method->name) }}" class="admin-input" maxlength="160" required placeholder="Credit / Debit Card">
            </label>
            <label class="admin-label">
                Code
                <input type="text" name="code" value="{{ old('code', $method->code) }}" class="admin-input" maxlength="160" placeholder="card">
                <span class="mt-2 block text-xs font-medium text-slate-500">Leave empty while creating to auto-generate from name.</span>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="admin-input" min="0" max="999999">
            </label>
            <label class="admin-label">
                Provider
                <input type="text" name="provider" value="{{ old('provider', $method->provider ?? 'manual') }}" class="admin-input" maxlength="80" placeholder="stripe, paypal, manual">
            </label>
            <label class="admin-label">
                Payment type
                <input type="text" name="payment_type" value="{{ old('payment_type', $method->payment_type ?? 'manual') }}" class="admin-input" maxlength="50" placeholder="card, paypal, invoice">
            </label>
            <label class="admin-label">
                Badge
                <input type="text" name="badge" value="{{ old('badge', $method->badge) }}" class="admin-input" maxlength="80" placeholder="Secure">
            </label>
            <label class="admin-label lg:col-span-4">
                Description
                <textarea name="description" class="admin-textarea min-h-[100px]" maxlength="2000" placeholder="Shown to customers during checkout.">{{ old('description', $method->description) }}</textarea>
            </label>
            <label class="admin-label lg:col-span-4">
                Customer instructions
                <textarea name="instructions" class="admin-textarea min-h-[120px]" maxlength="4000" placeholder="Payment instructions or provider notes. Do not put secret keys here.">{{ old('instructions', $method->instructions) }}</textarea>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Amount Rules" description="Restrict payment methods by final checkout grand total after shipping, rural surcharge, tax, and discounts.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Minimum order total
                <input type="number" name="minimum_total" value="{{ old('minimum_total', $method->minimum_total) }}" class="admin-input" min="0" max="999999999.99" step="0.01" placeholder="Optional">
            </label>
            <label class="admin-label">
                Maximum order total
                <input type="number" name="maximum_total" value="{{ old('maximum_total', $method->maximum_total) }}" class="admin-input" min="0" max="999999999.99" step="0.01" placeholder="Optional">
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Payment Behavior" description="Use provider redirect for hosted payment pages. Use manual review for invoices, bank transfers, quote orders, or payment methods that need admin confirmation.">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-black text-blue-800">
                <input type="hidden" name="is_online" value="0">
                <input type="checkbox" name="is_online" value="1" @checked($isOnline) class="h-5 w-5 rounded border-blue-300 text-blue-600">
                Online payment
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-black text-indigo-800">
                <input type="hidden" name="requires_provider_redirect" value="0">
                <input type="checkbox" name="requires_provider_redirect" value="1" @checked($requiresRedirect) class="h-5 w-5 rounded border-indigo-300 text-indigo-600">
                Provider redirect
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-black text-amber-800">
                <input type="hidden" name="requires_manual_review" value="0">
                <input type="checkbox" name="requires_manual_review" value="1" @checked($requiresManualReview) class="h-5 w-5 rounded border-amber-300 text-amber-600">
                Manual review
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                <input type="hidden" name="allows_saved_methods" value="0">
                <input type="checkbox" name="allows_saved_methods" value="1" @checked($allowsSaved) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                Allow saved cards
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Only active payment methods are shown to customers. The default method is preselected when available.">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                Active
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-brand-red/20 bg-red-50 px-4 py-3 text-sm font-black text-brand-red">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked($isDefault) class="h-5 w-5 rounded border-red-300 text-brand-red">
                Default customer choice
            </label>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Payment Method' : 'Create Payment Method' }}</button>
    </div>
</form>
