<x-layouts.admin
    title="Create Email Template"
    eyebrow="Email Customization"
    subtitle="Choose a starter layout preset or clone an existing template to quickly create and customize transactional emails."
>
    @php
        $presetList = $presets ?? \App\Models\EmailTemplate::starterPresets();
        $eventsList = $standardEvents ?? \App\Models\EmailTemplate::standardEvents();
        $existingList = $existingTemplates ?? \App\Models\EmailTemplate::query()->orderBy('name')->get();
    @endphp

    <div
        class="space-y-6 pb-12"
        x-data="{
            selectedPreset: '{{ $selectedPreset ?? 'blank' }}',
            presets: {{ Js::from($presetList) }},
            cloneFrom: '',
            name: '',
            key: '',
            customKeyEdited: false,
            triggerEvent: 'Order Placed',
            customEvent: false,
            description: '',
            icon: 'mail',
            subject: '',
            heading: '',
            preheader: '',
            intro: '',
            ctaLabel: 'View Details',
            ctaUrl: 'order_details',
            ctaCustomUrl: '',
            actionType: 'draft',
            slugify(text) {
                return text.toString().toLowerCase().trim()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            },
            onNameChange() {
                if (!this.customKeyEdited) {
                    this.key = this.slugify(this.name);
                }
            },
            applyPreset(pKey) {
                this.selectedPreset = pKey;
                if (pKey === 'clone') {
                    return;
                }
                const p = this.presets[pKey];
                if (!p) return;

                if (!this.name || this.name.startsWith('New ')) {
                    this.name = 'New ' + p.name;
                    this.key = this.slugify(this.name);
                }
                this.triggerEvent = p.trigger_event || 'Custom Event';
                this.description = p.description || '';
                this.icon = p.icon || 'mail';
                this.subject = p.subject || '';
                this.heading = p.heading || '';
                this.preheader = p.preheader_text || '';
                this.intro = p.intro_message || '';
                this.ctaLabel = p.cta_label || 'View Details';
                this.ctaUrl = p.cta_url_type || 'order_details';
                this.ctaCustomUrl = p.cta_custom_url || '';
            },
            init() {
                this.applyPreset(this.selectedPreset);
            }
        }"
    >
        <!-- Top Action Bar -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <a
                href="{{ route('admin.email-customization.templates.index') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Back to Templates</span>
            </a>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.email-customization.workflow') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    <span>🧭</span>
                    <span>Workflow Guide</span>
                </a>
            </div>
        </div>

        <!-- Validation Errors Alert -->
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-800 shadow-sm space-y-1">
                <div class="flex items-center gap-2">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-rose-200 text-xs font-black text-rose-800">✕</span>
                    <span>Please resolve the following errors:</span>
                </div>
                <ul class="list-disc list-inside mt-2 text-rose-700 font-medium pl-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email-customization.templates.store') }}" class="space-y-6">
            @csrf

            <!-- Section 1: Choose Starter Layout Preset -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-brand-ink">1. Choose Starter Layout</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Select a pre-assembled layout or start with a clean blank canvas.</p>
                    </div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Step 1 of 3</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($presetList as $pKey => $preset)
                        <button
                            type="button"
                            @click="applyPreset('{{ $pKey }}')"
                            :class="selectedPreset === '{{ $pKey }}' ? 'border-[#CF5D38] ring-2 ring-[#CF5D38]/20 bg-orange-50/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            class="flex flex-col text-left rounded-xl border p-4 transition relative group"
                        >
                            <div class="flex items-center justify-between w-full mb-2">
                                <div class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-base">
                                    @if(($preset['icon'] ?? '') === 'cart')
                                        🛒
                                    @elseif(($preset['icon'] ?? '') === 'box')
                                        📦
                                    @elseif(($preset['icon'] ?? '') === 'truck')
                                        🚚
                                    @elseif(($preset['icon'] ?? '') === 'lock')
                                        🔒
                                    @elseif(($preset['icon'] ?? '') === 'gift')
                                        🎁
                                    @else
                                        ✉️
                                    @endif
                                </div>
                                <span
                                    x-show="selectedPreset === '{{ $pKey }}'"
                                    class="h-5 w-5 rounded-full bg-[#CF5D38] text-white text-[11px] font-black grid place-items-center"
                                >✓</span>
                            </div>
                            <h4 class="text-xs font-black text-slate-800">{{ $preset['name'] }}</h4>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ $preset['description'] }}</p>
                        </button>
                    @endforeach

                    <!-- Option: Clone from existing template -->
                    <button
                        type="button"
                        @click="selectedPreset = 'clone'"
                        :class="selectedPreset === 'clone' ? 'border-[#CF5D38] ring-2 ring-[#CF5D38]/20 bg-orange-50/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        class="flex flex-col text-left rounded-xl border p-4 transition relative group"
                    >
                        <div class="flex items-center justify-between w-full mb-2">
                            <div class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-base">
                                📋
                            </div>
                            <span
                                x-show="selectedPreset === 'clone'"
                                class="h-5 w-5 rounded-full bg-[#CF5D38] text-white text-[11px] font-black grid place-items-center"
                            >✓</span>
                        </div>
                        <h4 class="text-xs font-black text-slate-800">Clone Existing Template</h4>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2 leading-relaxed">Copy blocks, copy text, and styling from an existing template in your library.</p>
                    </button>
                </div>

                <!-- Hidden Input for preset selection -->
                <input type="hidden" name="preset" :value="selectedPreset">

                <!-- Conditional Clone Dropdown -->
                <div x-show="selectedPreset === 'clone'" x-cloak class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Select Template to Clone From *</label>
                    <select name="clone_from" x-model="cloneFrom" class="admin-input text-xs w-full max-w-md">
                        <option value="">-- Choose a template to duplicate --</option>
                        @foreach($existingList as $existTmpl)
                            <option value="{{ $existTmpl->key }}">
                                {{ $existTmpl->name }} ({{ $existTmpl->trigger_event }}) [{{ $existTmpl->key }}]
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Section 2: Template Information -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-brand-ink">2. Template Details</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Specify name, URL identifier, trigger event, and icon.</p>
                    </div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Step 2 of 3</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Template Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Template Name *</label>
                        <input
                            type="text"
                            name="name"
                            x-model="name"
                            @input="onNameChange"
                            placeholder="e.g. VIP Order Confirmation, Order Cancelled Notice"
                            maxlength="150"
                            required
                            class="admin-input text-xs w-full"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Descriptive name shown in the NextPlay admin panel.</p>
                    </div>

                    <!-- Template Key / Slug -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Template Key / Slug *</span>
                            <span class="text-[10px] text-slate-400 font-mono">Unique Identifier</span>
                        </label>
                        <input
                            type="text"
                            name="key"
                            x-model="key"
                            @input="customKeyEdited = true"
                            placeholder="e.g. vip-order-confirmation"
                            maxlength="80"
                            class="admin-input text-xs w-full font-mono text-slate-800"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Lowercase letters, numbers, and hyphens. Auto-generated from name.</p>
                    </div>

                    <!-- Trigger / Event -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">Trigger / Event *</label>
                            <button
                                type="button"
                                @click="customEvent = !customEvent"
                                class="text-[11px] font-bold text-[#CF5D38] hover:underline"
                                x-text="customEvent ? 'Choose from standard events' : '+ Enter custom event name'"
                            ></button>
                        </div>

                        <template x-if="!customEvent">
                            <select name="trigger_event" x-model="triggerEvent" class="admin-input text-xs w-full">
                                @foreach($eventsList as $ev)
                                    <option value="{{ $ev }}">{{ $ev }}</option>
                                @endforeach
                            </select>
                        </template>

                        <template x-if="customEvent">
                            <input
                                type="text"
                                name="trigger_event"
                                x-model="triggerEvent"
                                placeholder="e.g. Order Cancelled, Subscription Renewed, Back in Stock"
                                maxlength="100"
                                required
                                class="admin-input text-xs w-full"
                            >
                        </template>
                        <p class="text-[11px] text-slate-400 mt-1">The business action or workflow event that triggers this email.</p>
                    </div>

                    <!-- Icon Picker -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Template Icon</label>
                        <div class="flex items-center gap-2">
                            <select name="icon" x-model="icon" class="admin-input text-xs flex-1">
                                <option value="mail">✉️ Mail (General / Account)</option>
                                <option value="cart">🛒 Cart (Orders / Checkout)</option>
                                <option value="box">📦 Box (Shipment / Packaging)</option>
                                <option value="truck">🚚 Truck (Transit / Delivery)</option>
                                <option value="lock">🔒 Lock (Security / Passwords)</option>
                                <option value="gift">🎁 Gift (Rewards / Promos)</option>
                                <option value="bell">🔔 Bell (Alerts / Notices)</option>
                                <option value="star">⭐ Star (VIP / Special)</option>
                                <option value="user">👤 User (Profile / Welcome)</option>
                            </select>
                            <div class="h-9 w-9 shrink-0 rounded-xl border border-slate-200 bg-slate-50 grid place-items-center text-lg">
                                <span x-show="icon === 'mail'">✉️</span>
                                <span x-show="icon === 'cart'">🛒</span>
                                <span x-show="icon === 'box'">📦</span>
                                <span x-show="icon === 'truck'">🚚</span>
                                <span x-show="icon === 'lock'">🔒</span>
                                <span x-show="icon === 'gift'">🎁</span>
                                <span x-show="icon === 'bell'">🔔</span>
                                <span x-show="icon === 'star'">⭐</span>
                                <span x-show="icon === 'user'">👤</span>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Visual icon displayed in the email templates index table.</p>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Description (Optional)</label>
                    <input
                        type="text"
                        name="description"
                        x-model="description"
                        placeholder="Brief summary of when and why this email is sent..."
                        maxlength="255"
                        class="admin-input text-xs w-full"
                    >
                </div>
            </div>

            <!-- Section 3: Initial Content Defaults -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-brand-ink">3. Initial Subject & Content</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Set starting copy. You can further customize blocks, order, and styling in the editor.</p>
                    </div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Step 3 of 3</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Email Subject -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Subject *</label>
                        <input
                            type="text"
                            name="subject"
                            x-model="subject"
                            placeholder="e.g. Your NextPlay order has been confirmed"
                            maxlength="200"
                            required
                            class="admin-input text-xs w-full"
                        >
                        <div class="mt-1 flex justify-between text-[11px] text-slate-400">
                            <span>Supports variables like @{{customer_name}}</span>
                            <span x-text="(subject || '').length + '/200'"></span>
                        </div>
                    </div>

                    <!-- Preheader Text -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Preheader Snippet</label>
                        <input
                            type="text"
                            name="preheader"
                            x-model="preheader"
                            placeholder="Preview snippet visible in inbox list..."
                            maxlength="255"
                            class="admin-input text-xs w-full"
                        >
                        <div class="mt-1 flex justify-end text-[11px] text-slate-400">
                            <span x-text="(preheader || '').length + '/255'"></span>
                        </div>
                    </div>

                    <!-- Email Heading -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Heading *</label>
                        <input
                            type="text"
                            name="heading"
                            x-model="heading"
                            placeholder="e.g. Order Confirmed!, Important Account Update"
                            maxlength="200"
                            required
                            class="admin-input text-xs w-full"
                        >
                    </div>

                    <!-- Call-to-Action Button Label -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">CTA Button Label *</label>
                        <input
                            type="text"
                            name="cta_label"
                            x-model="ctaLabel"
                            placeholder="e.g. View Order, Track Package, Visit Account"
                            maxlength="100"
                            required
                            class="admin-input text-xs w-full"
                        >
                    </div>
                </div>

                <!-- Intro Message -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Intro Message</label>
                    <textarea
                        name="intro"
                        x-model="intro"
                        rows="3"
                        placeholder="Opening paragraph greeting the customer..."
                        maxlength="500"
                        class="admin-input text-xs w-full py-2.5 resize-y"
                    ></textarea>
                    <div class="mt-1 flex justify-end text-[11px] text-slate-400">
                        <span x-text="(intro || '').length + '/500'"></span>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- CTA Link Destination -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Button Destination</label>
                        <select name="cta_url" x-model="ctaUrl" class="admin-input text-xs w-full">
                            <option value="order_details">Order Details Page (Dynamic)</option>
                            <option value="account">Customer Account Dashboard</option>
                            <option value="shop">Storefront Catalog</option>
                            <option value="custom">Custom URL</option>
                        </select>
                    </div>

                    <!-- Custom CTA URL -->
                    <div x-show="ctaUrl === 'custom'">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Custom Destination URL</label>
                        <input
                            type="text"
                            name="cta_custom_url"
                            x-model="ctaCustomUrl"
                            placeholder="https://nextplay.com/special-link"
                            maxlength="255"
                            class="admin-input text-xs w-full"
                        >
                    </div>
                </div>
            </div>

            <!-- Sticky Action Footer -->
            <div class="sticky bottom-4 z-20 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/95 backdrop-blur-md p-4 shadow-lg">
                <a
                    href="{{ route('admin.email-customization.templates.index') }}"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                >
                    Cancel
                </a>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        name="action"
                        value="draft"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-800 shadow-sm hover:bg-slate-50 transition"
                    >
                        Create as Draft
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="publish"
                        class="rounded-xl px-6 py-2.5 text-xs font-black !text-white shadow-sm transition hover:opacity-95"
                        style="background-color: #CF5D38 !important; color: #ffffff !important;"
                    >
                        Create & Publish v1.0
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
