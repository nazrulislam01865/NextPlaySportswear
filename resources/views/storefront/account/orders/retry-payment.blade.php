<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" title="Retry Failed Payment" :subtitle="'Review the latest issue and create a new secure payment attempt for '.$order->order_number.'.'" eyebrow="Payment recovery">
    <x-slot:actions><a class="btn btn-white" href="{{ route('account.orders.show',$order) }}">Order Details</a></x-slot:actions>
    @include('storefront.account.orders.partials.payment-form', ['retryMode'=>true])
</x-storefront.account.orders.page>
