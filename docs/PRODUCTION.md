# Production Operations

This document is the production checklist for POS Faro. It focuses on the parts that must be true before the system is used at a real store counter.

## Required Services

- PHP 8.3+ with required Laravel extensions.
- MySQL 8.x or compatible managed database.
- Redis for production queues and cache. The database queue is acceptable only for small pilots.
- A process supervisor for `php artisan queue:work`.
- A scheduler entry that runs `php artisan schedule:run` every minute.
- HTTPS termination in front of the Laravel app.

## Required Environment

Set these values explicitly in production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.example.com
APP_TIMEZONE=America/Mexico_City

DB_CONNECTION=mysql
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

SWIFT_AUTH_TABLE_PREFIX=swift_auth_
API_TOKEN_TTL_MINUTES=480
CUSTOMER_REGISTRATION_TOKEN_TTL_DAYS=30
LOG_SENSITIVE=false
```

## Deployment Steps

1. Install dependencies with `composer install --no-dev --optimize-autoloader` and `npm ci`.
2. Build assets with `npm run build`.
3. Run `php artisan migrate --force`.
4. Run `php artisan optimize`.
5. Start or restart queue workers.
6. Verify `/up`, `/login`, and one authenticated `/api/v1/*` request.

## Operational Checks

- Queue workers must be running before opening sales.
- Failed jobs must be monitored and retried intentionally.
- `storage/` and `bootstrap/cache/` must be writable by the PHP user.
- Database backups must include the POS tables and SwiftAuth tables.
- Receipt delivery providers should be tested after every credential rotation.

## Release Gate

Do not deploy if any of these fail:

```bash
composer test
composer lint
composer audit
npm run audit
npm run build
npm run test:e2e
```

The GitHub Actions workflow at `.github/workflows/ci.yml` runs these checks with SQLite, PHP coverage, Node dependency audit, and Playwright Chromium.

## Security Notes

- API authentication uses SwiftAuth bearer tokens issued by `/api/v1/auth/login`.
- Checkout requires `X-Idempotency-Key` to prevent duplicate sales.
- Customer registration links use hashed, expiring, single-use sale tokens.
- Audit logs are stored in the SwiftAuth audit table using the configured table prefix.
