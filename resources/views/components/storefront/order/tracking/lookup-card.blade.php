@props([
    'order' => [],
])

<section class="np-track-lookup-card" aria-labelledby="track-order-lookup-title">
    <div class="np-track-lookup-heading">
        <h2 id="track-order-lookup-title">Look Up Your Order</h2>
        <p>Enter your order or tracking ID and the email address used at checkout to see your order status.</p>
    </div>

    @if (session('status'))
        <div class="np-track-feedback np-track-feedback--success" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="np-track-feedback np-track-feedback--error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('orders.track.lookup') }}" class="np-track-lookup-form">
        @csrf

        <div class="np-track-lookup-fields">
            <label class="np-track-field">
                <span>Order / tracking ID</span>
                <input
                    type="text"
                    name="order_number"
                    value="{{ old('order_number', $order['order_number'] ?? '') }}"
                    placeholder="e.g. NP-260929-ABC123"
                    autocomplete="off"
                    required
                >
            </label>

            <label class="np-track-field">
                <span>Email address used at checkout</span>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $order['customer_email'] ?? '') }}"
                    placeholder="e.g. name@example.com"
                    autocomplete="email"
                    required
                >
            </label>
        </div>

        <button type="submit" class="btn btn-secondary np-track-lookup-submit">Find My Order</button>
    </form>

    <p class="np-track-privacy-note">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 10V7a5 5 0 0 1 10 0v3h1a2 2 0 0 1 2 2v8H4v-8a2 2 0 0 1 2-2h1Zm2 0h6V7a3 3 0 0 0-6 0v3Zm3 3a1.5 1.5 0 0 0-.75 2.8V18h1.5v-2.2A1.5 1.5 0 0 0 12 13Z" fill="currentColor"/>
        </svg>
        <span>We use these details to show only your order.</span>
    </p>
</section>
