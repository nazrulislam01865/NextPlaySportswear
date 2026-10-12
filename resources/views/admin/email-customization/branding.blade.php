<x-layouts.admin
    title="Global Branding Settings"
    subtitle="Customize the global look and feel for all transactional emails. These settings will be applied to every email template."
>
    <div
        class="space-y-6"
        x-data="{
            device: 'desktop',
            headerBg: '{{ $branding->header_bg_color ?? '#0B2A4A' }}',
            buttonColor: '{{ $branding->button_color ?? '#F15A2B' }}',
            footerText: `{{ addslashes($branding->footer_text ?? "© 2024 NextPlay. All rights reserved.\nPlay More. Live Better.\nYou're receiving this email because you have an account with NextPlay.") }}`,
            supportEmail: '{{ $branding->support_email ?? 'support@nextplay.com' }}',
            supportPhone: '{{ $branding->support_phone ?? '+1 (888) 123-4567' }}',
            socialFb: '{{ $branding->social_links['facebook'] ?? 'https://facebook.com/nextplay' }}',
            socialIg: '{{ $branding->social_links['instagram'] ?? 'https://instagram.com/nextplay' }}',
            socialX: '{{ $branding->social_links['twitter'] ?? 'https://twitter.com/nextplay' }}',
            socialYt: '{{ $branding->social_links['youtube'] ?? 'https://youtube.com/nextplay' }}',
            logoPreview: '{{ $branding->logoUrl() ?? '' }}',
            removeLogo: false,
            onLogoSelected(e) {
                const file = e.target.files[0];
                if (file) {
                    this.logoPreview = URL.createObjectURL(file);
                    this.removeLogo = false;
                }
            },
            clearLogo() {
                this.logoPreview = '';
                this.removeLogo = true;
                if ($refs.logoFileInput) {
                    $refs.logoFileInput.value = '';
                }
            }
        }"
    >

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

        <!-- Notification Banner (Matching ghi.png) -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-start justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50/90 p-4 text-xs font-bold text-amber-950 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-[#c2410c] text-xs font-black text-white shrink-0 mt-0.5">
                    i
                </span>
                <p class="leading-relaxed font-semibold text-amber-900">Changes apply to the next email sent after you publish.</p>
            </div>
            <button type="button" @click="dismissed = true" class="text-amber-700 hover:text-amber-950 transition text-sm font-bold">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.email-customization.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="file" name="logo" x-ref="logoFileInput" @change="onLogoSelected" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden">
            <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)] lg:items-start">
                <!-- Left Column: Branding Controls (Matching ghi.png) -->
                <div class="space-y-4">
                    <!-- Brand Identity Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-brand-ink">Brand Identity</h2>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                            <span class="text-xs font-bold text-slate-700 w-24">Logo</span>
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-44 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-1.5 overflow-hidden">
                                    <template x-if="logoPreview">
                                        <img :src="logoPreview" alt="Brand Logo" class="max-h-8 max-w-full object-contain">
                                    </template>
                                    <template x-if="!logoPreview">
                                        <span class="text-sm font-black tracking-tight text-slate-900">NEXT<span :style="'color: ' + buttonColor">PLAY</span></span>
                                    </template>
                                </div>
                                <button
                                    type="button"
                                    @click="$refs.logoFileInput.click()"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                                >
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>Change Logo</span>
                                </button>
                                <button
                                    type="button"
                                    @click="clearLogo()"
                                    class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 transition"
                                    title="Remove logo"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 pl-0 sm:pl-28">Recommended: 200 × 60px (PNG, SVG, or JPEG). Transparent background preferred.</p>
                    </div>

                    <!-- Header Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-brand-ink">Header Style</h2>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                            <span class="text-xs font-bold text-slate-700 w-44">Header Background Color</span>
                            <div class="flex items-center gap-3">
                                <div class="relative flex items-center">
                                    <input
                                        type="color"
                                        x-model="headerBg"
                                        class="absolute left-2 h-5 w-5 cursor-pointer rounded border-0 p-0 opacity-0"
                                    >
                                    <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm cursor-pointer" @click="$el.previousElementSibling.click()">
                                        <span class="h-4 w-4 rounded-md shrink-0 shadow-xs border border-black/10" :style="'background-color: ' + headerBg"></span>
                                        <span class="font-mono text-xs font-bold text-slate-800 uppercase" x-text="headerBg"></span>
                                        <span class="text-slate-400 text-xs">▼</span>
                                    </div>
                                    <input type="hidden" name="header_bg_color" :value="headerBg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Button Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-brand-ink">Button Style</h2>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                            <span class="text-xs font-bold text-slate-700 w-44">Button / Accent Color</span>
                            <div class="flex items-center gap-3">
                                <div class="relative flex items-center">
                                    <input
                                        type="color"
                                        x-model="buttonColor"
                                        class="absolute left-2 h-5 w-5 cursor-pointer rounded border-0 p-0 opacity-0"
                                    >
                                    <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 shadow-sm cursor-pointer" @click="$el.previousElementSibling.click()">
                                        <span class="h-4 w-4 rounded-md shrink-0 shadow-xs border border-black/10" :style="'background-color: ' + buttonColor"></span>
                                        <span class="font-mono text-xs font-bold text-slate-800 uppercase" x-text="buttonColor"></span>
                                        <span class="text-slate-400 text-xs">▼</span>
                                    </div>
                                    <input type="hidden" name="button_color" :value="buttonColor">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Font Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="w-48">
                                <h2 class="text-sm font-extrabold text-brand-ink">Font Style</h2>
                            </div>
                            <div class="flex-1 flex flex-col sm:flex-row sm:items-center gap-4">
                                <input
                                    type="text"
                                    name="font_family"
                                    readonly
                                    value="Inter (Default)"
                                    class="admin-input text-xs w-40 bg-slate-50 font-bold text-slate-700 cursor-not-allowed"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Footer Content Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="w-48">
                                <h2 class="text-sm font-extrabold text-brand-ink">Footer Content</h2>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Footer Text</label>
                                <textarea
                                    x-model="footerText"
                                    name="footer_text"
                                    rows="4"
                                    maxlength="500"
                                    class="admin-input h-auto min-h-[105px] py-2.5 text-xs w-full leading-relaxed resize-y"
                                ></textarea>
                                <div class="mt-1 flex justify-end text-[10px] text-slate-400">
                                    <span x-text="footerText.length"></span>/500
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support Information Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="w-48">
                                <h2 class="text-sm font-extrabold text-brand-ink">Support Information</h2>
                            </div>
                            <div class="flex-1 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Support Email</label>
                                    <input
                                        type="email"
                                        x-model="supportEmail"
                                        name="support_email"
                                        class="admin-input text-xs w-full"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Support Phone <span class="text-slate-400 font-normal">(Optional)</span></label>
                                    <input
                                        type="text"
                                        x-model="supportPhone"
                                        name="support_phone"
                                        class="admin-input text-xs w-full"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="w-48">
                                <h2 class="text-sm font-extrabold text-brand-ink">Social Links</h2>
                            </div>
                            <div class="flex-1 grid gap-2 sm:grid-cols-4">
                                <!-- Facebook -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Facebook</label>
                                    <input type="url" x-model="socialFb" name="social_facebook" class="admin-input text-xs w-full py-1.5" placeholder="https://facebook.com/nextplay">
                                </div>

                                <!-- Instagram -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Instagram</label>
                                    <input type="url" x-model="socialIg" name="social_instagram" class="admin-input text-xs w-full py-1.5" placeholder="https://instagram.com/nextplay">
                                </div>

                                <!-- X Twitter -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">X (Twitter)</label>
                                    <input type="url" x-model="socialX" name="social_twitter" class="admin-input text-xs w-full py-1.5" placeholder="https://twitter.com/nextplay">
                                </div>

                                <!-- YouTube -->
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">YouTube</label>
                                    <input type="url" x-model="socialYt" name="social_youtube" class="admin-input text-xs w-full py-1.5" placeholder="https://youtube.com/nextplay">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sticky Live Email Preview (Matching ghi.png) -->
                <div class="sticky top-28 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                        </div>
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            <button
                                type="button"
                                @click="device = 'desktop'"
                                :class="device === 'desktop' ? 'bg-slate-100 font-black text-brand-ink shadow-xs' : 'text-slate-500'"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition flex items-center gap-1"
                            >
                                <span>🖥</span>
                                <span>Desktop</span>
                            </button>
                            <button
                                type="button"
                                @click="device = 'mobile'"
                                :class="device === 'mobile' ? 'bg-slate-100 font-black text-brand-ink shadow-xs' : 'text-slate-500'"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition flex items-center gap-1"
                            >
                                <span>📱</span>
                                <span>Mobile</span>
                            </button>
                        </div>
                    </div>

                    <!-- Email Canvas Mockup (Matching ghi.png) -->
                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-4 shadow-sm transition-all"
                        :class="device === 'mobile' ? 'max-w-[340px] mx-auto' : 'w-full'"
                    >
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <!-- Email Header (Customizable headerBg) -->
                            <div
                                class="px-6 py-6 text-center text-white transition-colors"
                                :style="'background-color: ' + headerBg"
                            >
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Brand Logo" class="max-h-7 mx-auto object-contain">
                                </template>
                                <template x-if="!logoPreview">
                                    <span class="text-xl font-black tracking-wider text-white">NEXT<span :style="'color: ' + buttonColor">PLAY</span></span>
                                </template>
                            </div>

                            <!-- Email Body -->
                            <div class="p-6 text-slate-700 space-y-4">
                                <h4 class="text-base font-extrabold text-brand-ink">Hi there,</h4>
                                <p class="text-xs leading-relaxed text-slate-500 font-medium">
                                    Thanks for being a part of the NextPlay community! This is a sample of how your emails will look with your current branding settings.
                                </p>

                                <div class="pt-2 text-center">
                                    <button
                                        type="button"
                                        class="inline-block rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                        :style="'background-color: ' + buttonColor"
                                    >
                                        View Your Order
                                    </button>
                                </div>

                                <!-- Footer -->
                                <div class="border-t border-slate-100 pt-4 text-center text-[10px] text-slate-400 space-y-1.5">
                                    <p class="whitespace-pre-line leading-relaxed font-medium" x-text="footerText"></p>
                                    <div class="flex items-center justify-center gap-3 pt-1 text-slate-600 font-semibold">
                                        <span class="flex items-center gap-1">
                                            <span>✉</span>
                                            <span x-text="supportEmail"></span>
                                        </span>
                                        <template x-if="supportPhone">
                                            <span class="flex items-center gap-1">
                                                <span>| 📞</span>
                                                <span x-text="supportPhone"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons (Matching ghi.png) -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a
                    href="{{ route('admin.email-customization.templates.index') }}"
                    class="rounded-xl border border-slate-300 bg-white px-6 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    Cancel
                </a>
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
                    style="background-color: #CF5D38 !important; color: #ffffff !important;"
                >
                    Save & Publish
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
