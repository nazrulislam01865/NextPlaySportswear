<x-layouts.admin title="Payment Methods" subtitle="Manage checkout payment choices, footer payment icons, amount rules, provider behavior, and manual review settings.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold leading-6 text-slate-500">These payment methods are loaded dynamically in checkout. Active methods can also be shown in the storefront footer with an uploaded logo or an automatic fallback mark.</p>
            <p class="mt-1 text-xs font-semibold leading-5 text-slate-400">Footer icons never store or expose gateway credentials; they are presentation-only and remain tied to the same payment method record.</p>
        </div>
        <a href="{{ route('admin.payment-methods.create') }}" class="btn btn-red">+ Add Payment Method</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Payment methods table">
            <table class="admin-table min-w-[1180px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Method</th>
                        <th class="px-5 py-4">Footer Icon</th>
                        <th class="px-5 py-4">Provider</th>
                        <th class="px-5 py-4">Amount Rules</th>
                        <th class="px-5 py-4">Behavior</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($methods as $method)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-brand-ink">{{ $method->name }}</strong>
                                <span class="text-xs font-semibold text-slate-500">{{ $method->code }}</span>
                                @if($method->description)<p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">{{ $method->description }}</p>@endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <x-storefront.payment-mark
                                        :name="$method->name"
                                        :provider="$method->provider"
                                        :code="$method->code"
                                        :icon-url="$method->footerIconUrl()"
                                        :icon-alt="$method->footer_icon_alt"
                                    />
                                    <div class="min-w-0">
                                        <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->show_in_footer && $method->is_active ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $method->show_in_footer && $method->is_active ? 'Shown in footer' : 'Hidden from footer' }}
                                        </span>
                                        <span class="mt-1 block text-[11px] font-semibold text-slate-400">{{ filled($method->footer_icon_path) ? 'Uploaded icon' : 'Fallback icon' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <strong>{{ str($method->provider)->headline() }}</strong>
                                <span class="block text-xs font-semibold text-slate-500">{{ str($method->payment_type)->replace('_', ' ')->headline() }}</span>
                                @if($method->badge)<span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-700">{{ $method->badge }}</span>@endif
                                @php($gatewayReady = (bool) data_get($gatewayStatuses, $method->provider.'.configured', false))
                                <span class="mt-1 inline-block rounded-full px-2 py-1 text-xs font-black {{ $gatewayReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $gatewayReady ? 'Gateway ready' : 'Gateway not configured' }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold leading-5 text-slate-600">
                                <span class="block">Min: {{ $method->minimum_total ? '$'.number_format((float) $method->minimum_total, 2) : 'Any' }}</span>
                                <span class="block">Max: {{ $method->maximum_total ? '$'.number_format((float) $method->maximum_total, 2) : 'Any' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_online ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_online ? 'Online' : 'Offline/manual' }}</span>
                                    @if($method->requires_provider_redirect)<span class="admin-status-pill bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">Provider redirect</span>@endif
                                    @if($method->requires_manual_review)<span class="admin-status-pill bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Manual review</span>@endif
                                    @if($method->allows_saved_methods)<span class="admin-status-pill bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Saved cards</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-bold {{ $method->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $method->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($method->is_default)<span class="admin-status-pill bg-brand-red/10 px-2.5 py-1 text-xs font-bold text-brand-red">Default</span>@endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a class="admin-row-action border-slate-200" href="{{ route('admin.payment-methods.edit', $method) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" onsubmit="return confirm('Delete this payment method?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
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
