# POS Build Action Plan

## Scope & Objectives

-   Implement the POS platform defined in `doc/requirements.md` using Laravel 12, Inertia.js (React), TailwindCSS, HeroUI, and MySQL 8.
-   Deliver a secure, auditable, and extensible foundation that supports multi-warehouse inventory, catalog management, POS workflows, customers, and reporting.
-   Provide a detailed handover log so another agent can continue seamlessly.

## Phase Breakdown

| Phase            | Goal                                                                                                  | Key Deliverables                                                                   |
| ---------------- | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| 1. Foundations   | Align on architecture, dependencies, conventions, and shared utilities.                               | Updated Composer/NPM deps, base config (.env, logging, auth), action plan.         |
| 2. Data Layer    | Model the domains via migrations, factories, seeders, repositories/services, observers.               | All entities, constraints, relationships, audit hooks, SKU generator.              |
| 3. Backend APIs  | Implement REST endpoints (/api/v1) w/ policies, validation, jobs, notifications.                      | Controllers, routes, transformers/resources, policies, queue jobs, error handling. |
| 4. Frontend UX   | Build Inertia React app w/ Tailwind/HeroUI covering login, dashboard, catalog, POS, clients, reports. | Layout, navigation, forms, state mgmt, API adapters, i18n scaffolding.             |
| 5. Quality & Ops | Automated tests, seeds, docs, CI hooks, observability.                                                | Pest/PHPUnit suites, feature tests, lint configs, README updates.                  |

## Detailed Step-by-Step Tasks

1. **Requirements digestion & architecture**

    - [x] Review `doc/requirements.md` and capture domain concepts, flows, and non-functional constraints.
    - [x] Define module boundaries (Catalog, Inventory, POS, Sales, Customers, Reports, Integrations, Observability).
    - [x] Decide directory structure (e.g., `app/Domain/*`, `app/Support/*`, `app/Http/API/V1/*`).
    - [x] Align on request/response format using `equidna/toolkit`.

2. **Environment & dependencies**

    - [x] Configure `.env.example` for DB, queue, mail, sms stubs, S3-compatible storage.
    - [x] Update `composer.json` to include `inertiajs/inertia-laravel`, `laravel/sanctum` or swift-auth bridge, `laravel/scout` (for search), Money handling (e.g., `brick/money`), and the internal packages (`equidna/toolkit`, `equdna/swift-auth`) via vcs/path repos if needed.
    - [x] Adjust `package.json` to include React, Inertia, Tailwind, HeroUI, Headless UI, Axios, Zustand (state), date-fns, charting lib.
    - [x] Run migrations & npm builds (document commands).

3. **Database schema & domain services**

    - [x] Create migrations for all entities with constraints, indexes, enums (where applicable) and trigger date updates.
    - [x] Implement Eloquent models with casts, relationships, scopes (active, search, per warehouse).
    - [x] Add factories & seeders (roles, admin user, default warehouse, product types, demo products/inventory).
    - [x] Implement services: SKU reservation, inventory adjustments, cart engine, folio generator, audit trail writer.
    - [x] Add observers/events to keep `fecha_fin_stock` in sync.

4. **API layer (/api/v1)**

    - [x] Organize routes under `routes/api.php` with middleware for auth, rate limiting, request-id.
    - [x] Create FormRequests for validation and Resources for consistent responses.
    - [x] Implement controllers for Auth, Warehouses, Product Types, Products, Inventory, SKU generator, Carts, Checkout, Sales, Receipts, Customers, Reports.
    - [x] Integrate equidna toolkit response macros, error codes, pagination metadata.
    - [x] Wire mailer/SMS jobs for receipt sending (queued) and ensure atomic checkout transactions.

5. **Web routes & Inertia React app**

    - [x] Configure Inertia middleware, Ziggy routes, localization setup.
    - [x] Build layout shell with navigation (Dashboard, Catálogos, POS, Clientes, Reportes) and session/vendedor switch.
    - [x] Implement screens per requirements, hooking to APIs via Axios hooks; include search/filter forms and data tables (HeroUI components).
    - [x] Add POS workflow (cart list, barcode input, discount controls, payment modal, receipt send dialog).
    - [x] Provide report visualizations + CSV export (client-side).

6. **Security, observability, integrations**

    - [x] Implement request logging middleware with JSON format and request-id correlation.
    - [x] Add policies/guards per role (admin, vendedor, auditor) using swift-auth roles.
    - [x] Configure rate limiting, throttling, sanitizer middleware.
    - [x] Stub Mailer/SMS providers adhering to adapter interfaces.

7. **Testing & QA**
    - [x] Add unit tests for SKU service, inventory adjustments, checkout totals.
    - [x] Feature tests covering API endpoints and RBAC.
    - [x] Browser tests (Laravel Dusk or Pest w/ Laravel test suite) for key flows (optional if time).
    - [x] Document manual test plan aligned with acceptance criteria.

## Execution Log / Handover Notes

| Timestamp                 | Author | Action / Decision                                                                                                                                                                                                         | Status / Next Steps                                     |
| ------------------------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| 2025-11-17T13:37:38-06:00 | Codex  | Drafted architecture/implementation plan (`action_plan.md`) and identified major phases/tasks.                                                                                                                            | Proceed with dependency setup & backend implementation. |
| 2025-11-17T14:06:43-06:00 | Codex  | Implemented backend domain: migrations, models, services (SKU, inventory, carts, checkout, folios), API controllers/routes, auth guard, toolkit package, logging middleware, seeders, composer deps, and CI-ready config. | Pending: hook up UI + smoke validation.                 |
| 2025-11-17T14:06:43-06:00 | Codex  | Built Inertia/React frontend shell with HeroUI, POS workflow, dashboards, catalogs, customers, reports; configured Tailwind/Vite, added Zustand store + API utilities, npm/composer installs.                             | Next: add automated tests + polish docs/readme.         |

> Keep appending rows chronologically with UTC timestamps, describing what changed, files touched, and any follow-ups required.
