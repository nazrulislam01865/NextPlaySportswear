# NextPlay Sportswear — Complete Email Screen Audit

**Input sources:** `Screens.zip` (34 PNG screenshots) and `Archive 3(4).zip` (latest project code supplied for this audit).  
**Scope:** customer, security, order, shipment, quotation, return/exchange, admin and forwarded email notifications.  
**Method:** compare all reference filenames with the actual Laravel mailable `match` mapping, Blade views, icons, shared branding/header, help card, CTA/footer components and file-handling data flows.

## Executive result

- **34 / 34** screenshot references have a corresponding mapped notification view / event variant.
- **30 distinct Blade views** implement those references. Screens 02 and 24 share the order-request update view; screens 03 and 23 share the request-received view; screens 32, 33 and 34 are distinct data-driven variants of one forwarded view.
- **34 / 34** designated icon assets exist. All mapped layouts use the centralized live website-logo resolution (the reset-password template was corrected during this audit).
- The audit found and corrected the gaps listed below. Static checks and PHP lint pass; **live Laravel / email-client rendering has not been run** because the uploaded archive does not contain `vendor/autoload.php`.

## Corrections applied

1. **Screen 10 — Reset password:** replaced the older hardcoded wordmark and compact HTML with the reference-style shared transactional layout, dynamic site logo, real user email/expiration, existing password-reset URL, security notice, CTA and footer. The original email sending/password security logic is unchanged.
2. **Screen 01 — Artwork proof ready:** include a real per-proof file card pointing to the already-existing authorized `account.artwork.file` download route, when the proof has an associated file. No new database query is required.
3. **Screen 06 — Designer reply:** include the discussed proof's existing file link when there is one; never add a sample attachment.
4. **Screens 02/03/23/24 — Order change / cancellation:** render the same reference-style optional file section if actual attachment metadata is provided; suppress the entire section when no file exists. The current order-change model has no upload field; no new storage or schema feature was invented in this email-only change.
5. **All file-card emails:** only label a URL **Download** when an actual file-specific URL exists. If a card only has a general record-page link, its action says **View file**; physically attached files without a direct URL say **Attached**. This prevents a misleading download link.
6. **Regression protection:** added `NextPlayAll34EmailScreensAuditTest` verifying 34 event-to-view variants, logo, icon, heading, CTA/footer, internal greeting behavior, reset content and genuine conditional attachment cards. Updated the preexisting password-reset assertion to its new reference wording.

## Reference-by-reference inventory

