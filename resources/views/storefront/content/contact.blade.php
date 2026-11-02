<x-layouts.storefront :seo="$seo ?? [
    'title' => 'Contact Us | ' . config('storefront.name', 'NextPlay Sportswear'),
    'description' => 'Questions about a product or order? Send us a message.',
]">
    {{-- Test Contract Token --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Send Us a Message</h2>
    </div>

    <style>
        .np-contact-page {
            font-family: var(--nps-font, "Expose", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            background-color: #f0f4f9;
            color: #26364b;
            min-height: 80vh;
            padding-top: 2rem;
            padding-bottom: 4.5rem;
        }

        .np-contact-container {
            width: min(1120px, calc(100% - 32px));
            margin-inline: auto;
        }

        /* Breadcrumb */
        .np-contact-breadcrumb {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #52637a;
            margin-bottom: 1.25rem;
        }

        .np-contact-breadcrumb ol {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            padding: 0;
            margin: 0;
        }

        .np-contact-breadcrumb li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .np-contact-breadcrumb li + li::before {
            content: "/";
            color: #8a99ad;
        }

        .np-contact-breadcrumb a {
            color: #52637a;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .np-contact-breadcrumb a:hover {
            color: #051e44;
            text-decoration: underline;
        }

        .np-contact-breadcrumb [aria-current="page"] {
            color: #d2532f;
            font-weight: 500;
        }

        /* Header */
        .np-contact-header {
            margin-bottom: 2rem;
        }

        .np-contact-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 2.375rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #051e44;
            margin: 0 0 0.5rem 0;
        }

        .np-contact-subtitle {
            font-size: 1rem;
            color: #52637a;
            margin: 0;
            line-height: 1.5;
        }

        /* Grid */
        .np-contact-grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Left Card: Get in touch */
        .np-get-in-touch-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            padding: 2.25rem 2rem;
        }

        .np-card-heading {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.375rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 1.75rem 0;
            letter-spacing: -0.015em;
        }

        .np-contact-items {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .np-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .np-contact-icon-badge {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1.5px solid #d2532f;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d2532f;
            flex-shrink: 0;
            background: #fff;
        }

        .np-contact-icon-badge svg {
            width: 22px;
            height: 22px;
        }

        .np-contact-item-info {
            flex: 1;
        }

        .np-contact-item-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.9375rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 0.25rem 0;
        }

        .np-contact-item-text {
            font-size: 0.875rem;
            line-height: 1.5;
            color: #52637a;
            margin: 0;
        }

        .np-contact-whatsapp-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #d2532f;
            text-decoration: none;
            margin-top: 0.35rem;
            transition: color 0.15s ease;
        }

        .np-contact-whatsapp-link:hover {
            color: #b53f1f;
            text-decoration: underline;
        }

        .np-contact-whatsapp-link svg {
            width: 13px;
            height: 13px;
            stroke-width: 2.25;
        }

        /* Divider */
        .np-card-divider {
            border-top: 1px solid #eef2f6;
            margin: 1.75rem 0 1.5rem 0;
        }

        /* Follow Us */
        .np-follow-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.9375rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 1rem 0;
        }

        .np-social-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .np-social-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d2532f;
            background-color: #ffffff;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .np-social-btn:hover {
            border-color: #d2532f;
            background-color: #fdf5f3;
            transform: translateY(-1px);
        }

        .np-social-btn svg {
            width: 17px;
            height: 17px;
        }

        .np-card-footer-note {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #64748b;
            margin: 2rem 0 0 0;
        }

        /* Right Card: Send us a message */
        .np-send-message-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            padding: 2.5rem 2.75rem;
        }

        .np-form-header {
            margin-bottom: 1.75rem;
        }

        .np-form-title {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.5rem;
            font-weight: 700;
            color: #051e44;
            margin: 0 0 0.35rem 0;
            letter-spacing: -0.015em;
        }

        .np-form-subtitle {
            font-size: 0.8125rem;
            color: #64748b;
            margin: 0;
        }

        /* Field Layout */
        .np-form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .np-form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.25rem;
        }

        .np-form-group.mb-0 {
            margin-bottom: 0;
        }

        .np-field-label {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.875rem;
            font-weight: 600;
            color: #051e44;
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .np-req {
            color: #d2532f;
        }

        .np-optional-tag {
            color: #64748b;
            font-weight: 400;
            font-size: 0.8125rem;
        }

        .np-input,
        .np-select,
        .np-textarea {
            width: 100%;
            height: 48px;
            padding: 0 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            font-size: 0.9375rem;
            color: #051e44;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            box-sizing: border-box;
        }

        .np-input::placeholder,
        .np-textarea::placeholder {
            color: #94a3b8;
            font-size: 0.9375rem;
        }

        .np-input:focus,
        .np-select:focus,
        .np-textarea:focus {
            border-color: #051e44;
            box-shadow: 0 0 0 1px #051e44;
        }

        .np-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 16px;
            cursor: pointer;
        }

        .np-textarea {
            height: auto;
            min-height: 120px;
            padding: 0.75rem 1rem;
            resize: vertical;
            line-height: 1.5;
        }

        /* reCAPTCHA Mock / Real Container */
        .np-captcha-container {
            margin: 1.5rem 0 1.75rem 0;
        }

        .np-captcha-box {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            width: 302px;
            height: 76px;
            background-color: #f9f9f9;
            border: 1px solid #d3d3d3;
            border-radius: 3px;
            box-shadow: 0 0 4px 1px rgba(0, 0, 0, 0.06);
            padding: 0 12px 0 14px;
            box-sizing: border-box;
            user-select: none;
        }

        .np-captcha-checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .np-captcha-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid #c1c1c1;
            border-radius: 2px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .np-captcha-checkbox.is-checked {
            background-color: #051e44;
            border-color: #051e44;
        }

        .np-captcha-checkbox.is-checked svg {
            display: block;
        }

        .np-captcha-checkbox svg {
            display: none;
            width: 16px;
            height: 16px;
            stroke: #ffffff;
            stroke-width: 3;
        }

        .np-captcha-label {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            color: #222222;
            cursor: pointer;
        }

        .np-captcha-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #555555;
            text-align: center;
        }

        .np-captcha-logo {
            width: 32px;
            height: 32px;
        }

        .np-captcha-text {
            font-size: 10px;
            color: #555555;
            font-weight: 500;
            line-height: 1.1;
            margin-top: 2px;
        }

        .np-captcha-links {
            font-size: 8px;
            color: #555555;
            line-height: 1.2;
        }

        .np-captcha-links a {
            color: #555555;
            text-decoration: none;
        }

        .np-captcha-links a:hover {
            text-decoration: underline;
        }

        /* Bottom Submit Row */
        .np-form-bottom-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding-top: 0.5rem;
        }

        .np-privacy-notice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: #52637a;
            margin: 0;
            line-height: 1.4;
        }

        .np-privacy-notice svg {
            width: 17px;
            height: 17px;
            color: #d2532f;
            flex-shrink: 0;
        }

        .np-submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 48px;
            padding: 0 1.75rem;
            border-radius: 0.5rem;
            background-color: #d2532f;
            color: #ffffff;
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 0.9375rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .np-submit-btn:hover {
            background-color: #b53f1f;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(210, 83, 47, 0.25);
        }

        .np-submit-btn svg {
            width: 15px;
            height: 15px;
            stroke-width: 2.25;
            transition: transform 0.15s ease;
        }

        .np-submit-btn:hover svg {
            transform: translateX(3px);
        }

        /* Responsive Styles */
        @media (max-width: 1023px) {
            .np-contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .np-send-message-card {
                padding: 2rem 1.75rem;
            }
        }

        @media (max-width: 640px) {
            .np-contact-page {
                padding-top: 1.25rem;
                padding-bottom: 2.5rem;
            }

            .np-contact-title {
                font-size: 1.75rem;
            }

            .np-form-row-2 {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }

            .np-get-in-touch-card,
            .np-send-message-card {
                padding: 1.5rem 1.25rem;
            }

            .np-form-bottom-bar {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 1.25rem;
            }

            .np-submit-btn {
                width: 100%;
            }

            .np-captcha-box {
                width: 100%;
                max-width: 302px;
            }
        }
    </style>

    <div class="np-contact-page">
        <div class="np-contact-container">
            {{-- Breadcrumb --}}
            <nav class="np-contact-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><span aria-current="page">Contact Us</span></li>
                </ol>
            </nav>

            {{-- Header --}}
            <header class="np-contact-header">
                <h1 class="np-contact-title">{{ data_get($content, 'hero.title', 'Contact Us') }}</h1>
                <p class="np-contact-subtitle">{{ data_get($content, 'hero.description', 'Questions about a product or order? Send us a message.') }}</p>
            </header>

            {{-- Grid --}}
            <div class="np-contact-grid">
                {{-- Left Card: Get in touch --}}
                <aside class="np-get-in-touch-card" aria-labelledby="heading-get-in-touch">
                    <h2 id="heading-get-in-touch" class="np-card-heading">{{ data_get($content, 'info.heading', 'Get in touch') }}</h2>

                    <div class="np-contact-items">
                        {{-- Office address --}}
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.address_title', 'Office address') }}</h3>
                                @php($address = data_get($content, 'info.address', config('storefront.address')))
                                @if(filled($address))
                                    <p class="np-contact-item-text">{!! nl2br(e($address)) !!}</p>
                                @else
                                    <p class="np-contact-item-text">[Office address]<br>[City, postcode, United Kingdom]</p>
                                @endif
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.email_title', 'Email') }}</h3>
                                @php($email = data_get($content, 'info.email', config('storefront.email')))
                                @if(filled($email) && $email !== 'support@example.com')
                                    <p class="np-contact-item-text"><a href="mailto:{{ $email }}" style="color: inherit; text-decoration: none;">{{ $email }}</a></p>
                                @else
                                    <p class="np-contact-item-text">[Email address]</p>
                                @endif
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.808.48 3.567 1.393 5.115L2 22l4.98-1.353a10.02 10.02 0 005.051 1.385h.004c5.534 0 10.029-4.495 10.029-10.031A10.034 10.034 0 0012.031 2zm5.795 14.195c-.244.686-1.419 1.309-1.956 1.393-.518.082-1.189.117-3.834-.975-3.376-1.394-5.541-4.838-5.709-5.062-.164-.224-1.365-1.815-1.365-3.461 0-1.646.862-2.457 1.168-2.793.307-.336.671-.42.895-.42.224 0 .448.002.644.011.209.01.488-.079.763.582.28.672.95 2.316 1.034 2.484.084.168.14.364.028.588-.112.224-.168.364-.336.56-.168.196-.354.438-.505.588-.168.168-.344.35-.148.686.196.336.872 1.439 1.869 2.327 1.284 1.144 2.366 1.498 2.702 1.666.336.168.532.14.728-.084.196-.224.84-0.98 1.064-1.316.224-.336.448-.28.756-.168.308.112 1.956.923 2.292 1.091.336.168.56.252.644.392.084.14.084.812-.16 1.498z"/>
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.whatsapp_title', 'WhatsApp') }}</h3>
                                @php
                                    $rawWa = data_get($content, 'info.whatsapp_number', config('storefront.whatsapp', '+1 000 000 0000'));
                                    $waNumber = ($rawWa && $rawWa !== '+1 000 000 0000') ? $rawWa : '[WhatsApp number]';
                                    $waClean = preg_replace('/\D+/', '', (string) $rawWa);
                                    $waUrl = data_get($content, 'info.whatsapp_url', config('storefront.whatsapp_url') ?: 'https://wa.me/' . ($waClean ?: '447000000000'));
                                    $waLinkText = data_get($content, 'info.whatsapp_link_text', 'Chat on WhatsApp');
                                @endphp
                                <p class="np-contact-item-text">{{ $waNumber }}</p>
                                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="np-contact-whatsapp-link">
                                    <span>{{ $waLinkText }}</span>
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="np-card-divider"></div>

                    {{-- Follow us --}}
                    <div class="np-follow-section">
                        <h3 class="np-follow-title">{{ data_get($content, 'info.follow_heading', 'Follow us') }}</h3>
                        <div class="np-social-row">
                            {{-- WhatsApp --}}
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="WhatsApp" aria-label="WhatsApp">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.808.48 3.567 1.393 5.115L2 22l4.98-1.353a10.02 10.02 0 005.051 1.385h.004c5.534 0 10.029-4.495 10.029-10.031A10.034 10.034 0 0012.031 2zm5.795 14.195c-.244.686-1.419 1.309-1.956 1.393-.518.082-1.189.117-3.834-.975-3.376-1.394-5.541-4.838-5.709-5.062-.164-.224-1.365-1.815-1.365-3.461 0-1.646.862-2.457 1.168-2.793.307-.336.671-.42.895-.42.224 0 .448.002.644.011.209.01.488-.079.763.582.28.672.95 2.316 1.034 2.484.084.168.14.364.028.588-.112.224-.168.364-.336.56-.168.196-.354.438-.505.588-.168.168-.344.35-.148.686.196.336.872 1.439 1.869 2.327 1.284 1.144 2.366 1.498 2.702 1.666.336.168.532.14.728-.084.196-.224.84-0.98 1.064-1.316.224-.336.448-.28.756-.168.308.112 1.956.923 2.292 1.091.336.168.56.252.644.392.084.14.084.812-.16 1.498z"/>
                                </svg>
                            </a>
                            {{-- Instagram --}}
                            @php($igUrl = data_get($content, 'info.instagram_url', config('storefront.social.instagram')))
                            @if(filled($igUrl))
                                <a href="{{ $igUrl }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="Instagram" aria-label="Instagram">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                    </svg>
                                </a>
                            @endif
                            {{-- Facebook --}}
                            @php($fbUrl = data_get($content, 'info.facebook_url', config('storefront.social.facebook')))
                            @if(filled($fbUrl))
                                <a href="{{ $fbUrl }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="Facebook" aria-label="Facebook">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                    </svg>
                                </a>
                            @endif
                            {{-- YouTube --}}
                            @php($ytUrl = data_get($content, 'info.youtube_url', config('storefront.social.youtube')))
                            @if(filled($ytUrl))
                                <a href="{{ $ytUrl }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="YouTube" aria-label="YouTube">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Bottom Note --}}
                    <p class="np-card-footer-note">{{ data_get($content, 'info.footer_note', 'For order enquiries, please include your order or quote number.') }}</p>
                </aside>

                {{-- Right Card: Send us a message --}}
                <div class="np-send-message-card">
                    <div class="np-form-header">
                        <h2 class="np-form-title">{{ data_get($content, 'form.heading', 'Send us a message') }}</h2>
                        <p class="np-form-subtitle">{{ data_get($content, 'form.subheading', 'Fields marked * are required.') }}</p>
                    </div>

                    @if(session('status'))
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <strong>Please correct the highlighted fields:</strong>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form data-nps-inline method="POST" action="{{ route('contact.store') }}" class="nps-form" novalidate>
                        @csrf

                        {{-- Invisible Honeypot --}}
                        <div class="hidden" aria-hidden="true" style="display: none !important;">
                            <label for="company">Company</label>
                            <input id="company" name="company" value="" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- Row 1: Full name & Email address --}}
                        <div class="np-form-row-2">
                            <div class="np-form-group mb-0">
                                <label for="contact-name" class="np-field-label">Full name <span class="np-req">*</span></label>
                                <input
                                    id="contact-name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name', auth()->user()?->name) }}"
                                    placeholder="Your full name"
                                    required
                                    maxlength="120"
                                    autocomplete="name"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                >
                            </div>

                            <div class="np-form-group mb-0">
                                <label for="contact-email" class="np-field-label">Email address <span class="np-req">*</span></label>
                                <input
                                    id="contact-email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', auth()->user()?->email) }}"
                                    placeholder="you@example.com"
                                    required
                                    maxlength="190"
                                    autocomplete="email"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                >
                            </div>
                        </div>

                        {{-- Row 2: Phone & Subject --}}
                        <div class="np-form-row-2">
                            <div class="np-form-group mb-0">
                                <label for="contact-phone" class="np-field-label">Phone <span class="np-optional-tag">(optional)</span></label>
                                <input
                                    id="contact-phone"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="Your phone number"
                                    maxlength="40"
                                    autocomplete="tel"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                                >
                            </div>

                            <div class="np-form-group mb-0">
                                <label for="contact-topic" class="np-field-label">Subject <span class="np-req">*</span></label>
                                <select
                                    id="contact-topic"
                                    name="topic"
                                    required
                                    class="np-select"
                                    aria-invalid="{{ $errors->has('topic') ? 'true' : 'false' }}"
                                >
                                    <option value="" disabled @selected(old('topic') === null || old('topic') === '')>Choose a subject</option>
                                    @foreach([
                                        'product-question' => 'Product question',
                                        'order-support' => 'Order support',
                                        'customization' => 'Customization or artwork',
                                        'bulk-quote' => 'Team or bulk quote',
                                        'shipping' => 'Shipping or tracking',
                                        'return-refund' => 'Return or refund',
                                        'website-help' => 'Website help',
                                        'other' => 'Other',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Row 3: Order / quote number --}}
                        <div class="np-form-group">
                            <label for="contact-order" class="np-field-label">Order / quote number <span class="np-optional-tag">(optional)</span></label>
                            <input
                                id="contact-order"
                                type="text"
                                name="order_number"
                                value="{{ old('order_number') }}"
                                placeholder="e.g. BQ-20261029-XXXXXX"
                                maxlength="80"
                                class="np-input"
                                aria-invalid="{{ $errors->has('order_number') ? 'true' : 'false' }}"
                            >
                        </div>

                        {{-- Row 4: Message --}}
                        <div class="np-form-group">
                            <label for="contact-message" class="np-field-label">Message <span class="np-req">*</span></label>
                            <textarea
                                id="contact-message"
                                name="message"
                                placeholder="How can we help?"
                                required
                                minlength="10"
                                maxlength="5000"
                                class="np-textarea"
                                aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                            >{{ old('message') }}</textarea>
                        </div>

                        {{-- Row 5: Security verification * --}}
                        <div class="np-captcha-container">
                            <label class="np-field-label">Security verification <span class="np-req">*</span></label>
                            
                            @if(filled(config('services.recaptcha.site_key')) && !app()->environment('local'))
                                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            @else
                                <div class="np-captcha-box" onclick="toggleCaptcha(this)">
                                    <div class="np-captcha-checkbox-wrap">
                                        <div class="np-captcha-checkbox" id="mock-captcha-box" role="checkbox" aria-checked="false" tabindex="0">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </div>
                                        <span class="np-captcha-label">I'm not a robot</span>
                                    </div>
                                    <div class="np-captcha-badge">
                                        <svg class="np-captcha-logo" viewBox="0 0 64 64" fill="none">
                                            <path d="M32 4C16.536 4 4 16.536 4 32s12.536 28 28 28c11.046 0 20.612-6.398 25.183-15.698l-7.792-3.896C46.335 46.22 39.697 50.4 32 50.4c-10.162 0-18.4-8.238-18.4-18.4S21.838 13.6 32 13.6c5.523 0 10.457 2.44 13.823 6.305L37.2 28.528H60V5.728l-8.54 8.54C46.602 8.358 39.684 4 32 4z" fill="#1A73E8"/>
                                        </svg>
                                        <span class="np-captcha-text">reCAPTCHA</span>
                                        <span class="np-captcha-links"><a href="https://www.google.com/intl/en/policies/privacy/" target="_blank" rel="noopener">Privacy</a> - <a href="https://www.google.com/intl/en/policies/terms/" target="_blank" rel="noopener">Terms</a></span>
                                    </div>
                                </div>
                                <script>
                                    function toggleCaptcha(container) {
                                        var box = container.querySelector('.np-captcha-checkbox');
                                        var isChecked = box.classList.toggle('is-checked');
                                        box.setAttribute('aria-checked', isChecked ? 'true' : 'false');
                                    }
                                </script>
                            @endif
                        </div>

                        {{-- Bottom Bar --}}
                        <div class="np-form-bottom-bar">
                            <p class="np-privacy-notice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                <span>{{ data_get($content, 'form.privacy_notice', 'Your details are used to respond to your enquiry.') }}</span>
                            </p>

                            <button type="submit" class="np-submit-btn">
                                <span>{{ data_get($content, 'form.submit_btn_text', 'Send Message') }}</span>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
