# Storefront login rate and account storage fix

- Changed the customer login route from a shared numeric 5/minute throttle to a dedicated limiter.
- The limiter allows normal retries while retaining per-IP and per-account brute-force protection.
- Added single-submit protection to login and registration forms to stop accidental duplicate POST requests.
- Customer emails are normalized before authentication.
- Successful login updates `users.last_login_at`; limiter windows expire automatically.
- Customer registration continues to persist the account in the `users` table.
- The `User` model `password => hashed` cast hashes the password before it is written to the database.
- Storefront login uses Laravel's `web` guard and the Eloquent `users` provider, so it authenticates against the same stored signup record.
- Changed the account dropdown Sign in button to `#061F44`.
