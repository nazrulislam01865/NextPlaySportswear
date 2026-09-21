# Homepage staged image upload fix

Homepage-controlled images now use a staged chunk uploader before the main admin form is submitted.

## Why

A multipart upload can fail inside PHP before Laravel validation runs (`UPLOAD_ERR_*`), which produces the generic “The image file failed to upload.” message even when the image is valid. Homepage uploads now avoid that PHP multipart temporary-file path.

## Flow

1. Admin selects a homepage image.
2. `resources/js/admin/homepage-staged-upload.js` sends the image in 512 KB raw PUT chunks.
3. `HomepageStagedUploadController` stores those chunks in private Laravel storage.
4. `HomepageStagedUploadService` assembles the chunks, verifies the real MIME type, size, dimensions, and creates a short-lived user-bound token.
5. The normal homepage form submits only that token.
6. The existing Form Request validates the staged image against the trusted homepage aspect-ratio definition.
7. The media service moves the validated staged image to the existing public homepage media directory.

Product image uploads are unchanged.

## Limits and security

- JPG, PNG, WebP, AVIF
- Maximum 10 MB
- 512 KB browser chunks (server accepts up to 768 KB per chunk)
- Staged uploads are bound to the authenticated admin user
- Staged uploads expire after 2 hours
- Chunk routes require an admin who can manage homepage sections or homepage slides
- Upload chunk requests are excluded from admin activity notifications to avoid log/notification spam

## Deployment

Rebuild the frontend assets after deploying:

```bash
npm install
npm run build
php artisan optimize:clear
```

`php artisan storage:link` is still required for normal public media serving if it has not already been created.
