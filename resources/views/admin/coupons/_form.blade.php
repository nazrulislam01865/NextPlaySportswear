@php
    $isEdit = $coupon->exists;
    $checked = static fn (string $field, bool $default = false): bool => old($field) !== null
        ? filter_var(old($field), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($coupon->{$field} ?? $default);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
    @endif

    <x-admin.section-card title="Coupon Basics" description="These values are used by the cart AJAX validator and again during final checkout placement.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <label class="admin-label xl:col-span-2">
                Coupon name
                <input type="text" name="name" value="{{ old('name', $coupon->name) }}" class="admin-input" maxlength="160" placeholder="Team Order 10% Off" required>
            </label>
            <label class="admin-label">
                Coupon code
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="admin-input uppercase" maxlength="60" placeholder="TEAM10" pattern="[A-Za-z0-9_-]+" required>
                <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">Letters, numbers, dash, and underscore only.</span>
            </label>
            <label class="admin-label sm:col-span-2 xl:col-span-3">
                Description
                <textarea name="description" class="admin-textarea" maxlength="2000" placeholder="Internal note or customer-facing explanation.">{{ old('description', $coupon->description) }}</textarea>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Discount Rules" description="Set the discount type, value, minimum cart amount, and maximum discount cap.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Discount type
                <select name="discount_type" class="admin-input" required>
                    <option value="percentage" @selected(old('discount_type', $coupon->discount_type ?: 'percentage') === 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('discount_type', $coupon->discount_type) === 'fixed')>Fixed amount</option>
                </select>
            </label>
            <label class="admin-label">
                Discount value
                <input type="number" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" class="admin-input" min="0.01" max="999999.99" step="0.01" required>
                <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">Use 10 for 10%, or 25 for $25.</span>
            </label>
            <label class="admin-label">
                Minimum subtotal
                <input type="number" name="minimum_subtotal" value="{{ old('minimum_subtotal', $coupon->minimum_subtotal ?? 0) }}" class="admin-input" min="0" max="999999.99" step="0.01">
            </label>
            <label class="admin-label">
                Maximum discount
                <input type="number" name="maximum_discount" value="{{ old('maximum_discount', $coupon->maximum_discount) }}" class="admin-input" min="0" max="999999.99" step="0.01" placeholder="75.00">
                <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">Required for percentage coupons.</span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Usage & Schedule" description="Limit total usage, per-customer usage, and active dates.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-5">
            <label class="admin-label">
                Total usage limit
                <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="admin-input" min="1" max="1000000" placeholder="Unlimited">
            </label>
            <label class="admin-label">
                Per-customer limit
                <input type="number" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $coupon->usage_limit_per_customer) }}" class="admin-input" min="1" max="1000000" placeholder="Unlimited">
            </label>
            <label class="admin-label">
                Starts at
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="admin-input">
            </label>
            <label class="admin-label">
                Expires at
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" class="admin-input">
            </label>
            <label class="flex items-center gap-3 self-end rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($checked('is_active', true)) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                Active
            </label>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Coupon' : 'Create Coupon' }}</button>
    </div>
</form>
