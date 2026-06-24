<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" title="Pay for Order" :subtitle="'Pay '.$order->order_number.' through a configured secure payment provider without storing raw card information.'" eyebrow="Secure payment">
    <x-slot:actions><a class="btn btn-white" href="{{ route('account.orders.show',$order) }}">Order Details</a></x-slot:actions>
    @include('storefront.account.orders.partials.payment-form', ['retryMode'=>false])
</x-storefront.account.orders.page>
