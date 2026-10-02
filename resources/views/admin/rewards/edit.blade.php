<x-layouts.admin title="Rewards Programme" eyebrow="Commerce" subtitle="Control the customer rewards and refer-a-friend values shown across the storefront.">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form method="POST" action="{{ route('admin.reward-program.update') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-card">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <h2 class="text-xl font-black text-brand-ink">Rewards settings</h2>
                    <p class="mt-1 text-sm text-slate-500">These defaults are used unless an individual customer has an override.</p>
                </div>
                <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $settings->is_active)) @disabled(!$canManageRewards)>
                    Rewards active
                </label>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <label class="block text-sm font-black text-slate-700">Default progress target (£)
                    <input class="admin-input" type="number" step="0.01" min="0.01" name="default_progress_target" value="{{ old('default_progress_target', $settings->default_progress_target) }}" required @disabled(!$canManageRewards)>
                </label>
                <label class="block text-sm font-black text-slate-700">Default next reward (£)
                    <input class="admin-input" type="number" step="0.01" min="0" name="default_reward_amount" value="{{ old('default_reward_amount', $settings->default_reward_amount) }}" required @disabled(!$canManageRewards)>
                </label>
            </div>

            <div class="mt-8 border-t border-slate-100 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-brand-ink">Refer a Friend</h2>
                        <p class="mt-1 text-sm text-slate-500">Controls the GIVE / GET banner, referral checkout discount, and referrer reward.</p>
                    </div>
                    <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                        <input type="checkbox" name="referral_enabled" value="1" @checked(old('referral_enabled', $settings->referral_enabled)) @disabled(!$canManageRewards)>
                        Referral active
                    </label>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-3">
                    <label class="block text-sm font-black text-slate-700">GIVE amount (£)
                        <input class="admin-input" type="number" step="0.01" min="0" name="referral_friend_reward_amount" value="{{ old('referral_friend_reward_amount', $settings->referral_friend_reward_amount) }}" required @disabled(!$canManageRewards)>
                    </label>
                    <label class="block text-sm font-black text-slate-700">GET amount (£)
                        <input class="admin-input" type="number" step="0.01" min="0" name="referral_referrer_reward_amount" value="{{ old('referral_referrer_reward_amount', $settings->referral_referrer_reward_amount) }}" required @disabled(!$canManageRewards)>
                    </label>
                    <label class="block text-sm font-black text-slate-700">Minimum friend order (£)
                        <input class="admin-input" type="number" step="0.01" min="0" name="referral_minimum_order" value="{{ old('referral_minimum_order', $settings->referral_minimum_order) }}" required @disabled(!$canManageRewards)>
                    </label>
                </div>
            </div>

            @if($canManageRewards)
                <div class="mt-7 flex justify-end"><button class="btn btn-red" type="submit">Save rewards settings</button></div>
            @endif
        </form>

        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-card">
            <p class="text-[10px] font-black uppercase tracking-[.18em] text-slate-400">Current customer-facing offer</p>
            <h2 class="mt-3 font-display text-4xl font-bold uppercase text-brand-ink">GIVE £{{ number_format((float)$settings->referral_friend_reward_amount, 0) }}. GET £{{ number_format((float)$settings->referral_referrer_reward_amount, 0) }}.</h2>
            <p class="mt-4 text-sm leading-6 text-slate-600">Friends receive the GIVE amount on their first eligible order of £{{ number_format((float)$settings->referral_minimum_order, 0) }} or more. The referrer receives the GET amount when that order is completed.</p>
            <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-bold leading-6 text-slate-600">Individual reward balance and progress controls are available from each customer record.</p>
        </aside>
    </div>
</x-layouts.admin>
