# NextPlay E-commerce Remaining Pages Audit

This audit reflects the current Laravel project after implementation of the persistent customer order-management module.

## Present and database-backed

### Storefront and checkout

- Homepage and managed homepage slider
- Category landing and category product pages
- Product listing/search
- Flexible product details and customization
- Session cart with server-side price recalculation
- Login, registration, customer profile, addresses, and tokenized payment-method metadata
- Checkout information, shipping address, billing address, shipping method, payment method, review, and place-order steps
- Durable order and item creation at checkout

### Customer order center

- Customer Orders dashboard
- My Orders / Order History
- Authenticated Order Details
- Pay for Order and Retry Failed Payment
- Order Again / Reorder
- Cancel Order Request
- Change Order Request
- Shipment Details
- Partial / Split Shipments
- Return Request with private evidence
- Exchange Request with private evidence
- Return History
- Return Details and Status
- Refund Status
- Credit Note preview and secure PDF download
- Secure Invoice preview and PDF download
- Private Order Downloads for digital files

### Admin

- Separate admin authentication
- Admin dashboard
- Category, attribute, navigation, product, and homepage-slider management
- Order list and order details
- Payment/order/fulfillment status management
- Split shipment creation and tracking updates
- Cancellation and change-request review
- Return/exchange review and private evidence access
- Refund processing state and automatic credit-note creation
- Private order-download upload, limits, expiry, and deletion
- Order history/audit timeline
- Least-privilege separation: catalog managers cannot access order/customer operations

## Highest-priority remaining work

1. **Live payment provider and webhooks**  
   Add Stripe, PayPal, or the chosen provider using hosted/tokenized payment collection and signature-verified idempotent webhooks. Current payment attempts are provider-neutral and intentionally do not mark an order paid without verified confirmation or authorized reconciliation.

2. **Bulk quote workflow**  
   Replace the current quote placeholder with quote submission, admin pricing, attachments, approval, expiry, and quote-to-order conversion.

3. **Artwork and proof workflow**  
   Add customer artwork uploads, admin/designer proof versions, approval/rejection comments, and production locking after approval.

4. **Password reset and email verification**  
   Complete forgot-password, reset-token, email-verification, and resend-verification pages.

5. **Queued customer notifications**  
   Send email notifications for order placement, payment, proof, production, shipment, return, refund, and download availability.

6. **Persistent saved carts**  
   The active cart remains session-based. Add database-backed saved carts for authenticated customers who need to resume large team orders.

## Other customer modules still useful

- Quote history and quote details
- Saved designs/artwork library
- Wishlist / saved products
- Review and rating submission
- Support ticket list and details
- Notification preferences
- Account data export and deletion request
- Gift-card balance/history only if gift cards remain in the product plan

## Content and compliance work still useful

The project includes several content/support pages, but final legal and policy wording should be reviewed for the actual business, fulfillment locations, payment provider, return rules, privacy practices, and applicable US state requirements:

- About and Contact
- FAQ / Help Center
- Size Guide
- Artwork and Logo Guidelines
- Shipping and Delivery Policy
- Returns, Refunds, and Exchanges Policy
- Privacy Policy
- Terms and Conditions
- Cookie consent/settings
- Accessibility Statement
- Team/bulk-order instructions

## Additional admin modules still useful

- Quote builder and quote conversion
- Artwork/proof production board
- Customer support/tickets
- Payment webhook/event log
- Coupons, promotions, and gift cards
- Shipping zones, carrier integrations, and live rates
- Tax-provider integration
- Review moderation
- Sales, product, customer, return, and fulfillment reports
- Broader audit log and data-retention controls

## Current technical boundaries

- Orders and order items are durable database records.
- Checkout form state and the active cart are still session-based until order placement.
- Product pricing is recalculated server-side.
- Customer order documents and evidence are served from private storage through authorization and signed links.
- A real payment-provider adapter and webhook verification are still required before live card/PayPal acceptance.
- PDF invoice and credit-note generation is intentionally dependency-free and compact; a branded PDF library can be introduced later if richer pagination/layout is required.
- Queued emails and object-storage malware scanning are recommended before high-volume production use.

## Recommended next implementation order

1. Live payment provider and verified webhooks
2. Quote, artwork, and proof approval workflow
3. Queued order/return/shipment notifications
4. Password reset and email verification
5. Persistent saved carts and saved designs
6. Carrier/tax integrations
7. Support tickets, reviews, promotions, and analytics
