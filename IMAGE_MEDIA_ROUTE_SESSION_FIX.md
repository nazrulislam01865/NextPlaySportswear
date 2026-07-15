# Image Media Route Session Fix

## Root cause

The application-owned `/media/{path}` route was declared inside `routes/web.php`, so Laravel applied the full `web` middleware group. The route then removed `StartSession`, but the active CSRF middleware in this Laravel version is `PreventRequestForgery`, not the class that had been excluded.

As a result, the CSRF middleware attempted to access a session that had been removed and image requests failed with HTTP 500:

`Session store not set on request.`

This prevented uploaded images from loading in admin previews and storefront views even though the files existed in `storage/app/public`.

## Correction

- Removed the media route from the `web` middleware route file.
- Registered `/media/{path}` from `bootstrap/app.php` through the routing `then` callback.
- The media route now runs outside session, cookie and CSRF middleware.
- Global security headers remain active.
- Existing `PublicMediaController` path validation, image-only MIME restriction and immutable cache headers remain active.

## Verification

A real stored product image was requested through Laravel's HTTP kernel.

- Response status: `200`
- Response type: `Symfony\\Component\\HttpFoundation\\StreamedResponse`
- Content type: `image/jpeg`
- Stored file size: returned correctly
- No session or CSRF exception

No database migration is required.
