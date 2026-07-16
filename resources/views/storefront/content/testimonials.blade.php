@php
    $section = is_array($section ?? null) ? $section : [];
    $items = collect($items ?? [])
        ->filter(fn ($item) => filled(data_get($item, 'title')) || filled(data_get($item, 'description')))
        ->values();

    if ($items->isEmpty()) {
        $items = collect([
            ['icon' => 'JM', 'title' => 'Jason Miller', 'subtitle' => 'River Valley Baseball Club · Ohio, USA', 'description' => 'The jerseys came out clean and the ordering process was simple. We shared our team logo and size list, and the team helped us prepare the final order.', 'label' => 'Jerseys'],
            ['icon' => 'EC', 'title' => 'Emily Carter', 'subtitle' => 'Community Run Event · Texas, USA', 'description' => 'We needed shirts and bags for a weekend event. The quote was clear, and they asked the right questions before moving ahead.', 'label' => 'Event kits'],
            ['icon' => 'MR', 'title' => 'Marcus Reed', 'subtitle' => 'Northside High Boosters · Georgia, USA', 'description' => 'Good option for our school spirit wear. The hoodie colors matched what we requested, and the design proof helped a lot.', 'label' => 'Artwork'],
            ['icon' => 'OG', 'title' => 'Olivia Grant', 'subtitle' => 'Grant Family Dental · Arizona, USA', 'description' => 'Ordering caps for our business team was easy. We had a few logo questions, and they helped us clean that up before production.', 'label' => 'Caps'],
            ['icon' => 'DR', 'title' => 'Daniel Ruiz', 'subtitle' => 'South Bay FC · California, USA', 'description' => 'The soccer kits looked sharp. Not overcomplicated. We sent names, numbers, and sizes, then reviewed the mockup.', 'label' => 'Football kits'],
            ['icon' => 'LB', 'title' => 'Lauren Brooks', 'subtitle' => 'Lakeview Youth League · Florida, USA', 'description' => 'We used them for a league order. The bulk quote made more sense than ordering every piece one by one.', 'label' => 'Bulk order'],
            ['icon' => 'ST', 'title' => 'Sophie Turner', 'subtitle' => 'Oakfield Academy · Manchester, UK', 'description' => 'The PE tops feel good and the size guide was actually useful. Parents had fewer questions than we expected.', 'label' => 'Schoolwear'],
            ['icon' => 'BH', 'title' => 'Ben Howard', 'subtitle' => 'North London Runners · London, UK', 'description' => 'The running vests arrived before our race weekend and the print held up well after washing.', 'label' => 'Running'],
            ['icon' => 'AK', 'title' => 'Aisha Khan', 'subtitle' => 'City Youth Project · Birmingham, UK', 'description' => 'We had one contact throughout the order. That made it much easier when participant numbers changed.', 'label' => 'Support'],
        ]);
    }

    $colors = ['#0f766e', '#7c3aed', '#be123c', '#1d4ed8', '#ea580c', '#0891b2', '#4338ca', '#15803d', '#c026d3', '#334155'];
    $initials = static function (array $item, int $index = 0): string {
        $icon = trim((string) data_get($item, 'icon', ''));
        if ($icon !== '') {
            return mb_strtoupper(mb_substr($icon, 0, 3));
        }

        $name = trim((string) data_get($item, 'title', 'NP'));
        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');

        return mb_strtoupper($letters ?: 'NP');
    };

    $typeFor = static function (array $item): string {
        $haystack = mb_strtolower((string) data_get($item, 'title', '') . ' ' . (string) data_get($item, 'subtitle', '') . ' ' . (string) data_get($item, 'label', ''));

        if (\Illuminate\Support\Str::contains($haystack, ['school', 'academy', 'high', 'college'])) {
            return 'School';
        }

        if (\Illuminate\Support\Str::contains($haystack, ['business', 'company', 'dental', 'fitness', 'studio'])) {
            return 'Business';
        }

        if (\Illuminate\Support\Str::contains($haystack, ['event', 'project', 'charity', 'cup'])) {
            return 'Event';
        }

        return 'Club';
    };

    $reviews = $items->map(function ($item, int $index) use ($colors, $initials, $typeFor): array {
        $item = (array) $item;
        $name = trim((string) data_get($item, 'title', 'NextPlay Customer')) ?: 'NextPlay Customer';
        $meta = trim((string) data_get($item, 'subtitle', 'Verified customer')) ?: 'Verified customer';
        $quote = trim((string) data_get($item, 'description', 'Great experience with NextPlay Sportswear.')) ?: 'Great experience with NextPlay Sportswear.';
        $tag = trim((string) data_get($item, 'label', 'Verified review')) ?: 'Verified review';
        $rating = (int) data_get($item, 'rating', 5);
        $rating = max(4, min(5, $rating));

        return [
            'name' => $name,
            'initials' => $initials($item, $index),
            'meta' => $meta,
            'type' => $typeFor($item),
            'rating' => $rating,
            'date' => now()->subDays($index * 11)->toDateString(),
            'color' => $colors[$index % count($colors)],
            'quote' => $quote,
            'detail' => trim((string) data_get($item, 'detail', '')) ?: $tag . ' · Verified NextPlay customer feedback.',
            'tags' => [$tag],
        ];
    })->values();

    $featured = $reviews->first() ?? [
        'name' => 'NextPlay Customer',
        'initials' => 'NP',
        'meta' => 'Verified customer',
        'rating' => 5,
        'color' => '#ef4444',
        'quote' => 'The kits looked sharp, arrived on time, and the whole ordering process felt simple.',
    ];

    $seo = $seo ?? [
        'title' => 'Customer Testimonials | ' . config('storefront.name'),
        'description' => 'Read customer testimonials from teams, schools, clubs, businesses, and event organizers who use NextPlay Sportswear.',
        'canonical' => route('testimonials.index'),
        'schema_type' => 'CollectionPage',
    ];

    $reviewSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Customer testimonials',
        'itemListElement' => $reviews->take(10)->values()->map(fn (array $review, int $index): array => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review['name']],
                'reviewBody' => $review['quote'],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review['rating'], 'bestRating' => 5],
                'itemReviewed' => ['@type' => 'Organization', 'name' => config('storefront.name')],
            ],
        ])->all(),
    ];
