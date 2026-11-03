<x-layouts.admin title="Payment Methods" subtitle="Read-only checkout payment methods and provider configuration.">
    <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="font-bold text-brand-ink">Payment methods are read only</p>
        <p class="mt-1 text-sm leading-6 text-slate-600">Administrators, including super administrators, can inspect the payment methods but cannot add, edit, delete, enable, or change their provider or amount rules.</p>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Payment methods table">
            <table class="admin-table min-w-[940px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-4 font-bold">Method</th>
                        <th class="px-4 py-4 font-bold">Footer Icon</th>
                        <th class="px-4 py-4 font-bold">Provider</th>
                        <th class="px-4 py-4 font-bold">Amount Rules</th>
                        <th class="px-4 py-4 font-bold">Behavior</th>
                        <th class="px-4 py-4 font-bold">Status</th>
                        <th class="px-5 py-4 text-right font-bold sticky right-0 bg-slate-50 z-10 border-l border-slate-200/60 shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.04)]">Access</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($methods as $method)
                        <tr class="group hover:bg-slate-50/70 transition-colors">
                            <td class="px-5 py-4">
                                <strong class="block font-black text-brand-ink text-sm">{{ $method->name }}</strong>
                                <span class="inline-block text-xs font-bold text-slate-400 font-mono mt-0.5">{{ $method->code }}</span>
                                @if($method->description)
                                    <p class="mt-1 max-w-xs truncate text-xs text-slate-500" title="{{ $method->description }}">{{ $method->description }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="shrink-0">
                                        <x-storefront.payment-mark
                                            :name="$method->name"
                                            :provider="$method->provider"
                                            :code="$method->code"
                                            :icon-url="$method->footerIconUrl()"
                                            :icon-alt="$method->footer_icon_alt"
                                        />
                                    </div>
                                    <div class="min-w-0 flex flex-col items-start gap-1">
                                        <span class="admin-status-pill px-2 py-0.5 text-[11px] font-bold {{ $method->show_in_footer && $method->is_active ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $method->show_in_footer && $method->is_active ? 'In footer' : 'Hidden' }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-slate-400">{{ filled($method->footer_icon_path) ? 'Custom icon' : 'Fallback mark' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <strong class="block text-brand-ink font-bold">{{ str($method->provider)->headline() }}</strong>
                                <span class="block text-xs font-medium text-slate-500">{{ str($method->payment_type)->replace('_', ' ')->headline() }}</span>
                                @php
                                    $gatewayReady = (bool) data_get($gatewayStatuses, $method->provider.'.configured', false);
                                    $showBadge = filled($method->badge) && strtolower($method->badge) !== strtolower($method->provider) && strtolower($method->badge) !== strtolower($method->payment_type);
                                @endphp
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $gatewayReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $gatewayReady ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        {{ $gatewayReady ? 'Configured' : 'Needs setup' }}
                                    </span>
                                    @if($showBadge)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $method->badge }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if(!$method->minimum_total && !$method->maximum_total)
                                    <span class="text-xs font-semibold text-slate-400">No limits</span>
                                @else
                                    <div class="text-xs font-semibold leading-tight text-slate-600 space-y-0.5">
                                        <span class="block">Min: {{ $method->minimum_total ? '$'.number_format((float) $method->minimum_total, 2) : 'Any' }}</span>
                                        <span class="block">Max: {{ $method->maximum_total ? '$'.number_format((float) $method->maximum_total, 2) : 'Any' }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1.5 items-center max-w-[200px]">
                                    <span class="admin-status-pill px-2 py-0.5 text-xs font-bold {{ $method->is_online ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $method->is_online ? 'Online' : 'Offline' }}
                                    </span>
                                    @if($method->requires_provider_redirect)
                                        <span class="admin-status-pill bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">Redirect</span>
                                    @endif
                                    @if($method->requires_manual_review)
                                        <span class="admin-status-pill bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700">Manual review</span>
                                    @endif
                                    @if($method->allows_saved_methods)
                                        <span class="admin-status-pill bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">Saved cards</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-1.5 flex-nowrap whitespace-nowrap">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $method->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($method->is_default)
                                        <span class="admin-status-pill bg-brand-red/10 px-2.5 py-1 text-xs font-black text-brand-red">
                                            Default
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right sticky right-0 bg-white group-hover:bg-slate-50/90 transition-colors z-10 border-l border-slate-100 shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.04)]">
                                <span class="text-xs font-semibold text-slate-500">Read only</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No payment methods have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $methods->links('pagination.nextplay') }}</div>
</x-layouts.admin>
