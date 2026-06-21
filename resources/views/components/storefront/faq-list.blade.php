@props([
    'faqs' => [],
])

<div class="mx-auto max-w-3xl" x-data="{ open: 0 }">
    @foreach($faqs as $index => $faq)
        <div class="mb-2 overflow-hidden rounded-xl border border-slate-200 bg-white">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-extrabold text-brand-ink"
                @click="open = open === {{ $index }} ? null : {{ $index }}"
                :aria-expanded="(open === {{ $index }}).toString()"
            >
                <span>{{ $faq['question'] }}</span>
                <span class="text-xl text-slate-500" x-text="open === {{ $index }} ? '−' : '+'"></span>
            </button>

            <div
                x-cloak
                x-show="open === {{ $index }}"
                x-transition
                class="px-5 pb-5 text-sm text-slate-500"
            >
                {{ $faq['answer'] }}
            </div>
        </div>
    @endforeach
</div>
