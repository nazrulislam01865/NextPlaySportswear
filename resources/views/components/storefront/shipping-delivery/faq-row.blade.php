@props(['faq'])
<details class="np-shipping-delivery-faq" open>
    <summary>
        <span>{{ data_get($faq, 'question') }}</span>
        <span class="np-shipping-delivery-faq__chevron" aria-hidden="true">⌃</span>
    </summary>
    <div class="np-shipping-delivery-faq__answer">{{ data_get($faq, 'answer') }}</div>
</details>
