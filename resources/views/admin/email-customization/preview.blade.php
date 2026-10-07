<x-layouts.admin
    title="Preview & Send Test Email"
    eyebrow="Email Customization"
    subtitle="Preview how this email looks and send a test to verify content, style, and personalization."
>
    <div
        class="space-y-6"
        x-data="{
            device: 'desktop',
            sampleData: 'np-12345',
            showInboxModal: false,
            recipientEmail: 'admin@nextplay.com',
            useSampleData: true,
            usePublishedVersion: true
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
                <span class="text-brand-ink">Preview & Send Test Email</span>
            </nav>

            <div class="flex items-center gap-2">
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
            </div>
        </div>

        <!-- Status Alert -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Banner -->
        <div class="flex items-start gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
            <p class="leading-relaxed">Preview reflects current settings. Publish to make changes live for the next email action.</p>
        </div>

        <!-- Grid -->
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(340px,.8fr)] lg:items-start">
            <!-- Left: Email Preview Canvas -->
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-extrabold text-brand-ink">Email Preview</h3>
                        <p class="text-xs text-slate-500">See how your email will look to customers across different devices.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <select x-model="sampleData" class="admin-input h-9 text-xs">
                            <option value="np-12345">Sample Order #NP-12345</option>
                            <option value="np-67890">Sample Order #NP-67890</option>
                            <option value="np-99211">Sample Order #NP-99211</option>
                        </select>

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

                <!-- Realistic Device/Browser Window Frame -->
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-4 shadow-card transition-all"
                    :class="device === 'mobile' ? 'max-w-[360px] mx-auto' : 'w-full'"
                >
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <!-- Simulated browser dots -->
                        <div class="flex items-center gap-1.5 border-b border-slate-100 bg-slate-50/80 px-4 py-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                        </div>

                        <!-- Email Header -->
                        <div class="p-6 text-center">
                            <div class="font-black text-lg text-brand-ink">
                                NEXT<span class="text-[#F15A2B]">PLAY</span>
                            </div>

                            <div class="mt-4 grid h-14 w-14 place-items-center rounded-2xl bg-orange-50 mx-auto text-2xl">
                                🚚
                            </div>

                            <h3 class="mt-3 text-lg font-black text-brand-ink">
                                Your delivery estimate has been updated
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500 max-w-md mx-auto">
                                Good news! We have an updated delivery estimate for your order. You can view the latest details below.
                            </p>

                            <!-- Order Details Box -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-5 text-left text-xs">
                                <div class="grid gap-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Order Number</span>
                                        <span class="font-extrabold text-brand-ink">#NP-12345</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Estimated Delivery</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-brand-ink">Wed, Oct 23, 2026</span>
                                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-emerald-800">Updated</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Shipping Method</span>
                                        <span class="font-semibold text-slate-700">Standard Shipping</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-500">Tracking Number</span>
                                        <span class="font-mono text-slate-700">1Z999AA1234567890</span>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-6">
                                <a
                                    href="#"
                                    class="inline-block rounded-xl bg-[#F15A2B] px-6 py-3 text-xs font-black text-white shadow-sm hover:bg-[#d94a1d]"
                                >
                                    View Order Details
                                </a>
                            </div>

                            <p class="mt-4 text-[11px] text-slate-400">
                                Need help? <a href="#" class="font-bold text-[#F15A2B] underline">Contact our support team</a> anytime.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Send Test Email Form & Publish Status -->
            <div class="space-y-5">
                <!-- Test Email Card -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <h3 class="text-base font-extrabold text-brand-ink">Test Email</h3>
                    <p class="mt-1 text-xs text-slate-500">Send a test email to see how it will look in an inbox.</p>

                    <form method="POST" action="{{ route('admin.email-customization.templates.send-test', $template->key) }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="admin-label text-xs">Recipient Email</label>
                            <input
                                type="email"
                                x-model="recipientEmail"
                                name="recipient_email"
                                required
                                class="admin-input text-xs"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">Enter an email address to receive the test email.</p>
                        </div>

                        <div>
                            <label class="admin-label text-xs">Subject Preview</label>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-semibold text-slate-700">
                                Your delivery estimate has been updated
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">This is the subject line that will be used for the test email.</p>
                        </div>

                        <div class="space-y-3 border-t border-slate-100 pt-3">
                            <label class="flex items-center justify-between text-xs font-bold text-slate-700">
                                <div>
                                    <span>Use sample data</span>
                                    <p class="text-[11px] font-normal text-slate-400">Populate dynamic fields (customer name, tracking info).</p>
                                </div>
                                <input type="checkbox" x-model="useSampleData" class="h-4 w-4 rounded border-slate-300 text-brand-red">
                            </label>

                            <label class="flex items-center justify-between text-xs font-bold text-slate-700">
                                <div>
                                    <span>Use current published version</span>
                                    <p class="text-[11px] font-normal text-slate-400">Send using published v1.4 instead of unsaved changes.</p>
                                </div>
                                <input type="checkbox" x-model="usePublishedVersion" class="h-4 w-4 rounded border-slate-300 text-brand-red">
                            </label>
                        </div>

                        <div class="rounded-xl bg-sky-50 p-3 text-[11px] text-sky-800 flex items-start gap-2">
                            <span>ℹ</span>
                            <span>The test email will be sent using sample data for Order #NP-12345.</span>
                        </div>

                        <button
                            type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#F15A2B] px-5 py-2.5 text-xs font-black text-white hover:bg-[#d94a1d]"
                        >
                            <span>✈</span>
                            <span>Send Test Email</span>
                        </button>
                    </form>
                </div>

                <!-- Publish Status Card -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Publish Status</h3>

                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-extrabold text-emerald-800">Published</span>
                            <span class="font-bold text-slate-600 text-xs">v1.4</span>
                        </div>
                        <span class="text-[11px] text-slate-400">Last updated Oct 21, 2026 by Admin</span>
                    </div>

                    <div class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50/80 p-3 text-xs font-bold text-emerald-900 border border-emerald-200">
                        <span class="text-emerald-600">✓</span>
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
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Published Template Preview
                    </span>
                    <button type="button" @click="showInboxModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-base">✕</button>
                </div>

                <!-- Simulated Gmail Header -->
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-brand-ink">Delivery estimate updated for your order</h2>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Inbox</span>
                        </div>
                        <span class="text-xs text-slate-400">Oct 21, 2026, 9:14 AM</span>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-[#0B2A4A] font-black text-white text-sm">
                            NP
                        </div>
                        <div class="text-xs">
                            <span class="font-extrabold text-brand-ink">NextPlay</span>
                            <span class="text-slate-400">&lt;noreply@nextplay.com&gt;</span>
                            <p class="text-[11px] text-slate-500">to me ▾</p>
                        </div>
                    </div>

                    <!-- Email Container matching stu.png -->
                    <div class="mt-6 rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <!-- Top banner -->
                        <div class="bg-[#0B2A4A] p-6 text-white flex items-center justify-between">
                            <span class="text-lg font-black tracking-tight">NEXT<span class="text-[#F15A2B]">PLAY</span></span>
                            <span class="text-[10px] uppercase tracking-widest text-slate-300 font-bold">Play More. Live Better.</span>
                        </div>

                        <!-- Content -->
                        <div class="p-8 text-slate-700 text-xs">
                            <span class="text-[10px] uppercase tracking-widest font-extrabold text-slate-400">Order Update</span>
                            <h1 class="mt-2 text-2xl font-black text-brand-ink">Delivery estimate updated for your order</h1>

                            <p class="mt-4 leading-relaxed text-slate-600 text-sm">
                                Hi Jordan,<br>
                                We wanted to let you know that the estimated delivery date for your order has been updated. Holiday schedules may impact carrier delivery times.
                            </p>

                            <!-- Order info banner -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 flex items-center justify-between">
                                <div>
                                    <span class="font-extrabold text-brand-ink text-sm">Order #NP-12345</span>
                                    <p class="text-[11px] text-slate-400">Placed on Oct 18, 2026</p>
                                </div>
                                <a href="#" class="rounded-xl bg-[#F15A2B] px-5 py-2.5 font-bold text-white text-xs">View Order →</a>
                            </div>

                            <!-- Previous vs Updated comparison -->
                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 flex items-center justify-around">
                                <div class="text-center">
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Previous Estimate</span>
                                    <p class="mt-1 font-extrabold text-brand-ink text-sm">Tue, Dec 24, 2026</p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                                <span class="text-slate-300 text-xl font-bold">→</span>
                                <div class="text-center">
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Updated Estimate</span>
                                    <p class="mt-1 font-extrabold text-[#F15A2B] text-sm">Fri, Dec 27, 2026</p>
                                    <p class="text-[10px] text-slate-400">Arriving by end of day</p>
                                </div>
                            </div>

                            <!-- Holiday Reason Card -->
                            <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50/70 p-4 flex items-start gap-3">
                                <span class="text-xl">❄</span>
                                <div>
                                    <strong class="text-orange-950 font-extrabold">Christmas Day affects the original delivery date.</strong>
                                    <p class="mt-1 text-orange-900 leading-relaxed text-[11px]">Due to the Christmas Day holiday, carrier operations are running on a modified schedule, which may cause delays.</p>
                                </div>
                            </div>

                            <!-- Support Help line -->
                            <div class="mt-8 border-t border-slate-100 pt-6 flex items-center justify-between text-slate-500 text-[11px]">
                                <div class="flex items-center gap-2">
                                    <span>🎧</span>
                                    <span>Need help? Contact <a href="#" class="underline text-brand-ink font-bold">support@nextplay.com</a> | 1-800-555-PLAY</span>
                                </div>
                                <a href="#" class="font-bold text-[#F15A2B]">Live Chat</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-slate-50 border-t border-slate-100 p-6 text-center text-[10px] text-slate-400">
                            <span class="font-black text-brand-ink">NEXTPLAY</span>
                            <p class="mt-1">© 2026 NextPlay. All rights reserved. | 1234 Play Way, Austin, TX 78701</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
