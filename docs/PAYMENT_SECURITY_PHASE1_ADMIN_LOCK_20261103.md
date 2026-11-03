# NextPlay Payment Security — Phase 1: Admin Financial Write Lock

Date: 3 November 2026  
Scope: **Phase 1 only** of the approved Stripe-controlled payment plan.  
Deploy only after reviewing the changes and completing the production validation checklist.

## Approved behavior

- NextPlay admins and super admins may **read** payment attempts and existing refund records.
- Payment status, paid timestamps, order prices/totals, provider references, refund status/amount, and previously approved financial amounts are **not editable** from order or return administration.
- Admins can still update **non-financial** order notes/statuses, operational fulfillment, shipments, and return-review statuses, subject to existing authorization and transition rules.
- Admin payment-method configuration is read-only. All create/edit/update/delete HTTP endpoints return **403**, even for a super admin. Payment methods already configured remain available to checkout; customer saved-payment-method controls remain intact.
- Refund initiation from NextPlay is disabled **at the payment orchestrator boundary**, including direct service calls. Authorized finance operators must initiate refunds in Stripe Dashboard. Existing signed-webhook processing remains unchanged.
- The customer checkout design, existing Stripe payment initiation, pricing engine, email templates, public Payment Information CMS editor, and payment/refund historical rows are unchanged.

## Security boundaries changed

| Layer | Phase 1 change |
| --- | --- |
| Admin order request | `missing` validation for financial fields; rejects crafted requests |
| Admin order workflow | Reusable financial-field guard; financial fields excluded from persistence; no `provider=admin` paid receipt creation |
| Order approval | Moves workflow to Design Review without creating a `paid_at` timestamp |
| Admin order UI | Payment status shown as read-only; cannot select provider-owned payment lifecycle states |
| Admin return request | Rejects approved amount, refund status, and provider reference changes |
| Admin return workflow | Retains return logistics/reviews but never initiates refunds or edits payment/refund records |
| Admin return UI | Refund and approved amount information is read-only |
| Payment-method administration | All mutations disabled in controller and request authorization; existing read-only listing retained |
| RBAC | Obsolete `payment_methods.manage` permission removed from code catalog; old DB role records do not grant access to locked endpoints |
| Payment orchestrator | `issueReturnRefund()` rejects all application-initiated refunds; both Stripe and manual gateway adapters reject direct refund calls; webhook handlers remain available |

## Critical limitations and deployment cautions

1. **Phase 1 does not implement Phase 2–6 functionality.** Failure handling, automatic reconciliation of externally created Stripe Dashboard refunds, partial payments, installment schedules, and enhanced provider-event sequencing remain future work. In particular, Stripe Dashboard-created refunds might **not automatically appear in NextPlay** under the current webhook matching rules. Until Phase 4 is implemented, finance must verify refunds directly in Stripe and maintain an external reconciliation record. Do not treat the site's refund summary as authoritative for Dashboard refunds during this interim period.
2. Existing historic `provider=admin` payments and manually set payment states are **preserved** and have **not been verified**. Finance should audit these against Stripe/other original evidence before treating them as confirmed. Do not delete historical rows without an approved reconciliation procedure.
3. An application admin lock cannot stop an operator with production database access or unrestricted Stripe credentials. Restrict server roles and Stripe Dashboard/API keys separately, and enforce MFA for Stripe finance owners.
4. The admin Payment Information **content page** remains editable. Those rows are explanatory marketing/content data only and **cannot drive financial transactions**.
5. No schema migration is required. Changing checkout payment methods after this change must be performed through a separately authorized deployment/configuration workflow, not via NextPlay's admin UI.

## Recommended pre-production test commands

```bash
php artisan optimize:clear
php artisan test --filter=PaymentReadOnlyPhase1Test
php artisan test --filter=OrderManagementTest
php artisan test --filter=StripePaymentArchitectureTest
php artisan test --filter=PaymentInformationPageTest
```

These tests **must** be run with project Composer dependencies installed and the correct local test database configured. Inspect errors before any deployment.

## Production release gate

- [ ] Take a verified database backup and preserve existing financial records.
- [ ] Audit existing admin-origin paid receipts, and review current pending refunds.
- [ ] Confirm `admin` and `super_admin` cannot submit financial field overrides, issue refunds, edit payment methods, or access old edit URLs.
- [ ] Confirm legitimate order notes, shipment, return review, and customer payment initiation still work.
- [ ] Confirm Stripe webhook endpoints, queue workers, and existing paid-payment transitions still function in sandbox.
- [ ] Establish an interim manual **Stripe Dashboard refund reconciliation** procedure until Phase 4.
- [ ] Verify Stripe finance account ownership, MFA, and restricted API credentials outside the CMS.
- [ ] Retain a compatible prior release for code rollback. Never delete financial events recorded during a deployment.

## Backout policy

Restore the prior code release if Phase 1 causes unexpected operational issues, and restore permissions/configuration only through a reviewed deployment. Since no database schema changes or historical financial data modifications are made, there is no Phase 1 data migration to reverse. Reconcile Stripe transactions recorded during any outage before resuming.
