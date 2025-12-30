# POS Faro

POS Faro is a Laravel 12 + Inertia/React point of sale platform built for multi-warehouse retailers. It provides inventory discipline, simultaneous carts, rich catalog tooling, and executive reporting with a modern UI built on an Atomic Design System (ADS).

The repository contains a JSON API under `/api/v1` and a Vite-powered React SPA with dark/light theme support.

## Table of Contents

1. [Core Features](#core-features)
2. [Technology Stack](#technology-stack)
3. [Quick Start](#quick-start)
4. [Environment Configuration](#environment-configuration)
5. [Running Locally](#running-locally)
6. [Useful Scripts](#useful-scripts)
7. [Documentation](#documentation)
8. [Deployment](#deployment)

## Core Features

-   **Multi-warehouse inventory** with reorder points and automatic stock depletion timestamps
-   **Simultaneous POS carts** per seller with discounts, mixed payments, and receipt jobs
-   **Catalog management** for warehouses, product types, SKU generation and allocation
-   **Customer capture** in-store or remote with marketing consent flags
-   **Comprehensive reporting** (daily, weekly, monthly, by-seller) at `/api/v1/reports`
-   **Security & Audit** via Swift Auth guard, RBAC, request-id logging, audit trails
-   **Dark/Light theme** support via CSS variables and React Context
-   **Responsive UI** optimized for desktop and tablet (1280×800 min)

## Technology Stack

| Layer             | Technology                                                      |
| ----------------- | --------------------------------------------------------------- |
| **Backend**       | Laravel 12, PHP 8.3, MySQL 8.x, Redis (optional)                |
| **Frontend**      | React 19, Inertia.js, TailwindCSS 4, Atomic Design System (ADS) |
| **Auth**          | `equidna/swift-auth` guard and JWT token issuer                 |
| **UI Components** | Custom ADS atoms, molecules, organisms (100% HeroUI-free)       |
| **Build**         | Vite, npm 10+, Composer 2.5+                                    |
| **Queue**         | Redis or database driver (email, SMS, reports)                  |
| **Logging**       | Structured JSON logs with request-id correlation                |

## Quick Start

```bash
git clone <repo> pos-faro
cd pos-faro

# Setup backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# Setup frontend
npm install
npm run build        # or npm run dev for development with hot reload

# Start servers
php artisan serve                    # API at http://localhost:8000
npm run dev                          # Vite at http://localhost:5173 (if needed)
php artisan queue:work               # Process jobs (email, receipts, reports)
```

**Helper script**:

```bash
composer setup  # Runs install, key:generate, migrate --seed, storage:link
```

### Demo Credentials

Three seeded Swift Auth users:

| Role    | Email                | Password |
| ------- | -------------------- | -------- |
| Admin   | `admin@pos.local`    | `secret` |
| Seller  | `vendedor@pos.local` | `secret` |
| Auditor | `auditor@pos.local`  | `secret` |

## Environment configuration

## Requirements

-   PHP 8.3+ with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo
-   Composer 2.5+
-   Node.js 20.19+ (or 22.12+) and npm 10+
-   MySQL 8.x (InnoDB, utf8mb4, strict mode)
-   Redis optional (queues default to database driver)

## Environment Configuration

Key `.env` variables:

-   `APP_LOCALE`, `APP_TIMEZONE` - defaults to `es_MX`, `America/Mexico_City`
-   `DB_*` - MySQL connection settings
-   `CACHE_STORE`, `QUEUE_CONNECTION` - defaults to `database`; switch to `redis` for production
-   `FILESYSTEM_DISK` - `public` (local) or `s3` (cloud)
-   `SWIFT_AUTH_*` - Admin bootstrap and SPA redirect URL
-   `SMS_FROM`, `MAIL_*` - Receipt notification channels (stubs log by default)
-   `LOG_STACK=daily` - Rotates JSON logs to `storage/logs/`

Review `.env.example` for the complete list.

## Running Locally

```bash
# Backend
php artisan serve                    # API at http://localhost:8000

# Frontend (optional Vite dev server with hot reload)
npm run dev                          # http://localhost:5173

# Queue Worker (for receipts, reports, email/SMS jobs)
php artisan queue:work
```

## Useful Scripts

| Command                      | Purpose                                            |
| ---------------------------- | -------------------------------------------------- |
| `composer setup`             | Install, key:generate, migrate, seed, storage:link |
| `npm run build`              | Production build (outputs to `public/build/`)      |
| `npm run dev`                | Development with Vite hot reload                   |
| `php artisan migrate --seed` | Run migrations + seeders                           |
| `php artisan queue:work`     | Process queued jobs                                |
| `php artisan optimize`       | Cache config/routes for production                 |
| `php artisan tinker`         | Interactive shell for testing                      |

## Quality & Logging

-   **Static analysis**: Larastan (`vendor/bin/phpstan analyse`), PHPCS (see composer.json)
-   **Logging**: JSON logs with request-ids in `storage/logs/laravel.log`
-   **Audit trail**: `AuditLog` table tracks sensitive events (product edits, inventory changes, sales)
-   **Queue jobs**: `SendReceiptJob` renders and delivers receipts via email/SMS
-   **Response format**: Standardized via `equidna/laravel-toolkit` (`{ success, data, error }`)

## Design System

All UI components follow **Atomic Design** principles:

-   **Atoms**: Button, Card, Text, Input, Badge, Toggle, etc.
-   **Molecules**: FormField, Modal, Navbar, StatCard, ThemeToggle
-   **Organisms**: DataTable, AppLayout (with sidebar/header)

**Zero HeroUI dependencies** — components are 100% custom with:

-   CSS custom properties for theme tokens
-   Dark/light mode toggle with instant theme switching
-   Fully responsive (mobile, tablet, desktop)
-   Tailwind CSS 4.1 for styling

See [docs/DESIGN_SYSTEM.md](./docs/DESIGN_SYSTEM.md) for component API and usage examples.

## Deployment & Maintenance

**CI/CD Pipeline**:

1. Run static checks: `composer analyse`, npm lint
2. Build frontend: `npm run build` → `public/build/manifest.json`
3. Migrate DB: `php artisan migrate --force`
4. Cache config: `php artisan optimize`

**Production Checklist**:

-   [ ] Storage writable: `storage/` and `bootstrap/cache/`
-   [ ] Queue worker running: `php artisan queue:work` (or Supervisor/systemd)
-   [ ] Scheduler active: `* * * * * php /path/artisan schedule:run`
-   [ ] Logs monitored: Ship `storage/logs/` to your log platform
-   [ ] Backups: Snapshot MySQL and `storage/app/`
-   [ ] Secrets: Rotate Swift Auth tokens monthly; never change `APP_KEY` after first deploy

**Maintenance**:

-   Update dependencies monthly: `composer update`, `npm update`
-   Rerun static checks after updates
-   Monitor queue backlog and job failures
-   Archive old audit logs quarterly

## Documentation

📖 **Full documentation available in `/docs` folder**:

| Document                                         | Purpose                                                        |
| ------------------------------------------------ | -------------------------------------------------------------- |
| [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md)   | System architecture, tech stack, API design                    |
| [docs/DESIGN_SYSTEM.md](./docs/DESIGN_SYSTEM.md) | ADS component library, theme system, usage guide               |
| [docs/REQUIREMENTS.md](./docs/REQUIREMENTS.md)   | Functional spec, entities, business rules, API endpoints       |
| [docs/USER_GUIDE.md](./docs/USER_GUIDE.md)       | End-user guide: login, POS, catalogs, reports, troubleshooting |
| [docs/MIGRATION.md](./docs/MIGRATION.md)         | HeroUI phase-out & ADS migration (completed)                   |

**API Documentation**:

-   OpenAPI spec: [doc/openapi.yaml](./doc/openapi.yaml)
-   Controller PHPDoc: See `app/Http/Controllers/API/V1/`

## Support

-   **Questions?** Open an issue or contact the maintainer
-   **Feature requests?** Check [docs/REQUIREMENTS.md](./docs/REQUIREMENTS.md) roadmap section
-   **Operational issues?** See [docs/USER_GUIDE.md](./docs/USER_GUIDE.md#troubleshooting)

Happy selling! Keep the queues running and theme toggling. 🎨✨
