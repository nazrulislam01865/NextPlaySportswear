# Session/Redis local-development fix

The previous performance package could fail locally with:

```text
Database connection [sessions] not configured.
```

This happened when a local `.env` used `SESSION_DRIVER=database` while also
containing `SESSION_CONNECTION=sessions`. The `sessions` connection belongs to
Redis; it is not a SQL database connection.

## Recommended local XAMPP configuration

Use file-backed sessions and cache locally, which requires no Redis service:

```env
APP_ENV=local
SESSION_DRIVER=file
SESSION_CONNECTION=
CACHE_STORE=file
```

Then run:

```bash
php artisan optimize:clear
php artisan storage:link
php artisan serve
```

## Optional local database sessions

If database-backed sessions are preferred:

```env
SESSION_DRIVER=database
SESSION_CONNECTION=
CACHE_STORE=database
```

Make sure the `sessions` and `cache` tables exist, then run migrations:

```bash
php artisan migrate
php artisan optimize:clear
```

## Production Redis configuration

Production should continue using the dedicated Redis connections:

```env
APP_ENV=production
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=sessions
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
```

The updated `config/session.php` now also detects the invalid local
`database + sessions` combination and safely uses the default SQL connection.
