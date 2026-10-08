<x-layouts.admin
    title="Edit Email Template"
    eyebrow="Email Customization"
    subtitle="Update the content and settings for this email template. Use variables to personalize the message."
>
    @php
        $defaultBlocks = [
            [
                'id' => 'greeting',
                'name' => 'Greeting',
                'enabled' => true,
                'desc' => 'Personalized greeting to the customer.',
                'variables' => ['{{customer_name}}'],
                'icon' => 'greeting',
            ],
            [
                'id' => 'delivery_card',
                'name' => 'Delivery Estimate Card',
                'enabled' => true,
                'desc' => 'Shows previous and updated delivery dates.',
                'variables' => ['{{previous_estimate}}', '{{updated_estimate}}'],
                'icon' => 'delivery_card',
            ],
            [
                'id' => 'holiday_notice',
                'name' => 'Holiday Reason Notice',
                'enabled' => true,
                'desc' => 'Optional notice when delay is due to a holiday.',
                'variables' => ['{{holiday_reason}}'],
                'icon' => 'holiday_notice',
            ],
            [
                'id' => 'order_summary',
                'name' => 'Order Summary',
                'enabled' => true,
                'desc' => 'Brief order information and items.',
                'variables' => ['{{order_number}}'],
                'icon' => 'order_summary',
            ],
            [
                'id' => 'cta_button',
                'name' => 'CTA Button',
                'enabled' => true,
                'desc' => 'Button linking to the order details page.',
                'variables' => [],
                'icon' => 'cta_button',
            ],
            [
                'id' => 'support_footer',
                'name' => 'Support Footer',
                'enabled' => true,
                'desc' => 'Contact information and helpful links.',
                'variables' => [],
                'icon' => 'support_footer',
            ],
        ];

        $templateBlocks = $template->blocks ?: $defaultBlocks;

        // Ensure array items have all keys and smart icon resolution
        $initialBlocks = array_values(array_map(function($b) {
            $id = $b['id'] ?? ('block_' . uniqid());
            $icon = $b['icon'] ?? null;
            if (!$icon) {
                if (str_contains($id, 'greeting')) $icon = 'greeting';
                elseif (str_contains($id, 'delivery')) $icon = 'delivery_card';
                elseif (str_contains($id, 'holiday')) $icon = 'holiday_notice';
                elseif (str_contains($id, 'tracking')) $icon = 'tracking_card';
                elseif (str_contains($id, 'order')) $icon = 'order_summary';
                elseif (str_contains($id, 'cta') || str_contains($id, 'button')) $icon = 'cta_button';
                elseif (str_contains($id, 'support') || str_contains($id, 'footer')) $icon = 'support_footer';
                elseif (str_contains($id, 'security') || str_contains($id, 'lock')) $icon = 'security_notice';
                elseif (str_contains($id, 'reward')) $icon = 'reward_card';
                else $icon = 'custom';
            }
            return [
                'id' => $id,
                'name' => $b['name'] ?? 'Custom Block',
                'desc' => $b['desc'] ?? 'Content section block.',
                'enabled' => (bool) ($b['enabled'] ?? true),
                'variables' => (array) ($b['variables'] ?? []),
                'icon' => $icon,
            ];
        }, $templateBlocks));

        $defaultSampleData = [
            'customer_name' => 'Jordan Smith',
            'order_number' => '#NP12345678',
            'carrier' => 'UPS Ground',
            'tracking_number' => '1Z999AA1234567890',
            'estimated_delivery' => 'Nov 2, 2024',
            'previous_estimate' => 'Oct 28, 2024',
            'updated_estimate' => 'Nov 2, 2024',
            'holiday_reason' => 'This delay is due to the upcoming holiday and increased carrier volume.',
            'items_count' => '2 items',
            'order_total' => '$129.98',
            'support_email' => $branding->support_email ?? 'support@nextplay.com',
            'reward_amount' => '$25.00 Store Credit',
        ];
        $mergedSampleData = array_merge($defaultSampleData, (array) ($template->sample_data ?? []));
    @endphp

    <div
        class="space-y-6"
        x-data="{
            device: 'desktop',
            subject: '{{ addslashes($template->subject ?? 'Your delivery estimate has been updated') }}',
            preheader: '{{ addslashes($template->preheader_text ?? "Here's your new estimated delivery date.") }}',
            heading: '{{ addslashes($template->heading ?? 'Your delivery estimate has been updated') }}',
            intro: '{{ addslashes($template->intro_message ?? "We wanted to let you know that your order's estimated delivery date has changed. You can find the updated details below.") }}',
            ctaLabel: '{{ addslashes($template->cta_label ?? 'View Order Details') }}',
            ctaUrl: '{{ addslashes($template->cta_url_type ?? 'Order Details Page') }}',
            variables: ['@{{customer_name}}', '@{{order_number}}', '@{{previous_estimate}}', '@{{updated_estimate}}', '@{{holiday_reason}}'],
            blocks: {{ Js::from($initialBlocks) }},
            sampleData: {{ Js::from($mergedSampleData) }},
            draggedIdx: null,
            editingBlockIdx: null,
            showAddBlockModal: false,
            newBlock: { name: '', desc: '', variables: '' },
            resolveText(str) {
                if (!str) return '';
                let res = String(str);
                for (const [key, val] of Object.entries(this.sampleData)) {
                    if (val !== undefined && val !== null) {
                        const valStr = String(val);
                        const doubleTag = '{' + '{' + key + '}' + '}';
                        const singleTag = '{' + key + '}';
                        res = res.split(doubleTag).join(valStr);
                        res = res.split(singleTag).join(valStr);
                    }
                }
                return res;
            },
            insertVar(v) {
                this.intro += ' ' + v;
            },
            toggleBlock(index) {
                this.blocks[index].enabled = !this.blocks[index].enabled;
                this.blocks = [...this.blocks];
            },
            dropItem(targetIdx) {
                if (this.draggedIdx !== null && this.draggedIdx !== targetIdx) {
                    const item = this.blocks.splice(this.draggedIdx, 1)[0];
                    this.blocks.splice(targetIdx, 0, item);
                    this.blocks = [...this.blocks];
                }
                this.draggedIdx = null;
            },
            isBlockEnabled(id) {
                const b = this.blocks.find(x => x.id === id);
                return b ? Boolean(b.enabled) : true;
            },
            addCustomBlock() {
                if (!this.newBlock.name.trim()) return;
                const newId = 'custom_' + Date.now();
                const vars = this.newBlock.variables ? this.newBlock.variables.split(',').map(s => s.trim()) : [];
                this.blocks.push({
                    id: newId,
                    name: this.newBlock.name.trim(),
                    desc: this.newBlock.desc.trim() || 'Custom content block.',
                    enabled: true,
                    variables: vars,
                    icon: 'custom',
                });
                this.blocks = [...this.blocks];
                this.newBlock = { name: '', desc: '', variables: '' };
                this.showAddBlockModal = false;
            }
        }"
    >
        <!-- Breadcrumb & Nav (Exact match of pqr.png) -->
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5 font-medium">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-600 transition">⌂</a>
                <span>›</span>
                <span>Content</span>
                <span>›</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="text-slate-500 hover:text-brand-ink transition">Email Templates</a>
                <span>›</span>
                <span class="text-slate-600 font-medium">{{ $template->name }}</span>
            </nav>
            <h1 class="text-3xl font-black text-brand-ink tracking-tight">Edit Email Template</h1>
            <p class="text-xs text-slate-500 mt-1">Update the content and settings for this email template. Use variables to personalize the message.</p>
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

        <!-- Info Header Card (Exact match of pqr.png) -->
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <!-- Left: Icon + Template Name + Description -->
            <div class="flex items-center gap-3.5">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-700">
                    <svg class="h-6 w-6 stroke-current text-slate-700" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 3v5a1 1 0 01-1 1h-1m-4 0h-1"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-brand-ink">{{ $template->name }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $template->description ?: 'Sent when the estimated delivery date changes.' }}</p>
                </div>
            </div>

            <!-- Middle: Event Trigger & Right: Status Pills -->
            <div class="flex flex-wrap items-center gap-8 text-xs">
                <div>
                    <span class="block text-xs font-medium text-slate-500">Event Trigger</span>
                    <span class="font-extrabold text-slate-800 mt-0.5 block">{{ $template->trigger_event ?: 'Delivery Estimate Updated' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-medium text-slate-500">Status</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="rounded-full bg-[#FEF3C7] px-2.5 py-0.5 text-xs font-medium text-[#92400E]">Draft {{ $template->draft_version ?: 'v1.5' }}</span>
                        <span class="rounded-full bg-[#DCFCE7] px-2.5 py-0.5 text-xs font-medium text-[#166534]">Published {{ $template->active_version ?: 'v1.4' }}</span>
                    </div>
                    <span class="block text-[11px] text-slate-400 mt-0.5">Last published {{ $template->published_at?->format('M d, Y') ?? 'Oct 21, 2024' }} by {{ $template->publishedBy?->name ?? 'System' }}</span>
                </div>
            </div>
        </div>

        <!-- Amber Notice Banner (Exact match of pqr.png) -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-center justify-between gap-3 rounded-2xl border border-orange-200 bg-[#FFF7ED] p-4 text-xs transition-all">
            <div class="flex items-center gap-3">
                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#EA580C] text-[11px] font-black text-white">i</span>
                <p class="leading-relaxed">
                    <strong class="font-bold text-[#9A3412]">Only published changes are used for the next email send.</strong>
                    <span class="text-[#C2410C]">Save your changes as a draft to review, or publish to make them live.</span>
                </p>
            </div>
            <button type="button" @click="dismissed = true" class="text-[#EA580C] hover:text-[#9A3412] text-sm font-bold">✕</button>
        </div>

        <!-- Form and Preview Grid (Exact match of pqr.png) -->
        <form method="POST" action="{{ route('admin.email-customization.templates.update', $template->key) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(340px,.75fr)] lg:items-start">
                <!-- Left Column: Content & Blocks -->
                <div class="space-y-6">
                    <!-- Email Content Card (Exact match of pqr.png) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-brand-ink">Email Content</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Edit the main content and structure of your email template.</p>
                            </div>
                            <div x-data="{ open: false }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                                >
                                    <span>{ }</span>
                                    <span>Insert Variable</span>
                                    <span class="text-[10px]">▾</span>
                                </button>
                                <div
                                    x-show="open"
                                    @click.outside="open = false"
                                    class="absolute right-0 z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl text-xs"
                                >
                                    <template x-for="v in variables" :key="v">
                                        <button
                                            type="button"
                                            @click="insertVar(v); open = false"
                                            class="block w-full rounded-lg px-2.5 py-1.5 text-left font-mono font-bold text-slate-700 hover:bg-slate-50 hover:text-brand-red"
                                            x-text="v"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields Grid (Exact match of pqr.png) -->
                        <div class="mt-5 space-y-4">
                            <!-- Row 1: Email Subject & Preheader Text -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700">Email Subject *</label>
                                    <input type="text" x-model="subject" name="subject" maxlength="100" class="admin-input text-xs" required>
                                    <div class="mt-1 flex justify-end text-[11px] text-slate-400"><span x-text="subject.length"></span>/100</div>
                                </div>
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700 flex items-center gap-1">
                                        <span>Preheader Text</span>
                                        <span class="text-slate-400" title="Visible as snippet preview in email clients">ⓘ</span>
                                    </label>
                                    <input type="text" x-model="preheader" name="preheader" maxlength="150" class="admin-input text-xs">
                                    <div class="mt-1 flex justify-end text-[11px] text-slate-400"><span x-text="preheader.length"></span>/150</div>
                                </div>
                            </div>

                            <!-- Row 2: Email Heading & Intro Message -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700">Email Heading *</label>
                                    <input type="text" x-model="heading" name="heading" maxlength="100" class="admin-input text-xs" required>
                                    <div class="mt-1 flex justify-end text-[11px] text-slate-400"><span x-text="heading.length"></span>/100</div>
                                </div>
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700">Intro Message</label>
                                    <textarea x-model="intro" name="intro" rows="3" maxlength="500" class="admin-input text-xs"></textarea>
                                    <div class="mt-1 flex justify-end text-[11px] text-slate-400"><span x-text="intro.length"></span>/500</div>
                                </div>
                            </div>

                            <!-- Row 3: CTA Label & CTA URL -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700">CTA Label *</label>
                                    <input type="text" x-model="ctaLabel" name="cta_label" maxlength="50" class="admin-input text-xs" required>
                                    <div class="mt-1 flex justify-end text-[11px] text-slate-400"><span x-text="ctaLabel.length"></span>/50</div>
                                </div>
                                <div>
                                    <label class="admin-label text-xs font-bold text-slate-700">CTA URL *</label>
                                    <select name="cta_url" x-model="ctaUrl" class="admin-input text-xs">
                                        <option value="Order Details Page">Order Details Page</option>
                                        <option value="Storefront Homepage">Storefront Homepage</option>
                                        <option value="Account Dashboard">Account Dashboard</option>
                                        <option value="Custom URL">Custom URL</option>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-400">Links to the customer's order details page.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Blocks Card (Exact match of pqr.png) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-brand-ink">Email Blocks</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Customize the content blocks, reorder them, or show/hide sections.</p>
                            </div>
                            <button
                                type="button"
                                @click="showAddBlockModal = true"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                            >
                                <span>+</span>
                                <span>Add Block</span>
                            </button>
                        </div>

                        <!-- Blocks List with Drag & Drop (Exact match of pqr.png rows) -->
                        <div class="mt-4 space-y-2">
                            <template x-for="(block, idx) in blocks" :key="block.id">
                                <div
                                    draggable="true"
                                    @dragstart="draggedIdx = idx"
                                    @dragover.prevent
                                    @drop.prevent="dropItem(idx)"
                                    class="rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-sm transition-all hover:border-slate-300"
                                    :class="draggedIdx === idx ? 'opacity-50 border-brand-orange border-dashed' : ''"
                                >
                                    <!-- Hidden Inputs for Form Submission -->
                                    <input type="hidden" :name="`blocks[${idx}][id]`" :value="block.id">
                                    <input type="hidden" :name="`blocks[${idx}][name]`" :value="block.name">
                                    <input type="hidden" :name="`blocks[${idx}][desc]`" :value="block.desc">
                                    <input type="hidden" :name="`blocks[${idx}][enabled]`" :value="block.enabled ? '1' : '0'">

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <!-- Left: Drag Handle, Icon, Name & Description -->
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <!-- Drag Handle (⠿) -->
                                            <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 text-sm select-none" title="Drag to reorder">
                                                ⠿
                                            </span>

                                            <!-- Block Icon (Matching pqr.png) -->
                                            <div class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-600 text-xs border border-slate-100">
                                                <template x-if="block.icon === 'greeting' || block.id === 'greeting'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                </template>
                                                <template x-if="block.icon === 'delivery_card' || block.id === 'delivery_card'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 3v5a1 1 0 01-1 1h-1m-4 0h-1"/></svg>
                                                </template>
                                                <template x-if="block.icon === 'holiday_notice' || block.id === 'holiday_notice' || block.id === 'holiday_reason'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 3"/></svg>
                                                </template>
                                                <template x-if="block.icon === 'order_summary' || block.id === 'order_summary'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </template>
                                                <template x-if="block.icon === 'cta_button' || block.id === 'cta_button'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="10" rx="3" stroke-width="2"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>
                                                </template>
                                                <template x-if="block.icon === 'support_footer' || block.id === 'support_footer'">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v5m0-8h.01"/></svg>
                                                </template>
                                                <template x-if="!['greeting','delivery_card','holiday_notice','holiday_reason','order_summary','cta_button','support_footer'].includes(block.icon) && !['greeting','delivery_card','holiday_notice','holiday_reason','order_summary','cta_button','support_footer'].includes(block.id)">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" stroke-width="2"/></svg>
                                                </template>
                                            </div>

                                            <div class="min-w-0">
                                                <span class="font-extrabold text-brand-ink text-xs truncate block" x-text="block.name"></span>
                                                <span class="text-[11px] text-slate-500 truncate block" x-text="block.desc"></span>
                                            </div>
                                        </div>

                                        <!-- Right: Variables Badges, Toggle Switch, Edit & Accordion Controls (Exact match of pqr.png) -->
                                        <div class="flex items-center gap-3 shrink-0">
                                            <!-- Variable Badges -->
                                            <div class="hidden md:flex items-center gap-1.5">
                                                <template x-for="v in (block.variables || [])" :key="v">
                                                    <span class="inline-flex items-center rounded bg-[#EFF6FF] px-2 py-0.5 text-[11px] font-mono font-medium text-[#2563EB] border border-[#DBEAFE]" x-text="v"></span>
                                                </template>
                                            </div>

                                            <!-- Active Toggle Switch (Pill toggle with #10B981 on / slate-300 off) -->
                                            <button
                                                type="button"
                                                @click="toggleBlock(idx)"
                                                :style="block.enabled ? 'background-color: #10B981 !important;' : 'background-color: #CBD5E1 !important;'"
                                                style="position: relative; display: inline-flex; height: 22px; width: 42px; flex-shrink: 0; cursor: pointer; border-radius: 9999px; border: 2px solid transparent; transition: background-color 0.2s ease-in-out; outline: none; padding: 0;"
                                                :title="block.enabled ? 'Click to disable' : 'Click to enable'"
                                            >
                                                <span
                                                    :style="block.enabled ? 'transform: translateX(20px);' : 'transform: translateX(0px);'"
                                                    style="pointer-events: none; display: inline-block; height: 18px; width: 18px; border-radius: 9999px; background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.25); transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);"
                                                ></span>
                                            </button>

                                            <!-- Edit Inline Toggle (✎ Edit) -->
                                            <button
                                                type="button"
                                                @click="editingBlockIdx = (editingBlockIdx === idx ? null : idx)"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-brand-ink px-1.5 py-1 rounded hover:bg-slate-100 transition"
                                            >
                                                <svg class="h-3.5 w-3.5 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span>Edit</span>
                                            </button>

                                            <!-- Accordion Chevron Down (▾) -->
                                            <button
                                                type="button"
                                                @click="editingBlockIdx = (editingBlockIdx === idx ? null : idx)"
                                                class="text-slate-400 hover:text-slate-700 text-xs px-1"
                                            >
                                                <span :class="editingBlockIdx === idx ? 'rotate-180' : ''" class="inline-block transition-transform duration-200">▾</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Inline Block Editor -->
                                    <div x-show="editingBlockIdx === idx" x-cloak class="mt-3 border-t border-slate-100 pt-3 space-y-3 bg-slate-50/50 p-3 rounded-lg">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="text-[10px] font-bold uppercase text-slate-500">Block Name</label>
                                                <input type="text" x-model="block.name" class="admin-input text-xs mt-1">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-bold uppercase text-slate-500">Description</label>
                                                <input type="text" x-model="block.desc" class="admin-input text-xs mt-1">
                                            </div>
                                        </div>
                                        <div class="flex justify-end">
                                            <button
                                                type="button"
                                                @click="editingBlockIdx = null"
                                                class="btn btn-white text-[11px] py-1 px-3"
                                            >
                                                Done
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sticky Live Preview (Exact match of pqr.png) -->
                <div class="sticky top-24 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                            <p class="text-[11px] text-slate-500">This preview shows how the email will look to customers.</p>
                        </div>
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                            <button
                                type="button"
                                @click="device = 'desktop'"
                                :class="device === 'desktop' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500 hover:text-slate-700'"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>Desktop</span>
                            </button>
                            <button
                                type="button"
                                @click="device = 'mobile'"
                                :class="device === 'mobile' ? 'bg-slate-100 font-black text-brand-ink' : 'text-slate-500 hover:text-slate-700'"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Mobile</span>
                            </button>
                        </div>
                    </div>

                    <!-- Email Canvas Frame (Exact match of pqr.png) -->
                    <div
                        class="overflow-hidden rounded-3xl border border-slate-200/80 bg-slate-100/70 p-5 shadow-sm transition-all"
                        :class="device === 'mobile' ? 'max-w-[340px] mx-auto' : 'w-full'"
                    >
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 text-slate-700 text-xs">
                            <!-- Logo -->
                            <div class="text-center font-black text-lg text-brand-ink tracking-wider">
                                NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                            </div>

                            <!-- Heading & Intro -->
                            <div class="mt-4 text-center">
                                <h3 class="text-base font-extrabold text-brand-ink leading-tight" x-text="resolveText(heading)"></h3>
                                <p class="mt-2 leading-relaxed text-slate-500 text-xs max-w-sm mx-auto" x-text="resolveText(intro)"></p>
                            </div>

                            <!-- Dynamic Ordered Blocks in Live Preview (Exact match of pqr.png) -->
                            <div class="mt-5 space-y-3.5">
                                <template x-for="block in blocks" :key="block.id">
                                    <div x-show="Boolean(block.enabled)" x-transition.opacity.duration.200ms>
                                        <!-- Greeting Preview -->
                                        <template x-if="block.id === 'greeting'">
                                            <div class="text-slate-700 font-semibold text-xs py-1" x-text="`Hi ${sampleData.customer_name ? sampleData.customer_name.split(' ')[0] : 'Jordan'},`">
                                            </div>
                                        </template>

                                        <!-- Delivery Estimate Card Preview (Exact match of pqr.png) -->
                                        <template x-if="block.id === 'delivery_card'">
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                                                <div class="flex items-center gap-3.5">
                                                    <!-- Round Gray Badge with Truck Icon -->
                                                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-200/80 text-slate-700">
                                                        <svg class="h-5 w-5 stroke-current" fill="none" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8h4l3 3v5a1 1 0 01-1 1h-1m-4 0h-1"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <span class="block text-xs font-bold text-slate-800">Estimated Delivery</span>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <span class="line-through text-slate-400 text-xs font-medium" x-text="sampleData.previous_estimate || 'Oct 28, 2024'"></span>
                                                            <span class="text-slate-400 text-xs">→</span>
                                                            <span class="rounded-lg bg-[#DCFCE7] px-2.5 py-0.5 text-xs font-bold text-[#166534]" x-text="sampleData.updated_estimate || 'Nov 2, 2024'"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Holiday Reason Notice Preview (Exact match of pqr.png) -->
                                        <template x-if="block.id === 'holiday_notice' || block.id === 'holiday_reason'">
                                            <div class="rounded-2xl border border-[#FDE68A] bg-[#FEF3C7]/60 p-4 text-xs text-amber-900 flex items-start gap-3">
                                                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#FEF3C7] text-amber-700">
                                                    <svg class="h-4 w-4 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-xs text-amber-950">Delayed due to holiday</p>
                                                    <p class="text-[11px] text-amber-800 leading-snug mt-0.5" x-text="sampleData.holiday_reason || 'This delay is due to the upcoming holiday and increased carrier volume.'"></p>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Order Summary Preview (Exact match of pqr.png) -->
                                        <template x-if="block.id === 'order_summary'">
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-xs shadow-sm">
                                                <div class="flex items-center gap-2 font-bold text-slate-900 text-xs mb-3">
                                                    <svg class="h-4 w-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                    <span>Order Summary</span>
                                                </div>
                                                <div class="space-y-1.5 text-xs">
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Order Number</span>
                                                        <span class="font-bold text-slate-900" x-text="sampleData.order_number || '#NP12345678'"></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Items</span>
                                                        <span class="text-slate-700" x-text="sampleData.items_count || '2 items'"></span>
                                                    </div>
                                                    <div class="flex justify-between border-t border-slate-100 pt-1.5 mt-1">
                                                        <span class="text-slate-500">Total</span>
                                                        <span class="font-bold text-slate-900" x-text="sampleData.order_total || '$129.98'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- CTA Button Preview (Exact match of pqr.png) -->
                                        <template x-if="block.id === 'cta_button'">
                                            <div class="pt-2 text-center">
                                                <button
                                                    type="button"
                                                    class="w-full rounded-xl py-3 text-xs font-bold text-white shadow-sm transition hover:opacity-95"
                                                    style="background-color: {{ $branding->button_color }} !important; color: #ffffff !important;"
                                                    x-text="ctaLabel"
                                                ></button>
                                            </div>
                                        </template>

                                        <!-- Support Footer Preview (Exact match of pqr.png) -->
                                        <template x-if="block.id === 'support_footer'">
                                            <div class="pt-3 text-center text-[11px] text-slate-400">
                                                Need help? Visit our <a href="#" class="font-medium text-blue-600 underline">Help Center</a> or contact our support team.
                                            </div>
                                        </template>

                                        <!-- Carrier Tracking Card Preview (if active) -->
                                        <template x-if="block.id === 'tracking_card'">
                                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <span>🚚</span>
                                                        <span class="font-bold text-slate-700 text-xs">Carrier Tracking</span>
                                                    </div>
                                                    <span class="rounded-md bg-blue-100 px-2 py-0.5 font-bold text-blue-800 text-[10px]">In Transit</span>
                                                </div>
                                                <div class="space-y-1.5 text-[11px]">
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Carrier</span>
                                                        <span class="font-bold text-brand-ink" x-text="sampleData.carrier || 'UPS Ground'"></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Tracking Number</span>
                                                        <span class="font-mono font-bold text-slate-700" x-text="sampleData.tracking_number || '1Z999AA1234567890'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Fallback for Custom Blocks -->
                                        <template x-if="!['greeting','delivery_card','holiday_notice','holiday_reason','tracking_card','order_summary','cta_button','support_footer'].includes(block.id)">
                                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-3 text-xs">
                                                <div class="flex items-center gap-2">
                                                    <span>📦</span>
                                                    <span class="font-bold text-slate-700" x-text="block.name"></span>
                                                </div>
                                                <p class="mt-1 text-[11px] text-slate-500" x-text="block.desc"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions Bar (Exact match of pqr.png) -->
            <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <a href="{{ route('admin.email-customization.templates.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">Discard</a>
                <button type="submit" name="action" value="draft" class="rounded-xl border border-slate-300 bg-white px-5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition">Save Draft</button>
                <button
                    type="submit"
                    name="action"
                    value="publish"
                    class="rounded-xl px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important;"
                >
                    Publish Changes
                </button>
            </div>
        </form>

        <!-- Add Custom Block Modal -->
        <div
            x-cloak
            x-show="showAddBlockModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
            @keydown.escape.window="showAddBlockModal = false"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                @click.outside="showAddBlockModal = false"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-brand-ink">Add Custom Email Block</h3>
                    <button type="button" @click="showAddBlockModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>
                <div class="mt-4 space-y-3.5">
                    <div>
                        <label class="admin-label text-xs">Block Name *</label>
                        <input type="text" x-model="newBlock.name" placeholder="e.g. Return Policy" class="admin-input text-xs" required>
                    </div>
                    <div>
                        <label class="admin-label text-xs">Description</label>
                        <input type="text" x-model="newBlock.desc" placeholder="Brief explanation of this block" class="admin-input text-xs">
                    </div>
                    <div>
                        <label class="admin-label text-xs">Dynamic Variables (comma separated)</label>
                        <input type="text" x-model="newBlock.variables" placeholder="@{{tracking_url}}, @{{policy_link}}" class="admin-input text-xs">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2.5 border-t border-slate-100 pt-3">
                    <button type="button" @click="showAddBlockModal = false" class="btn btn-white text-xs">Cancel</button>
                    <button
                        type="button"
                        @click="addCustomBlock()"
                        class="btn btn-orange text-xs !text-white"
                        style="background-color: #CF5D38 !important; color: #ffffff !important;"
                    >
                        Add to Blocks
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
