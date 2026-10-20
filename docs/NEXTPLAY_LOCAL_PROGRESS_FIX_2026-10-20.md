# NextPlay <-> StepPromo local progress callback and preview fix

## What was corrected

1. FlowTracker's order/task changes already dispatch progress jobs. Jobs now use a per-order queue lock, reload the current job state, and send microsecond-precision delivery timestamps. This prevents same-second production changes from being discarded by the NextPlay database comparison.
2. NextPlay's progress timestamp column gains microsecond precision with a **new** migration; old second-precision callbacks remain accepted, and stale or conflicting job callbacks remain rejected. Payment/refund state is not changed by production callbacks.
3. NextPlay tracking prominently displays the latest FlowTracker production stage/status; its timeline uses the mapped production phase where appropriate without falsely marking pending payments paid. Registered order details show the operational stage prominently and keep the storefront status available.
4. The FlowTracker health response now includes `progress_callback_ready`; `php artisan flowtrack:health` warns explicitly when reverse status sync is not configured.
5. StepPromo's artwork preview no longer references the NextPlay private file URL directly. Copied files are served from private StepPromo storage, via an authenticated order-scoped endpoint with an image MIME whitelist.
6. A local macOS/Linux launcher starts both Laravel servers and both integration workers in one terminal and terminates only those processes on Ctrl+C.

## One-command local setup

The directories must be siblings:

- `/Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo`
- `/Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear`

Keep the local `.env`, Composer `vendor` and storage uploads from each existing project; neither application shares a database with the other.

Stop existing port-8000/8001 servers and integration workers before starting the combined launcher (the launcher refuses to interfere with an existing listener).

Run migrations in **NextPlay** before starting the two applications:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan migrate
php artisan optimize:clear
```

In StepPromo:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan optimize:clear
bash scripts/run-nextplay-local.sh --check
bash scripts/run-nextplay-local.sh
```

The launcher detects sibling project directories, uses NextPlay port 8000, StepPromo port 8001, database `integrations` queue workers and prints log locations. **Ctrl+C** stops them all. Use `--help` for overrides. It does not start MySQL; start XAMPP MySQL yourself.

### Required environment settings

StepPromo `.env`:

```ini
NEXTPLAY_ORDER_PROGRESS_ENABLED=true
NEXTPLAY_SOURCE_BASE_URL=http://127.0.0.1:8000
NEXTPLAY_PROGRESS_QUEUE_CONNECTION=database
NEXTPLAY_PROGRESS_QUEUE_NAME=integrations
NEXTPLAY_INTEGRATION_TOKEN=<same private secret used by NextPlay>
```

NextPlay `.env`:

```ini
FLOWTRACK_INTEGRATION_ENABLED=true
FLOWTRACK_BASE_URL=http://127.0.0.1:8001
FLOWTRACK_QUEUE_ENABLED=true
FLOWTRACK_QUEUE_CONNECTION=database
FLOWTRACK_QUEUE_NAME=integrations
FLOWTRACK_INTEGRATION_TOKEN=<same private secret used by StepPromo>
```

Set `QUEUE_CONNECTION=database` in both projects or retain an equivalent connection configured for the dedicated workers. Keep `retry_after` greater than worker timeout (70 seconds). Use HTTPS for production. Never commit secrets.

## Verify job 333 / NP-261020-W1D4QQ

Open another terminal while the combined launcher is running:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan flowtrack:health
```

`Reverse progress callbacks: ready` must appear. If NOT READY, inspect StepPromo `.env` settings above, clear config and restart workers.

After advancing a real StepPromo workflow task:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/StepPromo
php artisan nextplay:push-order-progress --order=NP-261020-W1D4QQ
```

That command enqueues one latest-state callback. It does not create or edit orders. Inspect StepPromo's `storage/logs/nextplay-local/steppromo-queue.log` and `storage/logs/laravel.log` for `nextplay.progress_callback_delivered` or `nextplay.progress_callback_failed`. In NextPlay, refresh the customer's order detail and verified public tracking pages. The production stage should display the current StepPromo stage. Payment status should remain unchanged.

If a callback fails, check both projects' `php artisan queue:failed` output, integration workers, and HTTP authentication. Retries are supported, but never promise unconditional 1-2-second delivery under network failures.

## Validation

The PHP, shell, migration and source checks can be run without DB access. Run these integration tests on local databases with Composer dependencies installed:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/laravel/NextPlaySportswear
php artisan test --filter=FlowTrackOrderProgressTest
```

Also test the protected StepPromo artwork preview as an authorized order viewer and as an unrelated user (must not expose files), and record actual callback latency by comparing source task-change and NextPlay callback reception timestamps.
