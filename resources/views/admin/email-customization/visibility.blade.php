<x-layouts.admin
    title="Content Visibility Settings"
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
        <!-- Top Action Bar -->
        <div class="flex items-center justify-end">
            <a
                href="{{ route('admin.email-customization.templates.edit', $template->key) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Back to Template Editor</span>
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

        <!-- Notification Banner (Matching mno.png) -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-start justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50/90 p-4 text-xs font-bold text-amber-950 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-[#c2410c] text-xs font-black text-white shrink-0 mt-0.5">
                    i
                </span>
                <p class="leading-relaxed font-semibold text-amber-900">Saved and published visibility changes affect the next email action.</p>
            </div>
            <button type="button" @click="dismissed = true" class="text-amber-700 hover:text-amber-950 transition text-sm font-bold">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.email-customization.templates.visibility.update', $template->key) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)] lg:items-start">
                <!-- Left: Toggle Groups (Matching mno.png) -->
                <div class="space-y-4">
                    <!-- 1. Header Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-xl">🖼</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-brand-ink">Header Elements</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Branding and introduction content at the top of the email.</p>
                            </div>
                        </div>

                        <div class="space-y-3.5 pl-8">
                            <!-- Show Logo -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Logo</p>
                                    <p class="text-[11px] text-slate-400">Display the NextPlay logo at the top of the email.</p>
                                </div>
                                <input type="hidden" name="show_logo" :value="showLogo ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showLogo = !showLogo"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showLogo ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showLogo"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showLogo ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Greeting -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Greeting</p>
                                    <p class="text-[11px] text-slate-400">Display a personalized greeting (e.g., Hi {Customer Name}).</p>
                                </div>
                                <input type="hidden" name="show_greeting" :value="showGreeting ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showGreeting = !showGreeting"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showGreeting ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showGreeting"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showGreeting ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Delivery Details -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-xl">🚚</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-brand-ink">Delivery Details</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Information about the delivery estimate and any changes.</p>
                            </div>
                        </div>

                        <div class="space-y-3.5 pl-8">
                            <!-- Show Previous Estimate -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Previous Estimate</p>
                                    <p class="text-[11px] text-slate-400">Display the previous estimated delivery date.</p>
                                </div>
                                <input type="hidden" name="show_previous_estimate" :value="showPreviousEstimate ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showPreviousEstimate = !showPreviousEstimate"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showPreviousEstimate ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showPreviousEstimate"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showPreviousEstimate ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Updated Estimate -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Updated Estimate</p>
                                    <p class="text-[11px] text-slate-400">Display the new estimated delivery date.</p>
                                </div>
                                <input type="hidden" name="show_updated_estimate" :value="showUpdatedEstimate ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showUpdatedEstimate = !showUpdatedEstimate"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showUpdatedEstimate ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showUpdatedEstimate"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showUpdatedEstimate ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Holiday Reason -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Holiday Reason</p>
                                    <p class="text-[11px] text-slate-400">Display the reason for the delay (e.g., holiday, weather, etc.).</p>
                                </div>
                                <input type="hidden" name="show_holiday_reason" :value="showHolidayReason ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showHolidayReason = !showHolidayReason"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showHolidayReason ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showHolidayReason"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showHolidayReason ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Estimated Delivery Card -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Estimated Delivery Card</p>
                                    <p class="text-[11px] text-slate-400">Display a highlighted card with delivery information.</p>
                                </div>
                                <input type="hidden" name="show_delivery_card" :value="showDeliveryCard ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showDeliveryCard = !showDeliveryCard"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showDeliveryCard ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showDeliveryCard"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showDeliveryCard ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Order Information -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-xl">📄</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-brand-ink">Order Information</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Order details and reference information.</p>
                            </div>
                        </div>

                        <div class="space-y-3.5 pl-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Order Number</p>
                                    <p class="text-[11px] text-slate-400">Display the order number in the email.</p>
                                </div>
                                <input type="hidden" name="show_order_number" :value="showOrderNumber ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showOrderNumber = !showOrderNumber"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showOrderNumber ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showOrderNumber"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showOrderNumber ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Action Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-xl">👆</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-brand-ink">Action Elements</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Call-to-action buttons and next steps.</p>
                            </div>
                        </div>

                        <div class="space-y-3.5 pl-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show CTA Button</p>
                                    <p class="text-[11px] text-slate-400">Display the primary call-to-action button (e.g., View Order).</p>
                                </div>
                                <input type="hidden" name="show_cta_button" :value="showCtaButton ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showCtaButton = !showCtaButton"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showCtaButton ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showCtaButton"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showCtaButton ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Footer Elements -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="text-xl">🔗</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-brand-ink">Footer Elements</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Support and additional information at the bottom of the email.</p>
                            </div>
                        </div>

                        <div class="space-y-3.5 pl-8">
                            <!-- Show Support Contact -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Support Contact</p>
                                    <p class="text-[11px] text-slate-400">Display customer support contact information.</p>
                                </div>
                                <input type="hidden" name="show_support_contact" :value="showSupportContact ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showSupportContact = !showSupportContact"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showSupportContact ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showSupportContact"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showSupportContact ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Social Links -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Social Links</p>
                                    <p class="text-[11px] text-slate-400">Display social media links.</p>
                                </div>
                                <input type="hidden" name="show_social_links" :value="showSocialLinks ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showSocialLinks = !showSocialLinks"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showSocialLinks ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showSocialLinks"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showSocialLinks ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            <!-- Show Footer Note -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3.5">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Show Footer Note</p>
                                    <p class="text-[11px] text-slate-400">Display the footer note with legal or promotional text.</p>
                                </div>
                                <input type="hidden" name="show_footer_note" :value="showFooterNote ? 1 : 0">
                                <button
                                    type="button"
                                    @click="showFooterNote = !showFooterNote"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="showFooterNote ? 'bg-blue-600' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="showFooterNote"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="showFooterNote ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Email Preview with Tagged Badges (Matching mno.png) -->
                <div class="sticky top-28 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                        <div class="flex items-center gap-3 text-xs font-bold">
                            <span class="flex items-center gap-1.5 text-emerald-700">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Visible</span>
                            </span>
                            <span class="flex items-center gap-1.5 text-slate-400">
                                <span class="h-2 w-2 rounded-full border border-slate-400"></span>
                                <span>Hidden</span>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm text-xs space-y-4">
                        <!-- Logo Row -->
                        <div class="flex items-center justify-between">
                            <div :class="showLogo ? 'opacity-100' : 'opacity-25'" class="transition-opacity">
                                <span class="text-lg font-black tracking-wider text-brand-ink">NEXT<span style="color: {{ $branding->button_color }};">PLAY</span></span>
                            </div>
                            <span
                                :class="showLogo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold"
                                x-text="showLogo ? 'Logo (Visible)' : 'Logo (Hidden)'"
                            ></span>
                        </div>

                        <!-- Greeting Row -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showGreeting ? 'opacity-100' : 'opacity-25'" class="transition-opacity">
                                <h4 class="text-sm font-extrabold text-brand-ink">Hi Alex,</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Your delivery estimate has been updated. Here are the latest details for your order.</p>
                            </div>
                            <span
                                :class="showGreeting ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold"
                                x-text="showGreeting ? 'Greeting (Visible)' : 'Greeting (Hidden)'"
                            ></span>
                        </div>

                        <!-- Estimated Delivery Card -->
                        <div
                            :class="showDeliveryCard ? 'opacity-100' : 'opacity-25'"
                            class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 transition-opacity relative"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                    <span class="font-extrabold text-slate-900 text-xs">Estimated Delivery Update</span>
                                </div>
                                <span
                                    :class="showDeliveryCard ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                    class="rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                    x-text="showDeliveryCard ? 'Delivery Card (Visible)' : 'Delivery Card (Hidden)'"
                                ></span>
                            </div>

                            <div class="flex items-center justify-around text-center py-1">
                                <template x-if="showPreviousEstimate">
                                    <div>
                                        <span class="block text-[10px] uppercase font-bold text-slate-400">Previous Estimate</span>
                                        <span class="text-xs font-semibold text-slate-600 line-through">Thu, Oct 17, 2024</span>
                                    </div>
                                </template>
                                <template x-if="showPreviousEstimate">
                                    <span class="text-slate-400">→</span>
                                </template>
                                <div>
                                    <span class="block text-[10px] uppercase font-bold text-slate-400">Updated Estimate</span>
                                    <span class="text-xs font-black text-brand-ink">Mon, Oct 21, 2024</span>
                                </div>
                            </div>
                        </div>

                        <!-- Holiday Reason Callout -->
                        <div class="flex items-center justify-between gap-2">
                            <div
                                :class="showHolidayReason ? 'opacity-100' : 'opacity-25'"
                                class="flex-1 rounded-xl border border-amber-200 bg-amber-50 p-2.5 text-[11px] font-semibold text-amber-900 flex items-center gap-2 transition-opacity"
                            >
                                <span>📅</span>
                                <span>Delayed due to upcoming holiday</span>
                            </div>
                            <span
                                :class="showHolidayReason ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2 py-0.5 text-[10px] font-bold shrink-0"
                                x-text="showHolidayReason ? 'Holiday Reason (Visible)' : 'Holiday Reason (Hidden)'"
                            ></span>
                        </div>

                        <!-- Order Number -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showOrderNumber ? 'opacity-100' : 'opacity-25'" class="transition-opacity">
                                <span class="font-extrabold text-slate-900 text-xs">Order Number: #NP5839201</span>
                            </div>
                            <span
                                :class="showOrderNumber ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold"
                                x-text="showOrderNumber ? 'Order Number (Visible)' : 'Order Number (Hidden)'"
                            ></span>
                        </div>

                        <!-- CTA Button -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showCtaButton ? 'opacity-100' : 'opacity-25'" class="flex-1 transition-opacity mr-3">
                                <button
                                    type="button"
                                    class="w-full rounded-xl py-2.5 text-xs font-bold text-white shadow-sm"
                                    style="background-color: {{ $branding->button_color }};"
                                >
                                    View Order
                                </button>
                            </div>
                            <span
                                :class="showCtaButton ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2.5 py-0.5 text-[10px] font-bold shrink-0"
                                x-text="showCtaButton ? 'CTA Button (Visible)' : 'CTA Button (Hidden)'"
                            ></span>
                        </div>

                        <!-- Support Contact -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showSupportContact ? 'opacity-100' : 'opacity-25'" class="transition-opacity flex items-center gap-2">
                                <span class="text-base">🎧</span>
                                <div>
                                    <p class="font-bold text-slate-800 text-[11px]">Need Help?</p>
                                    <p class="text-[10px] text-slate-500">Contact our support team at {{ $branding->support_email }} or call {{ $branding->support_phone }}</p>
                                </div>
                            </div>
                            <span
                                :class="showSupportContact ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2 py-0.5 text-[10px] font-bold shrink-0"
                                x-text="showSupportContact ? 'Support Contact (Visible)' : 'Support Contact (Hidden)'"
                            ></span>
                        </div>

                        <!-- Social Links -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showSocialLinks ? 'opacity-100' : 'opacity-30'" class="flex items-center gap-2 transition-opacity">
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">f</span>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-600">📷</span>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">𝕏</span>
                                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-600">▶</span>
                            </div>
                            <span
                                :class="showSocialLinks ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                x-text="showSocialLinks ? 'Social Links (Visible)' : 'Social Links (Hidden)'"
                            ></span>
                        </div>

                        <!-- Footer Note -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <div :class="showFooterNote ? 'opacity-100' : 'opacity-25'" class="text-[10px] text-slate-400 transition-opacity">
                                <p>Play More. Live Better.</p>
                                <p>© 2024 NextPlay. All rights reserved.</p>
                            </div>
                            <span
                                :class="showFooterNote ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-400 border-slate-200'"
                                class="rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                x-text="showFooterNote ? 'Footer Note (Visible)' : 'Footer Note (Hidden)'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons (Matching mno.png) -->
            <div class="flex items-center justify-between gap-3 pt-2">
                <a
                    href="{{ route('admin.email-customization.templates.edit', $template->key) }}"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    ← Back
                </a>
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        name="action"
                        value="draft"
                        class="rounded-xl border border-slate-300 bg-white px-6 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                    >
                        Save Draft
                    </button>
                    <button
                        type="submit"
                        name="action"
                        value="publish"
                        class="rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:opacity-95"
                        style="background-color: var(--np-color-secondary) !important; color: #ffffff !important;"
                    >
                        Save & Publish
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
