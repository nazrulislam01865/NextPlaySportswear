<x-layouts.admin
    title="Content Visibility Settings"
    eyebrow="Email Customization"
    subtitle="Choose which content elements to show in the '{{ $template->name }}' email. Toggle elements on or off to customize what your customers see."
>
    <div
        class="space-y-6"
        x-data="{
            showLogo: {{ !empty($template->visibility_settings['show_logo'] ?? true) ? 'true' : 'false' }},
            showGreeting: {{ !empty($template->visibility_settings['show_greeting'] ?? true) ? 'true' : 'false' }},
            showPreviousEstimate: {{ !empty($template->visibility_settings['show_previous_estimate'] ?? false) ? 'true' : 'false' }},
            showUpdatedEstimate: {{ !empty($template->visibility_settings['show_updated_estimate'] ?? true) ? 'true' : 'false' }},
            showHolidayReason: {{ !empty($template->visibility_settings['show_holiday_reason'] ?? true) ? 'true' : 'false' }},
            showDeliveryCard: {{ !empty($template->visibility_settings['show_delivery_card'] ?? true) ? 'true' : 'false' }},
            showOrderNumber: {{ !empty($template->visibility_settings['show_order_number'] ?? true) ? 'true' : 'false' }},
            showCtaButton: {{ !empty($template->visibility_settings['show_cta_button'] ?? true) ? 'true' : 'false' }},
            showSupportContact: {{ !empty($template->visibility_settings['show_support_contact'] ?? true) ? 'true' : 'false' }},
            showSocialLinks: {{ !empty($template->visibility_settings['show_social_links'] ?? false) ? 'true' : 'false' }},
            showFooterNote: {{ !empty($template->visibility_settings['show_footer_note'] ?? true) ? 'true' : 'false' }}
        }"
    >
        <!-- Breadcrumb & Top Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="hover:text-slate-700">Email Templates</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="hover:text-slate-700">{{ $template->name }}</a>
                <span>/</span>
                <span class="text-brand-ink">Visibility Settings</span>
            </nav>

            <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="btn btn-white text-xs">
                ← Back to Editor
            </a>
        </div>

        <!-- Status & Error Alerts -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-800 shadow-sm space-y-1">
                <p class="font-black">Please fix the following validation errors:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Banner -->
        <div class="flex items-start gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
            <p class="leading-relaxed">Saved and published visibility changes affect the next email action immediately.</p>
        </div>

        <form method="POST" action="{{ route('admin.email-customization.templates.visibility.update', $template->key) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(340px,.8fr)] lg:items-start">
                <!-- Left: Toggle Groups -->
                <div class="space-y-5">
                    <!-- Header Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">🖼</span>
                            <h3 class="text-base font-extrabold text-brand-ink">Header Elements</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Branding and introduction content at the top of the email.</p>

                        <div class="mt-4 divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Logo</p>
                                    <p class="text-[11px] text-slate-400">Display the NextPlay logo at the top of the email.</p>
                                </div>
                                <input type="checkbox" x-model="showLogo" name="show_logo" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Greeting</p>
                                    <p class="text-[11px] text-slate-400">Display a personalized greeting (e.g., Hi {Customer Name}).</p>
                                </div>
                                <input type="checkbox" x-model="showGreeting" name="show_greeting" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Details -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">🚚</span>
                            <h3 class="text-base font-extrabold text-brand-ink">Delivery Details</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Information about the delivery estimate and any changes.</p>

                        <div class="mt-4 divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Previous Estimate</p>
                                    <p class="text-[11px] text-slate-400">Display the previous estimated delivery date.</p>
                                </div>
                                <input type="checkbox" x-model="showPreviousEstimate" name="show_previous_estimate" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Updated Estimate</p>
                                    <p class="text-[11px] text-slate-400">Display the new estimated delivery date.</p>
                                </div>
                                <input type="checkbox" x-model="showUpdatedEstimate" name="show_updated_estimate" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Holiday Reason</p>
                                    <p class="text-[11px] text-slate-400">Display delay note if rescheduled due to holidays.</p>
                                </div>
                                <input type="checkbox" x-model="showHolidayReason" name="show_holiday_reason" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Delivery Card</p>
                                    <p class="text-[11px] text-slate-400">Display the highlighted summary card with the date badge.</p>
                                </div>
                                <input type="checkbox" x-model="showDeliveryCard" name="show_delivery_card" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">📋</span>
                            <h3 class="text-base font-extrabold text-brand-ink">Order Information</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Order-specific identifiers and details.</p>

                        <div class="mt-4 divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Order Number</p>
                                    <p class="text-[11px] text-slate-400">Display the order reference number (e.g., #NP12345).</p>
                                </div>
                                <input type="checkbox" x-model="showOrderNumber" name="show_order_number" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                        </div>
                    </div>

                    <!-- Action Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">🔘</span>
                            <h3 class="text-base font-extrabold text-brand-ink">Action Elements</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Call-to-action buttons for customer interaction.</p>

                        <div class="mt-4 divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show CTA Button</p>
                                    <p class="text-[11px] text-slate-400">Display primary action button linking to order details.</p>
                                </div>
                                <input type="checkbox" x-model="showCtaButton" name="show_cta_button" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">🦶</span>
                            <h3 class="text-base font-extrabold text-brand-ink">Footer Elements</h3>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Support contact and legal notes at the bottom.</p>

                        <div class="mt-4 divide-y divide-slate-100">
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Support Contact</p>
                                    <p class="text-[11px] text-slate-400">Include support email and phone number in footer.</p>
                                </div>
                                <input type="checkbox" x-model="showSupportContact" name="show_support_contact" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Social Links</p>
                                    <p class="text-[11px] text-slate-400">Include social media icons and links configured in branding.</p>
                                </div>
                                <input type="checkbox" x-model="showSocialLinks" name="show_social_links" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-700">Show Footer Note</p>
                                    <p class="text-[11px] text-slate-400">Include copyright and disclaimer text in the footer.</p>
                                </div>
                                <input type="checkbox" x-model="showFooterNote" name="show_footer_note" value="1" class="h-5 w-5 rounded border-slate-300 text-brand-red">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Live Visibility Status Preview -->
                <div class="sticky top-28 space-y-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-brand-ink">Element Visibility Preview</h3>
                        <p class="text-[11px] text-slate-500">Live preview of visible/hidden elements.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card text-xs space-y-4">
                        <!-- Header Preview -->
                        <div class="rounded-xl p-4 text-center text-white" style="background-color: {{ $branding->header_bg_color }};">
                            <span :class="showLogo ? 'bg-emerald-500/20 text-emerald-200 border-emerald-400/40' : 'bg-red-500/20 text-red-200 border-red-400/40'" class="inline-block rounded-full border px-2.5 py-0.5 text-[10px] font-bold" x-text="showLogo ? 'Header Logo (Visible)' : 'Header Logo (Hidden)'"></span>
                            <div class="mt-2 text-base font-black">NEXT<span style="color: {{ $branding->button_color }};">PLAY</span></div>
                        </div>

                        <!-- Content mock -->
                        <div class="space-y-3 p-2">
                            <!-- Greeting item -->
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                                <span class="font-bold text-brand-ink">Hi Jordan,</span>
                                <span :class="showGreeting ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showGreeting ? 'Greeting (Visible)' : 'Greeting (Hidden)'"></span>
                            </div>

                            <!-- Delivery Details -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500">Previous: Oct 28, 2026</span>
                                    <span :class="showPreviousEstimate ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showPreviousEstimate ? 'Previous Date (Visible)' : 'Previous Date (Hidden)'"></span>
                                </div>
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-bold text-slate-700">Updated: Nov 2, 2026</span>
                                    <span :class="showUpdatedEstimate ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showUpdatedEstimate ? 'Updated Date (Visible)' : 'Updated Date (Hidden)'"></span>
                                </div>
                            </div>

                            <!-- Holiday Reason item -->
                            <div class="flex items-center justify-between rounded-lg bg-orange-50 p-2.5 text-[11px] text-orange-900">
                                <span>Delayed due to holiday</span>
                                <span :class="showHolidayReason ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showHolidayReason ? 'Holiday Reason (Visible)' : 'Holiday Reason (Hidden)'"></span>
                            </div>

                            <!-- Order number -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                <span>Order: <strong>#NP-12345</strong></span>
                                <span :class="showOrderNumber ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showOrderNumber ? 'Order Number (Visible)' : 'Order Number (Hidden)'"></span>
                            </div>

                            <!-- CTA Button -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="rounded-lg px-3 py-1 text-white font-bold text-[11px]" style="background-color: {{ $branding->button_color }};">View Order</span>
                                <span :class="showCtaButton ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showCtaButton ? 'CTA Button (Visible)' : 'CTA Button (Hidden)'"></span>
                            </div>

                            <!-- Support Contact -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-[11px]">
                                <span>Support Contact</span>
                                <span :class="showSupportContact ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showSupportContact ? 'Support Contact (Visible)' : 'Support Contact (Hidden)'"></span>
                            </div>

                            <!-- Social Links -->
                            <div class="flex items-center justify-between text-[11px]">
                                <span>Social Links</span>
                                <span :class="showSocialLinks ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200'" class="rounded-full border px-2 py-0.5 text-[10px] font-bold" x-text="showSocialLinks ? 'Social Links (Visible)' : 'Social Links (Hidden)'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="btn btn-white text-xs">Back</a>
                <button type="submit" name="action" value="draft" class="btn btn-white text-xs">Save Draft</button>
                <button
                    type="submit"
                    name="action"
                    value="publish"
                    class="btn btn-orange rounded-xl px-5 py-2.5 text-xs font-black !text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important; border: 1px solid #CF5D38 !important;"
                >
                    Save & Publish
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
