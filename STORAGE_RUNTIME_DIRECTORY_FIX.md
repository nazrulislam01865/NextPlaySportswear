# Storage runtime directory fix

Laravel's file session driver writes session files into:

```text
storage/framework/sessions
```

Some ZIP utilities omit empty directories. On a fresh extraction this caused:

```text
file_put_contents(.../storage/framework/sessions/...): No such file or directory
```

The project now creates all required runtime directories during bootstrap:

- `storage/framework/cache/data`
- `storage/framework/sessions`
- `storage/framework/testing`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

Each directory also contains a `.gitignore`, so Git and ZIP archives retain it.

## Local XAMPP configuration

```env
APP_ENV=local
SESSION_DRIVER=file
SESSION_CONNECTION=
CACHE_STORE=file
```

After replacing the project, run:

```bash
php artisan optimize:clear
php artisan storage:link
php artisan serve
```

If XAMPP reports a permission error rather than a missing directory, run:

```bash
chmod -R ug+rwX storage bootstrap/cache
```
