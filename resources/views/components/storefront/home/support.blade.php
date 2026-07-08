@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section class="support">
    <div class="container">
        <div class="support-card">
            <span class="small-red">{{ $text('eyebrow', 'Order support') }}</span>
            <h2>{{ $text('title', 'Need Help with Sizes, Logos, or Artwork?') }}</h2>
            <p>{{ $text('description', 'For team orders, send us the size list, logo file, player names, numbers, preferred colors, and delivery deadline. We’ll review the details and let you know if anything needs to be adjusted.') }}</p>
            @if(filled($text('primary_label')))<a class="btn btn-red" href="{{ $text('primary_url', route('contact')) }}">{{ $text('primary_label', 'Start Your Order') }}</a>@endif
            <p class="support-note">Artwork proof or mockup review may be needed before custom production.</p>
        </div>
    </div>
</section>
