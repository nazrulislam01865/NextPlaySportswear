@php
    $isEdit = ($formMethod ?? 'POST') !== 'POST';
    $isActive = (bool) old('is_active', $method->is_active ?? true);
    $isDefault = (bool) old('is_default', $method->is_default ?? false);
    $isOnline = (bool) old('is_online', $method->is_online ?? false);
    $requiresRedirect = (bool) old('requires_provider_redirect', $method->requires_provider_redirect ?? false);
    $requiresManualReview = (bool) old('requires_manual_review', $method->requires_manual_review ?? false);
    $allowsSaved = (bool) old('allows_saved_methods', $method->allows_saved_methods ?? false);
    $showInFooter = (bool) old('show_in_footer', $method->show_in_footer ?? false);
    $footerIconUrl = $method->footerIconUrl();
@endphp

<form method="POST" action="{{ $action }}" class="grid gap-6" enctype="multipart/form-data" novalidate>
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
                <select name="provider" class="admin-input" required>
                    @foreach($providers as $provider)
                        <option value="{{ $provider }}" @selected(old('provider', $method->provider ?? 'manual') === $provider)>{{ str($provider)->headline() }}</option>
                    @endforeach
                </select>
                <span class="mt-2 block text-xs font-medium text-slate-500">Only centrally registered gateways can be selected for new methods. Existing legacy providers remain editable without exposing API secrets.</span>
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

    <x-admin.section-card title="Footer Payment Icon" description="Control whether this active payment method appears in the storefront footer. Upload your own logo or rely on the built-in brand-aware fallback icon.">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.7fr)]" x-data="{ preview: null }">
            <div class="space-y-5">
                <label class="admin-label">
                    Payment icon upload <span class="font-normal text-slate-400">(optional)</span>
                    <input
                        type="file"
                        name="footer_icon"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        class="admin-input h-auto py-3 @error('footer_icon') border-red-400 @enderror"
                        @change="const file = $event.target.files[0]; if (!file) { preview = null; return; } const reader = new FileReader(); reader.onload = event => preview = event.target.result; reader.readAsDataURL(file);"
                    >
                    <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">PNG, JPG, WebP, or AVIF up to 1 MB. A transparent PNG/WebP works best for payment logos. If no image is uploaded, a safe fallback mark is used automatically.</span>
                    @error('footer_icon')<span class="mt-2 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="admin-label">
                    Icon alt text <span class="font-normal text-slate-400">(optional)</span>
                    <input type="text" name="footer_icon_alt" value="{{ old('footer_icon_alt', $method->footer_icon_alt) }}" class="admin-input" maxlength="180" placeholder="{{ $method->name ?: 'Stripe secure payment' }}">
                    <span class="mt-2 block text-xs font-medium text-slate-500">Leave blank to use the payment method name for accessibility.</span>
                </label>

                @if(filled($method->footer_icon_path))
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 text-sm font-bold text-slate-700">
                        <input type="hidden" name="remove_footer_icon" value="0">
                        <input class="mt-0.5" type="checkbox" name="remove_footer_icon" value="1" @checked(old('remove_footer_icon'))>
                        <span>
                            Remove uploaded icon
                            <small class="mt-1 block font-medium leading-5 text-slate-500">The footer will immediately fall back to the built-in payment mark after saving.</small>
                        </span>
                    </label>
                @else
                    <input type="hidden" name="remove_footer_icon" value="0">
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-black uppercase tracking-[.16em] text-slate-500">Footer preview</p>
                <div class="mt-4 flex min-h-24 items-center justify-center rounded-xl bg-[#0d2545] p-5">
                    <div x-show="preview" style="display:none" class="inline-flex h-9 min-w-[56px] items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 shadow-sm">
                        <img :src="preview" alt="Selected payment icon preview" class="max-h-5 max-w-[82px] object-contain">
                    </div>
                    <div x-show="!preview">
                        <x-storefront.payment-mark
                            :name="old('name', $method->name ?: 'Payment method')"
                            :provider="old('provider', $method->provider ?: '')"
                            :code="old('code', $method->code ?: '')"
                            :icon-url="$footerIconUrl"
                            :icon-alt="old('footer_icon_alt', $method->footer_icon_alt)"
                        />
                    </div>
                </div>
                <p class="mt-3 text-xs font-semibold leading-5 text-slate-500">The logo is contained inside a consistent white payment badge so different uploaded logo proportions stay aligned in the footer.</p>
            </div>
        </div>

        <label class="mt-6 flex items-start gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm font-black text-indigo-800">
            <input type="hidden" name="show_in_footer" value="0">
            <input class="mt-0.5 h-5 w-5 rounded border-indigo-300 text-indigo-600" type="checkbox" name="show_in_footer" value="1" @checked($showInFooter)>
            <span>
                Show this payment method in the storefront footer
                <small class="mt-1 block font-medium leading-5 text-indigo-700/80">For accuracy, the footer only renders methods that are both Active and enabled here. Inactive checkout methods are never advertised to customers.</small>
            </span>
        </label>
    </x-admin.section-card>

    <x-admin.section-card title="Amount Rules" description="Restrict payment methods by final checkout grand total after shipping, remote area surcharge, tax, and discounts.">
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

    <x-admin.section-card title="Payment Behavior" description="Security-sensitive behavior is controlled centrally by the registered gateway adapter and cannot be overridden from the database.">
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-semibold leading-6 text-blue-900">
            Stripe is always treated as an online hosted redirect, never as manual review, and saved cards remain disabled until tokenized Stripe vaulting is implemented. Manual providers are always treated as manual-review methods. Changing the provider and saving automatically applies the correct centralized behavior.
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
