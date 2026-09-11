<x-layouts.admin title="{{ $customer->name }}" eyebrow="Customer Details" subtitle="Customer account, order activity, addresses, returns, and saved payment metadata.">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a class="btn btn-white" href="{{ route('admin.customers.index') }}">Back to Customers</a>
        <div class="flex flex-wrap gap-2">
            <span class="admin-status-pill border px-3 py-1.5 text-[11px] font-black uppercase tracking-wide {{ $customer->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                {{ $customer->is_active ? 'Active account' : 'Suspended account' }}
            </span>
            <span class="admin-status-pill border px-3 py-1.5 text-[11px] font-black uppercase tracking-wide {{ $customer->email_verified_at ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                {{ $customer->email_verified_at ? 'Email verified' : 'Email unverified' }}
            </span>
        </div>
    </div>

    @if($canManageCustomer || ! $customer->is_active || $customer->suspended_at)
        <section class="mb-6 rounded-3xl border {{ $customer->is_active ? 'border-slate-200 bg-white' : 'border-red-200 bg-red-50' }} p-5 shadow-card">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-[10px] font-black uppercase tracking-[.18em] {{ $customer->is_active ? 'text-slate-400' : 'text-red-700' }}">Account access</p>
                    <h2 class="mt-2 text-xl font-black text-brand-ink">
                        {{ $customer->is_active ? 'Customer can sign in' : 'Customer access is suspended' }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        @if($customer->is_active)
                            Suspending this account blocks new sign-ins and invalidates existing customer authentication sessions without deleting the customer, orders, addresses, returns, or payment metadata.
                        @else
                            This customer cannot sign in or continue using an authenticated storefront session. Reactivating restores sign-in access with the customer's existing credentials.
                        @endif
                    </p>

                    @if($customer->suspended_at)
                        <dl class="mt-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Last suspended</dt>
                                <dd class="mt-1 text-sm font-bold text-slate-800">{{ $customer->suspended_at->format('M d, Y - g:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Suspended by</dt>
                                <dd class="mt-1 text-sm font-bold text-slate-800">{{ $customer->suspendedBy?->name ?: 'Administrator unavailable' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Reason</dt>
                                <dd class="mt-1 text-sm font-semibold leading-6 text-slate-700">{{ $customer->suspension_reason ?: 'No reason recorded.' }}</dd>
                            </div>
                            @if($customer->reactivated_at)
                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Last reactivated</dt>
                                    <dd class="mt-1 text-sm font-bold text-slate-800">{{ $customer->reactivated_at->format('M d, Y - g:i A') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-[.14em] text-slate-400">Reactivated by</dt>
                                    <dd class="mt-1 text-sm font-bold text-slate-800">{{ $customer->reactivatedBy?->name ?: 'Administrator unavailable' }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                </div>

                @if($canManageCustomer)
                    <div class="w-full max-w-md shrink-0">
                        @if($customer->is_active)
                            <form method="POST" action="{{ route('admin.customers.suspend', $customer) }}" class="rounded-2xl border border-red-200 bg-red-50 p-4">
                                @csrf
                                @method('PATCH')
                                <label for="suspension_reason" class="text-[10px] font-black uppercase tracking-[.16em] text-red-700">Suspension reason <span aria-hidden="true">*</span></label>
                                <textarea
                                    id="suspension_reason"
                                    name="suspension_reason"
                                    rows="4"
                                    maxlength="500"
                                    required
                                    class="admin-input mt-2 resize-y bg-white"
                                    placeholder="Explain why this customer account is being suspended."
                                >{{ old('suspension_reason') }}</textarea>
                                @error('suspension_reason')
                                    <p class="mt-2 text-xs font-bold text-red-700">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs leading-5 text-red-700">This takes effect immediately and signs the customer out on their next request.</p>
                                <button class="btn btn-red mt-4 w-full justify-center" type="submit" onclick="return confirm('Suspend this customer account? The customer will lose storefront account access immediately.')">
                                    Suspend customer
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.customers.reactivate', $customer) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                @csrf
                                @method('PATCH')
                                <p class="text-sm font-bold text-emerald-900">Restore customer access</p>
                                <p class="mt-2 text-xs leading-5 text-emerald-800">The account and its existing order history stay unchanged. The customer will be able to sign in again.</p>
                                <button class="btn mt-4 w-full justify-center border border-green-600 bg-green-600 text-white" type="submit" onclick="return confirm('Reactivate this customer account?')">
                                    Reactivate customer
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Orders</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['orders']) }}</p>
            <p class="mt-1 text-xs font-semibold text-slate-500">{{ number_format($stats['open_orders']) }} open</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Net paid value</p>
            @if($netSpend->isEmpty())
                <p class="mt-2 text-2xl font-black text-brand-ink">$0.00</p>
            @else
                <div class="mt-2 space-y-1">
                    @foreach($netSpend as $currency => $amount)
                        <p class="text-xl font-black text-brand-ink">{{ strtoupper($currency) }} {{ number_format((float) $amount, 2) }}</p>
                    @endforeach
                </div>
            @endif
            <p class="mt-1 text-xs font-semibold text-slate-500">Paid payments minus issued refunds</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Returns / Exchanges</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['returns']) }}</p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Saved addresses</p>
            <p class="mt-2 text-2xl font-black text-brand-ink">{{ number_format($stats['addresses']) }}</p>
        </section>
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,.9fr)]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-xl font-black text-brand-ink">Account Profile</h2>
                <p class="mt-1 text-sm text-slate-500">Storefront registration and account activity.</p>
            </div>
            <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Name</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->name }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Email</dt><dd class="mt-1 break-all font-bold text-slate-800">{{ $customer->email }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Phone</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->phone ?: 'Not provided' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Company</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->company_name ?: 'Not provided' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Preferred sport</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->preferred_sport ?: 'Not provided' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Marketing consent</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->marketing_consent ? 'Opted in' : 'Not opted in' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Joined</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->created_at?->format('M d, Y - g:i A') }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Last login</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->last_login_at?->format('M d, Y - g:i A') ?: 'Never' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Email verified</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->email_verified_at?->format('M d, Y - g:i A') ?: 'Not verified' }}</dd></div>
                <div><dt class="text-[10px] font-black uppercase tracking-[.16em] text-slate-400">Welcome email</dt><dd class="mt-1 font-bold text-slate-800">{{ $customer->welcome_email_sent_at?->format('M d, Y - g:i A') ?: 'Not sent' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-xl font-black text-brand-ink">Saved Payment Methods</h2>
                <p class="mt-1 text-sm text-slate-500">Safe metadata only. Full card numbers and security codes are never shown.</p>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($paymentMethods as $method)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-black text-brand-ink">{{ $method->maskedLabel() }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ strtoupper($method->provider) }}{{ $method->nickname ? ' - '.$method->nickname : '' }}</p>
                            </div>
                            @if($method->is_default)
                                <span class="admin-status-pill border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">Default</span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Expires {{ $method->expiryLabel() }}</p>
                        @if($method->billing_name)<p class="mt-1 text-sm text-slate-500">Billing name: {{ $method->billing_name }}</p>@endif
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-500">No saved payment methods.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-brand-ink">Saved Addresses</h2>
            <p class="mt-1 text-sm text-slate-500">Billing and shipping addresses saved by this customer.</p>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse($addresses as $address)
                <article class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-black text-brand-ink">{{ $address->typeLabel() }}</p>
                            <p class="mt-1 text-sm font-bold text-slate-700">{{ $address->formattedName() ?: $customer->name }}</p>
                        </div>
                        @if($address->is_default)
                            <span class="admin-status-pill border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">Default</span>
                        @endif
                    </div>
                    @if($address->company_name)<p class="mt-3 text-sm text-slate-600">{{ $address->company_name }}</p>@endif
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $address->address_line_1 }}<br>
                        @if($address->address_line_2){{ $address->address_line_2 }}<br>@endif
                        {{ collect([$address->city, $address->state, $address->postal_code])->filter()->join(', ') }}
                        @if($address->country)<br>{{ $address->country }}@endif
                    </p>
                    @if($address->phone)<p class="mt-3 text-xs font-semibold text-slate-500">Phone: {{ $address->phone }}</p>@endif
                    @if($address->email)<p class="mt-1 break-all text-xs font-semibold text-slate-500">Email: {{ $address->email }}</p>@endif
                    @if($address->delivery_instruction)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600">{{ $address->delivery_instruction }}</p>@endif
                </article>
            @empty
                <p class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm font-semibold text-slate-500">No saved addresses.</p>
            @endforelse
        </div>
    </section>

    <section class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-black text-brand-ink">Order History</h2>
                <p class="mt-1 text-sm text-slate-500">All orders associated with this customer account.</p>
            </div>
            @if($canViewOrders)
                <a class="btn btn-white" href="{{ route('admin.orders.index', ['q' => $customer->email]) }}">Open in Orders</a>
            @endif
        </div>
        <div class="admin-table-scroll" tabindex="0" aria-label="Customer order history">
            <table class="admin-table min-w-[900px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Fulfillment</th><th class="px-5 py-3">Placed</th><th class="px-5 py-3 text-right">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                @if($canViewOrders)
                                    <a class="font-black text-brand-blue" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                @else
                                    <span class="font-black text-brand-ink">{{ $order->order_number }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><x-storefront.account.orders.status-pill :status="$order->status" /></td>
                            <td class="px-5 py-4"><x-storefront.account.orders.status-pill :status="$order->payment_status" /></td>
                            <td class="px-5 py-4 font-semibold text-slate-600">{{ $order->fulfillmentStatusLabel() }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $order->placed_at?->format('M d, Y - g:i A') ?: 'Not recorded' }}</td>
                            <td class="px-5 py-4 text-right font-black text-brand-ink">{{ strtoupper($order->currency) }} {{ number_format((float) $order->grand_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">This customer has no orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="p-5">{{ $orders->links('pagination.nextplay') }}</div>@endif
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-xl font-black text-brand-ink">Recent Returns & Exchanges</h2>
            <p class="mt-1 text-sm text-slate-500">Latest post-purchase requests created by this customer.</p>
        </div>
        <div class="admin-table-scroll" tabindex="0" aria-label="Customer returns and exchanges">
            <table class="admin-table min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-[.12em] text-slate-500">
                    <tr><th class="px-5 py-3">Request</th><th class="px-5 py-3">Order</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Requested</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentReturns as $return)
                        <tr>
                            <td class="px-5 py-4">
                                @if($canViewReturns)
                                    <a class="font-black text-brand-blue" href="{{ route('admin.returns.show', $return) }}">{{ $return->return_number }}</a>
                                @else
                                    <span class="font-black text-brand-ink">{{ $return->return_number }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($canViewOrders && $return->order)
                                    <a class="font-bold text-brand-blue" href="{{ route('admin.orders.show', $return->order) }}">{{ $return->order->order_number }}</a>
                                @else
                                    <span class="font-bold text-slate-700">{{ $return->order?->order_number ?: 'Order unavailable' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-700">{{ ucfirst($return->type) }}</td>
                            <td class="px-5 py-4"><x-storefront.account.orders.status-pill :status="$return->status" /></td>
                            <td class="px-5 py-4 text-slate-600">{{ $return->requested_at?->format('M d, Y') ?: 'Not recorded' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No return or exchange requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
