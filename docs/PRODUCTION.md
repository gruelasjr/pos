# Production Operations

This is the release gate for a live POS Faro store. The detailed incident, backup, restore, rollback, and reconciliation procedures are in [OPERATIONS_RUNBOOK.md](./OPERATIONS_RUNBOOK.md).

## Required runtime

- PHP 8.3 or 8.4, MySQL 8.x, Redis 7, HTTPS, and a deployment secret store.
- Separate HTTP, `queue:work`, and `schedule:work` processes. Restart workers after every release.
- Caronte as the only identity authority and Bee-Hive with `CaronteTenantResolver`; do not set a static tenant in production.
- Real, credentialed drivers for every enabled payment, fiscal, ERP, printer, cash-drawer, and scanner flow. `mock` and `stub` are local/test only.

Minimum production configuration:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.example.com
DB_CONNECTION=mysql
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

CARONTE_URL=https://caronte.example.com
CARONTE_OIDC_ISSUER=https://caronte.example.com
CARONTE_OIDC_CLIENT_ID=pos
CARONTE_OIDC_CLIENT_SECRET=<secret-store-reference>
CARONTE_OIDC_REDIRECT_URI=https://pos.example.com/auth/oidc/callback
CARONTE_ALLOW_HTTP_REQUESTS=false
CARONTE_TLS_VERIFY=true
BEE_HIVE_TENANT_KEY=tenant_id
BEE_HIVE_STATIC_TENANT_ID=

POS_PAYMENT_DRIVER=<production-driver>
POS_FISCAL_DRIVER=<production-driver>
POS_RECEIPT_PRINTER_DRIVER=<production-driver>
POS_CASH_DRAWER_DRIVER=<production-driver>
POS_BARCODE_SCANNER_DRIVER=<production-driver>
LOG_SENSITIVE=false
```

## Deployment and verification

1. Build an immutable artifact with `composer install --no-dev --classmap-authoritative` and `npm ci && npm run build`.
2. Run `php artisan migrate --force`, then `php artisan optimize` in the release environment.
3. Restart queue workers and ensure the scheduler is active.
4. Verify `/up` (liveness) and `/ready` (database, Redis when required, secure Caronte configuration, and safe provider selection).
5. Perform a Caronte-authenticated tenant-isolation smoke test and a sandbox payment before enabling store traffic.

`/ready` returns HTTP 503 when a required dependency or production configuration is unsafe. It must not expose credentials, tokens, exception messages, or tenant data.

## Release gate

```bash
composer audit --locked --no-interaction
composer test
composer lint
npm ci
npm run audit
npm run build
npm run test:e2e
```

Do not deploy with critical/high dependency advisories, a failed clean MySQL migration, an untested restore, missing workers/scheduler, invalid Caronte issuer/client settings, or any enabled mock/stub integration.

Backups must include all tenant-scoped POS data and global customer-registration links. Encrypt backups, restrict restore access, and validate the documented RPO/RTO through recurring restore drills.
