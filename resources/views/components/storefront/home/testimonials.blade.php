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
    $reviewBaseHref = route('contact') . '?topic=testimonial';
    $googleReviewHref = $reviewBaseHref . '&platform=google-reviews';
    $trustpilotReviewHref = $reviewBaseHref . '&platform=trustpilot';
    $facebookReviewHref = $reviewBaseHref . '&platform=facebook';
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


        .np-review-platforms {
            width: 100%;
            margin-top: 30px;
            border: 1px solid rgba(226, 232, 240, .95);
            border-radius: 22px;
            background: rgba(255, 255, 255, .90);
            padding: clamp(18px, 2vw, 22px);
            box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
            backdrop-filter: blur(8px);
        }

        .np-review-platforms-header h3 {
            margin: 0 0 8px;
            color: var(--np-ink);
            font-size: clamp(15px, 1.5vw, 18px);
            font-weight: 950;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .np-review-platforms-header p {
            margin: 0;
            color: var(--np-muted);
            font-size: clamp(14px, 1.6vw, 16px);
            line-height: 1.5;
        }

        .np-review-platforms-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .np-review-platform-card {
            display: flex;
            min-height: 128px;
            flex-direction: column;
            justify-content: space-between;
            gap: 15px;
            border: 1px solid var(--np-line);
            border-radius: 18px;
            background: #fff;
            padding: clamp(14px, 1.7vw, 18px);
            color: var(--np-ink);
            text-decoration: none;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .np-review-platform-card:hover {
            transform: translateY(-3px);
            border-color: rgba(239, 35, 60, .35);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
        }

        .np-review-platform-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .np-review-platform-icon {
            display: grid;
            flex: 0 0 auto;
            width: clamp(38px, 3.8vw, 48px);
            height: clamp(38px, 3.8vw, 48px);
            place-items: center;
        }

        .np-review-platform-icon svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .np-review-platform-icon.is-trustpilot svg { width: 86%; height: 86%; }
        .np-review-platform-icon.is-facebook svg { width: 94%; height: 94%; }

        .np-review-platform-brand strong {
            display: block;
            color: var(--np-ink);
            font-size: clamp(15px, 1.55vw, 19px);
            font-weight: 950;
            line-height: 1.12;
            letter-spacing: -.035em;
        }

        .np-review-platform-brand small {
            display: block;
            margin-top: 4px;
            color: var(--np-muted);
            font-size: 13px;
            line-height: 1.35;
        }

        .np-review-platform-brand .np-stars {
            display: block;
            color: var(--np-gold);
            font-size: clamp(13px, 1.4vw, 16px);
            letter-spacing: 2px;
        }

        .np-review-platform-action {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--np-red);
            font-size: clamp(13px, 1.45vw, 15px);
            font-weight: 950;
            line-height: 1.1;
        }

        .np-review-platform-action-text {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .np-review-platform-external {
            display: grid;
            flex: 0 0 auto;
            width: 24px;
            height: 24px;
            margin-left: auto;
            place-items: center;
            border: 1.8px solid currentColor;
            border-radius: 7px;
        }

        .np-review-platform-external svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
        }

        .np-review-platforms-note {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 16px;
            border-top: 1px solid var(--np-line);
            padding-top: 14px;
            color: var(--np-muted);
            font-size: clamp(13px, 1.35vw, 15px);
            line-height: 1.35;
        }

        .np-review-platforms-note svg {
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

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
            .np-review-platforms-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .np-review-platform-card { min-height: 128px; }
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
            .np-review-platforms { padding: 18px; border-radius: 22px; }
            .np-review-platforms-grid { grid-template-columns: minmax(0, 1fr); gap: 14px; margin-top: 18px; }
            .np-review-platform-card { min-height: 144px; gap: 18px; border-radius: 18px; }
            .np-review-platform-brand { align-items: flex-start; gap: 14px; }
            .np-review-platform-brand small { font-size: 15px; }
            .np-review-platform-external { width: 28px; height: 28px; border-radius: 7px; }
            .np-review-platforms-note { align-items: flex-start; font-size: 15px; }
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

                <div class="np-review-platforms" aria-label="Review sharing platforms">
                    <div class="np-review-platforms-header">
                        <h3>Share your team’s story</h3>
                        <p>Your feedback helps other teams order with confidence.</p>
                    </div>

                    <div class="np-review-platforms-grid">
                        <a class="np-review-platform-card" href="{{ $googleReviewHref }}" aria-label="Write a NextPlay review on Google Reviews">
                            <span class="np-review-platform-brand">
                                <span class="np-review-platform-icon is-google" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" role="img" focusable="false">
                                        <path fill="#4285f4" d="M59.2 32.7c0-2-.2-3.9-.5-5.7H32v10.8h15.3c-.7 3.5-2.7 6.5-5.7 8.5v7h9.2c5.4-5 8.4-12.3 8.4-20.6Z"/>
                                        <path fill="#34a853" d="M32 60c7.7 0 14.2-2.5 18.9-6.8l-9.2-7c-2.6 1.7-5.8 2.7-9.7 2.7-7.4 0-13.7-5-15.9-11.7H6.6v7.2C11.3 53.7 20.9 60 32 60Z"/>
                                        <path fill="#fbbc05" d="M16.1 37.2c-.6-1.7-.9-3.5-.9-5.3s.3-3.6.9-5.3v-7.2H6.6A27.7 27.7 0 0 0 4 32c0 4.5 1 8.7 2.6 12.4l9.5-7.2Z"/>
                                        <path fill="#ea4335" d="M32 15.1c4.2 0 7.9 1.4 10.8 4.2l8.2-8.2C46.1 6.5 39.6 4 32 4 20.9 4 11.3 10.3 6.6 19.5l9.5 7.2c2.2-6.7 8.5-11.6 15.9-11.6Z"/>
                                    </svg>
                                </span>
                                <span>
                                    <strong>Google Reviews</strong>
                                    <small><span class="np-stars" aria-label="5 out of 5 stars">★★★★★</span></small>
                                </span>
                            </span>
                            <span class="np-review-platform-action">
                                <span class="np-review-platform-action-text">Write a review <span aria-hidden="true">→</span></span>
                                <span class="np-review-platform-external" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"/><path d="M10 14 20 4"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/></svg>
                                </span>
                            </span>
                        </a>

                        <a class="np-review-platform-card" href="{{ $trustpilotReviewHref }}" aria-label="Leave NextPlay feedback on Trustpilot">
                            <span class="np-review-platform-brand">
                                <span class="np-review-platform-icon is-trustpilot" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" role="img" focusable="false">
                                        <path fill="#00b67a" d="m32 4.8 7.4 15 16.6 2.4-12 11.7 2.8 16.5L32 42.6l-14.8 7.8L20 33.9 8 22.2l16.6-2.4L32 4.8Z"/>
                                    </svg>
                                </span>
                                <span>
                                    <strong>Trustpilot</strong>
                                    <small>Share your experience</small>
                                </span>
                            </span>
                            <span class="np-review-platform-action">
                                <span class="np-review-platform-action-text">Leave feedback <span aria-hidden="true">→</span></span>
                                <span class="np-review-platform-external" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"/><path d="M10 14 20 4"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/></svg>
                                </span>
                            </span>
                        </a>

                        <a class="np-review-platform-card" href="{{ $facebookReviewHref }}" aria-label="Recommend NextPlay on Facebook">
                            <span class="np-review-platform-brand">
                                <span class="np-review-platform-icon is-facebook" aria-hidden="true">
                                    <svg viewBox="0 0 64 64" role="img" focusable="false">
                                        <circle cx="32" cy="32" r="29" fill="#1877f2"/>
                                        <path fill="#fff" d="M39.1 35.2 40.3 27h-7.8v-5.3c0-2.2 1.1-4.4 4.6-4.4h3.6v-7A43.8 43.8 0 0 0 34.4 10c-6.5 0-10.8 4-10.8 11.1V27h-7.2v8.2h7.2V55h8.9V35.2h6.6Z"/>
                                    </svg>
                                </span>
                                <span>
                                    <strong>Facebook</strong>
                                    <small>Recommend NextPlay</small>
                                </span>
                            </span>
                            <span class="np-review-platform-action">
                                <span class="np-review-platform-action-text">Recommend us <span aria-hidden="true">→</span></span>
                                <span class="np-review-platform-external" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"/><path d="M10 14 20 4"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/></svg>
                                </span>
                            </span>
                        </a>
                    </div>

                    <div class="np-review-platforms-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <span>Choose your preferred platform <span aria-hidden="true">•</span> Takes about 2 minutes</span>
                    </div>
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
