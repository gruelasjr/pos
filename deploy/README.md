# POS Faro deployment

These scripts deploy the Laravel 12 + Inertia/React application to a Linux host with PHP-FPM,
Nginx, Supervisor, MySQL, and Redis. Production never creates or replaces the application `.env`;
the direct development deploy compiles and uploads it from `.env.d`.

## Production releases

`deploy/deploy.sh` creates a timestamped release below `/ometra/pos-faro/releases`, links the
shared `.env` and `storage`, installs production Composer dependencies, builds Vite assets, runs
migrations, optimizes Laravel, activates the release, and probes `/up` and `/ready`.

Run it on the target host as the deploy user:

```bash
BRANCH=main DOMAIN=pos.example.com bash deploy/deploy.sh
```

CI may upload an archive and invoke the same script with `SOURCE_ARCHIVE=/path/release.tar.gz`.
Defaults are in `deploy/config.sh`; every value can be overridden through the environment.

Before the first release, create:

```text
/ometra/pos-faro/
├── releases/
└── shared/
    ├── .env
    └── storage/
```

The deploy user needs write access to that tree and passwordless sudo access to reload Nginx and
PHP-FPM and manage the `pos-faro-worker` Supervisor group. The shared `.env` must satisfy
`docs/PRODUCTION.md`, including MySQL, Redis, Caronte/OIDC, and real POS provider drivers.

## Direct development deployment

`deploy/deploy_dev.sh` synchronizes the current workspace in place with `rsync`, so it includes
uncommitted changes. Before synchronization, it compiles `.env` from the four base `.env.d/*.env`
fragments and applies the application, authentication, and integration development overrides. The
local-only `database.env.dev` SQLite override is excluded so the remote environment keeps the base
MySQL/Redis configuration. The result is uploaded atomically; the source fragments are excluded
from `rsync`. `Storage`, dependencies, and built assets remain protected from deletion. SSH key or
interactive password authentication may be used.

```bash
REMOTE_HOST=dev.pos.example.com \
REMOTE_PATH=/ometra/pos-faro \
bash deploy/deploy_dev.sh
```

Supported overrides include `REMOTE_USER`, `REMOTE_HOST`, `REMOTE_PATH`, `REMOTE_APP_USER`,
`REMOTE_GROUP`, `APP_NAME`, `WEB_SERVICE`, `PHP_FPM_SERVICE`, and
`FORCE_DEPENDENCY_INSTALL=true`.

Before deploying, populate the required secrets in the encrypted `.env.d` fragments. The script
validates the compiled file before replacing the remote `.env`; incomplete Caronte or database
credentials abort the deployment while preserving the current environment.

The server must define the Supervisor groups `pos-faro-worker` and `pos-faro-scheduler`. Both
flows restart workers/scheduler and require the application readiness endpoint to return HTTP 200.

## Validation

```bash
bash -n deploy/*.sh
shellcheck deploy/*.sh
```

Deployment scripts do not provision servers, databases, Redis, TLS certificates, backups, or
secrets. Follow `docs/PRODUCTION.md` and `docs/OPERATIONS_RUNBOOK.md` for those requirements.
