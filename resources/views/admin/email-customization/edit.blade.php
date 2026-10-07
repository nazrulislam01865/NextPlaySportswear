<x-layouts.admin
    title="Edit Email Template"
    eyebrow="Email Customization"
    subtitle="Update the content and settings for this email template. Use variables to personalize the message."
>
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
            insertVar(v) {
                this.intro += ' ' + v;
            }
        }"
    >
        <!-- Breadcrumb & Nav -->
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

        <!-- Info Header Card -->
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

            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div>
                    <span class="block text-[10px] font-bold uppercase text-slate-400">Event Trigger</span>
                    <span class="font-extrabold text-slate-700">{{ $template->trigger_event }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($template->draft_version)
                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-800">Draft {{ $template->draft_version }}</span>
                    @endif
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-extrabold text-emerald-800">Published {{ $template->active_version }}</span>
                </div>
            </div>
        </div>

        <!-- Notice Banner -->
        <div class="flex items-start gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
            <p class="leading-relaxed">Only published changes are used for the next email send. Save your changes as a draft to review, or publish to make them live.</p>
        </div>

        <!-- Form and Preview Grid -->
        <form method="POST" action="{{ route('admin.email-customization.templates.update', $template->key) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(340px,.8fr)] lg:items-start">
                <!-- Left: Content & Blocks -->
                <div class="space-y-6">
                    <!-- Email Content Card -->
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

                    <!-- Email Blocks Card -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-brand-ink">Email Blocks</h3>
                                <p class="text-xs text-slate-500">Configure content blocks and show/hide sections.</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            @php
                                $blocks = $template->blocks ?? [
                                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'desc' => 'Personalized customer greeting.'],
                                    ['id' => 'delivery_card', 'name' => 'Delivery Estimate Card', 'enabled' => true, 'desc' => 'Shows previous and updated delivery dates.'],
                                    ['id' => 'holiday_notice', 'name' => 'Holiday Reason Notice', 'enabled' => true, 'desc' => 'Optional notice when delay is due to a holiday.'],
                                    ['id' => 'order_summary', 'name' => 'Order Summary', 'enabled' => true, 'desc' => 'Order details and purchased items.'],
                                    ['id' => 'cta_button', 'name' => 'CTA Action Button', 'enabled' => true, 'desc' => 'Primary destination action button.'],
                                ];
                            @endphp

                            @foreach($blocks as $idx => $block)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/70 p-3 text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="text-slate-400">⠿</span>
                                        <div>
                                            <p class="font-extrabold text-brand-ink">{{ $block['name'] ?? ucfirst($block['id']) }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $block['desc'] ?? 'Content section block.' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="blocks[{{ $idx }}][id]" value="{{ $block['id'] }}">
                                        <input type="hidden" name="blocks[{{ $idx }}][name]" value="{{ $block['name'] ?? $block['id'] }}">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="blocks[{{ $idx }}][enabled]"
                                                value="1"
                                                @checked(!empty($block['enabled']))
                                                class="h-4 w-4 rounded border-slate-300 text-brand-red"
                                            >
                                            <span class="text-[11px] font-bold text-slate-600">Active</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right: Sticky Preview -->
                <div class="sticky top-28 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-ink">Email Preview</h3>
                            <p class="text-[11px] text-slate-500">Shows how email looks to customers.</p>
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
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm p-6 text-slate-700 text-xs">
                            <div class="text-center font-black text-base text-brand-ink">
                                NEXT<span style="color: {{ $branding->button_color }};">PLAY</span>
                            </div>

                            <div class="mt-4 text-center">
                                <h3 class="text-base font-extrabold text-brand-ink" x-text="heading"></h3>
                                <p class="mt-2 leading-relaxed text-slate-500" x-text="intro"></p>
                            </div>

                            <!-- Sample card preview -->
                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-700">Estimated Delivery</span>
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 font-bold text-emerald-800 text-[10px]">Nov 2, 2026</span>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-5 text-center">
                                <button
                                    type="button"
                                    class="inline-block rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-sm"
                                    style="background-color: {{ $branding->button_color }} !important;"
                                    x-text="ctaLabel"
                                ></button>
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-3 text-center text-[10px] text-slate-400">
                                Need help? Contact {{ $branding->support_email }}.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
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
    </div>
</x-layouts.admin>
