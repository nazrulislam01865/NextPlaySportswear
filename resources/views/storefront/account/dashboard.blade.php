<x-storefront.account.page
    :seo="$seo"
    title="MY ACCOUNT"
    subtitle="Manage your orders, quotes, addresses, payments, and account settings all in one place."
    :account="$account"
    :navigation="$navigation"
>
    <div class="np-account-dashboard">
        <section class="np-account-dashboard-overview">
            <div class="np-account-welcome-card">
                <div class="np-account-welcome-card__person">
                    <div class="np-account-welcome-card__avatar">{{ $account['summary']['initials'] ?? 'NP' }}</div>
                    <div>
                        <span>WELCOME BACK</span>
                        <h2>{{ $account['summary']['name'] }}</h2>
                        <p>{{ $account['summary']['email'] }}</p>
                    </div>
                </div>

                <p class="np-account-welcome-card__description">Manage your team orders, proofs, delivery details, returns, and more from your account.</p>

                <div class="np-account-welcome-card__actions">
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">
                        <span>Start New Order</span>
                        <x-storefront.account.icon name="arrow-right" :size="18" />
                    </a>
                    <a href="{{ route('quote.request') }}" class="btn btn-outline-inverse">Request Bulk Quote</a>
                </div>
            </div>

            <div class="np-account-dashboard-metrics">
                <div class="np-account-dashboard-metrics__grid">
                    <x-storefront.account.dashboard-stat icon="order-history" :value="$account['stats']['open_orders']" label="Open Orders" tone="orange" :href="route('account.orders.index')" />
                    <x-storefront.account.dashboard-stat icon="address" :value="$account['stats']['saved_addresses']" label="Saved Addresses" tone="blue" :href="route('account.addresses.index')" />
                    <x-storefront.account.dashboard-stat icon="payment" :value="$account['stats']['payment_methods']" label="Payment Methods" tone="blue" :href="route('account.payment-methods.index')" />
                    <x-storefront.account.dashboard-stat icon="rewards" :value="$account['stats']['reward_balance_display']" label="Rewards Balance" tone="orange" :href="route('account.rewards')" />
                </div>

                <div class="np-account-dashboard-status">
                    <span class="np-account-dashboard-status__icon"><x-storefront.account.icon name="secure" :size="25" /></span>
                    <span>
                        <small>Account Status</small>
                        <strong>{{ $account['summary']['membership'] ?? 'Customer account' }}</strong>
                    </span>
                    <span class="np-account-dashboard-status__badge">SECURE SESSION</span>
                </div>
            </div>
        </section>

        <section class="np-account-panel np-account-center-panel">
            <header class="np-account-panel__heading">
                <span>ACCOUNT CENTER</span>
                <h2>Quick Access to Your Account</h2>
                <p>Manage your orders, account settings, addresses, payment methods and more.</p>
            </header>

            <div class="np-account-quick-grid">
                @foreach($account['cards'] as $card)
                    <x-storefront.account.quick-card
                        :title="$card['title']"
                        :description="$card['description']"
                        :href="$card['href']"
                        :icon="$card['icon']"
                        :tone="$card['tone'] ?? 'blue'"
                    />
                @endforeach
            </div>
        </section>

        <div class="np-account-dashboard-bottom">
            <section class="np-account-panel np-account-setup-panel">
                <header class="np-account-panel__heading">
                    <span>RECOMMENDED SETUP</span>
                    <h2>Make Future Orders Faster</h2>
                    <p>Complete a few quick steps to save time on your next order.</p>
                </header>

                <ol class="np-account-setup-list">
                    @foreach($account['quickSteps'] as $index => $step)
                        <li>
                            <span>{{ $index + 1 }}</span>
                            <p>{{ $step }}</p>
                            <x-storefront.account.icon name="chevron-right" :size="17" />
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="np-account-team-help">
                <div class="np-account-team-help__icon"><x-storefront.account.icon name="team" :size="29" /></div>
                <h2>Need Team Help?</h2>
                <p>Send your team colors, roster, deadline, and artwork. We’ll prepare the right quote flow for your custom sportswear order.</p>
                <div class="np-account-team-help__actions">
                    <a href="{{ route('quote.request') }}" class="btn btn-primary">
                        <span>Request Bulk Quote</span>
                        <x-storefront.account.icon name="arrow-right" :size="18" />
                    </a>
                    <a href="{{ route('products.index') }}" class="btn btn-outline">Browse Products</a>
                </div>
            </section>
        </div>
    </div>
</x-storefront.account.page>
