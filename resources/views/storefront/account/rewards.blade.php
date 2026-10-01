<x-storefront.account.rewards.page
    :seo="$seo"
    title="MY REWARDS"
    subtitle="A little more back when you shop and share."
    :navigation="$navigation"
    breadcrumb="My Rewards"
    :badge="$rewards['is_example'] ? 'Example dashboard' : null"
>
    <section class="np-rewards-progress-card" aria-labelledby="rewards-progress-title">
        <div class="np-rewards-progress-card__progress">
            <p class="np-rewards-section-kicker" id="rewards-progress-title">YOUR PROGRESS</p>
            <div class="np-rewards-progress-card__amount">
                <strong>£{{ number_format((float) $rewards['spent'], 0) }}</strong>
                <span>of £{{ number_format((float) $rewards['target'], 0) }} spent</span>
            </div>
            <div
                class="np-rewards-progress-bar"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="{{ (int) $rewards['progress_percent'] }}"
                aria-label="Reward progress"
            >
                <span style="width: {{ (int) $rewards['progress_percent'] }}%"></span>
            </div>
            <p class="np-rewards-progress-card__remaining">£{{ number_format((float) $rewards['remaining'], 0) }} until your next £{{ number_format((float) $rewards['reward_value'], 0) }} reward</p>
        </div>

        <div class="np-rewards-progress-card__available">
            <p class="np-rewards-section-kicker">AVAILABLE REWARDS</p>
            <strong>£{{ number_format((float) $rewards['available_rewards'], 0) }}</strong>
            <p>Rewards will appear here once you’ve earned them.</p>
            <a href="{{ route('products.index') }}" class="btn btn-secondary np-rewards-wide-action">
                <span>SHOP NOW</span>
                <x-storefront.account.rewards.icon name="arrow-right" :size="20" />
            </a>
        </div>
    </section>

    <x-storefront.account.rewards.referral-banner :action-href="route('account.referrals')" />

    <section class="np-rewards-panel" aria-labelledby="reward-activity-title">
        <h2 id="reward-activity-title">REWARD ACTIVITY</h2>
        <div class="np-rewards-table-wrap">
            <table class="np-rewards-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Activity</th>
                        <th scope="col">Progress</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rewards['activity'] as $activity)
                        <tr>
                            <td data-label="Date">{{ $activity['date'] }}</td>
                            <td data-label="Activity">{{ $activity['activity'] }}</td>
                            <td data-label="Progress">£{{ number_format((float) $activity['progress'], 2) }}</td>
                            <td data-label="Status">{{ $activity['status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="np-rewards-help-row">
        <div class="np-rewards-help-row__copy">
            <span class="np-rewards-help-row__icon" aria-hidden="true"><x-storefront.account.rewards.icon name="help" :size="31" /></span>
            <div>
                <h2>How rewards work</h2>
                <p>Learn more about earning, using and referring with NEXTPLAY Rewards.</p>
            </div>
        </div>
        <a href="{{ route('terms') }}" class="np-rewards-text-link">
            <span>View our programme terms</span>
            <x-storefront.account.rewards.icon name="arrow-right" :size="20" />
        </a>
    </section>
</x-storefront.account.rewards.page>
