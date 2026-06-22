@props([
    'title',
    'description',
    'eyebrow',
    'updated',
    'sections' => [],
])

<x-storefront.content.hero :eyebrow="$eyebrow" :title="$title" :description="$description">
    <span class="rounded-full border border-slate-300 bg-white px-4 py-2 text-xs font-extrabold text-slate-600">Last updated: {{ $updated }}</span>
</x-storefront.content.hero>

<section class="section-padding">
    <div class="site-container grid gap-8 lg:grid-cols-[250px_1fr] lg:items-start">
        <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-5 lg:sticky lg:top-36">
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">On this page</p>
            <nav class="mt-4 grid gap-1 text-sm font-bold text-slate-600" aria-label="Page contents">
                @foreach($sections as $section)
                    <a href="#{{ $section['id'] }}" class="rounded-lg px-3 py-2 hover:bg-white hover:text-brand-red">{{ $section['title'] }}</a>
                @endforeach
            </nav>
        </aside>

        <article class="min-w-0 rounded-3xl border border-slate-200 bg-white p-6 shadow-card sm:p-8 lg:p-10">
            <div class="space-y-10">
                @foreach($sections as $section)
                    <section id="{{ $section['id'] }}" class="scroll-mt-36">
                        <h2 class="font-display text-2xl font-bold uppercase text-brand-ink sm:text-3xl">{{ $section['title'] }}</h2>
                        @foreach($section['paragraphs'] ?? [] as $paragraph)
                            <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">{{ $paragraph }}</p>
                        @endforeach
                        @if(!empty($section['items']))
                            <ul class="mt-4 grid gap-3 text-sm text-slate-600">
                                @foreach($section['items'] as $item)
                                    <li class="flex gap-3"><span class="mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-red-50 text-xs font-black text-brand-red">✓</span><span>{{ $item }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        </article>
    </div>
</section>
