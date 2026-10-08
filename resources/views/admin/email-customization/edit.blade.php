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
            'estimated_delivery' => 'Oct 24, 2026',
            'previous_estimate' => 'Oct 28, 2026',
            'updated_estimate' => 'Nov 2, 2026',
            'holiday_reason' => 'Christmas Day affects carrier schedule and delivery volume.',
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
            subject: '{{ addslashes($template->subject) }}',
            preheader: '{{ addslashes($template->preheader_text ?? '') }}',
            heading: '{{ addslashes($template->heading) }}',
            intro: '{{ addslashes($template->intro_message ?? '') }}',
            ctaLabel: '{{ addslashes($template->cta_label ?? '') }}',
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
                        res = res.replaceAll(`@{{${key}}}`, String(val));
                        res = res.replaceAll(`{{${key}}}`, String(val));
                        res = res.replaceAll(`{${key}}`, String(val));
                    }
                }
                return res;
            },
            insertVar(v) {
                this.intro += ' ' + v;
            },
            moveUp(index) {
                if (index > 0) {
                    const item = this.blocks.splice(index, 1)[0];
                    this.blocks.splice(index - 1, 0, item);
                    this.blocks = [...this.blocks];
                }
            },
            moveDown(index) {
                if (index < this.blocks.length - 1) {
                    const item = this.blocks.splice(index, 1)[0];
                    this.blocks.splice(index + 1, 0, item);
                    this.blocks = [...this.blocks];
                }
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
        <!-- Breadcrumb & Nav (Matching pqr.png) -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <a href="{{ route('admin.email-customization.templates.index') }}" class="hover:text-slate-700">Email Templates</a>
                <span>/</span>
                <span class="text-brand-ink">{{ $template->name }}</span>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.email-customization.templates.visibility', $template->key) }}" class="btn btn-white text-xs">
                    Content Visibility Settings →
                </a>
                <a href="{{ route('admin.email-customization.templates.preview', $template->key) }}" class="btn btn-white text-xs">
                    Preview & Test →
                </a>
            </div>
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

        <!-- Info Header Card (Matching pqr.png) -->
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-slate-50 text-xl text-slate-700">
                    @if($template->icon === 'cart') 🛒
                    @elseif($template->icon === 'truck') 🚚
                    @elseif($template->icon === 'box') 📦
                    @elseif($template->icon === 'lock') 🔒
                    @elseif($template->icon === 'gift') 🎁
                    @else 👤
                    @endif
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-brand-ink">{{ $template->name }}</h2>
                    <p class="text-xs text-slate-500">{{ $template->description }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 text-xs">
                <div>
                    <span class="block text-[10px] font-bold uppercase text-slate-400">Event Trigger</span>
                    <span class="font-extrabold text-slate-700">{{ $template->trigger_event }}</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase text-slate-400">Status</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        @if($template->draft_version)
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-800">Draft {{ $template->draft_version }}</span>
                        @endif
                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-extrabold text-emerald-800">Published {{ $template->active_version }}</span>
                    </div>
                    <span class="block text-[10px] text-slate-400 mt-0.5">Last published {{ $template->published_at?->format('M d, Y') ?? 'Recently' }} by {{ $template->publishedBy?->name ?? 'System' }}</span>
                </div>
            </div>
        </div>

        <!-- Notice Banner (Matching pqr.png) -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-center justify-between gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
                <p class="leading-relaxed">Only published changes are used for the next email send. Save your changes as a draft to review, or publish to make them live.</p>
            </div>
            <button type="button" @click="dismissed = true" class="text-orange-500 hover:text-orange-800 text-sm font-bold">✕</button>
        </div>

        <!-- Form and Preview Grid -->
        <form method="POST" action="{{ route('admin.email-customization.templates.update', $template->key) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(340px,.8fr)] lg:items-start">
                <!-- Left: Content & Blocks -->
                <div class="space-y-6">
                    <!-- Email Content Card (Matching pqr.png) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-brand-ink">Email Content</h3>
                                <p class="text-xs text-slate-500">Edit the main content and structure of your email template.</p>
                            </div>
                            <div x-data="{ open: false }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100"
                                >
                                    <span>{ }</span>
                                    <span>Insert Variable</span>
                                    <span>▾</span>
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

                        <div class="mt-5 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs">Email Subject *</label>
                                    <input type="text" x-model="subject" name="subject" maxlength="100" class="admin-input text-xs" required>
                                    <div class="mt-1 flex justify-end text-[10px] text-slate-400"><span x-text="subject.length"></span>/100</div>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Preheader Text</label>
                                    <input type="text" x-model="preheader" name="preheader" maxlength="150" class="admin-input text-xs">
                                    <div class="mt-1 flex justify-end text-[10px] text-slate-400"><span x-text="preheader.length"></span>/150</div>
                                </div>
                            </div>

                            <div>
                                <label class="admin-label text-xs">Email Heading *</label>
                                <input type="text" x-model="heading" name="heading" maxlength="100" class="admin-input text-xs" required>
                                <div class="mt-1 flex justify-end text-[10px] text-slate-400"><span x-text="heading.length"></span>/100</div>
                            </div>

                            <div>
                                <label class="admin-label text-xs">Intro Message</label>
                                <textarea x-model="intro" name="intro" rows="3" maxlength="500" class="admin-input text-xs"></textarea>
                                <div class="mt-1 flex justify-end text-[10px] text-slate-400"><span x-text="intro.length"></span>/500</div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs">CTA Label *</label>
                                    <input type="text" x-model="ctaLabel" name="cta_label" maxlength="50" class="admin-input text-xs" required>
                                    <div class="mt-1 flex justify-end text-[10px] text-slate-400"><span x-text="ctaLabel.length"></span>/50</div>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">CTA URL *</label>
                                    <select name="cta_url" x-model="ctaUrl" class="admin-input text-xs">
                                        <option value="Order Details Page">Order Details Page</option>
                                        <option value="Storefront Homepage">Storefront Homepage</option>
                                        <option value="Account Dashboard">Account Dashboard</option>
                                        <option value="Custom URL">Custom URL</option>
                                    </select>
                                    <p class="mt-1 text-[10px] text-slate-400">Links to customer destination page.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Blocks Card (Matching pqr.png) -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-brand-ink">Email Blocks</h3>
                                <p class="text-xs text-slate-500">Customize the content blocks, reorder them, or show/hide sections.</p>
                            </div>
                            <button
                                type="button"
                                @click="showAddBlockModal = true"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                            >
                                <span>+</span>
                                <span>Add Block</span>
                            </button>
                        </div>

                        <!-- Blocks List with Drag & Drop + Move Up/Down Controls -->
                        <div class="mt-4 space-y-2.5">
                            <template x-for="(block, idx) in blocks" :key="block.id">
                                <div
                                    draggable="true"
                                    @dragstart="draggedIdx = idx"
                                    @dragover.prevent
                                    @drop.prevent="dropItem(idx)"
                                    class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm transition-all hover:border-slate-300"
                                    :class="draggedIdx === idx ? 'opacity-50 border-brand-orange border-dashed' : ''"
                                >
                                    <!-- Hidden Inputs for Form Submission -->
                                    <input type="hidden" :name="`blocks[${idx}][id]`" :value="block.id">
                                    <input type="hidden" :name="`blocks[${idx}][name]`" :value="block.name">
                                    <input type="hidden" :name="`blocks[${idx}][desc]`" :value="block.desc">
                                    <input type="hidden" :name="`blocks[${idx}][enabled]`" :value="block.enabled ? '1' : '0'">

                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <!-- Left: Drag Handle, Icon, Name & Description -->
                                        <div class="flex items-center gap-3 min-w-0">
                                            <!-- Drag Handle -->
                                            <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 text-sm" title="Drag to reorder">
                                                ⠿
                                            </span>

                                            <!-- Block Icon -->
                                            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-700 text-sm">
                                                <template x-if="block.icon === 'greeting' || block.id === 'greeting'">
                                                    <span>👋</span>
                                                </template>
                                                <template x-if="block.icon === 'delivery_card' || block.id === 'delivery_card'">
                                                    <span>🚚</span>
                                                </template>
                                                <template x-if="block.icon === 'holiday_notice' || block.id === 'holiday_notice' || block.id === 'holiday_reason'">
                                                    <span>⏱</span>
                                                </template>
                                                <template x-if="block.icon === 'tracking_card' || block.id === 'tracking_card'">
                                                    <span>🚚</span>
                                                </template>
                                                <template x-if="block.icon === 'order_summary' || block.id === 'order_summary'">
                                                    <span>📦</span>
                                                </template>
                                                <template x-if="block.icon === 'security_notice' || block.id === 'security_notice'">
                                                    <span>🔒</span>
                                                </template>
                                                <template x-if="block.icon === 'reward_card' || block.id === 'reward_card'">
                                                    <span>🎁</span>
                                                </template>
                                                <template x-if="block.icon === 'cta_button' || block.id === 'cta_button'">
                                                    <span>🔘</span>
                                                </template>
                                                <template x-if="block.icon === 'support_footer' || block.id === 'support_footer'">
                                                    <span>ⓘ</span>
                                                </template>
                                                <template x-if="!['greeting','delivery_card','holiday_notice','holiday_reason','tracking_card','order_summary','security_notice','reward_card','cta_button','support_footer'].includes(block.icon) && !['greeting','delivery_card','holiday_notice','holiday_reason','tracking_card','order_summary','security_notice','reward_card','cta_button','support_footer'].includes(block.id)">
                                                    <span>📦</span>
                                                </template>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="font-extrabold text-brand-ink text-xs truncate" x-text="block.name"></p>
                                                <p class="text-[11px] text-slate-500 truncate" x-text="block.desc"></p>
                                            </div>
                                        </div>

                                        <!-- Right: Variables Badges, Toggle Switch, Edit & Order Controls -->
                                        <div class="flex items-center gap-3">
                                            <!-- Variable Badges (Matching pqr.png) -->
                                            <div class="hidden sm:flex items-center gap-1.5">
                                                <template x-for="v in (block.variables || [])" :key="v">
                                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-[10px] font-bold font-mono text-blue-600 border border-blue-100" x-text="v"></span>
                                                </template>
                                            </div>

                                            <!-- Active Toggle Switch (Matching pqr.png) -->
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

                                            <!-- Edit Inline Toggle -->
                                            <button
                                                type="button"
                                                @click="editingBlockIdx = (editingBlockIdx === idx ? null : idx)"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 hover:text-brand-ink px-1.5 py-1 rounded hover:bg-slate-100"
                                            >
                                                <span>✏</span>
                                                <span>Edit</span>
                                            </button>

                                            <!-- Move Up / Down Buttons (Matching pqr.png controls) -->
                                            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                                                <button
                                                    type="button"
                                                    @click="moveUp(idx)"
                                                    :disabled="idx === 0"
                                                    :class="idx === 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:text-brand-ink hover:bg-white'"
                                                    class="rounded px-1.5 py-0.5 text-[11px] font-black transition"
                                                    title="Move up"
                                                >
                                                    ▲
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="moveDown(idx)"
                                                    :disabled="idx === blocks.length - 1"
                                                    :class="idx === blocks.length - 1 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:text-brand-ink hover:bg-white'"
                                                    class="rounded px-1.5 py-0.5 text-[11px] font-black transition"
                                                    title="Move down"
                                                >
                                                    ▼
                                                </button>
                                            </div>
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

                <!-- Right: Sticky Live Preview (Matching pqr.png) -->
                <div class="sticky top-28 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                            <p class="text-[11px] text-slate-500">This preview shows how the email will look to customers.</p>
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

                    <!-- Email Canvas Mockup (Matching pqr.png) -->
                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-4 shadow-card transition-all"
                        :class="device === 'mobile' ? 'max-w-[340px] mx-auto' : 'w-full'"
                    >
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm p-6 text-slate-700 text-xs">
                            <!-- Logo -->
                            <div class="text-center font-black text-base text-brand-ink tracking-wider">
                                NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                            </div>

                            <!-- Heading & Intro -->
                            <div class="mt-4 text-center">
                                <h3 class="text-base font-extrabold text-brand-ink" x-text="resolveText(heading)"></h3>
                                <p class="mt-2 leading-relaxed text-slate-500 text-xs" x-text="resolveText(intro)"></p>
                            </div>

                            <!-- Dynamic Ordered Blocks in Live Preview -->
                            <div class="mt-4 space-y-3.5">
                                <template x-for="block in blocks" :key="block.id">
                                    <div x-show="Boolean(block.enabled)" x-transition.opacity.duration.200ms>
                                        <!-- Greeting Preview -->
                                        <template x-if="block.id === 'greeting'">
                                            <div class="text-slate-700 font-semibold text-xs py-1" x-text="`Hi ${sampleData.customer_name ? sampleData.customer_name.split(' ')[0] : 'Jordan'},`">
                                            </div>
                                        </template>

                                        <!-- Carrier Tracking Card Preview -->
                                        <template x-if="block.id === 'tracking_card'">
                                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base">🚚</span>
                                                        <span class="font-bold text-slate-700 text-xs">Carrier Tracking</span>
                                                    </div>
                                                    <span class="rounded-md bg-blue-100 px-2 py-0.5 font-black text-blue-800 text-[10px]">In Transit</span>
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
                                                    <div class="flex justify-between border-t border-slate-200/60 pt-1.5 mt-1 text-[11px]">
                                                        <span class="text-slate-500">Estimated Delivery</span>
                                                        <span class="font-bold text-brand-ink" x-text="sampleData.estimated_delivery || 'Oct 24, 2026'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Delivery Estimate Card Preview -->
                                        <template x-if="block.id === 'delivery_card'">
                                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base">🚚</span>
                                                        <span class="font-bold text-slate-700 text-xs">Estimated Delivery</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="line-through text-slate-400 text-[11px]" x-text="sampleData.previous_estimate || 'Oct 28, 2026'"></span>
                                                        <span class="text-slate-400 text-xs">→</span>
                                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-black text-emerald-800 text-[11px]" x-text="sampleData.updated_estimate || 'Nov 2, 2026'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Holiday Reason Notice Preview -->
                                        <template x-if="block.id === 'holiday_notice' || block.id === 'holiday_reason'">
                                            <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-900 flex items-start gap-2.5">
                                                <span class="text-base leading-none">⏱</span>
                                                <div>
                                                    <p class="font-extrabold text-[11px] text-amber-950">Delayed due to holiday</p>
                                                    <p class="text-[10px] text-amber-800 leading-tight mt-0.5" x-text="sampleData.holiday_reason || 'This delay is due to the upcoming holiday and increased carrier volume.'"></p>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Order Summary Preview -->
                                        <template x-if="block.id === 'order_summary'">
                                            <div class="rounded-xl border border-slate-200 bg-white p-3.5 text-xs shadow-sm">
                                                <div class="flex items-center gap-2 font-extrabold text-brand-ink text-xs mb-2">
                                                    <span>📦</span>
                                                    <span>Order Summary</span>
                                                </div>
                                                <div class="space-y-1.5 text-[11px]">
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Order Number</span>
                                                        <span class="font-bold text-brand-ink" x-text="sampleData.order_number || '#NP12345678'"></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-slate-500">Items</span>
                                                        <span class="font-semibold text-slate-700" x-text="sampleData.items_count || '2 items'"></span>
                                                    </div>
                                                    <div class="flex justify-between border-t border-slate-100 pt-1.5">
                                                        <span class="font-bold text-slate-700">Total</span>
                                                        <span class="font-extrabold text-brand-ink" x-text="sampleData.order_total || '$129.98'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Security Notice Preview -->
                                        <template x-if="block.id === 'security_notice'">
                                            <div class="rounded-xl border border-blue-200 bg-blue-50/80 p-3 text-xs text-blue-900 flex items-start gap-2.5">
                                                <span class="text-base leading-none">🔒</span>
                                                <div>
                                                    <p class="font-extrabold text-[11px] text-blue-950">Security Notice</p>
                                                    <p class="text-[10px] text-blue-800 leading-tight mt-0.5">This secure link will expire in 60 minutes. If you did not request this, please contact support.</p>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Reward Card Preview -->
                                        <template x-if="block.id === 'reward_card'">
                                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3.5 text-center">
                                                <span class="text-xl">🎁</span>
                                                <p class="font-black text-emerald-900 text-sm mt-1" x-text="sampleData.reward_amount || '$25.00 Store Credit'"></p>
                                                <p class="text-[10px] text-emerald-700 mt-0.5">Applied directly to your NextPlay account balance.</p>
                                            </div>
                                        </template>

                                        <!-- CTA Button Preview -->
                                        <template x-if="block.id === 'cta_button'">
                                            <div class="pt-1 text-center">
                                                <button
                                                    type="button"
                                                    class="w-full rounded-xl py-3 text-xs font-black text-white shadow-sm transition hover:opacity-95"
                                                    style="background-color: {{ $branding->button_color }} !important; color: #ffffff !important;"
                                                    x-text="ctaLabel"
                                                ></button>
                                            </div>
                                        </template>

                                        <!-- Support Footer Preview -->
                                        <template x-if="block.id === 'support_footer'">
                                            <div class="pt-2 text-center text-[10px] text-slate-400">
                                                Need help? Visit our <a href="#" class="font-bold underline" style="color: {{ $branding->button_color }};">Help Center</a> or contact our support team at <span class="font-semibold text-slate-600" x-text="sampleData.support_email || '{{ $branding->support_email }}'"></span>.
                                            </div>
                                        </template>

                                        <!-- Fallback for Custom Blocks -->
                                        <template x-if="!['greeting','delivery_card','holiday_notice','holiday_reason','tracking_card','order_summary','security_notice','reward_card','cta_button','support_footer'].includes(block.id)">
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

            <!-- Bottom Actions Bar (Matching pqr.png) -->
            <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <a href="{{ route('admin.email-customization.templates.index') }}" class="btn btn-white text-xs">Discard</a>
                <button type="submit" name="action" value="draft" class="btn btn-white text-xs">Save Draft</button>
                <button
                    type="submit"
                    name="action"
                    value="publish"
                    class="btn btn-orange rounded-xl px-5 py-2.5 text-xs font-black !text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important; border: 1px solid #CF5D38 !important;"
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