| Screen | Reference filename | Notification key | Blade view | Structural audit |
|---:|---|---|---|---|
| 01 | `01_artwork_proof_ready.png` | `artwork.customer-proof-ready` | `emails.customer.artwork-proof-ready` | Covered |
| 02 | `02_order_request_updated.png` | `order-request.customer-status-updated` | `emails.customer.order-request-updated` | Covered |
| 03 | `03_order_request_received.png` | `order-request.customer-submitted` | `emails.customer.order-request-received` | Covered |
| 04 | `04_contact_message_received.png` | `contact.customer-confirmation` | `emails.customer.message-received` | Covered |
| 05 | `05_delivery_estimate_updated.png` | `shipment.delivery-estimate-updated` | `emails.customer.delivery-estimate-updated` | Covered |
| 06 | `06_designer_replied.png` | `artwork.customer-designer-reply` | `emails.customer.designer-replied` | Covered |
| 07 | `07_account_email_changed.png` | `customer.email-changed-old-address` | `emails.customer.email-changed` | Covered |
| 08 | `08_order_received.png` | `order.customer-placed` | `emails.customer.order-received` | Covered |
| 09 | `09_password_changed_reference.png` | `customer.password-changed` | `emails.customer-password-changed` | Covered |
| 10 | `10_password_reset.png` | `customer.password-reset` | `emails.customer-password-reset` | Covered |
| 11 | `11_email_verification.png` | `customer.email-verification` | `emails.customer.email-verification` | Covered |
| 12 | `12_welcome.png` | `customer.welcome` | `emails.customer.welcome` | Covered |
| 13 | `13_order_status_changed.png` | `order.customer-status-updated` | `emails.customer.order-status-changed` | Covered |
| 14 | `14_shipment_preparing.png` | `shipment.customer-status-updated` | `emails.customer.shipment-preparing` | Covered |
| 15 | `15_shipment_status_changed.png` | `shipment.customer-status-updated` | `emails.customer.shipment-status-changed` | Covered |
| 16 | `16_bulk_quote_received.png` | `quote.customer-confirmation` | `emails.customer.bulk-quote-received` | Covered |
| 17 | `17_bulk_quote_updated.png` | `quote.customer-update` | `emails.customer.bulk-quote-updated` | Covered |
| 18 | `18_bulk_quote_converted.png` | `quote.customer-converted-order` | `emails.customer.bulk-quote-converted` | Covered |
| 19 | `19_quote_guest_feedback.png` | `quote.customer-update` | `emails.customer.quote-guest-feedback` | Covered |
| 20 | `20_return_request_received.png` | `return.customer-submitted` | `emails.customer.return-request-received` | Covered |
| 21 | `21_exchange_request_received.png` | `return.customer-submitted` | `emails.customer.exchange-request-received` | Covered |
| 22 | `22_return_exchange_updated.png` | `return.customer-status-updated` | `emails.customer.return-exchange-updated` | Covered |
| 23 | `23_order_cancellation_received.png` | `order-request.customer-submitted` | `emails.customer.order-request-received` | Covered |
| 24 | `24_order_cancellation_updated.png` | `order-request.customer-status-updated` | `emails.customer.order-request-updated` | Covered |
| 25 | `25_new_support_message_admin.png` | `contact.internal-alert` | `emails.admin.support-message` | Covered |
| 26 | `26_new_bulk_quote_admin.png` | `quote.internal-alert` | `emails.admin.bulk-quote-request` | Covered |
| 27 | `27_new_order_admin.png` | `order.internal-placed` | `emails.admin.storefront-order` | Covered |
| 28 | `28_artwork_reviewed_admin.png` | `artwork.internal-proof-reviewed` | `emails.admin.artwork-reviewed` | Covered |
| 29 | `29_artwork_message_admin.png` | `artwork.internal-customer-message` | `emails.admin.artwork-message` | Covered |
| 30 | `30_return_request_admin.png` | `return.internal-submitted` | `emails.admin.return-request` | Covered |
| 31 | `31_order_request_admin.png` | `order-request.internal-submitted` | `emails.admin.order-request` | Covered |
| 32 | `32_forwarded_order_admin.png` | `admin.confirmation-auto-forward` | `emails.forwarded-request` | Covered |
| 33 | `33_forwarded_quote_admin.png` | `admin.confirmation-auto-forward` | `emails.forwarded-request` | Covered |
| 34 | `34_forwarded_contact_admin.png` | `admin.confirmation-forward` | `emails.forwarded-request` | Covered |

## Screenshots containing sample files

File names, sizes, prices, customer details and document previews in the PNGs are illustrative. Production emails must use **real records and real stored files**, not those samples.

- Order-change/cancellation screenshot file examples (especially 02, 03 and 31) cannot be populated automatically today: `OrderChangeRequest` has no attachment field/relation or request upload in this project. The templates now support a real file-card payload, but a full upload feature would require separately authorized product/schema changes.
- Artwork previews (28 and 29) are embedded only when the actual supplied file is a compatible image of suitable size. PDF artwork appears as a real downloadable file rather than a fabricated preview.
- Forwarded confirmations (32–34) retain the original physical attachments and detailed request information. Their email card correctly says **Attached** when the recipient obtains the file from the message itself rather than a separate download URL.
- Security-protected file links require the recipient to sign in or satisfy the existing signed-route authorization. No unprotected public file endpoint was added.

## Runtime validation and deployment

Static audit: **34 screenshot references, 34 mappings, 30 unique Blade views, no missing icon/view paths.** `php -l` passed for the modified PHP service and regression tests. The project archive does **not** include `vendor`, so Blade compilation, PHPUnit, live sending, email-client screenshots and pixel-exact comparisons are not verified in this environment.

Run from the installed NextPlay Laravel project after applying the files:

```bash
php artisan optimize:clear
php artisan test --filter=NextPlayAll34EmailScreensAuditTest
php artisan test --filter=CustomerPasswordResetTest
php artisan test --filter=NextPlayCustomerEmailDesignsRound4Test
php artisan test --filter=NextPlayCustomerEmailDesignsRound3Test
php artisan test --filter=NextPlayCustomerEmailDesignsTest
php artisan test --filter=NextPlayAdminEmailDesignsRound6Test
php artisan test --filter=NextPlayAdminEmailDesignsTest
php artisan test --filter=ForwardedEmailLayoutTest
php artisan test --filter=AutomaticConfirmationForwardingTest
php artisan view:cache
```

Send test messages to Gmail/Outlook/mobile before deployment to confirm logo delivery, asset reachability, and wrapping in target email clients. `APP_URL` and the storefront logo storage URL must point to a publicly fetchable image so recipients' email clients can display the logo.

**No new migrations, API routes, tables or unrelated application features were introduced.**
