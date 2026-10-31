# NextPlay: unified email verification and homepage FAQ alignment (2026-10-31)

## Root causes

1. Normal customer signup already uses a 6-digit code before account creation, but **referral signup did not**: the referral route skipped the code, created an unverified account, and entered the older signed-link `/email/verify` flow. The account was not persisted as verified, even though the public registration experience suggested the code-first process.
2. The older signed verification URL required an existing `auth:web` customer session on the **same browser**. Opening that email in another browser/device could not complete verification and would return to sign-in/verification, leading to repeat attempts.
3. The shared safe-redirect helper did not exclude `/email/verify` and `/email/verified` from captured post-login destinations. A stale or referral-origin destination could therefore send an already verified user right back into the old verification screen.
4. On the homepage, Common Questions and Quick Help were two independently sized columns. The optional heading description changed their content start positions. Clearing the Quick Help subtitle therefore moved its cards upward while the accordion retained its previous header height.

## Changes

- The existing referral signup experience is preserved, but its steps now use the existing pre-registration `register.email.send` and `register.email.verify` endpoints. Referral customers verify their email **before** creating the account, just like normal signup. At the final step, the original referral registration form and offer details remain.
- Both signup paths persist `users.email_verified_at` at registration and log the new customer in. They send the same `Verified` event used by the welcome-email listener; they **do not send a second verification link**. Referral associations, reward eligibility, and safe storefront redirects are retained.
- The post-authentication redirect sanitizer now explicitly rejects legacy verification notice/success URLs as continuation destinations, so a stale intended URL cannot recreate the redirect loop.
- The legacy signed verification route remains available for **already-created unverified accounts and email changes**. Its signature, expiry, active-customer record, and email hash are validated before marking the persisted account verified. The URL works without sharing a browser session; it does **not** authenticate the visitor. Opening in a guest browser redirects to sign-in with a verified-success status. Opening it while another account is signed in leaves that session intact.
- Homepage FAQ and Quick Help use a shared intrinsic CSS grid row (subgrid on supported desktop browsers) so their accordions/cards start on the same line whether either subtitle is present. A conservative height fallback covers older browsers. The original cards, icons, colors, admin controls and mobile stacking are unchanged.

## Changed files

- `app/Http/Controllers/Storefront/Auth/RegisteredUserController.php`
- `app/Http/Controllers/Storefront/Auth/EmailVerificationController.php`
- `routes/web.php`
- `app/Support/StorefrontRedirect.php`
- `resources/views/storefront/referral/register.blade.php`
- `resources/views/components/layouts/storefront.blade.php` (only stylesheet version)
- `resources/css/storefront-typography.css` and generated `public/css/storefront-typography.css`
- `tests/Feature/CustomerEmailVerificationTest.php` (new integration assertions)
- `tests/Regression/EmailVerificationHomeAlignmentTest.php` (standalone guard)

No migrations, new tables, login permission bypass, product card/filter edits or other feature changes.

## Validation performed

- PHP syntax checks on changed PHP files: passed.
- `php tests/Regression/EmailVerificationHomeAlignmentTest.php`: 15 contracts passed.
- `php tests/Regression/HomeQuickHelpAdminAndLayoutTest.php`: passed.
- `php tests/Support/storefront_design_system_static_check.php all`: passed.
- `node --test tests/frontend/*.test.mjs`: 41/41 passed.
- Generated CSS parsed with `tinycss2` without syntax errors.
- Original protected files compared byte-for-byte; unchanged.
- **The new Laravel feature/integration tests could not be executed here** because Composer `vendor/` dependencies are not included in the uploaded ZIP. Run them on the development machine or server before rollout.

## Deployment (NextPlay only)

Deploy the changed files preserving directory structure. The generated CSS file is included; no Vite build or database migration is needed.

```bash
cd /var/www/nextplay
php artisan optimize:clear
php artisan test --filter=CustomerEmailVerificationTest
php tests/Regression/EmailVerificationHomeAlignmentTest.php
```

If the application is running with long-lived PHP processes/opcache, reload the NextPlay PHP-FPM process via your normal deployment process. Make sure `APP_URL` uses the **actual public HTTPS host**, since Laravel signs absolute verification links, and ensure the deployed code/route cache is refreshed.

## Manual acceptance checks

1. Normal signup: request code, verify code, create account -> My Account directly, never `/email/verify`.
2. Referral signup: request code, verify code, complete the original referral form -> store customer as verified, land on the intended storefront page, referral remains associated.
3. Existing unverified account: request a new signed link, open it in a **different browser** -> verified success then sign-in. Sign in and reach My Account without another verification screen.
4. Expired/tampered link must fail; a link for another email must not mark the account verified; repeat use does not generate extra welcome messages.
5. Remove Quick Help subtitle in admin, save, reload homepage desktop: top edges of Quick Help cards and Common Questions accordion remain aligned. Restore subtitle; alignment should stay unchanged. Mobile should stack naturally.
