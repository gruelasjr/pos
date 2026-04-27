# Testing Guide

The test suite includes PHP feature coverage and browser E2E coverage for critical POS flows.

## Commands

```bash
composer test
composer lint
composer audit
npm run audit
npm run build
npm run test:e2e
```

## Current Coverage Areas

- Cart item subtotal calculations.
- Sensitive payload redaction.
- API login and inactive-user blocking.
- Bearer-token enforcement for protected API routes.
- Role middleware blocking non-seller POS access.
- Checkout idempotency requirements.
- Sale creation, inventory decrement, receipt job dispatch, and idempotent replay.
- Public customer registration with single-use secure tokens.
- Browser checkout flow: seller session, cart creation, product selection, and payment confirmation.
- Theme persistence: toggle, reload, navigation, keyboard access, and logout/session-clear behavior.

## Test Database

`phpunit.xml` uses an in-memory SQLite database for PHP tests:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="SWIFT_AUTH_TABLE_PREFIX" value="swift_auth_"/>
```

This makes the suite fast and forces migrations to remain portable enough for CI.

Playwright E2E uses an isolated file database:

```text
database/e2e.sqlite
```

The E2E server script runs `php artisan migrate:fresh --seed --force` before launching Laravel on `127.0.0.1:8010`.

If Chromium is missing locally:

```bash
npx playwright install chromium
```

## Adding Tests

- Use `tests/Feature` for HTTP, auth, database, queue, and full workflow tests.
- Use `tests/Unit` for pure domain behavior that does not need Laravel bootstrapping.
- Use `tests/e2e` for browser-visible behavior that must work for cashiers and operators.
- Use `Tests\Feature\Concerns\BuildsPosFixtures` for common POS fixtures.
- Fake queues with `Queue::fake()` when the assertion is about dispatching, not delivery.

## CI Gate

CI runs the following gates through `.github/workflows/ci.yml`:

```bash
composer install
npm ci
composer audit
XDEBUG_MODE=coverage composer test:coverage
composer lint
npm run audit
npx playwright install --with-deps chromium
npm run test:e2e
```

Do not merge if any gate fails.
