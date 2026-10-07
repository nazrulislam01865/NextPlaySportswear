<x-layouts.admin
    title="Global Branding Settings"
    eyebrow="Email Customization"
    subtitle="Customize the global look and feel for all transactional emails. These settings will be applied to every email template."
>
    <div
        class="space-y-6"
        x-data="{
            device: 'desktop',
            headerBg: '{{ $branding->header_bg_color }}',
            buttonColor: '{{ $branding->button_color }}',
            footerText: '{{ addslashes($branding->footer_text ?? '') }}',
            supportEmail: '{{ $branding->support_email }}',
            supportPhone: '{{ $branding->support_phone }}',
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
        <!-- Breadcrumb & Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="hover:text-slate-700">Email Templates</a>
                <span>/</span>
                <span class="text-brand-ink">Global Branding Settings</span>
            </nav>

            <a href="{{ route('admin.email-customization.templates.index') }}" class="btn btn-white text-xs">
                ← Back to Templates
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

        <!-- Notification Banner -->
        <div class="flex items-start gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
            <p class="leading-relaxed">Changes apply to the next email sent after you publish.</p>
        </div>

        <form method="POST" action="{{ route('admin.email-customization.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="file" name="logo" x-ref="logoFileInput" @change="onLogoSelected" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden">
            <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(340px,.8fr)] lg:items-start">
                <!-- Left Column: Branding Controls -->
                <div class="space-y-5">
                    <!-- Brand Identity Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Brand Identity</h2>
                        <p class="mt-1 text-xs text-slate-500">Upload your logo and set your brand basics for emails.</p>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <div class="flex h-16 w-44 items-center justify-center rounded-xl border border-slate-200 bg-slate-900 px-4 py-2 overflow-hidden">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Brand Logo" class="max-h-12 max-w-full object-contain">
                                </template>
                                <template x-if="!logoPreview">
                                    <span class="text-base font-black tracking-tight text-white">NEXT<span :style="'color: ' + buttonColor">PLAY</span></span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="$refs.logoFileInput.click()" class="btn btn-white text-xs">Upload Logo</button>
                                <button type="button" @click="clearLogo()" class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Remove logo">🗑</button>
                            </div>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400">Recommended: 200 × 60px (PNG, SVG, or JPEG). Transparent background preferred.</p>
                    </div>

                    <!-- Header Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Header Style</h2>
                        <p class="mt-1 text-xs text-slate-500">Set the header background color that appears at the top of your emails.</p>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    x-model="headerBg"
                                    class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-1"
                                >
                                <input
                                    type="text"
                                    x-model="headerBg"
                                    name="header_bg_color"
                                    class="admin-input h-10 w-32 font-mono text-xs uppercase"
                                >
                            </div>
                            <span class="text-xs text-slate-500">This color will be used for the email header behind your logo.</span>
                        </div>
                    </div>

                    <!-- Button Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Button Style</h2>
                        <p class="mt-1 text-xs text-slate-500">Set the accent color for buttons and primary call-to-actions.</p>

                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    x-model="buttonColor"
                                    class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-1"
                                >
                                <input
                                    type="text"
                                    x-model="buttonColor"
                                    name="button_color"
                                    class="admin-input h-10 w-32 font-mono text-xs uppercase"
                                >
                            </div>
                            <span class="text-xs text-slate-500">This color will be used for buttons, links, and other accent elements.</span>
                        </div>
                    </div>

                    <!-- Font Style Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Font Style</h2>
                        <div class="mt-3 flex items-center gap-4">
                            <input
                                type="text"
                                name="font_family"
                                readonly
                                value="Inter (Default)"
                                class="admin-input h-10 w-44 bg-slate-50 font-bold text-slate-700"
                            >
                            <span class="text-xs text-slate-500">Emails use Inter, a clean and modern font for maximum readability across all email clients.</span>
                        </div>
                    </div>

                    <!-- Footer Content Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Footer Content</h2>
                        <p class="mt-1 text-xs text-slate-500">This text will appear at the bottom of all emails.</p>

                        <div class="mt-4">
                            <label class="admin-label text-xs">Footer Text</label>
                            <textarea
                                x-model="footerText"
                                name="footer_text"
                                rows="3"
                                maxlength="500"
                                class="admin-input text-xs"
                            ></textarea>
                            <div class="mt-1 flex justify-end text-[10px] text-slate-400">
                                <span x-text="footerText.length"></span>/500
                            </div>
                        </div>
                    </div>

                    <!-- Support Information Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Support Information</h2>
                        <p class="mt-1 text-xs text-slate-500">This information will be included in your email footer and help customers get in touch.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="admin-label text-xs">Support Email</label>
                                <input
                                    type="email"
                                    x-model="supportEmail"
                                    name="support_email"
                                    class="admin-input text-xs"
                                >
                            </div>
                            <div>
                                <label class="admin-label text-xs">Support Phone (Optional)</label>
                                <input
                                    type="text"
                                    x-model="supportPhone"
                                    name="support_phone"
                                    class="admin-input text-xs"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Social Links Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <h2 class="text-base font-extrabold text-brand-ink">Social Links</h2>
                        <p class="mt-1 text-xs text-slate-500">Add your social media links to display in the email footer.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="admin-label text-[11px]">Facebook</label>
                                <input type="url" name="social_facebook" value="{{ $branding->social_links['facebook'] ?? '' }}" class="admin-input text-xs" placeholder="https://facebook.com/nextplay">
                            </div>
                            <div>
                                <label class="admin-label text-[11px]">Instagram</label>
                                <input type="url" name="social_instagram" value="{{ $branding->social_links['instagram'] ?? '' }}" class="admin-input text-xs" placeholder="https://instagram.com/nextplay">
                            </div>
                            <div>
                                <label class="admin-label text-[11px]">X (Twitter)</label>
                                <input type="url" name="social_twitter" value="{{ $branding->social_links['twitter'] ?? '' }}" class="admin-input text-xs" placeholder="https://twitter.com/nextplay">
                            </div>
                            <div>
                                <label class="admin-label text-[11px]">YouTube</label>
                                <input type="url" name="social_youtube" value="{{ $branding->social_links['youtube'] ?? '' }}" class="admin-input text-xs" placeholder="https://youtube.com/nextplay">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sticky Live Email Preview -->
                <div class="sticky top-28 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                            <p class="text-[11px] text-slate-500">Preview with current settings.</p>
                        </div>
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            <button
                                type="button"
                                @click="device = 'desktop'"
                                :class="device === 'desktop' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500'"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition"
                            >🖥 Desktop</button>
                            <button
                                type="button"
                                @click="device = 'mobile'"
                                :class="device === 'mobile' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500'"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition"
                            >📱 Mobile</button>
                        </div>
                    </div>

                    <!-- Email Canvas Mockup -->
                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-4 shadow-card transition-all"
                        :class="device === 'mobile' ? 'max-w-[340px] mx-auto' : 'w-full'"
                    >
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <!-- Email Header -->
                            <div
                                class="px-6 py-6 text-center text-white transition-colors"
                                :style="'background-color: ' + headerBg"
                            >
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" alt="Brand Logo" class="max-h-8 mx-auto object-contain">
                                </template>
                                <template x-if="!logoPreview">
                                    <span class="text-lg font-black tracking-tight">NEXT<span :style="'color: ' + buttonColor">PLAY</span></span>
                                </template>
                            </div>

                            <!-- Email Body -->
                            <div class="p-6 text-slate-700">
                                <h4 class="text-base font-extrabold text-brand-ink">Hi there,</h4>
                                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                                    Thanks for being a part of the NextPlay community! This is a sample of how your emails will look with your current branding settings.
                                </p>

                                <div class="mt-5 text-center">
                                    <button
                                        type="button"
                                        class="inline-block rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-sm transition"
                                        :style="'background-color: ' + buttonColor"
                                    >
                                        View Your Order
                                    </button>
                                </div>

                                <!-- Social Icons -->
                                <div class="mt-6 flex items-center justify-center gap-3 text-slate-400">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs">fb</span>
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs">ig</span>
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs">x</span>
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs">yt</span>
                                </div>

                                <!-- Footer -->
                                <div class="mt-6 border-t border-slate-100 pt-4 text-center text-[10px] text-slate-400">
                                    <p class="whitespace-pre-line leading-relaxed" x-text="footerText"></p>
                                    <p class="mt-2 font-bold" x-text="supportEmail + (supportPhone ? ' | ' + supportPhone : '')"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <a href="{{ route('admin.email-customization.templates.index') }}" class="btn btn-white text-xs">Cancel</a>
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
