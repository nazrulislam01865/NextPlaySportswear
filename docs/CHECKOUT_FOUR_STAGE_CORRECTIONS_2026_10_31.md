# NextPlay checkout corrections (31 October 2026)

The customer-facing progress steps are now **Contact → Shipping → Billing → Review**.
There is no fifth payment screen. The Review summary contains the final terms checkbox
and submits directly to the existing Stripe-hosted Checkout via CheckoutController::submitOrder.
Card details are collected only by Stripe, never by NextPlay.

- Shipping address and remote-area surcharge continue to be resolved server-side
  when shipping is saved, and totals are validated again at order placement.
  The extra “Check address & charges” button and HTTP preview call were removed.
- Estimated production and delivery days are derived from `fulfillment_lines`, which
  combine the cart item's configured quantity-dependent production and shipping windows.
  There is no fixed 9–13 day value in the presentation.
- The progress stepper spans the complete checkout container above both columns.
- Removed adjacent Blade directive bugs in the reusable field component and the
  account order details view that could render directive text or cause a ParseError.
- CSS remains scoped to checkout and inherits NextPlay's central Expose font/theme.
- Legacy `/checkout/payment-method` GET remains for old links, but redirects to Review.
- Existing order/payment/FlowTrack sync services are retained; no migrations added.

## Local verification commands

```sh
php artisan optimize:clear
php artisan view:cache
php artisan test --filter=CheckoutPrototypeFlowTest
```

Then test a product configured with production and shipping times, visit all four
stages and confirm that the final button opens the Stripe-hosted checkout and that
`/account/orders/{order_number}` renders. Confirm remote-area pricing with a known
remote postcode. Use Stripe test mode. The test suite was not runnable in the
archive-only environment because the supplied ZIP did not include `vendor/`.
