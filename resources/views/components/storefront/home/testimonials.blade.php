@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))
        ->filter(fn ($item) => filled(data_get($item, 'title')) || filled(data_get($item, 'description')))
        ->values();

    if ($items->isEmpty()) {
        $items = collect([
            ['icon' => 'JM', 'title' => 'Jason Miller', 'subtitle' => 'River Valley Baseball Club · Ohio, USA', 'description' => 'The jerseys came out clean and the ordering process was simple. We shared our team logo and size list, and the team helped us prepare the final order.', 'label' => 'Jerseys'],
            ['icon' => 'EC', 'title' => 'Emily Carter', 'subtitle' => 'Community Run Event · Texas, USA', 'description' => 'We needed shirts and bags for a weekend event. The quote was clear, and they asked the right questions before moving ahead.', 'label' => 'Event kits'],
            ['icon' => 'MR', 'title' => 'Marcus Reed', 'subtitle' => 'Northside High Boosters · Georgia, USA', 'description' => 'Good option for our school spirit wear. The hoodie colors matched what we requested, and the design proof helped a lot.', 'label' => 'Artwork'],
        ]);
    }

    $featured = $items->first();
    $colors = ['#0f766e', '#7c3aed', '#be123c', '#1d4ed8', '#ea580c', '#0891b2', '#4338ca', '#15803d', '#c026d3'];
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

    $primaryLabel = 'Read all testimonials';
    $primaryHref = route('testimonials.index');
    $secondaryLabel = 'Share your experience';
    $secondaryHref = route('contact') . '?topic=testimonial';
@endphp

