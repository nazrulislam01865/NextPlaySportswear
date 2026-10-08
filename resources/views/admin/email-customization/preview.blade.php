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
            previewMode: 'standard', // 'standard' or 'inbox'
            recipientEmail: 'admin@nextplay.com',
            useSampleData: true,
            usePublishedVersion: true,
            alertDismissed: false,
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
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-1 font-medium">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-600 transition">⌂</a>
                    <span>›</span>
                    <span>Content</span>
                    <span>›</span>
                    <a href="{{ route('admin.email-customization.templates.index') }}" class="text-slate-500 hover:text-brand-ink transition">Email Templates</a>
                    <span>›</span>
                    <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="text-slate-500 hover:text-brand-ink transition">{{ $template->name }}</a>
                    <span>›</span>
                    <span class="text-slate-600 font-bold">Preview & Send Test Email</span>
                </nav>
                <h1 class="text-2xl font-black text-brand-ink tracking-tight">Preview & Send Test Email</h1>
                <p class="text-xs text-slate-500 mt-1">Preview how this email looks and send a test to verify content, style, and personalization.</p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.email-customization.templates.edit', $template->key) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    <span>←</span>
                    <span>Back to Editor</span>
                </a>
                <button
                    type="button"
                    @click="showInboxModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                    title="View simulated email client inbox"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>Webmail Client View</span>
                </button>
                <button
                    type="button"
                    @click="$refs.testEmailForm.requestSubmit()"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2 text-xs font-black text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important;"
                >
                    <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                    <span>Send Test Email</span>
                </button>
            </div>
        </div>

        <!-- Session Status Alert -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Notice Banner (Matching jkl.png) -->
        <div x-show="!alertDismissed" class="flex items-center justify-between gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900 transition-all">
            <div class="flex items-center gap-3">
                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#CF5D38] text-[11px] font-black text-white">i</span>
                <p class="leading-relaxed">Preview reflects current settings. Publish to make changes live for the next email action.</p>
            </div>
            <button type="button" @click="alertDismissed = true" class="text-orange-600 hover:text-orange-950 text-sm font-bold">✕</button>
        </div>

        <!-- Main Layout Grid (Exact match of jkl.png) -->
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
                            <select x-model="currentOrderKey" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none">
                                <option value="np-12345">Sample Order #NP-12345</option>
                                <option value="np-67890">Sample Order #NP-67890</option>
                                <option value="np-99211">Sample Order #NP-99211</option>
                            </select>
                        </div>

                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            <button
                                type="button"
                                @click="device = 'desktop'"
                                :class="device === 'desktop' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500 hover:text-slate-700'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Desktop</span>
                            </button>
                            <button
                                type="button"
                                @click="device = 'mobile'"
                                :class="device === 'mobile' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500 hover:text-slate-700'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Mobile</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Realistic Device Frame (Matches jkl.png) -->
                <div
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 p-4 shadow-card transition-all"
                    :class="device === 'mobile' ? 'max-w-[370px] mx-auto' : 'w-full'"
                >
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <!-- Simulated browser window dots (🔴 🟡 🟢 from jkl.png) -->
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                            <span class="h-2.5 w-2.5 rounded-full bg-[#EF4444]"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#F59E0B]"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-[#10B981]"></span>
                        </div>

                        <!-- Email Body Container -->
                        <div class="p-8 sm:p-10 text-center">
                            <!-- Brand Logo -->
                            <div class="text-xl font-black tracking-wider text-brand-ink">
                                @if(!empty($branding->logo_url))
                                    <img src="{{ $branding->logo_url }}" alt="NextPlay" class="mx-auto h-7 object-contain">
                                @else
                                    NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                                @endif
                            </div>

                            <!-- Delivery Truck Graphic with Speed Lines (Matching jkl.png) -->
                            <div class="my-6 flex justify-center items-center">
                                <div class="relative w-36 h-20 flex items-center justify-center">
                                    <svg viewBox="0 0 160 90" class="w-full h-full drop-shadow-sm" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <!-- Speed lines trailing behind truck -->
                                        <path d="M12 36H40" stroke="#F15A2B" stroke-width="3.5" stroke-linecap="round"/>
                                        <path d="M22 46H50" stroke="#38BDF8" stroke-width="3.5" stroke-linecap="round"/>
                                        <path d="M6 56H36" stroke="#F15A2B" stroke-width="3.5" stroke-linecap="round"/>
                                        
                                        <!-- Truck Cargo Body (Orange) -->
                                        <rect x="52" y="24" width="60" height="42" rx="4" fill="#CF5D38"/>
                                        
                                        <!-- Truck Cabin (Navy) -->
                                        <path d="M112 38H132C134.5 38 137 40.5 138.5 43.5L145 54C146 55.5 146.5 57 146.5 58.5V66H112V38Z" fill="#0B2A4A"/>
                                        
                                        <!-- Windshield -->
                                        <path d="M116 42H130L135 52H116V42Z" fill="#E2E8F0"/>
                                        
                                        <!-- Wheels -->
                                        <circle cx="74" cy="66" r="9" fill="#1E293B"/>
                                        <circle cx="74" cy="66" r="4.5" fill="#CBD5E1"/>
                                        
                                        <circle cx="132" cy="66" r="9" fill="#1E293B"/>
                                        <circle cx="132" cy="66" r="4.5" fill="#CBD5E1"/>
                                        
                                        <!-- Headlight -->
                                        <rect x="144" y="58" width="3" height="5" rx="1.5" fill="#FBBF24"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Heading & Intro (Matching jkl.png) -->
                            <h2 class="text-xl sm:text-2xl font-black text-brand-ink leading-tight" x-text="resolve('{{ addslashes($template->heading) }}')">
                                {{ $template->heading }}
                            </h2>
                            <p class="mt-2.5 text-xs text-slate-500 leading-relaxed max-w-md mx-auto" x-text="resolve('{{ addslashes($template->intro_message ?? 'Good news! We have an updated delivery estimate for your order. You can view the latest details below.') }}')">
                                {{ $template->intro_message ?? 'Good news! We have an updated delivery estimate for your order. You can view the latest details below.' }}
                            </p>

                            <!-- Order Details Box (Exact match of jkl.png) -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5 sm:p-6 text-left text-xs max-w-lg mx-auto">
                                <div class="grid gap-3">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Order Number</span>
                                        <span class="font-extrabold text-brand-ink" x-text="currentOrder.order_number"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Estimated Delivery</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-brand-ink" x-text="currentOrder.updated_estimate"></span>
                                            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800">Updated</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Shipping Method</span>
                                        <span class="font-semibold text-slate-700" x-text="currentOrder.shipping_method"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Tracking Number</span>
                                        <span class="font-mono font-bold text-slate-700" x-text="currentOrder.tracking_number"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button (Matching jkl.png) -->
                            <div class="mt-6">
                                <button
                                    type="button"
                                    class="inline-block w-full max-w-xs rounded-xl py-3 text-xs font-black text-white shadow-sm transition hover:opacity-95"
                                    style="background-color: {{ $branding->button_color }} !important; color: #ffffff !important;"
                                >
                                    {{ $template->cta_label ?? 'View Order Details' }}
                                </button>
                            </div>

                            <!-- Footer Support Link (Matching jkl.png) -->
                            <p class="mt-5 text-[11px] text-slate-400">
                                Need help? <a href="#" class="font-bold text-brand-ink underline" style="color: {{ $branding->button_color }};">Contact our support team</a> anytime.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Send Test Email Form & Publish Status (Matches jkl.png) -->
            <div class="space-y-5">
                <!-- Test Email Card (Matching jkl.png) -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <h3 class="text-base font-extrabold text-brand-ink">Test Email</h3>
                    <p class="mt-1 text-xs text-slate-500">Send a test email to see how it will look in an inbox.</p>

                    <form
                        x-ref="testEmailForm"
                        method="POST"
                        action="{{ route('admin.email-customization.templates.send-test', $template->key) }}"
                        class="mt-5 space-y-4"
                    >
                        @csrf
                        <input type="hidden" name="sample_order" :value="currentOrderKey">
                        <input type="hidden" name="use_sample_data" :value="useSampleData ? '1' : '0'">
                        <input type="hidden" name="use_published_version" :value="usePublishedVersion ? '1' : '0'">

                        <!-- Recipient Email -->
                        <div>
                            <label class="admin-label text-xs font-bold text-slate-700">Recipient Email</label>
                            <div class="relative mt-1">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-xs">👤</span>
                                <input
                                    type="email"
                                    x-model="recipientEmail"
                                    name="recipient_email"
                                    required
                                    class="w-full rounded-xl border border-slate-300 bg-white pl-8 pr-3 py-2 text-xs font-medium text-slate-800 shadow-sm focus:border-orange-500 focus:outline-none"
                                    placeholder="admin@nextplay.com"
                                >
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-400">Enter an email address to receive the test email.</p>
                        </div>

                        <!-- Subject Preview -->
                        <div>
                            <label class="admin-label text-xs font-bold text-slate-700">Subject Preview</label>
                            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50/90 p-2.5 text-xs font-semibold text-slate-600 select-none" x-text="resolve('{{ addslashes($template->subject) }}')">
                                {{ $template->subject }}
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-400">This is the subject line that will be used for the test email.</p>
                        </div>

                        <!-- Toggles with pill switches (Matches jkl.png) -->
                        <div class="space-y-4 border-t border-slate-100 pt-4">
                            <!-- Toggle 1: Use sample data -->
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Use sample data</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Use sample order data to populate dynamic content (e.g. customer name, order details, tracking info).</p>
                                </div>
                                <button
                                    type="button"
                                    @click="useSampleData = !useSampleData"
                                    :style="useSampleData ? 'background-color: #CF5D38 !important;' : 'background-color: #CBD5E1 !important;'"
                                    style="position: relative; display: inline-flex; height: 22px; width: 42px; flex-shrink: 0; cursor: pointer; border-radius: 9999px; border: 2px solid transparent; transition: background-color 0.2s ease-in-out; outline: none; padding: 0;"
                                >
                                    <span
                                        :style="useSampleData ? 'transform: translateX(20px);' : 'transform: translateX(0px);'"
                                        style="pointer-events: none; display: inline-block; height: 18px; width: 18px; border-radius: 9999px; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);"
                                    ></span>
                                </button>
                            </div>

                            <!-- Toggle 2: Use current published version -->
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Use current published version</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Send a test using the currently published version ({{ $template->active_version }}) instead of your unsaved changes.</p>
                                </div>
                                <button
                                    type="button"
                                    @click="usePublishedVersion = !usePublishedVersion"
                                    :style="usePublishedVersion ? 'background-color: #CF5D38 !important;' : 'background-color: #CBD5E1 !important;'"
                                    style="position: relative; display: inline-flex; height: 22px; width: 42px; flex-shrink: 0; cursor: pointer; border-radius: 9999px; border: 2px solid transparent; transition: background-color 0.2s ease-in-out; outline: none; padding: 0;"
                                >
                                    <span
                                        :style="usePublishedVersion ? 'transform: translateX(20px);' : 'transform: translateX(0px);'"
                                        style="pointer-events: none; display: inline-block; height: 18px; width: 18px; border-radius: 9999px; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <!-- Blue info callout card (Matching jkl.png) -->
                        <div class="rounded-xl bg-sky-50 border border-sky-100 p-3 text-[11px] text-sky-900 flex items-start gap-2.5">
                            <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full bg-sky-200 text-[10px] font-black text-sky-800 mt-0.5">i</span>
                            <span class="leading-relaxed">The test email will be sent using sample data for <strong x-text="currentOrder.order_number">Order #NP-12345</strong> and the current published version.</span>
                        </div>
                    </form>
                </div>

                <!-- Publish Status Card (Matches jkl.png) -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Publish Status</h3>
                        <span class="text-[11px] text-slate-400">Last updated {{ $template->updated_at?->format('M d, Y') ?? 'Oct 22, 2024' }} by {{ $template->publishedBy?->name ?? 'Admin' }}</span>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-extrabold text-emerald-800">Published</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-extrabold text-slate-600">{{ $template->active_version }}</span>
                    </div>

                    <div class="mt-4 flex items-center gap-2.5 rounded-xl bg-emerald-50/80 p-3 text-xs font-bold text-emerald-900 border border-emerald-200">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                        <span>This published version will be used for the next matching email send.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Webmail Inbox Style Modal (Exact match of stu.png) -->
        <div
            x-cloak
            x-show="showInboxModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4 backdrop-blur-sm"
            @keydown.escape.window="showInboxModal = false"
        >
            <div
                class="w-full max-w-4xl max-h-[92vh] overflow-y-auto rounded-3xl bg-white shadow-2xl"
                @click.outside="showInboxModal = false"
            >
                <!-- Modal Top Bar -->
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-white/95 backdrop-blur-sm">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Published Template Preview</span>
                    </span>
                    <button type="button" @click="showInboxModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg">✕</button>
                </div>

                <!-- Webmail Container (Exact visual match of stu.png) -->
                <div class="p-6 sm:p-10">
                    <!-- Subject line & tags -->
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-lg font-black text-brand-ink" x-text="resolve('{{ addslashes($template->subject) }}')">
                                {{ $template->subject }}
                            </h2>
                            <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Inbox ×</span>
                        </div>
                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ now()->format('M d, Y, g:i A') }}</span>
                    </div>

                    <!-- Sender line -->
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
                            <span title="Star" class="cursor-pointer hover:text-amber-500">☆</span>
                            <span title="Reply" class="cursor-pointer hover:text-slate-600">↩</span>
                            <span title="More" class="cursor-pointer hover:text-slate-600">⋮</span>
                        </div>
                    </div>

                    <!-- Inner Email Card (stu.png) -->
                    <div class="mt-6 rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <!-- Dark Navy Header Banner -->
                        <div class="bg-[#0B2A4A] px-8 py-6 text-white flex items-center justify-between">
                            <span class="text-xl font-black tracking-wider">NEXT<span style="color: {{ $branding->button_color }};">PLAY</span></span>
                            <span class="text-[11px] uppercase tracking-[.25em] text-slate-300 font-extrabold">PLAY MORE. LIVE BETTER.</span>
                        </div>

                        <!-- Email Body -->
                        <div class="p-8 sm:p-10 text-slate-700 text-xs space-y-6">
                            <!-- Eyebrow & Heading -->
                            <div>
                                <span class="text-[11px] uppercase tracking-wider font-extrabold text-slate-400">ORDER UPDATE</span>
                                <h1 class="mt-1 text-2xl font-black text-brand-ink" x-text="resolve('{{ addslashes($template->heading) }}')">
                                    {{ $template->heading }}
                                </h1>
                            </div>

                            <!-- Greeting & Intro -->
                            <div class="text-sm leading-relaxed text-slate-600 space-y-2">
                                <p class="font-bold text-slate-800">Hi <span x-text="currentOrder.customer_name"></span>,</p>
                                <p x-text="resolve('{{ addslashes($template->intro_message ?? 'We wanted to let you know that the estimated delivery date for your order has been updated. Holiday schedules may impact carrier delivery times.') }}')"></p>
                            </div>

                            <!-- Order Summary Row with CTA Button -->
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-wrap items-center justify-between gap-4 shadow-sm">
                                <div>
                                    <span class="font-extrabold text-brand-ink text-sm">Order <span x-text="currentOrder.order_number"></span></span>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Placed on <span x-text="currentOrder.order_date"></span></p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-xl px-5 py-2.5 font-bold text-white text-xs shadow-sm transition hover:opacity-95"
                                    style="background-color: {{ $branding->button_color }} !important;"
                                >
                                    {{ $template->cta_label ?? 'View Order' }} →
                                </button>
                            </div>

                            <!-- Previous Estimate vs Updated Estimate Comparison Card -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-6 grid grid-cols-[1fr_auto_1fr] items-center text-center gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="mb-2 text-slate-400">
                                        <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">PREVIOUS ESTIMATE</span>
                                    <p class="mt-1 font-extrabold text-brand-ink text-sm" x-text="currentOrder.previous_estimate"></p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                                <span class="text-slate-300 text-2xl font-bold">→</span>
                                <div class="flex flex-col items-center">
                                    <div class="mb-2 text-[#CF5D38]">
                                        <svg class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">UPDATED ESTIMATE</span>
                                    <p class="mt-1 font-extrabold text-sm" style="color: {{ $branding->button_color }};" x-text="currentOrder.updated_estimate"></p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                            </div>

                            <!-- Holiday Delay Reason Banner (stu.png) -->
                            <div class="rounded-2xl border border-orange-200 bg-orange-50/70 p-4 flex items-start gap-3.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#CF5D38] text-base font-black text-white">❄</span>
                                <div>
                                    <strong class="text-orange-950 font-extrabold text-xs">Christmas Day affects the original delivery date.</strong>
                                    <p class="mt-1 text-orange-900 leading-relaxed text-[11px]" x-text="currentOrder.holiday_reason || 'Due to the Christmas Day holiday, carrier operations are running on a modified schedule, which may cause delays.'"></p>
                                </div>
                            </div>

                            <!-- Support Help Line (stu.png) -->
                            <div class="border-t border-slate-100 pt-6 flex flex-wrap items-center justify-between gap-4 text-slate-500 text-[11px]">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🎧</span>
                                    <span><strong>Need help?</strong> If you have any questions about your order, we're here to help.</span>
                                </div>
                                <div class="flex items-center gap-3 text-brand-ink">
                                    <a href="#" class="underline text-brand-ink font-semibold">support@nextplay.com</a>
                                    <span class="text-slate-300">|</span>
                                    <span class="font-semibold text-slate-700">1-800-555-PLAY (7529)</span>
                                    <span class="text-slate-300">|</span>
                                    <span class="font-bold cursor-pointer" style="color: {{ $branding->button_color }};">Live Chat</span>
                                </div>
                            </div>

                            <!-- Email Footer (stu.png) -->
                            <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-[10px] text-slate-400">
                                <div>
                                    <div class="font-black text-sm text-brand-ink">
                                        NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                                    </div>
                                    <p class="mt-1">© 2024 NextPlay. All rights reserved. | 1234 Play Way, Austin, TX 78701</p>
                                </div>
                                <div class="flex flex-col sm:items-end gap-2">
                                    <div class="flex items-center gap-3 text-slate-600 text-xs">
                                        <span title="Instagram">📸</span>
                                        <span title="YouTube">▶</span>
                                        <span title="Facebook">📘</span>
                                        <span title="X">𝕏</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-400 text-[10px]">
                                        <a href="#" class="hover:underline">Privacy Policy</a>
                                        <span>|</span>
                                        <a href="#" class="hover:underline">Terms of Service</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
