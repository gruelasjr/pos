#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "${SCRIPT_DIR}/config.sh"
# shellcheck disable=SC1091
source "${SCRIPT_DIR}/lib.sh"

RELEASE_ID="$(date +%Y%m%d_%H%M%S)"
RELEASE_PATH="${APP_ROOT}/releases/${RELEASE_ID}"
CURRENT_PATH="${APP_ROOT}/current"
SHARED_PATH="${APP_ROOT}/shared"
LOCK_FILE="${APP_ROOT}/deploy.lock"
ACTIVATED=0

cleanup_failed_release() {
  if [[ "${ACTIVATED}" -eq 0 && -d "${RELEASE_PATH}" ]]; then
    echo "==> Removing failed release ${RELEASE_PATH}"
    rm -rf "${RELEASE_PATH}"
  fi
}

trap cleanup_failed_release ERR

require_command composer
require_command curl
require_command npm
require_command php
require_command sudo
require_command flock
require_command systemctl
require_command supervisorctl

if [[ -n "${SOURCE_ARCHIVE}" ]]; then
  require_command tar

  if [[ ! -f "${SOURCE_ARCHIVE}" ]]; then
    echo "ERROR: SOURCE_ARCHIVE does not exist: ${SOURCE_ARCHIVE}"
    exit 1
  fi
else
  require_command git
fi

validate_writable_dir() {
  if [[ ! -w "$1" ]]; then
    echo "ERROR: ${DEPLOY_USER} cannot write to $1."
    echo "Repair ownership and ACLs for ${APP_ROOT}/shared before retrying."
    exit 1
  fi
}

require_env_var() {
  local env_file="$1"
  local key="$2"

  if ! grep -Eq "^[[:space:]]*${key}=" "${env_file}"; then
    echo "ERROR: Missing required env var ${key} in ${env_file}."
    exit 1
  fi

  if grep -Eq "^[[:space:]]*${key}=[[:space:]]*(\"\"|'')?[[:space:]]*$" "${env_file}"; then
    echo "ERROR: Required env var ${key} is empty in ${env_file}."
    exit 1
  fi
}

restart_queue_workers() {
  local requested_prefix="${APP_NAME}-worker"
  local supervisor_status
  local supervisor_help
  local programs
  local program
  local worker_log="${SHARED_PATH}/storage/logs/worker.log"
  local laravel_log="${SHARED_PATH}/storage/logs/laravel.log"

  echo "==> Restarting queue workers (${requested_prefix}*)"

  if ! supervisor_help="$(sudo -n /usr/bin/supervisorctl help 2>&1)"; then
    echo "ERROR: Unable to query supervisor status as ${DEPLOY_USER}."
    echo "${supervisor_help}"
    exit 1
  fi

  supervisor_status="$(sudo -n /usr/bin/supervisorctl status 2>&1 || true)"
  programs="$(awk -v prefix="${requested_prefix}" '$1 ~ ("^" prefix) {split($1, p, /[_:]/); print p[1]}' <<<"${supervisor_status}" | sort -u)"

  if [[ -z "${programs}" ]]; then
    echo "WARN: No queue worker programs found in supervisor status. Running reread/update."
    sudo -n /usr/bin/supervisorctl reread
    sudo -n /usr/bin/supervisorctl update
    supervisor_status="$(sudo -n /usr/bin/supervisorctl status 2>&1 || true)"
    programs="$(awk -v prefix="${requested_prefix}" '$1 ~ ("^" prefix) {split($1, p, /[_:]/); print p[1]}' <<<"${supervisor_status}" | sort -u)"
  fi

  if [[ -z "${programs}" ]]; then
    echo "ERROR: No queue worker programs found for prefix ${requested_prefix}."
    echo "${supervisor_status}"
    exit 1
  fi

  while IFS= read -r program; do
    [[ -z "${program}" ]] && continue

    if ! sudo -n /usr/bin/supervisorctl restart "${program}:*"; then
      echo "WARN: Restart failed for ${program}:*. Trying start."
      sudo -n /usr/bin/supervisorctl start "${program}:*"
    fi
  done <<<"${programs}"

  supervisor_status="$(sudo -n /usr/bin/supervisorctl status 2>&1 || true)"
  if ! awk -v prefix="${requested_prefix}" '$1 ~ ("^" prefix) && $2 == "RUNNING" {found=1} END{exit(found?0:1)}' <<<"${supervisor_status}"; then
    echo "ERROR: Queue workers for ${requested_prefix} are not RUNNING after restart/start."
    echo "${supervisor_status}"
    if [[ -f "${worker_log}" ]]; then
      echo "==> Recent worker log output"
      tail -n 50 "${worker_log}" || true
    else
      echo "WARN: Worker log not found at ${worker_log}"
    fi
    if [[ -f "${laravel_log}" ]]; then
      echo "==> Recent Laravel log output"
      tail -n 80 "${laravel_log}" || true
    else
      echo "WARN: Laravel log not found at ${laravel_log}"
    fi
    exit 1
  fi
}

if [[ ! -f "${SHARED_PATH}/.env" ]]; then
  echo "ERROR: Missing ${SHARED_PATH}/.env. Run the server setup script and configure the environment first."
  exit 1
fi

require_env_var "${SHARED_PATH}/.env" "CARONTE_URL"
require_env_var "${SHARED_PATH}/.env" "CARONTE_APP_CN"
require_env_var "${SHARED_PATH}/.env" "CARONTE_APP_SECRET"
require_env_var "${SHARED_PATH}/.env" "CARONTE_OIDC_CLIENT_SECRET"
require_env_var "${SHARED_PATH}/.env" "DB_DATABASE"
require_env_var "${SHARED_PATH}/.env" "DB_USERNAME"
require_env_var "${SHARED_PATH}/.env" "DB_PASSWORD"

