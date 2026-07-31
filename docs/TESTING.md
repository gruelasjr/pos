# Testing Guide

## Local gates

```bash
composer audit --locked --no-interaction
composer test
composer lint
npm ci
npm run audit
npm run build
npm run test:e2e
```

PHPUnit uses in-memory SQLite for fast tests and CI additionally rehearses a clean migrate/rollback/migrate cycle on MySQL 8.4. Caronte test URLs are HTTPS placeholders; tests must fake token/JWKS behavior and never contact a real identity service.

Critical coverage includes tenant isolation (including cross-tenant 404s and allowed identifier collisions), Caronte token/session and role failures, shadow-user synchronization, persistent-worker tenant cleanup, inventory/folio concurrency, durable payment idempotency, outbox retries/DLQ, and public-link token isolation.

Browser coverage targets desktop Chrome, Pixel 7, and iPad portrait/landscape. It covers cash and mixed payments, declines, double taps, offline draft recovery without offline confirmation, receipt reprint, returns, safe-area layout, 44 px controls, axe checks, and light/dark visual regression.

Use `tests/Feature` for HTTP/database/queue workflows, `tests/Unit` for pure domain behavior, and `tests/e2e` for cashier-visible behavior. Do not merge when either audit reports critical/high advisories or any gate fails.
