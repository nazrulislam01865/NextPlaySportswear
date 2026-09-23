<x-layouts.storefront :seo="[
    'title' => 'Artwork and Logo Guidelines | ' . config('storefront.name'),
    'description' => 'Prepare logos and artwork for custom sportswear with guidance on vector files, image resolution, colors, fonts, trademarks, and proof approval.',
]">
    <x-storefront.content.hero
        eyebrow="Design file preparation"
        title="Better Artwork Creates a Smoother Production Review"
        description="Send the cleanest original logo or design file available. Good source artwork helps preserve edges, colors, text, and placement when a design is prepared for printing, sublimation, embroidery, or heat transfer."
        :image="asset('storage/storefront/content/artwork-guidelines.webp')"
        image-alt="Designer preparing digital artwork on a desk"
    >
        <a href="#file-types" class="btn btn-red">View File Requirements</a>
        <a href="{{ route('contact') }}" class="btn btn-white">Ask About Artwork</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="file-types">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Preferred files" title="Send Original, Editable Artwork When Possible" description="A screenshot or social-media image may be useful as a reference, but it is not always suitable for production." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="AI" title="AI or EPS" description="Preferred for scalable vector logos, clean shapes, editable text, and spot-color information." />
                <x-storefront.content.icon-card icon="SVG" title="SVG" description="Useful for scalable web and vector artwork when the file is complete and fonts are converted properly." />
                <x-storefront.content.icon-card icon="PDF" title="Print-ready PDF" description="Accepted when the artwork is high quality, properly embedded, and not only a low-resolution image inside a PDF." />
                <x-storefront.content.icon-card icon="PNG" title="High-resolution PNG" description="May work for raster artwork when the dimensions are large enough and the background is transparent." />
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_.9fr] lg:items-start">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Artwork checklist</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Review the file before uploading</h2>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        ['Use the original logo file', 'Avoid copying a small image from a website or messaging app.'],
                        ['Convert or include fonts', 'Text should not change when the file is opened on another computer.'],
                        ['Remove unwanted backgrounds', 'Transparent backgrounds are preferred unless a background is part of the design.'],
                        ['Provide color references', 'Pantone, CMYK, RGB, HEX, or a physical reference can reduce color ambiguity.'],
                        ['Check spelling and numbers', 'Team names, player names, dates, and numbers must be reviewed before approval.'],
                        ['Organize placement instructions', 'State front, back, sleeve, leg, cap, bag, or other intended locations.'],
                    ] as [$title, $text])
                        <article class="rounded-xl border border-slate-200 bg-white p-5">
                            <h3 class="font-extrabold text-brand-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-5">
                <x-storefront.content.callout title="Resolution guidance" tone="navy">For raster images, aim for approximately 300 DPI at the final print size. Increasing the DPI number without adding real image detail does not improve a blurry source file.</x-storefront.content.callout>
                <x-storefront.content.callout title="Do not upload without permission" tone="red">You must have the right to use every logo, trademark, photograph, name, mascot, sponsor mark, and design submitted for production.</x-storefront.content.callout>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Production methods" title="Artwork Needs Can Change by Decoration Type" description="The selected product and production method determine how colors, details, gradients, and small text can be reproduced." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Sublimation', 'Supports detailed full-color artwork integrated into suitable fabric. Color appearance can vary by fabric and screen calibration.'],
                    ['Embroidery', 'Fine details and tiny text may need simplification. Thread colors and stitch direction affect the finished appearance.'],
                    ['Screen Printing', 'Often works best with separated colors and clean shapes. The number of colors and print locations may affect price.'],
                    ['Heat Transfer', 'Useful for names, numbers, and selected graphics. Material, size, placement, and garment type affect suitability.'],
                ] as [$title, $text])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
                        <h3 class="font-display text-xl font-bold uppercase text-brand-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
            <div class="overflow-hidden rounded-3xl bg-white p-3 shadow-soft">
                <img loading="lazy" src="{{ asset('storage/storefront/content/artwork-guidelines.webp') }}" width="900" height="620" decoding="async" alt="Printed sportswear detail" class="h-[360px] w-full rounded-2xl object-cover">
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Proof review</p>
                <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Approval means the visible details have been checked</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Review the proof at a readable size. Check text, spelling, numbers, colors, scale, placement, orientation, and whether all requested logos are present. Ask questions before approval—not after production starts.</p>
                <ul class="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                    @foreach(['Names and spelling', 'Player numbers', 'Logo version', 'Sponsor marks', 'Front and back placement', 'Approximate color direction', 'Product color', 'Requested revisions'] as $item)
                        <li class="flex gap-2"><span class="font-black text-brand-red">✓</span>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <x-storefront.content.cta title="Have Artwork Ready?" description="Choose a customizable product or send your logo, quantity, size list, and deadline for a team or bulk quotation." primary-label="Browse Custom Products" :primary-href="route('products.index')" secondary-label="Request a Quote" :secondary-href="route('quote.request')" />
</x-layouts.storefront>
