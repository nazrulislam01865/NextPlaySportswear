@props([
    'slides' => [],
])

@if(count($slides) > 0)
    <section
        x-data="{
            active: 0,
            slides: {{ count($slides) }},
            next() {
                this.active = (this.active + 1) % this.slides;
            },
            previous() {
                this.active = this.active === 0 ? this.slides - 1 : this.active - 1;
            },
            init() {
                setInterval(() => this.next(), 6000);
            }
        }"
        class="relative overflow-hidden bg-brand-dark"
        aria-label="Homepage promotional slider"
    >
        <div class="relative h-[440px] sm:h-[500px] lg:h-[560px]">
            @foreach($slides as $index => $slide)
                <article
                    x-cloak
                    x-show="active === {{ $index }}"
                    x-transition.opacity.duration.500ms
                    class="absolute inset-0"
                >
                    <img
                        src="{{ $slide['image'] }}"
                        alt="{{ $slide['alt'] }}"
                        class="h-full w-full object-cover"
                        @if($index === 0) loading="eager" @else loading="lazy" @endif
                    >

                    <div class="absolute inset-0 bg-gradient-to-r from-brand-dark/95 via-brand-dark/75 to-brand-dark/20"></div>

                    <div class="site-container absolute inset-x-0 top-1/2 -translate-y-1/2">
                        <div class="max-w-2xl text-white">
                            <p class="mb-3 text-xs font-black uppercase tracking-[.18em] text-brand-red">
                                {{ $slide['eyebrow'] }}
                            </p>

                            <h1 class="font-display text-4xl font-bold uppercase leading-[.95] tracking-tight sm:text-5xl lg:text-7xl">
                                {{ $slide['title'] }}
                            </h1>

                            <p class="mt-5 max-w-xl text-base text-white/85 sm:text-lg">
                                {{ $slide['description'] }}
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ $slide['primary_url'] }}" class="btn btn-red">
                                    {{ $slide['primary_label'] }}
                                </a>

                                <a href="{{ $slide['secondary_url'] }}" class="btn btn-white">
                                    {{ $slide['secondary_label'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <button
            type="button"
            class="absolute left-4 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-ink shadow hover:bg-white"
            aria-label="Previous slide"
            @click="previous()"
        >
            ‹
        </button>

        <button
            type="button"
            class="absolute right-4 top-1/2 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-brand-ink shadow hover:bg-white"
            aria-label="Next slide"
            @click="next()"
        >
            ›
        </button>

        <div class="absolute bottom-5 left-0 right-0 flex justify-center gap-2">
            @foreach($slides as $index => $slide)
                <button
                    type="button"
                    class="h-2.5 rounded-full transition"
                    :class="active === {{ $index }} ? 'bg-brand-red w-8' : 'bg-white/70 w-2.5'"
                    aria-label="Go to slide {{ $index + 1 }}"
                    @click="active = {{ $index }}"
                ></button>
            @endforeach
        </div>
    </section>
@endif
