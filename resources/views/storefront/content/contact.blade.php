<x-layouts.storefront :seo="[
    'title' => 'Contact Us | ' . config('storefront.name'),
    'description' => 'Contact NextPlay Sportswear for product questions, order support, artwork guidance, shipping help, returns, or team and bulk orders.',
]">
    <x-storefront.content.hero
        eyebrow="Customer support"
        title="Tell Us What You Need Help With"
        description="Send a product question, order reference, artwork concern, shipping request, or general support message. Providing clear details helps our team respond accurately."
        :image="asset('storage/storefront/content/contact.webp')"
        image-alt="Customer support team working together"
    >
        <a href="#contact-form" class="btn btn-red">Send a Message</a>
        <a href="{{ route('faq') }}" class="btn btn-white">Browse FAQs</a>
    </x-storefront.content.hero>

    <section class="section-padding" id="contact-form">
        <div class="site-container grid gap-7 lg:grid-cols-[.72fr_1.28fr] lg:items-start">
            <aside class="grid gap-4 lg:sticky lg:top-36">
                <div class="rounded-2xl bg-brand-navy p-6 text-white shadow-soft">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-red-300">Contact details</p>
                    <h2 class="mt-2 font-display text-3xl font-bold uppercase">Reach the right support path</h2>
                    <div class="mt-6 grid gap-4 text-sm">
                        <div><span class="block text-white/60">Email</span><a class="font-bold hover:underline" href="mailto:{{ config('storefront.email') }}">{{ config('storefront.email') }}</a></div>
                        <div><span class="block text-white/60">WhatsApp</span><a class="font-bold hover:underline" href="https://wa.me/{{ preg_replace('/\D+/', '', config('storefront.whatsapp')) }}" rel="noopener">{{ config('storefront.whatsapp') }}</a></div>
                        <div><span class="block text-white/60">Business hours</span><strong>Monday–Friday</strong></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h3 class="font-extrabold text-brand-ink">Include these details when relevant</h3>
                    <ul class="mt-3 grid gap-2 text-sm text-slate-600">
                        <li>• Order or quote number</li>
                        <li>• Product name and quantity</li>
                        <li>• Required delivery date</li>
                        <li>• Artwork or customization concern</li>
                        <li>• Photos for damage or quality issues</li>
                    </ul>
                </div>
            </aside>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-card sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Support request</p>
                <h2 class="mt-2 font-display text-3xl font-bold uppercase text-brand-ink">Send Us a Message</h2>
                <p class="mt-2 text-sm text-slate-500">Fields marked with an asterisk are required. Do not include full payment-card information.</p>

                @if(session('status'))
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                        <strong>Please correct the highlighted fields.</strong>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="mt-7 grid gap-5" novalidate>
                    @csrf
                    <div class="hidden" aria-hidden="true">
                        <label for="company">Company</label><input id="company" name="company" value="" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="mb-2 block text-sm font-extrabold text-brand-ink">Name *</label>
                            <input id="contact-name" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="120" autocomplete="name" class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 outline-none focus:border-brand-blue" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                        </div>
                        <div>
                            <label for="contact-email" class="mb-2 block text-sm font-extrabold text-brand-ink">Email *</label>
                            <input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="190" autocomplete="email" class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 outline-none focus:border-brand-blue" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-phone" class="mb-2 block text-sm font-extrabold text-brand-ink">Phone</label>
                            <input id="contact-phone" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 outline-none focus:border-brand-blue">
                        </div>
                        <div>
                            <label for="contact-topic" class="mb-2 block text-sm font-extrabold text-brand-ink">Topic *</label>
                            <select id="contact-topic" name="topic" required class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 outline-none focus:border-brand-blue">
                                <option value="">Choose a topic</option>
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
                    <div>
                        <label for="contact-order" class="mb-2 block text-sm font-extrabold text-brand-ink">Order or quote number</label>
                        <input id="contact-order" name="order_number" value="{{ old('order_number') }}" maxlength="80" class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 uppercase outline-none focus:border-brand-blue" placeholder="Example: NP-2026-00125">
                    </div>
                    <div>
                        <label for="contact-message" class="mb-2 block text-sm font-extrabold text-brand-ink">Message *</label>
                        <textarea id="contact-message" name="message" required minlength="10" maxlength="5000" rows="7" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 outline-none focus:border-brand-blue" placeholder="Describe the product, issue, timeline, or support you need...">{{ old('message') }}</textarea>
                        <p class="mt-2 text-xs text-slate-500">Maximum 5,000 characters.</p>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-5 text-slate-500">By submitting this form, you agree that we may use the information to respond to your request.</p>
                        <button type="submit" class="btn btn-red shrink-0">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="section-alt section-padding">
        <div class="site-container">
            <x-storefront.section-heading eyebrow="Faster self-service" title="You May Find the Answer Here" description="Use the most relevant guide before sending a support request." />
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-storefront.content.icon-card icon="?" title="Help Center" description="Common questions about ordering, customization, shipping, returns, and payments."><a href="{{ route('faq') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">View FAQs →</a></x-storefront.content.icon-card>
                <x-storefront.content.icon-card icon="S" title="Size Guide" description="Measurement instructions and general adult and youth size charts."><a href="{{ route('size-guide') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">Check Sizes →</a></x-storefront.content.icon-card>
                <x-storefront.content.icon-card icon="A" title="Artwork Guidelines" description="Preferred files, image quality, color guidance, and proof expectations."><a href="{{ route('artwork-guidelines') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">Prepare Artwork →</a></x-storefront.content.icon-card>
                <x-storefront.content.icon-card icon="T" title="Track Order" description="Check the latest available production and shipping status for an order."><a href="{{ route('orders.track') }}" class="mt-4 inline-flex text-xs font-black uppercase text-brand-red">Track Order →</a></x-storefront.content.icon-card>
            </div>
        </div>
    </section>
</x-layouts.storefront>
