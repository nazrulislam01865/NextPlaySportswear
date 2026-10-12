<x-layouts.admin
    title="Email Templates"
    subtitle="Customize how transactional emails look and what content they show. Keep your messaging on-brand and your customers informed at every step."
>
    <div class="space-y-6">
        <!-- Top Action Bar (Workflow Guide & Global Branding) -->
        <div class="flex items-center justify-end gap-3">
            <a
                href="{{ route('admin.email-customization.workflow') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <span>🧭</span>
                <span>Workflow Guide</span>
            </a>
            <a
                href="{{ route('admin.email-customization.branding.edit') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold !text-white shadow-sm transition hover:opacity-95"
                style="background-color: #CF5D38 !important; color: #ffffff !important;"
            >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Global Branding Settings</span>
            </a>
        </div>

        <!-- Status Alert -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-rose-200 text-xs font-black text-rose-800">✕</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Notification Banner (Matching def.png) -->
        <div x-data="{ dismissed: false }" x-show="!dismissed" class="flex items-start justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50/90 p-4 text-xs font-bold text-amber-950 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-[#c2410c] text-xs font-black text-white shrink-0 mt-0.5">
                    i
                </span>
                <p class="leading-relaxed font-semibold text-amber-900">Changes you make to a template will apply to the next email sent after you publish the updated version.</p>
            </div>
            <button type="button" @click="dismissed = true" class="text-amber-700 hover:text-amber-950 transition text-sm font-bold">✕</button>
        </div>

        <!-- Filter Controls (Matching def.png) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.email-customization.templates.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_200px_200px_auto] items-end">
                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Search</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $currentSearch }}"
                        placeholder="Search templates by name or event..."
                        class="admin-input text-xs w-full"
                    >
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Status</label>
                    <select name="status" onchange="this.form.submit()" class="admin-input text-xs w-full">
                        <option value="">All Statuses</option>
                        <option value="published" @selected($currentStatus === 'published')>Published</option>
                        <option value="draft" @selected($currentStatus === 'draft')>Draft</option>
                    </select>
                </div>

                <!-- Trigger / Event Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Trigger/Event</label>
                    <select name="event" onchange="this.form.submit()" class="admin-input text-xs w-full">
                        <option value="all">All Events</option>
                        <option value="Order Placed" @selected($currentEvent === 'Order Placed')>Order Placed</option>
                        <option value="Delivery Estimate Updated" @selected($currentEvent === 'Delivery Estimate Updated')>Delivery Estimate Updated</option>
                        <option value="Order Shipped" @selected($currentEvent === 'Order Shipped')>Order Shipped</option>
                        <option value="Password Reset Requested" @selected($currentEvent === 'Password Reset Requested')>Password Reset Requested</option>
                        <option value="Referral Reward Earned" @selected($currentEvent === 'Referral Reward Earned')>Referral Reward Earned</option>
                        <option value="Account Created" @selected($currentEvent === 'Account Created')>Account Created</option>
                    </select>
                </div>

                <!-- Clear Filters Button -->
                <div>
                    <a
                        href="{{ route('admin.email-customization.templates.index') }}"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition w-full"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Clear Filters</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Templates Table (Matching def.png) -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="admin-table-scroll" tabindex="0" aria-label="Email templates table">
                <table class="admin-table min-w-[820px] text-xs">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Template Name</th>
                            <th class="px-5 py-3.5">Trigger/Event</th>
                            <th class="px-5 py-3.5">Active Version</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5">Last Updated</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($templates as $tmpl)
                            <tr class="hover:bg-slate-50/60 transition">
                                <!-- Template Name with Icon -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-base text-slate-700">
                                            @if($tmpl->icon === 'cart' || str_contains($tmpl->key, 'order-confirmation'))
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            @elseif($tmpl->icon === 'truck' || str_contains($tmpl->key, 'delivery-estimate'))
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                                </svg>
                                            @elseif($tmpl->icon === 'box' || str_contains($tmpl->key, 'shipment'))
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            @elseif($tmpl->icon === 'lock' || str_contains($tmpl->key, 'password'))
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            @elseif($tmpl->icon === 'gift' || str_contains($tmpl->key, 'referral'))
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V4a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm-7 4h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2a1 1 0 011-1zm2 4h10v5H7v-5z" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.email-customization.templates.edit', $tmpl->key) }}" class="font-extrabold text-brand-ink hover:text-brand-red transition block text-xs">
                                                {{ $tmpl->name }}
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- Trigger / Event -->
                                <td class="px-5 py-4 font-medium text-slate-600">
                                    {{ $tmpl->trigger_event }}
                                </td>

                                <!-- Active Version -->
                                <td class="px-5 py-4 font-bold text-slate-600">
                                    {{ $tmpl->active_version }}
                                </td>

                                <!-- Status Pill -->
                                <td class="px-5 py-4 text-center">
                                    @if(strtolower((string) $tmpl->status) === 'published')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            <span>Published</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                            <span>Draft</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Last Updated -->
                                <td class="px-5 py-4 text-slate-500 font-medium whitespace-nowrap">
                                    <div>{{ $tmpl->updated_at?->format('M d, Y') ?? 'Oct 21, 2024' }}</div>
                                    <div class="text-[11px] text-slate-400">by {{ $tmpl->publisher?->name ?? 'Admin' }}</div>
                                </td>

                                <!-- Actions (Matching def.png: Edit, Preview, Duplicate) -->
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <!-- Edit Button -->
                                        <a
                                            href="{{ route('admin.email-customization.templates.edit', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                                            title="Edit template"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>

                                        <!-- Visibility Button (Choose what to show or hide) -->
                                        <a
                                            href="{{ route('admin.email-customization.templates.visibility', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                                            title="Choose what to show and what to hide"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Visibility</span>
                                        </a>

                                        <!-- Preview Button -->
                                        <a
                                            href="{{ route('admin.email-customization.templates.preview', $tmpl->key) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                                            title="Preview email"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Preview</span>
                                        </a>

                                        <!-- Duplicate Button -->
                                        <form method="POST" action="{{ route('admin.email-customization.templates.duplicate', $tmpl->key) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                                                title="Duplicate template"
                                            >
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span>Duplicate</span>
                                            </button>
                                        </form>

                                        <!-- Delete Button (Only for copies) -->
                                        @if($tmpl->isCopy())
                                            <form method="POST" action="{{ route('admin.email-customization.templates.destroy', $tmpl->key) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this copied template? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/70 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 hover:text-rose-700 shadow-sm transition"
                                                    title="Delete copy template"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <span class="text-3xl block mb-2">✉</span>
                                    <p class="font-bold text-xs text-slate-600">No email templates found.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Try adjusting your filters above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <p>Showing 1–{{ count($templates) }} of {{ count($templates) }} templates</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
