<x-layouts.storefront :seo="[
    'title' => 'Size Guide | ' . config('storefront.name'),
    'description' => 'Use the NextPlay Sportswear size guide to measure chest, waist, hips, inseam, and height before ordering custom jerseys, uniforms, and apparel.',
]">
    <x-storefront.content.hero
        eyebrow="Fit and measurements"
        title="Measure First. Order with Confidence."
        description="Custom products may have limited return options, so accurate measurements matter. Use a flexible tape, measure over light clothing, and compare the result with the size chart shown for the specific product."
        :image="asset('storage/storefront/content/size-guide.webp')"
        image-alt="Athletic apparel prepared for sizing"
    >
        <a href="#size-tables" class="btn btn-red">View Size Charts</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Ask a Size Question</a>
    </x-storefront.content.hero>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Before measuring" title="Get a More Reliable Result" description="Small measurement mistakes can lead to the wrong fit across an entire team order." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="1" title="Use a soft tape" description="Keep the tape flat and comfortably close to the body without pulling it tight." />
                <x-storefront.content.icon-card icon="2" title="Measure light clothing" description="Avoid bulky layers unless the product is designed to be worn over pads or equipment." />
                <x-storefront.content.icon-card icon="3" title="Record actual measurements" description="Do not guess from age or a different brand. Write the measured inches before selecting a size." />
                <x-storefront.content.icon-card icon="4" title="Check the product chart" description="The product-specific chart takes priority over the general reference charts on this page." />
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
            <div class="overflow-hidden rounded-3xl bg-white p-3 shadow-soft">
                <img loading="lazy" src="{{ asset('storage/storefront/content/size-guide.webp') }}" width="900" height="620" decoding="async" alt="Athlete standing in fitted sportswear" class="h-[410px] w-full rounded-2xl object-cover">
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">How to measure</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Use the same method for the whole roster</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['Chest', 'Measure around the fullest part of the chest, keeping the tape level under the arms and across the shoulder blades.'],
                        ['Waist', 'Measure around the natural waistline without holding the breath or pulling the tape too tightly.'],
                        ['Hips', 'Stand with feet together and measure around the fullest part of the hips and seat.'],
                        ['Inseam', 'Measure from the crotch seam down the inside leg to the preferred pant or short length.'],
                        ['Height', 'Stand straight without shoes and measure from the floor to the top of the head.'],
                        ['Sleeve', 'Measure from the center back of the neck, across the shoulder, and down to the wrist.'],
                    ] as [$title, $text])
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <h3 class="font-extrabold text-brand-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" id="size-tables">
        <div class="site-container" x-data="{ chart: 'adult' }">
            <x-storefront.section-heading eyebrow="General reference" title="Size Charts" description="These tables are general guidance only. Always use the chart attached to the selected product when one is available." />
            <div class="flex flex-wrap justify-center gap-2">
                <button type="button" @click="chart = 'adult'" :class="chart === 'adult' ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-slate-600 border-slate-300'" class="rounded-full border px-5 py-2 text-sm font-extrabold">Adult Tops</button>
                <button type="button" @click="chart = 'youth'" :class="chart === 'youth' ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-slate-600 border-slate-300'" class="rounded-full border px-5 py-2 text-sm font-extrabold">Youth Tops</button>
                <button type="button" @click="chart = 'bottoms'" :class="chart === 'bottoms' ? 'bg-brand-navy text-white border-brand-navy' : 'bg-white text-slate-600 border-slate-300'" class="rounded-full border px-5 py-2 text-sm font-extrabold">Bottoms</button>
            </div>

            <div class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-brand-navy text-white">
                            <tr>
                                <th class="px-5 py-4 font-extrabold">Size</th>
                                <th class="px-5 py-4 font-extrabold">Chest</th>
                                <th class="px-5 py-4 font-extrabold">Waist</th>
                                <th class="px-5 py-4 font-extrabold">Hips</th>
                                <th class="px-5 py-4 font-extrabold">Approx. Height</th>
                            </tr>
                        </thead>
                        <tbody x-show="chart === 'adult'" class="divide-y divide-slate-200">
                            @foreach([
                                ['XS', '32–34 in', '26–28 in', '32–34 in', '5\'2\"–5\'6\"'],
                                ['S', '35–37 in', '29–31 in', '35–37 in', '5\'4\"–5\'8\"'],
                                ['M', '38–40 in', '32–34 in', '38–40 in', '5\'6\"–5\'10\"'],
                                ['L', '41–43 in', '35–37 in', '41–43 in', '5\'8\"–6\'0\"'],
                                ['XL', '44–46 in', '38–40 in', '44–46 in', '5\'9\"–6\'2\"'],
                                ['2XL', '47–50 in', '41–44 in', '47–50 in', '5\'10\"–6\'4\"'],
                                ['3XL', '51–54 in', '45–48 in', '51–54 in', '5\'10\"–6\'5\"'],
                            ] as $row)
                                <tr class="odd:bg-white even:bg-slate-50">@foreach($row as $cell)<td class="px-5 py-4 {{ $loop->first ? 'font-extrabold text-brand-ink' : 'text-slate-600' }}">{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                        <tbody x-cloak x-show="chart === 'youth'" class="divide-y divide-slate-200">
                            @foreach([
                                ['YXS', '24–26 in', '22–23 in', '25–27 in', '3\'10\"–4\'2\"'],
                                ['YS', '26–28 in', '23–24 in', '27–29 in', '4\'1\"–4\'5\"'],
                                ['YM', '28–30 in', '24–25 in', '29–31 in', '4\'4\"–4\'8\"'],
                                ['YL', '30–32 in', '25–27 in', '31–33 in', '4\'7\"–5\'0\"'],
                                ['YXL', '32–34 in', '27–29 in', '33–35 in', '4\'11\"–5\'4\"'],
                            ] as $row)
                                <tr class="odd:bg-white even:bg-slate-50">@foreach($row as $cell)<td class="px-5 py-4 {{ $loop->first ? 'font-extrabold text-brand-ink' : 'text-slate-600' }}">{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                        <tbody x-cloak x-show="chart === 'bottoms'" class="divide-y divide-slate-200">
                            @foreach([
                                ['XS', '—', '26–28 in', '32–34 in', '27–29 in inseam'],
                                ['S', '—', '29–31 in', '35–37 in', '28–30 in inseam'],
                                ['M', '—', '32–34 in', '38–40 in', '29–31 in inseam'],
                                ['L', '—', '35–37 in', '41–43 in', '30–32 in inseam'],
                                ['XL', '—', '38–40 in', '44–46 in', '31–33 in inseam'],
                                ['2XL', '—', '41–44 in', '47–50 in', '31–34 in inseam'],
                            ] as $row)
                                <tr class="odd:bg-white even:bg-slate-50">@foreach($row as $cell)<td class="px-5 py-4 {{ $loop->first ? 'font-extrabold text-brand-ink' : 'text-slate-600' }}">{{ $cell }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-4 text-center text-xs leading-5 text-slate-500">Measurements are body measurements in inches, not finished-garment measurements. Product cut, fabric stretch, protective equipment, and intended fit can change the recommended size.</p>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-6 lg:grid-cols-2">
            <x-storefront.content.callout title="Between two sizes?" tone="navy">Consider the intended fit, fabric, protective equipment, and growth allowance. A looser fit may be preferable for layering, while compression or performance products may follow a different chart.</x-storefront.content.callout>
            <x-storefront.content.callout title="Team roster tip" tone="amber">Have each player confirm their own size against the selected product chart. Avoid assigning sizes only by age, position, or a previous uniform from another manufacturer.</x-storefront.content.callout>
        </div>
    </section>

    <x-storefront.content.cta title="Need Help Before You Submit Sizes?" description="Send the product link, measurements, quantity, and intended fit so support can review the question before the order is finalized." primary-label="Contact Support" :primary-href="route('contact')" secondary-label="Browse Products" :secondary-href="route('products.index')" />
</x-layouts.storefront>
