<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Checkout Information" description="Enter contact information and order deadline details before selecting your shipping address." :summary="$summary">
    <x-storefront.checkout.panel title="Contact Information" description="We use this to send order updates, design proof messages, and delivery notifications.">
        @guest
            <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <strong class="text-sm text-brand-ink">Already have an account?</strong>
                    <p class="text-sm font-semibold text-slate-600">Sign in to use saved addresses, saved payment methods, saved designs, and repeat orders.</p>
                </div>
                <a href="{{ route('login') }}" class="btn btn-white shrink-0">Sign In</a>
            </div>
        @endguest

        <form method="POST" action="{{ route('checkout.information.store') }}" class="grid gap-5">
            @csrf
            <div class="grid gap-5 sm:grid-cols-2">
                <x-storefront.checkout.form-field label="Email address" name="email" type="email" placeholder="you@example.com" :value="$state['information']['email'] ?? auth()->user()?->email" required />
                <x-storefront.checkout.form-field label="Phone number" name="phone" type="tel" placeholder="+1 000 000 0000" :value="$state['information']['phone'] ?? auth()->user()?->phone" required />
                <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" :value="$state['information']['first_name'] ?? explode(' ', auth()->user()?->name ?? '')[0] ?? ''" required />
                <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" :value="$state['information']['last_name'] ?? ''" required />
                <x-storefront.checkout.form-field label="Order type" name="order_type" :value="$state['information']['order_type'] ?? 'personal'" :options="['personal' => 'Personal order', 'team' => 'Team order', 'school' => 'School / college order', 'corporate' => 'Corporate or event order']" full required />
                <x-storefront.checkout.form-field label="Preferred delivery deadline" name="delivery_deadline" type="date" :value="$state['information']['delivery_deadline'] ?? ''" />
                <x-storefront.checkout.form-field label="Design proof needed?" name="proof_preference" :value="$state['information']['proof_preference'] ?? 'proof_required'" :options="['proof_required' => 'Yes, send proof before production', 'use_artwork' => 'No, use uploaded artwork as provided', 'contact_first' => 'Not sure, contact me first']" required />
                <x-storefront.checkout.form-field label="Order note" name="order_note" placeholder="Add logo placement, player list note, event date, or special instruction..." :value="$state['information']['order_note'] ?? ''" textarea full hint="For custom products, production starts only after final details are confirmed." />
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('cart.index') }}">Back to Cart</a>
                <button class="btn btn-red" type="submit">Continue to Shipping</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
