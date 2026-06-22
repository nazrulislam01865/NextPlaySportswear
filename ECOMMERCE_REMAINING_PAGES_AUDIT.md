# NextPlay E-commerce Remaining Pages Audit

This audit is based on the current routes, controllers, models, services, migrations, and storefront views in the supplied Laravel project.

## Present in the project

- Homepage
- Database-driven category landing page
- Category product-result page
- Product listing/search page
- Product details and customization UI
- Cart
- Login and registration
- Customer profile
- Saved addresses
- Saved payment-method interface
- Checkout information
- Shipping-address selection
- Billing-address selection
- Shipping method
- Payment method
- Order review
- Place-order step
- Checkout success
- Order confirmation
- Payment success and failure
- Order details
- Order tracking UI
- Invoice view

## Storefront pages still required

### Highest priority

1. **Bulk quote request page and submission flow**  
   The current `/quote-request` route is a placeholder.

2. **Forgot-password and reset-password flow**  
   The current forgot-password route is a placeholder and no reset form/token flow exists.

3. **Customer order history**  
   The account “Orders” section is currently informational rather than database-backed.

4. **Quote history and quote details**  
   Customer quote pages are currently informational placeholders.

5. **Saved designs and artwork/proof approval**  
   Required for a custom sportswear workflow; the account section is currently a placeholder.

6. **Saved carts and repeat-order pages**  
   Both account modules are currently placeholders.

7. **Wishlist / saved products**  
   No wishlist model, route, or page exists.

8. **Real product reviews and rating submission**  
   Product ratings are display data only.

### Content and support pages

9. About Us
10. Contact Us with a working form
11. FAQ / Help Center
12. Standalone Size Guide
13. Artwork and Logo Guidelines
14. Shipping and Delivery Policy
15. Returns, Refunds, and Exchanges
16. Privacy Policy
17. Terms and Conditions
18. Cookie Policy / consent settings
19. Accessibility Statement
20. Custom-order / team-order instructions

### Account and authentication completion

21. Email verification
22. Resend verification page
23. Account deletion and data-download request
24. Notification preferences
25. Support ticket list and details
26. Gift-card balance/history if gift cards are retained in navigation

## Back-office/admin pages still required

The current archive contains no complete administration area. A production e-commerce site still needs:

1. Admin login and role/permission management
2. Admin dashboard
3. Category and filter-tag CRUD
4. Product CRUD
5. Product images/gallery management
6. Product variants, sizes, colors, fabrics, and customizable-option management
7. Tiered pricing and MOQ management
8. Inventory/availability management
9. Order list and order details
10. Production and artwork-proof workflow
11. Quote request list, quote builder, approval, and conversion to order
12. Customer management
13. Payment transaction and webhook logs
14. Refund and cancellation management
15. Coupon, promotion, and gift-card management
16. Shipping-zone, carrier, and rate management
17. Tax settings
18. Review moderation
19. CMS/banner/FAQ/legal-page management
20. Reports, sales analytics, product analytics, and audit logs

## Important backend gaps behind existing designs

Several pages exist visually but are not yet backed by production database modules:

- Products are still defined in `ProductCatalogService` arrays; product, variant, price, inventory, and image tables are missing.
- Cart content is session-based rather than persisted for logged-in customers.
- Checkout state is session-based.
- Orders, order items, payments, shipments, refunds, coupons, and invoices do not have database models/migrations.
- Order tracking and order details use session/demo order data rather than durable order records.
- Payment-method UI stores masked metadata only; a real tokenized payment-provider integration is still required.
- No Stripe/PayPal webhook processing exists.
- No email notification system for account, order, proof, shipping, or password-reset events exists.

## Recommended implementation order

1. Products, variants, customization options, pricing, and inventory database layer
2. Admin product/category management
3. Persistent carts and coupons
4. Persistent orders/order items and secure order authorization
5. Payment provider and webhook flow
6. Quote/artwork/proof workflow
7. Customer order/quote/saved-design pages
8. Password reset and email verification
9. Shipping, tax, refund, and notification modules
10. Content/legal/support pages and analytics
