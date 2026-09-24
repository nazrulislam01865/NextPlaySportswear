@props([
    'title' => 'Create Account',
])

<section class="np-customer-login np-customer-register" aria-labelledby="customer-register-title">
    <div class="np-customer-login__card np-customer-register__card">
        <h1 id="customer-register-title" class="np-customer-login__title np-customer-register__title">
            {{ $title }}
        </h1>

        {{ $slot }}
    </div>
</section>
