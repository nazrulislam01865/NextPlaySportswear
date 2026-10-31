# NextPlay customer authentication: email validation root-cause fix

## Root cause

`resources/js/customer-auth-inline-validation.js` bound an email validation handler to every `blur` event. The login, signup email, and forgot-password forms autofocus their email input. Clicking a different control causes the focused email input to blur, which showed `Enter a valid email address.` even without an attempted submit. Browsers can also restore/autofill an invalid value before the user interacts.

This was a shared validation-lifecycle issue, not a Laravel controller/database problem.

## Permanent behavior

- Merely loading, focusing, leaving, clicking outside, or moving to another form field never shows a new client-side email error.
- Validation happens when the user **submits** a login, signup email verification, or password recovery form.
- Invalid submissions remain on the page with an inline message and focus on the first invalid field.
- After one attempted submission, typing corrects/clears the error immediately. Existing server-returned errors remain visible until the user edits the corresponding input.
- Server-side Laravel validation still runs for submitted data and remains authoritative.
- The registration wizard, authentication routes, password security, and double-submission protection are unchanged.

## Files changed

- `resources/js/customer-auth-inline-validation.js`: remove blur-based validation and use submit-then-input lifecycle.
- `tests/frontend/customer-auth-inline-validation.test.mjs`: regression coverage for untouched autofocused emails, restored invalid values, both login fields, signup/recovery, server errors, repeated initialization, and valid submission.
- `public/build/manifest.json`: point at the updated, cache-busted production storefront bundle.
- `public/build/assets/storefront-authfix-1c92e05bad.js`: prebuilt storefront asset with the same corrected validation logic (no dependency installation required to use the zip). The former bundle is removed from the full-project archive.

No Blade templates, CSS files, other JS behavior, routes, controllers, database files, product cards, or filters were modified.

## Validation performed

- `node --test tests/frontend/*.test.mjs` — **41 tests passed**.
- `php tests/Support/storefront_design_system_static_check.php all` — **PASS**.
- JS syntax checks on source and production asset — **PASS**.
- A headless Chromium interaction test was attempted, but did not complete in this environment; live/browser integration is not claimed.
- `npm ci` could not complete here due unavailable dependency packages. The updated production bundle and manifest are included, so deployments using committed `public/build` assets work without a local Vite build.

## Deployment

1. Copy the entire corrected project, or overlay the changed-files package onto your existing matching Archive 7 codebase. Keep the updated `public/build/manifest.json` and new asset together.
2. If you maintain a different deployment build process, run `npm ci && npm run build` with network access to regenerate production assets from the corrected source rather than using the provided prebuilt asset.
3. Clear Laravel view cache:

   ```bash
   php artisan view:clear
   ```

4. Refresh the browser normally. The new manifest asset path will invalidate the prior JavaScript cache automatically.

No migration and no database change are required.
