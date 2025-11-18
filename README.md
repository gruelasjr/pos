# POS Faro

POS Faro is a Laravel 12 + Inertia/React point of sale platform built for multi warehouse retailers that need inventory discipline, simultaneous carts, rich catalog tooling, and executive reporting. The repository contains the JSON API under `/api/v1` and the Vite powered React SPA.

## Table of contents

1. [Core features](#core-features)
2. [Architecture](#architecture)
3. [Requirements](#requirements)
4. [Quick start](#quick-start)
5. [Environment configuration](#environment-configuration)
6. [Database and sample data](#database-and-sample-data)
7. [Running locally](#running-locally)
8. [Useful scripts](#useful-scripts)
9. [Quality, logging, and observability](#quality-logging-and-observability)
10. [Deployment and maintenance](#deployment-and-maintenance)
11. [Documentation and support](#documentation-and-support)

## Core features

- Multi warehouse inventory with reorder points and automatic stock depletion timestamps.
- Simultaneous POS carts per seller with discounts, mixed payments, and receipt jobs.
- Catalog management for warehouses, product types, and products (SKU ranges handled by the backend).
- Customer capture in store or remotely with marketing consent flags.
- Daily, weekly, monthly, and seller focused reports at `/api/v1/reports`.
- Swift Auth guard, RBAC, request id logging, and audit trails for sensitive events.

## Architecture

| Layer      | Stack / Notes                                                                 |
| ---------- | ----------------------------------------------------------------------------- |
| Backend    | Laravel 12, PHP 8.3, MySQL 8, Redis optional                                  |
| Frontend   | Inertia.js, React 18, HeroUI, TailwindCSS, Zustand, Chart.js                  |
| Auth       | `equidna/swift-auth` guard and token issuer                                   |
| Utilities  | `equidna/laravel-toolkit` responses, Folio generator, receipt renderer        |
| Build      | Vite, npm 10+, Composer 2.5+                                                  |

Folder highlights:

- `app/Domain` - domain services (catalog, inventory, POS, sales).
- `app/Http/Controllers/API/V1` - versioned API endpoints.
- `resources/js` - React pages, layouts, hooks, and Zustand store.
- `doc/requirements.md` - original product brief.
- `doc/user-manual.md` - end user guide (added in this review).

## Requirements

- PHP 8.3 with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo.
- Composer 2.5+
- Node.js 20.19+ (or 22.12+) and npm 10+
- MySQL 8.x
- Redis optional (queues default to the database driver).

## Quick start

```bash
git clone <repo> pos-faro
cd pos-faro
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build        # or npm run dev for watch mode
php artisan serve
```

Or run the helper:

```bash
composer setup
```

### Demo credentials

Seed data provisions three Swift Auth users:

| Role    | Email               | Password |
| ------- | ------------------- | -------- |
| Admin   | `admin@pos.local`   | `secret` |
| Seller  | `vendedor@pos.local`| `secret` |
| Auditor | `auditor@pos.local` | `secret` |

## Environment configuration

Key `.env` variables:

- `APP_LOCALE`, `APP_TIMEZONE` - defaults to `es_MX` and `America/Mexico_City`.
- `DB_*` - MySQL connection.
- `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` - default to `database`. Switch to Redis for production.
- `MEDIA_DISK` / `FILESYSTEM_DISK` - select `s3` or `public` based on asset storage.
- `SWIFT_AUTH_*` - admin bootstrap data and SPA redirect URL for Swift Auth.
- `SMS_FROM`, `MAIL_*` - channels for receipt notifications (stubs log by default).
- `LOG_STACK=daily` - rotates JSON logs under `storage/logs`.

Review `.env.example` for the full list and adjust before deployments.

## Database and sample data

Migrations in `database/migrations/` cover warehouses, catalog entities, carts, sales, folio sequences, and audit logs. Seeders create:

- Swift Auth roles (admin, vendedor, auditor) and demo users.
- Two warehouses, product types, products, and stock per warehouse.
- Reserved SKU ranges consumed by `SkuGeneratorService`.

Reset data when needed:

```bash
php artisan migrate:fresh --seed
```

## Running locally

### Backend runtime

Use the convenience script:

```bash
composer dev
```

It runs:

- `php artisan serve` - API and Inertia responses.
- `php artisan queue:listen --tries=1` - background jobs (`SendReceiptJob`, etc.).
- `php artisan pail --timeout=0` - structured log tailing.
- `npm run dev` - Vite dev server.

You can run individual processes manually, and remember to launch `php artisan schedule:work` if you add cron style tasks.

### Frontend workflow

- `npm run dev` - hot reload React and proxy `/api`.
- `npm run build` - produce versioned assets in `public/build`.

`resources/js/bootstrap.js` injects Axios defaults, the `/api/v1` base URL, and the Swift Auth bearer token from local storage.

## Useful scripts

| Command                | Description                                                                 |
| ---------------------- | --------------------------------------------------------------------------- |
| `composer setup`       | Installs composer deps, copies `.env`, runs key generation, migration, build|
| `composer dev`         | Runs API server, queue worker, log tail, and Vite concurrently              |
| `php artisan queue:work` | Production safe worker for receipts and async notifications               |
| `php artisan optimize` | Cache config/routes/views for production builds                            |

## Quality, logging, and observability

- Static checks: Larastan (`vendor/bin/phpstan analyse`) and PHPCS (see `composer.json`).
- Logging: JSON logs with request ids live in `storage/logs`, while `AuditLogger` persists sensitive events to `audit_logs`.
- Jobs: `SendReceiptJob` renders HTML receipts via `ReceiptRenderer` and routes through stubbed mail/SMS services in `app/Services/Notifications`.
- Response format: `equidna/laravel-toolkit` enforces `{ status, message, data, errors }` payloads across the API.

## Deployment and maintenance

1. Build artifacts - run `npm run build` and `php artisan optimize` during CI/CD.
2. Migrations - execute `php artisan migrate --force` before redirecting traffic.
3. Storage - ensure `storage/` and `bootstrap/cache` are writable and that `php artisan storage:link` has been executed once.
4. Queues - keep `php artisan queue:work --tries=1` (or Horizon) alive; receipts depend on it.
5. Scheduler - add `* * * * * php /path/artisan schedule:run` for upcoming periodic jobs (inventory sync, report snapshots, etc.).
6. Backups - snapshot the MySQL database and `storage/app` if using local media.
7. Secrets rotation - rotate Swift Auth tokens regularly; only change `APP_KEY` before the first deploy.
8. Monitoring - ship the `stack` channel or `storage/logs` to your log platform of choice.

Maintenance tips:

- Revisit dependency updates monthly (`composer update`, `npm update`) and rerun the static checks afterward.
- Cross reference `doc/requirements.md` before tackling roadmap items to stay aligned with the agreed scope.
- Document operational runbooks (queue restarts, cache clears) alongside your infra automation scripts.

## API contract & SDK

- `doc/openapi.yaml` defines the HTTP contract (OpenAPI 3.0). Keep it updated when controllers change.
- Generate strongly typed helpers for the React app by running `npm run openapi:types`. The command regenerates `resources/js/api/types.ts` from the spec.
- `resources/js/api/client.ts` exports `createApiClient`, a typed Axios wrapper with convenience methods (`warehouses.list()`, `carts.checkout()`, etc.). Example usage:

```ts
import { createApiClient } from '@/api/client';

const api = createApiClient({ token });
const warehouses = await api.warehouses.list({ per_page: 50 });
```

## Documentation and support

- [doc/requirements.md](doc/requirements.md) - original functional and technical specification.
- [doc/user-manual.md](doc/user-manual.md) - end user guide covering login, POS flows, catalogs, customers, and reports.
- API reference lives in the controller PHPDoc blocks inside `app/Http/Controllers/API/V1`.

For questions open an issue, ping the maintainer on your internal tracker, or follow the escalation channel defined by your team.

Happy selling, and keep the queues running.
