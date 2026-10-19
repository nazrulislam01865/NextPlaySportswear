# NextPlay Sportswear: Artwork Conversation, Admin Review UI and Stripe Return Verification

## Scope (19 October 2026)

Only the customer artwork conversation, admin order artwork-review section, and Stripe checkout-return verification have been changed. Existing order/cart/tier pricing/discount/fulfillment logic, routes, authorization policies, theme configuration and database schema are unchanged.

## Verified source-code causes

1. `ArtworkApprovalController::show()` loaded messages only when the `?message=1` query parameter was present. `storefront/account/artwork/show.blade.php` also guarded the entire messages section with the same parameter. Replies were saved but ordinarily invisible.
2. `PaymentReturnController` redirected back to the account without reconciling the authenticated customer's Stripe Checkout session. Actual payment processing still depended on the webhook job running on the `payments` queue, or the fifteen-minute scheduled reconciliation fallback. The displayed `Pending Payment`/`Processing` status could therefore lag an already-completed Stripe charge.

## Changes

- Customer proof review always displays the messages section. Existing replies are displayed chronologically, including those associated with older versions of the same order item. Customer messages and admin replies continue using existing secured routes, permissions and tables.
- Admin Order > Customer Artwork Approvals is divided into concise sections: latest version/download, highlighted customer feedback, designer notes, discussion/reply, collapsible previous versions, and compact upload-a-new-version form. Uses the existing centralized `btn`, `admin-input`, `admin-textarea` and color classes. No new JavaScript or API calls.
- For an authenticated customer's own Stripe session, the return handler requests confirmation directly from the configured provider via the existing `PaymentOrchestrator::reconcile()`. It runs only on returns with a matching Stripe session and pending/processing payment. This uses existing amount/currency checking and transactional/idempotent paid-state updates. Failed or incomplete provider verification never marks the order paid. Signed Stripe webhooks remain the primary asynchronous mechanism.

## Apply locally

1. Back up the project and the production database. Extract the changed-files ZIP into the Laravel project root, preserving folder structure.
2. Run:

```bash
php artisan optimize:clear
php artisan view:clear
npm run build
php artisan test --filter=ArtworkApprovalWorkflowTest
php artisan test --filter=StripePaymentArchitectureTest
```

No migration is needed for this patch.

## Verify manually

1. Open Admin > Orders > relevant order > Customer Artwork Approvals. The latest proof's **Customer Feedback** section should show the rejection reason.
2. Send a design-team reply; open the same proof as the correct customer without adding `?message=1`. The message must appear under **Messages with Design Team**. Submit a customer response and verify it appears for the admin.
3. Publish version 2. Messages from version 1 remain visible when the customer views version 2. Admin can expand **Previous proof versions** to review old messages.
4. Complete a **test-mode** Stripe checkout as the signed-in customer; on returning from Stripe, the same order should show **Paid** after verified success. When Stripe reports incomplete, it must stay pending/processing.
5. Confirm another user cannot access a customer's proof or reconcile another customer's Checkout session.

## Fix existing stuck payments safely (production operations)

The submitted archive contains application source, **not live Stripe transaction data**, so an individual order's actual payment cannot be confirmed from screenshots alone. Never change a payment to `paid` manually based only on the success page or customer claim.

Check Stripe Dashboard for the specific Checkout Session / PaymentIntent. On the server, ensure the correct `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_ENABLED`, and webhook URL `/webhooks/stripe` are configured (avoid printing secret values). Ensure a supervised queue worker processes the **payments** queue and the Laravel scheduler is active. Useful non-secret commands:

```bash
php artisan queue:failed
php artisan schedule:list
php artisan payments:reconcile --provider=stripe --limit=100
```

`payments:reconcile` only selects attempts older than `PAYMENT_RECONCILE_AFTER_MINUTES` (default 10). It performs a real provider check and is safe to rerun. Queued Stripe events require an active worker listening to the configured `PAYMENT_WEBHOOK_QUEUE` (default `payments`). Example foreground diagnostic command only (not an alternative to process supervision):

```bash
php artisan queue:work --queue=payments,default --tries=5 --stop-when-empty
```

If the actual payment method is manual/bank transfer, payment should remain pending until the authorized admin confirms it; Stripe verification does not apply. If the gateway reports paid but the app still does not, inspect failed jobs and application logs for a matching provider reference and check webhook configuration.

## Validation status

Updated PHP files passed `php -l`; archive integrity and focused static checks completed. Full Laravel/PHPUnit and Vite build could not run here because the uploaded archive does not include Composer `vendor/` or npm `node_modules/`. Run the two targeted tests and `npm run build` in the dependency-installed project before production deployment.
