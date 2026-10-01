<x-storefront.account.rewards.page
    :seo="$seo"
    title="REFER A FRIEND"
    subtitle="Share NEXTPLAY with friends. You’ll both get a little more back."
    :navigation="$navigation"
    breadcrumb="Refer a Friend"
>
    <x-storefront.account.rewards.referral-banner variant="referral" />

    <div class="np-referral-share-grid">
        <section
            class="np-rewards-panel np-referral-share-card"
            x-data="referralShareActions(@js([
                'url' => $referrals['share_url'],
                'title' => 'NEXTPLAY Refer a Friend',
                'text' => 'Use my NEXTPLAY referral link and get £5 off your first eligible order of £50 or more.',
            ]))"
            aria-labelledby="share-link-title"
        >
            <h2 id="share-link-title">SHARE YOUR LINK</h2>
            <p class="np-referral-share-card__intro">Copy your unique link and share it with friends. When they place an eligible order, you’ll both get £5.</p>

            <div class="np-referral-copy-row">
                <label class="sr-only" for="referral-share-url">Your unique referral link</label>
                <input id="referral-share-url" type="url" value="{{ $referrals['share_url'] }}" readonly x-ref="shareUrl">
                <button type="button" class="btn btn-secondary" @click="copyLink" :aria-busy="busy.toString()">
                    <x-storefront.account.rewards.icon name="copy" :size="20" />
                    <span x-text="copied ? 'COPIED' : 'COPY LINK'">COPY LINK</span>
                </button>
            </div>

            <div class="np-referral-share-actions">
                <a href="{{ $referrals['email_share_url'] }}" class="btn btn-outline">
                    <x-storefront.account.rewards.icon name="mail" :size="22" />
                    <span>SHARE BY EMAIL</span>
                </a>
                <button type="button" class="btn btn-outline" @click="shareLink" :aria-busy="busy.toString()">
                    <x-storefront.account.rewards.icon name="share" :size="22" />
                    <span>SHARE LINK</span>
                </button>
            </div>

            <div class="np-referral-note">
                <x-storefront.account.rewards.icon name="info" :size="22" />
                <p>Rewards are added after eligible orders are completed and the returns period has passed.</p>
            </div>
        </section>

        <section class="np-rewards-panel np-referral-how-card" aria-labelledby="how-it-works-title">
            <h2 id="how-it-works-title">HOW IT WORKS</h2>
            <ol>
                @foreach ($referrals['steps'] as $index => $step)
                    <li>
                        <span class="np-referral-step-number">{{ $index + 1 }}</span>
                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <section class="np-rewards-panel np-referrals-table-panel" aria-labelledby="your-referrals-title">
        <div class="np-referrals-table-panel__heading">
            <h2 id="your-referrals-title">YOUR REFERRALS</h2>
            @if ($referrals['is_example'])
                <span>Example account data</span>
            @endif
        </div>
        <div class="np-rewards-table-wrap">
            <table class="np-rewards-table np-referrals-table">
                <thead>
                    <tr>
                        <th scope="col">Friend</th>
                        <th scope="col">Order status</th>
                        <th scope="col">Your reward</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($referrals['referrals'] as $referral)
                        <tr>
                            <td data-label="Friend">{{ $referral['friend'] }}</td>
                            <td data-label="Order status"><x-storefront.account.rewards.status-pill :status="$referral['status']" /></td>
                            <td data-label="Your reward">{{ $referral['reward'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="np-rewards-panel np-referral-questions" aria-labelledby="common-questions-title">
        <h2 id="common-questions-title">COMMON QUESTIONS</h2>
        <div class="np-referral-questions__grid">
            @foreach ($referrals['questions'] as $question)
                <article>
                    <h3>{{ $question['question'] }}</h3>
                    <p>{{ $question['answer'] }}</p>
                </article>
            @endforeach
            <article>
                <h3>Full terms and conditions</h3>
                <a href="{{ route('terms') }}" class="np-rewards-text-link">
                    <span>View our programme terms</span>
                    <x-storefront.account.rewards.icon name="arrow-right" :size="20" />
                </a>
            </article>
        </div>
    </section>
</x-storefront.account.rewards.page>
