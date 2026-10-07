<x-layouts.admin
    title="Preview & Send Test Email"
    eyebrow="Email Customization"
    subtitle="Preview how this email looks and send a test to verify content, style, and personalization."
>
    <div
        class="space-y-6"
        x-data="{
            device: 'desktop',
            currentOrderKey: 'np-12345',
            showInboxModal: false,
            recipientEmail: 'admin@nextplay.com',
            useSampleData: true,
            usePublishedVersion: true,
            samples: {{ Js::from($sampleOrders) }},
            get currentOrder() {
                return this.samples[this.currentOrderKey] || this.samples['np-12345'];
            },
            resolve(text) {
                if (!text) return '';
                if (!this.useSampleData) return text;
                const o = this.currentOrder;
                return text
                    .replace(/\{\{\s*customer_name\s*\}\}/g, o.customer_name)
                    .replace(/\{\{\s*order_number\s*\}\}/g, o.order_number)
                    .replace(/\{\{\s*order_date\s*\}\}/g, o.order_date)
                    .replace(/\{\{\s*previous_estimate\s*\}\}/g, o.previous_estimate)
                    .replace(/\{\{\s*updated_estimate\s*\}\}/g, o.updated_estimate)
                    .replace(/\{\{\s*holiday_reason\s*\}\}/g, o.holiday_reason)
                    .replace(/\{\{\s*tracking_number\s*\}\}/g, o.tracking_number)
                    .replace(/\{\{\s*shipping_method\s*\}\}/g, o.shipping_method);
            }
        }"
    >
        <!-- Breadcrumb & Top Actions (Matching jkl.png) -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="hover:text-slate-700">Email Templates</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="hover:text-slate-700">{{ $template->name }}</a>
                <span>/</span>
                <span class="text-brand-ink">Preview & Send Test Email</span>
            </nav>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="btn btn-white text-xs">
                    ← Back to Editor
                </a>
                <button
                    type="button"
                    @click="showInboxModal = true"
                    class="btn btn-white inline-flex items-center gap-1.5 text-xs font-bold"
                >
                    <span>📬</span>
                    <span>View Customer Inbox Style</span>
                </button>
                <button
                    type="button"
                    @click="$refs.testEmailForm.requestSubmit()"
                    class="btn btn-orange inline-flex items-center gap-2 rounded-xl text-xs font-bold !text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important; border: 1px solid #CF5D38 !important;"
                >
                    <span>✈</span>
                    <span>Send Test Email</span>
                </button>
            </div>
        </div>

        <!-- Status Alert -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Notice Banner -->
        <div class="flex items-start justify-between gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
                <p class="leading-relaxed">Preview reflects current settings. Publish to make changes live for the next email action.</p>
            </div>
        </div>

        <!-- Main Layout Grid (Matches jkl.png) -->
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)] lg:items-start">
            <!-- Left: Email Preview Canvas -->
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-extrabold text-brand-ink">Email Preview</h3>
                        <p class="text-xs text-slate-500">See how your email will look to customers across different devices.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-bold text-slate-400">Sample Data</label>
                            <select x-model="currentOrderKey" class="admin-input h-9 text-xs font-bold">
                                <option value="np-12345">Sample Order #NP-12345</option>
                                <option value="np-67890">Sample Order #NP-67890</option>
                                <option value="np-99211">Sample Order #NP-99211</option>
                            </select>
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
                </div>

                <!-- Realistic Device Frame (Matches jkl.png) -->
                <div
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 p-4 shadow-card transition-all"
                    :class="device === 'mobile' ? 'max-w-[360px] mx-auto' : 'w-full'"
                >
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <!-- Simulated browser window dots -->
                        <div class="flex items-center gap-1.5 border-b border-slate-100 bg-slate-50/80 px-4 py-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                        </div>

                        <!-- Email Header -->
                        <div class="p-6 text-center">
                            <div class="font-black text-lg text-brand-ink">
                                NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                            </div>

                            <div class="mt-4 grid h-14 w-14 place-items-center rounded-2xl bg-orange-50 mx-auto text-2xl">
                                @if($template->icon === 'truck') 🚚
                                @elseif($template->icon === 'cart') 🛒
                                @elseif($template->icon === 'box') 📦
                                @elseif($template->icon === 'lock') 🔒
                                @elseif($template->icon === 'gift') 🎁
                                @else 👤
                                @endif
                            </div>

                            <h3 class="mt-3 text-lg font-black text-brand-ink" x-text="resolve('{{ addslashes($template->heading) }}')"></h3>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500 max-w-md mx-auto" x-text="resolve('{{ addslashes($template->intro_message ?? '') }}')"></p>

                            <!-- Order Details Box (Matches jkl.png) -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-5 text-left text-xs">
                                <div class="grid gap-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Order Number</span>
                                        <span class="font-extrabold text-brand-ink" x-text="currentOrder.order_number"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Estimated Delivery</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-brand-ink" x-text="currentOrder.updated_estimate"></span>
                                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800" x-text="currentOrder.delivery_status"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Shipping Method</span>
                                        <span class="font-semibold text-slate-700" x-text="currentOrder.shipping_method"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Tracking Number</span>
                                        <span class="font-mono text-slate-700" x-text="currentOrder.tracking_number"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-6">
                                <button
                                    type="button"
                                    class="inline-block rounded-xl px-6 py-3 text-xs font-black text-white shadow-sm transition hover:opacity-95"
                                    style="background-color: {{ $branding->button_color }} !important;"
                                >
                                    {{ $template->cta_label ?? 'View Order Details' }}
                                </button>
                            </div>

                            <p class="mt-4 text-[11px] text-slate-400">
                                Need help? <a href="#" class="font-bold underline" style="color: {{ $branding->button_color }};">Contact our support team</a> anytime.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Send Test Email Form & Publish Status (Matches jkl.png) -->
            <div class="space-y-5">
                <!-- Test Email Card -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <h3 class="text-base font-extrabold text-brand-ink">Test Email</h3>
                    <p class="mt-1 text-xs text-slate-500">Send a test email to see how it will look in an inbox.</p>

                    <form
                        x-ref="testEmailForm"
                        method="POST"
                        action="{{ route('admin.email-customization.templates.send-test', $template->key) }}"
                        class="mt-4 space-y-4"
                    >
                        @csrf
                        <input type="hidden" name="sample_order" :value="currentOrderKey">
                        <input type="hidden" name="use_sample_data" :value="useSampleData ? '1' : '0'">
                        <input type="hidden" name="use_published_version" :value="usePublishedVersion ? '1' : '0'">

                        <div>
                            <label class="admin-label text-xs">Recipient Email</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-xs">👤</span>
                                <input
                                    type="email"
                                    x-model="recipientEmail"
                                    name="recipient_email"
                                    required
                                    class="admin-input pl-8 text-xs"
                                    placeholder="admin@nextplay.com"
                                >
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">Enter an email address to receive the test email.</p>
                        </div>

                        <div>
                            <label class="admin-label text-xs">Subject Preview</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-semibold text-slate-700" x-text="resolve('{{ addslashes($template->subject) }}')">
                                {{ $template->subject }}
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">This is the subject line that will be used for the test email.</p>
                        </div>

                        <!-- Toggles with pill switches (Matches jkl.png) -->
                        <div class="space-y-4 border-t border-slate-100 pt-4">
                            <!-- Toggle 1: Use sample data -->
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Use sample data</p>
                                    <p class="text-[11px] text-slate-400">Use sample order data to populate dynamic content (e.g. customer name, order details, tracking info).</p>
                                </div>
                                <button
                                    type="button"
                                    @click="useSampleData = !useSampleData"
                                    :class="useSampleData ? 'bg-[#CF5D38]' : 'bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out"
                                >
                                    <span
                                        :class="useSampleData ? 'translate-x-5' : 'translate-x-1'"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-200 ease-in-out mt-1"
                                    ></span>
                                </button>
                            </div>

                            <!-- Toggle 2: Use current published version -->
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Use current published version</p>
                                    <p class="text-[11px] text-slate-400">Send a test using the currently published version ({{ $template->active_version }}) instead of unsaved changes.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="usePublishedVersion = !usePublishedVersion"
                                    :class="usePublishedVersion ? 'bg-[#CF5D38]' : 'bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out"
                                >
                                    <span
                                        :class="usePublishedVersion ? 'translate-x-5' : 'translate-x-1'"
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition duration-200 ease-in-out mt-1"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <!-- Informational note -->
                        <div class="rounded-xl bg-sky-50 p-3 text-[11px] text-sky-800 flex items-start gap-2">
                            <span>ℹ</span>
                            <span>The test email will be sent using sample data for Order <strong x-text="currentOrder.order_number">#NP-12345</strong> and the current published version.</span>
                        </div>

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#CF5D38] px-5 py-2.5 text-xs font-black text-white hover:bg-[#b84f2e] transition shadow-sm"
                            style="background-color: #CF5D38 !important; color: #ffffff !important;"
                        >
                            <span>✈</span>
                            <span>Send Test Email</span>
                        </button>
                    </form>
                </div>

                <!-- Publish Status Card (Matches jkl.png) -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Publish Status</h3>
                        <span class="text-[11px] text-slate-400">Last updated {{ $template->updated_at?->format('M d, Y') ?? 'Recently' }} by Admin</span>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-extrabold text-emerald-800">Published</span>
                        <span class="font-bold text-slate-600 text-xs">{{ $template->active_version }}</span>
                    </div>

                    <div class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50/80 p-3 text-xs font-bold text-emerald-900 border border-emerald-200">
                        <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                        <span>This published version will be used for the next matching email send.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Webmail Inbox Style Modal (Matches stu.png) -->
        <div
            x-cloak
            x-show="showInboxModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm"
            @keydown.escape.window="showInboxModal = false"
        >
            <div
                class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-2xl"
                @click.outside="showInboxModal = false"
            >
                <!-- Modal Top Bar -->
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50/60">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        Published Template Preview
                    </span>
                    <button type="button" @click="showInboxModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-base">✕</button>
                </div>

                <!-- Simulated Gmail Container (Matches stu.png) -->
                <div class="p-6 sm:p-8">
                    <!-- Subject line & tags -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-brand-ink" x-text="resolve('{{ addslashes($template->subject) }}')">
                                {{ $template->subject }}
                            </h2>
                            <span class="rounded bg-slate-200/80 px-2 py-0.5 text-[10px] font-bold text-slate-700">Inbox ×</span>
                        </div>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ now()->format('M d, Y, g:i A') }}</span>
                    </div>

                    <!-- Sender / Avatar line -->
                    <div class="mt-4 flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-full bg-[#0B2A4A] font-black text-white text-sm">
                                N
                            </div>
                            <div class="text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-brand-ink">NextPlay</span>
                                    <span class="text-slate-400">&lt;noreply@nextplay.com&gt;</span>
                                </div>
                                <p class="text-[11px] text-slate-500">to me ▾</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-slate-400 text-sm">
                            <span title="Star">☆</span>
                            <span title="Reply">↩</span>
                            <span title="More">⋮</span>
                        </div>
                    </div>

                    <!-- Inner Email Card (Exact match of stu.png) -->
                    <div class="mt-6 rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <!-- Navy Header Banner -->
                        <div class="bg-[#0B2A4A] px-8 py-6 text-white flex items-center justify-between">
                            <span class="text-xl font-black tracking-wider">NEXT<span style="color: {{ $branding->button_color }};">PLAY</span></span>
                            <span class="text-[10px] uppercase tracking-[.25em] text-slate-300 font-extrabold">PLAY MORE. LIVE BETTER.</span>
                        </div>

                        <!-- Email Body -->
                        <div class="p-8 text-slate-700 text-xs space-y-6">
                            <!-- Eyebrow & Heading -->
                            <div>
                                <span class="text-[11px] uppercase tracking-widest font-black text-slate-400">Order Update</span>
                                <h1 class="mt-1 text-2xl font-black text-brand-ink" x-text="resolve('{{ addslashes($template->heading) }}')">
                                    {{ $template->heading }}
                                </h1>
                            </div>

                            <!-- Greeting & Intro -->
                            <div class="text-sm leading-relaxed text-slate-600 space-y-2">
                                <p class="font-bold text-slate-800">Hi <span x-text="currentOrder.customer_name"></span>,</p>
                                <p x-text="resolve('{{ addslashes($template->intro_message ?? '') }}')"></p>
                            </div>

                            <!-- Order Summary Bar with CTA Button -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <span class="font-extrabold text-brand-ink text-sm">Order <span x-text="currentOrder.order_number"></span></span>
                                    <p class="text-[11px] text-slate-400">Placed on <span x-text="currentOrder.order_date"></span></p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-xl px-5 py-2.5 font-bold text-white text-xs shadow-sm transition"
                                    style="background-color: {{ $branding->button_color }} !important;"
                                >
                                    {{ $template->cta_label ?? 'View Order' }} →
                                </button>
                            </div>

                            <!-- Previous Estimate vs Updated Estimate Comparison Card -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 grid grid-cols-[1fr_auto_1fr] items-center text-center gap-4">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Previous Estimate</span>
                                    <p class="mt-1 font-extrabold text-brand-ink text-sm" x-text="currentOrder.previous_estimate"></p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                                <span class="text-slate-300 text-2xl font-bold">→</span>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Updated Estimate</span>
                                    <p class="mt-1 font-extrabold text-sm" style="color: {{ $branding->button_color }};" x-text="currentOrder.updated_estimate"></p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                            </div>

                            <!-- Holiday Delay Reason Banner -->
                            <div class="rounded-2xl border border-orange-200 bg-orange-50/70 p-4 flex items-start gap-3.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-orange-200 text-sm font-black text-orange-800">❄</span>
                                <div>
                                    <strong class="text-orange-950 font-extrabold text-xs">Holiday schedule impacts original delivery date.</strong>
                                    <p class="mt-1 text-orange-900 leading-relaxed text-[11px]" x-text="currentOrder.holiday_reason"></p>
                                </div>
                            </div>

                            <!-- Support Help Line -->
                            <div class="border-t border-slate-100 pt-6 flex flex-wrap items-center justify-between gap-3 text-slate-500 text-[11px]">
                                <div class="flex items-center gap-2">
                                    <span>🎧</span>
                                    <span>Need help? Contact <a href="#" class="underline text-brand-ink font-bold">{{ $branding->support_email }}</a> | {{ $branding->support_phone }}</span>
                                </div>
                                <span class="font-bold cursor-pointer" style="color: {{ $branding->button_color }};">Live Chat</span>
                            </div>

                            <!-- Email Footer -->
                            <div class="border-t border-slate-100 pt-6 text-center text-[10px] text-slate-400 space-y-2">
                                <div class="font-black text-sm text-brand-ink">
                                    NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                                </div>
                                <p class="whitespace-pre-line leading-relaxed">{{ $branding->footer_text }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
