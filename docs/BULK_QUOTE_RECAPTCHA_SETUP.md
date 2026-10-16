# Bulk Quote form: Google reCAPTCHA v2 setup

This integration protects **only** the public `/bulk-quote` POST form. It uses Google's **reCAPTCHA v2 checkbox**, verified server-side before any quote data is saved, file uploaded, email sent, or integration dispatched. Existing form throttling and the honeypot remain in place.

1. Register your website in Google's reCAPTCHA admin console: https://www.google.com/recaptcha/admin/create. Select **reCAPTCHA v2** and **"I'm not a robot" Checkbox**. Add your real site domain(s), e.g., `nextplay.com` and `www.nextplay.com` if both are used. For local testing, separately register `localhost` as appropriate. Do not use v3 or Enterprise keys with this widget.
2. Add the generated keys to your Laravel `.env` file on every environment that accepts quote requests:

   ```dotenv
   RECAPTCHA_SITE_KEY=your_public_site_key
   RECAPTCHA_SECRET_KEY=your_private_secret_key
   ```

   Keep the secret key server-side. Never place it in the Blade template, a JavaScript file, or source control.
3. Run `php artisan config:clear` (or rebuild the application config cache with `php artisan config:cache` if the deployment uses cached config). No database migration, Composer package, or frontend build is needed. The existing production Content Security Policy is extended only on the Bulk Quote page to permit the required Google reCAPTCHA scripts and iframe.
4. Visit `/bulk-quote`. Confirm that the checkbox appears above **Request Bulk Quote**. Test both unchecked and successfully checked submission. If either key is missing, the form intentionally disables submission (fail closed); add the keys before making this release live.

Google requires each token to be verified within **two minutes** and permits **one use**. If validation fails for any reason, the visitor must solve a new challenge on resubmission. The application rejects missing, incorrect, expired, duplicate, cross-domain, or unverifiable tokens; if Google's verification endpoint is temporarily unavailable, the request is not saved.

Google's verification API: https://developers.google.com/recaptcha/docs/verify
