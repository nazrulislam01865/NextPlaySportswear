@php
    $hasSavedContact = (bool) ($savedContact['is_complete'] ?? false);
    $defaultContactChoice = old('contact_choice', ($state['information']['source'] ?? null) === 'new' || ! $hasSavedContact ? 'new' : 'saved');
@endphp

<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Contact Information" description="Use your saved account contact or add the minimum details needed for order updates." :summary="$summary">
    <x-storefront.checkout.panel title="Contact Information" description="We only need the details required for order confirmation, payment updates, and delivery communication.">
        <form method="POST" action="{{ route('checkout.information.store') }}" class="grid gap-6" x-data="{ contactChoice: @js($defaultContactChoice) }">
            @csrf

            @if ($hasSavedContact)
                <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="contact_choice" value="saved" class="mt-1" x-model="contactChoice">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="text-brand-ink">Use saved contact</strong>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-black uppercase text-slate-500">Account</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                                {{ $savedContact['label'] ?? '' }} · {{ $savedContact['email'] ?? '' }} · {{ $savedContact['phone'] ?? '' }}
                            </p>
                        </div>
                    </div>
                </label>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <label class="mb-5 flex items-center gap-3 text-sm font-black text-brand-ink">
                    <input type="radio" name="contact_choice" value="new" x-model="contactChoice">
                    {{ $hasSavedContact ? 'Use different contact information' : 'Add contact information' }}
                </label>

                <div class="grid gap-5 sm:grid-cols-2" x-show="contactChoice === 'new'" x-cloak>
                    <x-storefront.checkout.form-field label="Email address" name="email" type="email" placeholder="you@example.com" :value="$state['information']['email'] ?? auth()->user()?->email" required />
                    <x-storefront.checkout.form-field label="Phone number" name="phone" type="tel" placeholder="+1 000 000 0000" :value="$state['information']['phone'] ?? auth()->user()?->phone" required />
                    <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" :value="$state['information']['first_name'] ?? $savedContact['first_name'] ?? ''" required />
                    <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" :value="$state['information']['last_name'] ?? $savedContact['last_name'] ?? ''" required />
                </div>

                @auth
                    <label class="mt-5 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-700" x-show="contactChoice === 'new'" x-cloak>
                        <input type="checkbox" name="save_to_account" value="1" class="mt-1">
                        Save this name and phone number to my account for faster checkout next time.
                    </label>
                @endauth
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <x-storefront.checkout.form-field label="Order note" name="order_note" placeholder="Optional: delivery notes or special order instructions..." :value="$state['information']['order_note'] ?? ''" textarea full hint="Optional. Keep product customization details on the product/cart item whenever possible." />
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('cart.index') }}">Back to Cart</a>
                <button class="btn btn-red" type="submit">Continue to Shipping</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
