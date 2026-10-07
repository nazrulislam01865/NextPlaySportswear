<x-layouts.admin
    title="Email Templates"
    eyebrow="Email Customization"
    subtitle="Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step."
>
    <div class="space-y-6">
        <!-- Top bar with Global Branding link -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Home</a>
                <span>/</span>
                <span class="text-slate-400">Content</span>
                <span>/</span>
                <span class="text-brand-ink">Email Templates</span>
            </nav>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('admin.email-customization.workflow') }}"
                    class="btn btn-white inline-flex items-center gap-2 text-xs font-bold"
                >
                    <span>🧭</span>
                    <span>Workflow Guide</span>
                </a>
                <a
                    href="{{ route('admin.email-customization.branding.edit') }}"
                    class="btn btn-orange inline-flex items-center gap-2 rounded-xl text-xs font-bold !text-white shadow-sm transition hover:opacity-90"
                    style="background-color: #CF5D38 !important; color: #ffffff !important; border: 1px solid #CF5D38 !important;"
                >
                    <span class="text-sm" style="color: #ffffff !important;">⚙</span>
                    <span style="color: #ffffff !important; font-weight: 700;">Global Branding Settings</span>
                </a>
            </div>
        </div>

        <!-- Status Alert -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Notification Banner -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-start justify-between gap-3 rounded-2xl border border-orange-200 bg-orange-50/80 p-4 text-xs font-bold text-orange-900">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-orange-200 text-xs font-black text-orange-800">i</span>
                <p class="leading-relaxed">Changes you make to a template will apply to the next email sent after you publish the updated version.</p>
            </div>
            <button type="button" @click="dismissed = true" class="text-orange-500 hover:text-orange-800">✕</button>
        </div>

        <!-- Filter and Search Card -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.email-customization.templates.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_200px_200px_auto]">
                <div>
                    <input
                        type="text"
                        name="q"
                        value="{{ $currentSearch }}"
                        placeholder="Search templates by name or event..."
                        class="admin-input text-xs"
                    >
                </div>

                <div>
                    <select name="status" class="admin-input text-xs">
                        <option value="">All Statuses</option>
                        <option value="published" @selected($currentStatus === 'published')>Published</option>
                        <option value="draft" @selected($currentStatus === 'draft')>Draft</option>
                    </select>
                </div>

                <div>
                    <select name="event" class="admin-input text-xs">
                        <option value="all">All Events</option>
                        <option value="Order Placed" @selected($currentEvent === 'Order Placed')>Order Placed</option>
                        <option value="Delivery Estimate Updated" @selected($currentEvent === 'Delivery Estimate Updated')>Delivery Estimate Updated</option>
                        <option value="Order Shipped" @selected($currentEvent === 'Order Shipped')>Order Shipped</option>
                        <option value="Password Reset Requested" @selected($currentEvent === 'Password Reset Requested')>Password Reset Requested</option>
                        <option value="Referral Reward Earned" @selected($currentEvent === 'Referral Reward Earned')>Referral Reward Earned</option>
                        <option value="Account Created" @selected($currentEvent === 'Account Created')>Account Created</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-navy text-xs" style="background-color: #061F44 !important; color: #ffffff !important;">Filter</button>
                    @if($currentSearch !== '' || $currentStatus || ($currentEvent && $currentEvent !== 'all'))
                        <a href="{{ route('admin.email-customization.templates.index') }}" class="btn btn-white text-xs">Clear Filters</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Templates Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 font-black uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3.5">Template Name</th>
                            <th class="px-5 py-3.5">Trigger/Event</th>
                            <th class="px-5 py-3.5">Active Version</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Last Updated</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($templates as $tmpl)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-base text-slate-600">
                                            @if($tmpl->icon === 'cart') 🛒
                                            @elseif($tmpl->icon === 'truck') 🚚
                                            @elseif($tmpl->icon === 'box') 📦
                                            @elseif($tmpl->icon === 'lock') 🔒
                                            @elseif($tmpl->icon === 'gift') 🎁
                                            @else 👤
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.email-customization.templates.edit', $tmpl->key) }}" class="font-extrabold text-brand-ink hover:text-brand-red">
                                                {{ $tmpl->name }}
                                            </a>
                                            <p class="mt-0.5 text-[11px] font-normal text-slate-500">{{ $tmpl->description }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                                        {{ $tmpl->trigger_event }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-600">
                                    {{ $tmpl->active_version }}
                                </td>
                                <td class="px-5 py-4">
                                    @if(strtolower((string) $tmpl->status) === 'published')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-extrabold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-[11px] font-extrabold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-[11px] text-slate-500">
                                    {{ $tmpl->updated_at?->format('M d, Y') ?? 'Recently' }} by Admin
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a
                                            href="{{ route('admin.email-customization.templates.edit', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                            title="Edit template content & blocks"
                                        >
                                            <span>✏</span>
                                            <span>Edit</span>
                                        </a>
                                        <a
                                            href="{{ route('admin.email-customization.templates.visibility', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                            title="Configure content visibility"
                                        >
                                            <span>👁</span>
                                            <span>Visibility</span>
                                        </a>
                                        <a
                                            href="{{ route('admin.email-customization.templates.preview', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                            title="Preview and send test email"
                                        >
                                            <span>👁</span>
                                            <span>Preview</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.email-customization.templates.duplicate', $tmpl->key) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50"
                                                title="Duplicate template"
                                            >
                                                <span>📋</span>
                                                <span>Duplicate</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                    No email templates matched your search filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-3 text-[11px] font-bold text-slate-500">
                Showing {{ count($templates) }} template(s)
            </div>
        </div>
    </div>
</x-layouts.admin>
