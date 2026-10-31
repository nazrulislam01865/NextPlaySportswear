# NextPlay — My Rewards, Refer a Friend and Artwork Approvals typography correction

Date: 31 October 2026. Based on supplied `Archive 6(7).zip` and `NextPlay_Typography_Design_System (1) (1)(4).html`.

## Problem identified

These screens retained older component-specific prototype CSS while the prior account-wide typography migration forced full-sized H2 (32px) and H3 (24px) headings onto small card subsections. In Artwork Approvals, its proof-list product description inherited a 24px H3; the card action buttons inherited 16px and 48px full-CTA treatment even though two controls share a narrow column. In Rewards/Refer a Friend, the same generic heading rule enlarged subsection headings, lengthy How It Works step headings and the referral reward banner. The old rewards styles also kept a 24px-tall progress bar, an oversized referral illustration, and inefficient panel spacing.

## Solution

Append **scoped rules** to `resources/css/storefront-typography.css` (the centralized stylesheet already used by all account pages). Regenerate `public/css/storefront-typography.css` using `python3 deploy/build-storefront-typography.py`, and change only its cache-version query in the shared storefront layout.

- Rewards/referral inner H2s: centralized H4 (20px/700). Step and FAQ H3s: H5 (18px/700).
- Descriptions inside these cards: approved 14px small text with 1.55 line height; the account page's normal body copy and hero remain on their original centralized roles.
- Progress metrics: centralized H3 (24px), with a thinner progress bar and consistent supporting text.
- Less intrusive referral banner icon, more balanced two-column referrals layout, consistent panel padding and reasonable spacing between steps.
- Artwork Approvals content-only H1 is visually H2-sized (32px, 26px on mobile) to match other in-column account titles. Semantics remain H1.
- Artwork list-card merchandise title: centralized H5 (18px/700), not global H3 (24px). Details column gets more space and can break long product names and file names.
- Artwork list-card actions and secondary share controls: compact **14px / 44px** touch targets; page-level Shop Now, Refer a Friend and other primary form CTAs retain the centralized **16px / 48px** rule.
- Maintain responsive stacking for artwork cards and reward/referral columns; no behavior or form field changes.

## Preserved

All original Blade templates, product cards, catalog filters, account sidebar, business/backend code, routes, database, authentication, payment, and JavaScript are unchanged. Original semantic status badge colors and navy sidebar selection are unchanged. The uploaded preview showed a missing logo asset, but this change intentionally does not alter branding or image loading.

## Validation

```bash
python3 deploy/build-storefront-typography.py
php tests/Support/storefront_design_system_static_check.php all
php tests/Support/nextplay_account_template_static_check.php
php tests/Support/nextplay_account_compact_three_pages_check.php
php tests/Regression/AccountSharedFrameAndAdminCompactButtonsTest.php
php tests/Regression/AccountDashboardOrderHistoryPrototypeTest.php
```

CSS was parsed with `tinycss2` without top-level errors. **Authenticated live-browser validation remains outstanding**, since the uploaded project snapshot lacks the running application/services. An isolated Chromium smoke fixture was attempted but timed out and therefore is not treated as a passing check.

## Deployment

Replace updated files or deploy the full archive. No migration or Vite rebuild required; the generated public CSS ships in the patch. Run `php artisan view:clear` then hard-refresh the browser. Preserve the Expose font assets already installed on the server.
