@props([
    'title' => 'Sign In',
])

<section class="np-customer-login" aria-labelledby="customer-login-title">
    <div class="np-customer-login__card">
        <h1 id="customer-login-title" class="np-customer-login__title">
            {{ $title }}
        </h1>

        {{ $slot }}
    </div>
</section>