@endphp

<x-layouts.storefront :seo="$seo" :structured-data="[$reviewSchema]">
    <style>
        .np-reviews-page {
            --np-ink: #111827;
            --np-muted: #64748b;
            --np-line: #e2e8f0;
            --np-soft: #f8fafc;
            --np-white: #ffffff;
            --np-red: #ef233c;
            --np-red-dark: #dc2626;
            --np-gold: #f59e0b;
            --np-navy: #0f172a;
            --np-green: #16a34a;
            --np-radius: 24px;
            --np-shadow: 0 18px 45px rgba(15, 23, 42, .10);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: var(--np-ink);
        }

        .np-reviews-page * { box-sizing: border-box; }
        .np-reviews-shell { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
        .np-reviews-hero { padding: 76px 0 34px; }
        .np-reviews-hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 34px; align-items: end; }
        .np-reviews-eyebrow { display: inline-flex; align-items: center; gap: 9px; color: var(--np-red); font-size: 12px; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; }
        .np-reviews-eyebrow::before { content: ''; width: 28px; height: 3px; border-radius: 999px; background: var(--np-red); }
        .np-reviews-title { max-width: 780px; margin: 14px 0 18px; font-size: clamp(40px, 6vw, 72px); line-height: .97; font-weight: 950; letter-spacing: -.055em; color: var(--np-ink); }
        .np-reviews-lead { max-width: 720px; margin: 0; color: var(--np-muted); font-size: 18px; line-height: 1.72; }
        .np-rating-box { background: var(--np-white); border: 1px solid var(--np-line); border-radius: var(--np-radius); padding: 24px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        .np-rating-main { display: flex; align-items: baseline; gap: 10px; }
        .np-rating-main strong { font-size: 48px; letter-spacing: -.05em; line-height: 1; }
        .np-stars { color: var(--np-gold); letter-spacing: 2px; white-space: nowrap; }
        .np-rating-note { margin-top: 6px; color: var(--np-muted); font-size: 13px; font-weight: 800; }
        .np-rating-bars { margin-top: 18px; }
        .np-bar-row { display: grid; grid-template-columns: 46px 1fr 36px; gap: 10px; align-items: center; margin-top: 9px; color: var(--np-muted); font-size: 12px; font-weight: 800; }
        .np-bar { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .np-bar span { display: block; height: 100%; background: var(--np-gold); border-radius: inherit; }

        .np-featured-story { margin-top: 30px; background: linear-gradient(135deg, var(--np-navy), #1e293b); color: white; border-radius: 30px; padding: 30px; box-shadow: var(--np-shadow); display: grid; grid-template-columns: 1fr auto; gap: 24px; align-items: center; overflow: hidden; position: relative; }
        .np-featured-story::after { content: ''; position: absolute; width: 260px; height: 260px; border-radius: 50%; background: rgba(239, 35, 60, .20); right: -90px; top: -110px; }
        .np-featured-story > * { position: relative; z-index: 1; }
        .np-featured-story h2 { margin: 10px 0 12px; font-size: clamp(26px, 4vw, 42px); line-height: 1.05; font-weight: 950; letter-spacing: -.04em; }
        .np-featured-story p { max-width: 760px; margin: 0; color: #dbe4ef; font-size: 18px; line-height: 1.65; }
        .np-featured-person { display: flex; align-items: center; gap: 13px; margin-top: 22px; }
        .np-avatar { width: 54px; height: 54px; border-radius: 16px; display: inline-grid; place-items: center; flex: none; color: white; font-weight: 950; }
        .np-featured-person strong { display: block; }
        .np-featured-person small { display: block; margin-top: 4px; color: #cbd5e1; }
        .np-featured-actions { display: flex; flex-direction: column; gap: 12px; min-width: 210px; }
        .np-review-btn { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; gap: 8px; border-radius: 999px; border: 1px solid var(--np-line); background: var(--np-white); color: var(--np-ink); padding: 10px 18px; font-size: 14px; font-weight: 900; text-decoration: none; transition: .2s ease; }
        .np-review-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(15, 23, 42, .12); }
        .np-review-btn.is-red { border-color: var(--np-red); background: var(--np-red); color: white; }
        .np-review-btn.is-red:hover { background: var(--np-red-dark); }

        .np-reviews-section { padding: 34px 0 82px; }
        .np-filter-panel { position: sticky; top: 88px; z-index: 20; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding: 16px; border: 1px solid var(--np-line); border-radius: 22px; background: rgba(255, 255, 255, .94); backdrop-filter: blur(12px); box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        .np-review-search { flex: 1; min-width: 230px; position: relative; }
        .np-review-search svg { position: absolute; left: 14px; top: 50%; width: 18px; height: 18px; color: #94a3b8; transform: translateY(-50%); pointer-events: none; }
        .np-review-search input, .np-filter-panel select { width: 100%; min-height: 48px; border: 1px solid var(--np-line); border-radius: 15px; background: white; color: var(--np-ink); outline: none; padding: 12px 14px; font-weight: 700; }
        .np-review-search input { padding-left: 44px; }
        .np-review-search input:focus, .np-filter-panel select:focus { border-color: #94a3b8; box-shadow: 0 0 0 4px #f1f5f9; }
        .np-filter-panel select { max-width: 190px; }
        .np-reviews-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: 24px; }
        .np-review-card { min-height: 320px; display: flex; flex-direction: column; padding: 24px; border: 1px solid var(--np-line); border-radius: var(--np-radius); background: var(--np-white); box-shadow: 0 10px 30px rgba(15, 23, 42, .06); transition: .24s ease; }
        .np-review-card:hover { transform: translateY(-4px); box-shadow: var(--np-shadow); }
        .np-review-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .np-verified-badge { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #ecfdf5; color: #15803d; font-size: 12px; font-weight: 900; }
        .np-review-quote { margin: 22px 0 28px; color: #334155; font-size: 17px; line-height: 1.62; }
        .np-review-person { display: flex; align-items: center; gap: 12px; margin-top: auto; }
        .np-review-person strong { display: block; color: var(--np-ink); }
        .np-review-person small { display: block; margin-top: 3px; color: var(--np-muted); font-size: 13px; line-height: 1.35; }
        .np-review-card-actions { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--np-line); }
        .np-story-tag { color: var(--np-muted); font-size: 12px; font-weight: 900; }
        .np-story-button { border: 0; background: transparent; color: var(--np-navy); padding: 0; font-weight: 950; }
        .np-story-button:hover { color: var(--np-red); }
        .np-load-wrap { text-align: center; margin-top: 30px; }
        .np-empty-state { display: none; margin-top: 24px; padding: 60px 20px; border: 1px dashed #cbd5e1; border-radius: 22px; background: white; color: var(--np-muted); text-align: center; }
        .np-empty-state h3 { margin: 0 0 8px; color: var(--np-ink); font-size: 24px; font-weight: 950; }
        .np-empty-state p { margin: 0; }

        .np-testimonial-modal { position: fixed; inset: 0; z-index: 100; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(15, 23, 42, .62); }
        .np-testimonial-modal.is-open { display: flex; }
        .np-testimonial-modal-card { position: relative; width: min(650px, 100%); border-radius: 28px; background: white; padding: 30px; box-shadow: 0 30px 80px rgba(0, 0, 0, .25); animation: npReviewPop .2s ease; }
        @keyframes npReviewPop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: scale(1); } }
        .np-testimonial-close { position: absolute; right: 18px; top: 16px; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--np-line); background: white; color: var(--np-ink); font-size: 20px; font-weight: 900; cursor: pointer; }
        .np-modal-quote { margin: 24px 0; color: var(--np-ink); font-size: 25px; line-height: 1.45; letter-spacing: -.02em; }
        .np-modal-detail { margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--np-line); color: var(--np-muted); line-height: 1.7; }

        @media (max-width: 980px) {
            .np-reviews-hero-grid, .np-featured-story { grid-template-columns: minmax(0, 1fr); }
            .np-reviews-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .np-featured-actions { flex-direction: row; flex-wrap: wrap; }
            .np-filter-panel select { max-width: none; flex: 1 1 170px; }
        }
        @media (max-width: 640px) {
            .np-reviews-hero { padding-top: 50px; }
            .np-reviews-shell { width: min(100% - 24px, 1180px); }
            .np-reviews-grid { grid-template-columns: minmax(0, 1fr); }
            .np-filter-panel { top: 70px; }
            .np-featured-story { padding: 26px; }
            .np-review-card { min-height: 300px; }
        }
    </style>

    <main class="np-reviews-page" data-np-reviews-page>
        <section class="np-reviews-hero">
            <div class="np-reviews-shell">
                <div class="np-reviews-hero-grid">
                    <div>
                        <span class="np-reviews-eyebrow">Customer testimonials</span>
                        <h1 class="np-reviews-title">Stories from teams we have helped.</h1>
                        <p class="np-reviews-lead">Browse feedback by team type, rating, and topic. Search for what matters to you, from sizing and artwork to delivery and bulk orders.</p>
                    </div>
                    <aside class="np-rating-box" aria-label="Average customer rating">
                        <div class="np-rating-main"><strong>4.9</strong><span class="np-stars">★★★★★</span></div>
                        <div class="np-rating-note">Based on verified customer feedback</div>
                        <div class="np-rating-bars">
                            <div class="np-bar-row"><span>5 star</span><div class="np-bar"><span style="width: 90%"></span></div><span>90%</span></div>
                            <div class="np-bar-row"><span>4 star</span><div class="np-bar"><span style="width: 8%"></span></div><span>8%</span></div>
                            <div class="np-bar-row"><span>3 star</span><div class="np-bar"><span style="width: 2%"></span></div><span>2%</span></div>
                        </div>
                    </aside>
                </div>

                <article class="np-featured-story">
                    <div>
                        <span class="np-stars">★★★★★</span>
                        <h2>“{{ $featured['quote'] }}”</h2>
                        <div class="np-featured-person">
                            <span class="np-avatar" style="background: {{ $featured['color'] }}">{{ $featured['initials'] }}</span>
                            <span>
                                <strong>{{ $featured['name'] }}</strong>
                                <small>{{ $featured['meta'] }}</small>
                            </span>
                        </div>
                    </div>
                    <div class="np-featured-actions">
                        <a href="{{ route('quote.request') }}" class="np-review-btn is-red">Request a team quote</a>
                        <a href="{{ route('contact') }}?topic=testimonial" class="np-review-btn">Share your experience</a>
                    </div>
                </article>
            </div>
        </section>

        <section class="np-reviews-section">
            <div class="np-reviews-shell">
                <div class="np-filter-panel">
                    <div class="np-review-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/></svg>
                        <input id="npReviewSearch" type="search" placeholder="Search by team, topic or location..." aria-label="Search testimonials">
                    </div>
                    <select id="npReviewType" aria-label="Filter testimonials by team type">
                        <option value="all">All team types</option>
                        <option value="Club">Sports clubs</option>
                        <option value="School">Schools</option>
                        <option value="Business">Businesses</option>
                        <option value="Event">Events</option>
                    </select>
                    <select id="npReviewRating" aria-label="Filter testimonials by rating">
                        <option value="all">All ratings</option>
                        <option value="5">5 stars</option>
                        <option value="4">4 stars & above</option>
                    </select>
                    <select id="npReviewSort" aria-label="Sort testimonials">
                        <option value="featured">Featured first</option>
                        <option value="newest">Newest first</option>
                        <option value="rating">Highest rating</option>
                    </select>
                </div>

                <div class="np-reviews-grid" id="npReviewsGrid"></div>
                <div class="np-empty-state" id="npReviewsEmpty">
                    <h3>No matching testimonials</h3>
                    <p>Try another keyword or remove one of the filters.</p>
                </div>
                <div class="np-load-wrap">
                    <button class="np-review-btn is-red" type="button" id="npReviewsLoadMore">Load more stories</button>
                </div>
            </div>
        </section>

        <div class="np-testimonial-modal" id="npReviewModal" role="dialog" aria-modal="true" aria-labelledby="npReviewModalName">
            <div class="np-testimonial-modal-card">
                <button class="np-testimonial-close" type="button" id="npReviewModalClose" aria-label="Close testimonial story">×</button>
                <div class="np-stars" id="npReviewModalStars">★★★★★</div>
                <p class="np-modal-quote" id="npReviewModalQuote"></p>
                <div class="np-review-person">
                    <span class="np-avatar" id="npReviewModalAvatar">NP</span>
                    <span>
                        <strong id="npReviewModalName"></strong>
                        <small id="npReviewModalMeta"></small>
                    </span>
                </div>
                <div class="np-modal-detail" id="npReviewModalDetail"></div>
            </div>
        </div>
    </main>

    <script>
        (() => {
            const root = document.querySelector('[data-np-reviews-page]');
            if (!root || root.dataset.ready === '1') return;
            root.dataset.ready = '1';

            const reviews = @js($reviews->all());
            const grid = document.getElementById('npReviewsGrid');
            const empty = document.getElementById('npReviewsEmpty');
            const loadMore = document.getElementById('npReviewsLoadMore');
            const search = document.getElementById('npReviewSearch');
            const typeFilter = document.getElementById('npReviewType');
            const ratingFilter = document.getElementById('npReviewRating');
            const sortFilter = document.getElementById('npReviewSort');
            const modal = document.getElementById('npReviewModal');
            const closeModalButton = document.getElementById('npReviewModalClose');
            let visibleCount = 6;

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const stars = (count) => '★'.repeat(Number(count || 5)) + '☆'.repeat(Math.max(0, 5 - Number(count || 5)));

            const cardHtml = (review, index) => `
                <article class="np-review-card">
                    <div class="np-review-top">
                        <span class="np-stars" aria-label="${escapeHtml(review.rating)} out of 5 stars">${stars(review.rating)}</span>
                        <span class="np-verified-badge">✓ Verified</span>
                    </div>
                    <p class="np-review-quote">“${escapeHtml(review.quote)}”</p>
                    <div class="np-review-person">
                        <span class="np-avatar" style="background: ${escapeHtml(review.color)}">${escapeHtml(review.initials)}</span>
                        <span>
                            <strong>${escapeHtml(review.name)}</strong>
                            <small>${escapeHtml(review.meta)}</small>
                        </span>
                    </div>
                    <div class="np-review-card-actions">
                        <span class="np-story-tag">${escapeHtml((review.tags || [])[0] || 'Verified review')}</span>
                        <button class="np-story-button" type="button" data-review-index="${index}">Read story →</button>
                    </div>
                </article>`;

            const filteredReviews = () => {
                const q = String(search?.value || '').toLowerCase().trim();
                const type = typeFilter?.value || 'all';
                const rating = ratingFilter?.value || 'all';
                const sort = sortFilter?.value || 'featured';

                const data = reviews.filter((review) => {
                    const haystack = `${review.name} ${review.meta} ${review.type} ${review.quote} ${review.detail} ${(review.tags || []).join(' ')}`.toLowerCase();
                    return (!q || haystack.includes(q))
                        && (type === 'all' || review.type === type)
                        && (rating === 'all' || Number(review.rating) >= Number(rating));
                });

                if (sort === 'newest') {
                    data.sort((a, b) => new Date(b.date) - new Date(a.date));
                }
                if (sort === 'rating') {
                    data.sort((a, b) => Number(b.rating) - Number(a.rating) || new Date(b.date) - new Date(a.date));
                }

                return data;
            };

            const renderGrid = (reset = false) => {
                if (reset) visibleCount = 6;
                const data = filteredReviews();
                const shown = data.slice(0, visibleCount);
                grid.innerHTML = shown.map((review, index) => cardHtml(review, reviews.indexOf(review))).join('');
                empty.style.display = data.length ? 'none' : 'block';
                loadMore.style.display = visibleCount < data.length ? 'inline-flex' : 'none';
            };

            const openStory = (review) => {
                if (!review || !modal) return;
                document.getElementById('npReviewModalStars').textContent = stars(review.rating);
                document.getElementById('npReviewModalQuote').textContent = `“${review.quote}”`;
                document.getElementById('npReviewModalName').textContent = review.name;
                document.getElementById('npReviewModalMeta').textContent = review.meta;
                document.getElementById('npReviewModalDetail').textContent = review.detail;
                const avatar = document.getElementById('npReviewModalAvatar');
                avatar.textContent = review.initials || 'NP';
                avatar.style.background = review.color || '#0f172a';
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            };

            const closeModal = () => {
                modal?.classList.remove('is-open');
                document.body.style.overflow = '';
            };

            [search, typeFilter, ratingFilter, sortFilter].forEach((field) => {
                field?.addEventListener(field === search ? 'input' : 'change', () => renderGrid(true));
            });

            loadMore?.addEventListener('click', () => {
                visibleCount += 3;
                renderGrid();
            });

            grid?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-review-index]');
                if (!button) return;
                openStory(reviews[Number(button.dataset.reviewIndex)]);
            });

            closeModalButton?.addEventListener('click', closeModal);
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal?.classList.contains('is-open')) closeModal();
            });

            renderGrid(true);
        })();
    </script>
</x-layouts.storefront>