if ! sudo -n systemctl show "php${PHP_VERSION}-fpm" --property=Id >/dev/null 2>&1; then
  echo "ERROR: ${DEPLOY_USER} cannot reload php${PHP_VERSION}-fpm with passwordless sudo."
  echo "Add a passwordless sudoers rule for:"
  echo "  $(command -v systemctl) show php${PHP_VERSION}-fpm --property=Id"
  echo "  $(command -v systemctl) reload php${PHP_VERSION}-fpm"
  exit 1
fi

if ! sudo -n systemctl show nginx --property=Id >/dev/null 2>&1; then
  echo "ERROR: ${DEPLOY_USER} cannot reload nginx with passwordless sudo."
  echo "Add a passwordless sudoers rule for:"
  echo "  $(command -v systemctl) show nginx --property=Id"
  echo "  $(command -v systemctl) reload nginx"
  exit 1
fi

if ! sudo -n /usr/bin/supervisorctl help >/dev/null 2>&1; then
  echo "ERROR: ${DEPLOY_USER} cannot execute supervisorctl with passwordless sudo."
  echo "Update sudoers to allow supervisorctl commands for queue workers."
  exit 1
fi

mkdir -p "${APP_ROOT}/releases"

exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
  echo "ERROR: Another deploy is already running for ${APP_NAME}."
  exit 1
fi

echo "==> Creating release ${RELEASE_ID}"
mkdir -p "${RELEASE_PATH}"

if [[ -n "${SOURCE_ARCHIVE}" ]]; then
  echo "==> Extracting uploaded release source"
  tar -xzf "${SOURCE_ARCHIVE}" -C "${RELEASE_PATH}"
  rm -f "${SOURCE_ARCHIVE}"
else
  echo "==> Cloning repository"
  git clone --depth=1 --branch "${BRANCH}" "${REPO_URL}" "${RELEASE_PATH}"
fi

cd "${RELEASE_PATH}"

echo "==> Linking shared files"
rm -rf storage
ln -s "${SHARED_PATH}/storage" storage
ln -s "${SHARED_PATH}/.env" .env

echo "==> Ensuring shared Laravel directories"
mkdir -p "${SHARED_PATH}/storage/app"
mkdir -p "${SHARED_PATH}/storage/framework/cache/data"
mkdir -p "${SHARED_PATH}/storage/framework/sessions"
mkdir -p "${SHARED_PATH}/storage/framework/views"
mkdir -p "${SHARED_PATH}/storage/logs"
mkdir -p "${RELEASE_PATH}/bootstrap/cache"

echo "==> Fixing permissions before Laravel commands"
chmod -R ug+rwX "${RELEASE_PATH}/bootstrap/cache"

echo "==> Validating shared storage permissions"
validate_writable_dir "${SHARED_PATH}/storage"
validate_writable_dir "${SHARED_PATH}/storage/app"
validate_writable_dir "${SHARED_PATH}/storage/framework"
validate_writable_dir "${SHARED_PATH}/storage/framework/cache"
validate_writable_dir "${SHARED_PATH}/storage/framework/cache/data"
validate_writable_dir "${SHARED_PATH}/storage/framework/sessions"
validate_writable_dir "${SHARED_PATH}/storage/framework/views"
validate_writable_dir "${SHARED_PATH}/storage/logs"

echo "==> Installing Composer dependencies"
composer install --no-dev --prefer-dist --classmap-authoritative --no-interaction

echo "==> Installing Node dependencies"
npm ci

echo "==> Building frontend"
npm run build

echo "==> Laravel clear cache"
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "==> Running migrations"
php artisan migrate --force

echo "==> Laravel cache build"
php artisan optimize

echo "==> Final permission fix"
chmod -R ug+rwX "${RELEASE_PATH}/bootstrap/cache"

echo "==> Activating release"
if [[ -d "${CURRENT_PATH}" && ! -L "${CURRENT_PATH}" ]]; then
  echo "ERROR: ${CURRENT_PATH} exists as a directory, not a symlink."
  echo "This breaks release activation and queue workers. Convert it to a symlink before deploying."
  ls -la "${CURRENT_PATH}" || true
  exit 1
fi

ln -sfn "${RELEASE_PATH}" "${CURRENT_PATH}"
ACTIVATED=1

if [[ ! -f "${CURRENT_PATH}/artisan" ]]; then
  echo "ERROR: Missing ${CURRENT_PATH}/artisan after activation."
  echo "Release path: ${RELEASE_PATH}"
  ls -la "${CURRENT_PATH}" || true
  ls -la "${RELEASE_PATH}" || true
  exit 1
fi

echo "==> Reloading services"
sudo -n /usr/bin/systemctl reload "php${PHP_VERSION}-fpm"
sudo -n /usr/bin/systemctl reload nginx

php artisan queue:restart || true
restart_queue_workers
restart_supervisor_program "${APP_NAME}-scheduler" "${SHARED_PATH}/storage/logs/scheduler.log"

echo "==> Verifying application readiness"
php artisan migrate:status --no-ansi >/dev/null
php artisan schedule:list --no-ansi >/dev/null
curl --fail --silent --show-error --max-time 15 "https://${DOMAIN}/up" >/dev/null
curl --fail --silent --show-error --max-time 15 "https://${DOMAIN}/ready" >/dev/null

echo "==> Cleaning old releases"
cd "${APP_ROOT}/releases"
find . -mindepth 1 -maxdepth 1 -type d -printf '%P\n' \
  | sort -r \
  | tail -n +"$((KEEP_RELEASES + 1))" \
  | xargs -r -I{} rm -rf -- "{}"

echo "==> Deploy completed successfully"