<section class="np-testimonials-v2" id="testimonials" data-np-testimonials-section>
    <style>
        .np-testimonials-v2 {
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
            position: relative;
            overflow: hidden;
            padding: 82px 0;
            background:
                radial-gradient(circle at 88% 8%, rgba(239, 35, 60, .12), transparent 28%),
                radial-gradient(circle at 8% 72%, rgba(15, 23, 42, .07), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: var(--np-ink);
        }

        .np-testimonials-v2 * { box-sizing: border-box; }

        .np-testimonials-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .np-testimonials-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 480px);
            gap: 42px;
            align-items: stretch;
            margin-bottom: 44px;
        }

        .np-testimonials-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--np-red);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .np-testimonials-eyebrow::before {
            content: '';
            width: 28px;
            height: 3px;
            border-radius: 999px;
            background: var(--np-red);
        }

        .np-testimonials-title {
            max-width: 720px;
            margin: 14px 0 16px;
            font-size: clamp(34px, 4.6vw, 56px);
            line-height: .98;
            font-weight: 950;
            letter-spacing: -.055em;
            color: var(--np-ink);
        }

        .np-testimonials-lead {
            max-width: 690px;
            margin: 0;
            color: var(--np-muted);
            font-size: 18px;
            line-height: 1.7;
        }

        .np-testimonials-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .np-testimonials-btn {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid var(--np-line);
            background: var(--np-white);
            padding: 12px 18px;
            color: var(--np-ink);
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .np-testimonials-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .10);
        }

        .np-testimonials-btn.is-red {
            border-color: var(--np-red);
            background: var(--np-red);
            color: #fff;
            box-shadow: 0 14px 30px rgba(239, 35, 60, .22);
        }

        .np-testimonials-btn.is-red:hover { background: var(--np-red-dark); }

        .np-featured-review {
            position: relative;
            min-height: 392px;
            overflow: hidden;
            border-radius: 32px;
            background: linear-gradient(135deg, var(--np-navy), #1e293b);
            color: #fff;
            padding: 30px;
            box-shadow: var(--np-shadow);
        }

        .np-featured-review::after {
            content: '';
            position: absolute;
            top: -95px;
            right: -80px;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: rgba(239, 35, 60, .24);
        }

        .np-featured-rating,
        .np-featured-quote,
        .np-featured-person { position: relative; z-index: 1; }

        .np-featured-rating {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: baseline;
            font-size: 46px;
            font-weight: 950;
            letter-spacing: -.05em;
        }

        .np-stars {
            color: var(--np-gold);
            letter-spacing: 2px;
            white-space: nowrap;
        }

        .np-featured-quote {
            margin: 50px 0 36px;
            font-size: clamp(24px, 3vw, 36px);
            font-weight: 850;
            line-height: 1.25;
            letter-spacing: -.03em;
        }

        .np-featured-person,
        .np-testimonial-person {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .np-testimonial-avatar {
            display: grid;
            flex: 0 0 auto;
            width: 54px;
            height: 54px;
            place-items: center;
            border-radius: 16px;
            color: #fff;
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.02em;
        }

        .np-testimonial-person strong,
        .np-featured-person strong {
            display: block;
            color: inherit;
            font-weight: 950;
        }

        .np-testimonial-person small,
        .np-featured-person small {
            display: block;
            margin-top: 4px;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.4;
        }

        .np-testimonials-row-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 22px;
        }

        .np-testimonials-row-head h3 {
            margin: 0;
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .np-testimonials-row-head p {
            max-width: 560px;
            margin: 7px 0 0;
            color: var(--np-muted);
            line-height: 1.65;
        }

        .np-testimonials-controls {
            display: flex;
            gap: 10px;
        }

        .np-testimonials-arrow {
            display: grid;
            width: 46px;
            height: 46px;
            place-items: center;
            border: 1px solid var(--np-line);
            border-radius: 999px;
            background: #fff;
            color: var(--np-ink);
            font-size: 20px;
            font-weight: 950;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .np-testimonials-arrow:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .11);
        }

        .np-testimonials-track {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(310px, 1fr);
            gap: 18px;
            overflow-x: auto;
            overscroll-behavior-x: contain;
            scroll-snap-type: x mandatory;
            padding: 8px 4px 24px;
            scrollbar-width: none;
        }

        .np-testimonials-track::-webkit-scrollbar { display: none; }

        .np-testimonial-card {
            scroll-snap-align: start;
            display: flex;
            min-height: 326px;
            flex-direction: column;
            border: 1px solid var(--np-line);
            border-radius: var(--np-radius);
            background: #fff;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .np-testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--np-shadow);
        }

        .np-testimonial-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .np-verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #15803d;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 950;
            white-space: nowrap;
        }

        .np-testimonial-quote {
            margin: 22px 0 28px;
            color: #334155;
            font-size: 18px;
            line-height: 1.62;
        }

        .np-testimonial-card .np-testimonial-person small {
            color: var(--np-muted);
        }

        .np-testimonial-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid var(--np-line);
        }

        .np-story-tag {
            color: var(--np-muted);
            font-size: 12px;
            font-weight: 900;
        }

        .np-read-story {
            border: 0;
            background: transparent;
            color: var(--np-navy);
            padding: 0;
            font-size: 14px;
            font-weight: 950;
        }

        .np-read-story:hover { color: var(--np-red); }

        .np-testimonials-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            overflow: hidden;
            margin-top: 28px;
            border: 1px solid var(--np-line);
            border-radius: 24px;
            background: var(--np-line);
        }

        .np-testimonial-stat {
            background: #fff;
            padding: 26px 18px;
            text-align: center;
        }

        .np-testimonial-stat strong {
            display: block;
            font-size: 30px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .np-testimonial-stat span {
            display: block;
            margin-top: 4px;
            color: var(--np-muted);
            font-size: 13px;
            font-weight: 750;
        }


        .np-testimonial-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, .64);
            padding: 20px;
        }

        .np-testimonial-modal.is-open { display: flex; }

        .np-testimonial-modal-card {
            position: relative;
            width: min(650px, 100%);
            border-radius: 26px;
            background: #fff;
            padding: 30px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
            animation: npTestimonialPop .2s ease;
        }

        @keyframes npTestimonialPop {
            from { opacity: 0; transform: scale(.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .np-testimonial-modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border: 1px solid var(--np-line);
            border-radius: 999px;
            background: #fff;
            color: var(--np-ink);
            font-size: 20px;
            font-weight: 800;
        }

        .np-modal-quote {
            margin: 24px 0;
            color: var(--np-ink);
            font-size: clamp(22px, 3vw, 28px);
            line-height: 1.45;
            letter-spacing: -.025em;
        }

        .np-modal-detail {
            margin-top: 22px;
            border-top: 1px solid var(--np-line);
            padding-top: 18px;
            color: var(--np-muted);
            line-height: 1.65;
        }

        .np-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 980px) {
            .np-testimonials-hero { grid-template-columns: minmax(0, 1fr); }
            .np-featured-review { min-height: 340px; }
            .np-testimonials-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 700px) {
            .np-testimonials-v2 { padding: 62px 0; }
            .np-testimonials-shell { width: min(100% - 24px, 1180px); }
            .np-testimonials-title { font-size: clamp(34px, 12vw, 44px); }
            .np-testimonials-lead { font-size: 16px; }
            .np-featured-review { min-height: 320px; padding: 24px; border-radius: 26px; }
            .np-featured-rating { font-size: 36px; }
            .np-featured-quote { margin: 38px 0 28px; }
            .np-testimonials-row-head { align-items: flex-start; flex-direction: column; }
            .np-testimonials-track { grid-auto-columns: minmax(286px, 86vw); }
            .np-testimonials-stats { grid-template-columns: minmax(0, 1fr); }
        }
    </style>

    <div class="np-testimonials-shell">
        <div class="np-testimonials-hero">
            <div>
                <span class="np-testimonials-eyebrow">{{ $text('eyebrow', 'Real teams. Real feedback.') }}</span>
                <h2 class="np-testimonials-title">{{ $text('title', 'Made for teams who want to look the part.') }}</h2>
                <p class="np-testimonials-lead">
                    {{ $text('description', 'See how clubs, schools, businesses, and event teams use NextPlay for custom sportswear, team uniforms, event kits, and bulk orders.') }}
                </p>

                <div class="np-testimonials-actions">
                    <a class="np-testimonials-btn is-red" href="{{ $primaryHref }}">{{ $primaryLabel }}</a>
                    <a class="np-testimonials-btn" href="{{ $secondaryHref }}">{{ $secondaryLabel }}</a>
                </div>
            </div>

            <article class="np-featured-review">
                <div class="np-featured-rating">
                    <span>4.9</span>
                    <span class="np-stars" aria-label="5 out of 5 stars">★★★★★</span>
                </div>
                <p class="np-featured-quote">“{{ data_get($featured, 'description', 'The kits looked sharp, arrived on time, and the whole ordering process felt simple.') }}”</p>
                <div class="np-featured-person">
                    <span class="np-testimonial-avatar" style="background: {{ $colors[0] }}">{{ $initials((array) $featured, 0) }}</span>
                    <span>
                        <strong>{{ data_get($featured, 'title', 'NextPlay Customer') }}</strong>
                        <small>{{ data_get($featured, 'subtitle', 'Verified customer') }}</small>
                    </span>
                </div>
            </article>
        </div>

        <div class="np-testimonials-row-head">
            <div>
                <h3>Customer stories</h3>
                <p>Swipe, scroll, or use the arrows. Each card opens into a focused review story.</p>
            </div>
            <div class="np-testimonials-controls" aria-label="Testimonial carousel controls">
                <button class="np-testimonials-arrow" type="button" data-np-testimonial-prev aria-label="Previous testimonial">←</button>
                <button class="np-testimonials-arrow" type="button" data-np-testimonial-next aria-label="Next testimonial">→</button>
            </div>
        </div>

        <div class="np-testimonials-track" data-np-testimonials-track>
            @foreach($items as $index => $testimonial)
                @php
                    $testimonial = (array) $testimonial;
                    $color = $colors[$index % count($colors)];
                    $name = (string) data_get($testimonial, 'title', 'NextPlay Customer');
                    $meta = (string) data_get($testimonial, 'subtitle', 'Verified customer');
                    $quote = (string) data_get($testimonial, 'description', 'Great experience with NextPlay Sportswear.');
                    $tag = filled(data_get($testimonial, 'label')) ? (string) data_get($testimonial, 'label') : 'Verified review';
                @endphp
                <article class="np-testimonial-card" data-np-testimonial-card>
                    <div class="np-testimonial-top">
                        <span class="np-stars" aria-label="5 out of 5 stars">★★★★★</span>
                        <span class="np-verified-badge">✓ Verified</span>
                    </div>

                    <p class="np-testimonial-quote">“{{ $quote }}”</p>

                    <div class="np-testimonial-person">
                        <span class="np-testimonial-avatar" style="background: {{ $color }}">{{ $initials($testimonial, $index) }}</span>
                        <span>
                            <strong>{{ $name }}</strong>
                            <small>{{ $meta }}</small>
                        </span>
                    </div>

                    <div class="np-testimonial-footer">
                        <span class="np-story-tag">{{ $tag }}</span>
                        <button class="np-read-story" type="button" data-np-story>Read story →</button>
                    </div>

                    <div class="np-sr-only" data-story-name>{{ $name }}</div>
                    <div class="np-sr-only" data-story-meta>{{ $meta }}</div>
                    <div class="np-sr-only" data-story-quote>{{ $quote }}</div>
                    <div class="np-sr-only" data-story-detail>{{ $tag }} · Verified NextPlay customer feedback.</div>
                    <div class="np-sr-only" data-story-initials>{{ $initials($testimonial, $index) }}</div>
                    <div class="np-sr-only" data-story-color>{{ $color }}</div>
                </article>
            @endforeach
        </div>

        <div class="np-testimonials-stats" aria-label="Customer testimonial statistics">
            <div class="np-testimonial-stat"><strong>4.9/5</strong><span>Average customer rating</span></div>
            <div class="np-testimonial-stat"><strong>96%</strong><span>Would order again</span></div>
            <div class="np-testimonial-stat"><strong>500+</strong><span>Teams served</span></div>
            <div class="np-testimonial-stat"><strong>24h</strong><span>Typical quote response</span></div>
        </div>

    </div>

    <div class="np-testimonial-modal" data-np-testimonial-modal role="dialog" aria-modal="true" aria-labelledby="np-testimonial-modal-name">
        <div class="np-testimonial-modal-card">
            <button class="np-testimonial-modal-close" type="button" data-np-testimonial-close aria-label="Close testimonial story">×</button>
            <div class="np-stars" data-np-modal-stars>★★★★★</div>
            <p class="np-modal-quote" data-np-modal-quote></p>
            <div class="np-testimonial-person">
                <span class="np-testimonial-avatar" data-np-modal-avatar>NP</span>
                <span>
                    <strong id="np-testimonial-modal-name" data-np-modal-name></strong>
                    <small data-np-modal-meta></small>
                </span>
            </div>
            <div class="np-modal-detail" data-np-modal-detail></div>
        </div>
    </div>

    <script>
        (() => {
            document.querySelectorAll('[data-np-testimonials-section]').forEach((root) => {
                if (root.dataset.npTestimonialsReady === '1') return;
                root.dataset.npTestimonialsReady = '1';

                const track = root.querySelector('[data-np-testimonials-track]');
                const prev = root.querySelector('[data-np-testimonial-prev]');
                const next = root.querySelector('[data-np-testimonial-next]');
                const modal = root.querySelector('[data-np-testimonial-modal]');
                const close = root.querySelector('[data-np-testimonial-close]');
                const modalQuote = root.querySelector('[data-np-modal-quote]');
                const modalName = root.querySelector('[data-np-modal-name]');
                const modalMeta = root.querySelector('[data-np-modal-meta]');
                const modalDetail = root.querySelector('[data-np-modal-detail]');
                const modalAvatar = root.querySelector('[data-np-modal-avatar]');

                const cardStep = () => Math.min(380, Math.max(300, track?.clientWidth ? track.clientWidth * 0.72 : 340));

                prev?.addEventListener('click', () => track?.scrollBy({ left: -cardStep(), behavior: 'smooth' }));
                next?.addEventListener('click', () => track?.scrollBy({ left: cardStep(), behavior: 'smooth' }));

                const text = (card, selector) => (card.querySelector(selector)?.textContent || '').trim();

                root.querySelectorAll('[data-np-story]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const card = button.closest('[data-np-testimonial-card]');
                        if (!card || !modal) return;

                        modalQuote.textContent = `“${text(card, '[data-story-quote]')}”`;
                        modalName.textContent = text(card, '[data-story-name]');
                        modalMeta.textContent = text(card, '[data-story-meta]');
                        modalDetail.textContent = text(card, '[data-story-detail]');
                        modalAvatar.textContent = text(card, '[data-story-initials]') || 'NP';
                        modalAvatar.style.background = text(card, '[data-story-color]') || '#0f172a';
                        modal.classList.add('is-open');
                        document.body.style.overflow = 'hidden';
                    });
                });

                const closeModal = () => {
                    modal?.classList.remove('is-open');
                    document.body.style.overflow = '';
                };

                close?.addEventListener('click', closeModal);
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal?.classList.contains('is-open')) closeModal();
                });
            });
        })();
    </script>
</section>
