#!/usr/bin/env bash

set -euo pipefail

REMOTE_USER="${REMOTE_USER:-gruelas}"
REMOTE_HOST="${REMOTE_HOST:-dev.ometra.mx}"
REMOTE_PATH="${REMOTE_PATH:-/ometra/pos}"
REMOTE_APP_USER="${REMOTE_APP_USER:-www-data}"
REMOTE_GROUP="${REMOTE_GROUP:-desarrollo}"
APP_NAME="${APP_NAME:-pos-faro}"
WEB_SERVICE="${WEB_SERVICE:-nginx}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-auto}"
FORCE_DEPENDENCY_INSTALL="${FORCE_DEPENDENCY_INSTALL:-false}"
FORCE_ASSET_BUILD="${FORCE_ASSET_BUILD:-false}"
REMOTE_LOCK="${REMOTE_PATH}/.deploy-dev.lock"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
TEMP_DIR=""
SSH_CONTROL_PATH=""
ASKPASS_SCRIPT=""
REMOTE_LOCKED=0
MAINTENANCE_ENABLED=0
SSH_BASE_OPTS=()

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

require_local_command() {
  command -v "$1" >/dev/null 2>&1 || fail "Required command not found: $1"
}

validate_identifier() {
  local name="$1"
  local value="$2"

  [[ "${value}" =~ ^[a-zA-Z_][a-zA-Z0-9_-]*$ ]] || fail "Invalid ${name}: ${value}"
}

validate_configuration() {
  validate_identifier "REMOTE_USER" "${REMOTE_USER}"
  validate_identifier "REMOTE_APP_USER" "${REMOTE_APP_USER}"
  validate_identifier "REMOTE_GROUP" "${REMOTE_GROUP}"
  validate_identifier "APP_NAME" "${APP_NAME}"

  [[ "${REMOTE_HOST}" =~ ^[a-zA-Z0-9._:-]+$ ]] || fail "Invalid REMOTE_HOST: ${REMOTE_HOST}"
  [[ "${REMOTE_PATH}" =~ ^/[a-zA-Z0-9._/-]+$ ]] || fail "Invalid REMOTE_PATH: ${REMOTE_PATH}"
  [[ "${WEB_SERVICE}" =~ ^[a-zA-Z0-9_.@-]+$ ]] || fail "Invalid WEB_SERVICE: ${WEB_SERVICE}"
  [[ "${PHP_FPM_SERVICE}" =~ ^[a-zA-Z0-9_.@-]+$ ]] || fail "Invalid PHP_FPM_SERVICE: ${PHP_FPM_SERVICE}"
  [[ "${FORCE_DEPENDENCY_INSTALL}" == "true" || "${FORCE_DEPENDENCY_INSTALL}" == "false" ]] \
    || fail "FORCE_DEPENDENCY_INSTALL must be true or false."
  [[ "${FORCE_ASSET_BUILD}" == "true" || "${FORCE_ASSET_BUILD}" == "false" ]] \
    || fail "FORCE_ASSET_BUILD must be true or false."
}

ssh_sudo() {
  local remote_command="$1"
  local quoted_command
  local sudo_command

  bash -n -c "${remote_command}" || fail "Generated an invalid privileged remote command."
  printf -v quoted_command '%q' "${remote_command}"
  sudo_command="IFS= read -rs SUDO_PASSWORD; \
    printf '%s\n' \"\$SUDO_PASSWORD\" | sudo -S -p '' -v && \
    unset SUDO_PASSWORD && sudo -n bash -lc ${quoted_command}"

  printf '%s\n' "${SSHPASS}" | ssh -tt "${SSH_BASE_OPTS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" \
    "${sudo_command}"
}

ssh_sudo_as_app() {
  local remote_command="$1"
  local quoted_command

  bash -n -c "${remote_command}" || fail "Generated an invalid application remote command."
  printf -v quoted_command '%q' "${remote_command}"
  ssh_sudo "sudo -u '${REMOTE_APP_USER}' -H bash -lc ${quoted_command}"
}

cleanup() {
  local exit_code=$?

  if [[ "${MAINTENANCE_ENABLED}" -eq 1 ]]; then
    ssh_sudo_as_app "if [[ -f '${REMOTE_PATH}/artisan' ]]; then php '${REMOTE_PATH}/artisan' up; fi" \
      >/dev/null 2>&1 || true
  fi
  if [[ "${REMOTE_LOCKED}" -eq 1 ]]; then
    ssh_sudo "rm -f '${REMOTE_PATH}/.env.deploying'; rmdir '${REMOTE_LOCK}'" >/dev/null 2>&1 || true
  fi
  if [[ -n "${SSH_CONTROL_PATH}" ]]; then
    ssh "${SSH_BASE_OPTS[@]}" -O exit "${REMOTE_USER}@${REMOTE_HOST}" 2>/dev/null || true
  fi
  if [[ -n "${TEMP_DIR}" ]]; then
    rm -rf "${TEMP_DIR}"
  fi
  unset SSHPASS SSH_ASKPASS SSH_ASKPASS_REQUIRE
  exit "${exit_code}"
}

for command in mktemp setsid ssh rsync php; do
  require_local_command "${command}"
done

validate_configuration

if [[ ! -f "${PROJECT_ROOT}/composer.json" || ! -f "${PROJECT_ROOT}/composer.lock" \
  || ! -f "${PROJECT_ROOT}/package.json" || ! -f "${PROJECT_ROOT}/package-lock.json" ]]; then
  fail "composer.json, composer.lock, package.json, and package-lock.json are required for deterministic deploys."
fi

if [[ -z "${SSHPASS:-}" ]]; then
  read -rsp "SSH password for ${REMOTE_USER}@${REMOTE_HOST}: " SSHPASS
  echo
fi
[[ -n "${SSHPASS}" ]] || fail "The SSH password cannot be empty."

export SSHPASS
TEMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pos-faro-deploy.XXXXXX")"
chmod 700 "${TEMP_DIR}"
SSH_CONTROL_PATH="${TEMP_DIR}/ssh-control"
ASKPASS_SCRIPT="${TEMP_DIR}/ssh-askpass"
SSH_BASE_OPTS=(
  -o PreferredAuthentications=password
  -o PubkeyAuthentication=no
  -o NumberOfPasswordPrompts=1
  -o StrictHostKeyChecking=accept-new
  -o ConnectTimeout=15
  -o ControlMaster=auto
  -o "ControlPath=${SSH_CONTROL_PATH}"
  -o ControlPersist=120
)

cat >"${ASKPASS_SCRIPT}" <<'EOF'
#!/bin/sh
printf '%s' "${SSHPASS}"
EOF
chmod 700 "${ASKPASS_SCRIPT}"
export SSH_ASKPASS="${ASKPASS_SCRIPT}"
export SSH_ASKPASS_REQUIRE=force
export DISPLAY="${DISPLAY:-:0}"
trap cleanup EXIT

printf -v RSYNC_SSH_COMMAND '%q ' ssh "${SSH_BASE_OPTS[@]}"
RSYNC_SSH_COMMAND="${RSYNC_SSH_COMMAND% }"

SOURCE_TREE_EXCLUSIONS="\\( -path '${REMOTE_PATH}/vendor' \
  -o -path '${REMOTE_PATH}/node_modules' \
  -o -path '${REMOTE_PATH}/storage' \
  -o -path '${REMOTE_PATH}/bootstrap/cache' \
  -o -path '${REMOTE_PATH}/public/hot' \
  -o -path '${REMOTE_PATH}/public/storage' \
  -o -path '${REMOTE_PATH}/.env' \
  -o -path '${REMOTE_PATH}/.env.backup' \
  -o -path '${REMOTE_PATH}/.env.production' \
  -o -path '${REMOTE_PATH}/.env.d' \
  -o -path '${REMOTE_PATH}/.git' \
  -o -path '${REMOTE_PATH}/.git-crypt' \
  -o -path '${REMOTE_PATH}/tests' \
  -o -path '${REMOTE_PATH}/.deploy-dev-assets.sha256' \
  -o -path '${REMOTE_LOCK}' \\)"
SOURCE_PERMISSION_COMMAND="find '${REMOTE_PATH}' \
  ${SOURCE_TREE_EXCLUSIONS} -prune \
  -o -exec chown '${REMOTE_USER}:${REMOTE_GROUP}' {} + \
  -exec chmod ug+rwX {} + \
  -type d -exec chmod g+s {} +"
SOURCE_OWNERSHIP_COMMAND="find '${REMOTE_PATH}' \
  ${SOURCE_TREE_EXCLUSIONS} -prune \
  -o -exec chown '${REMOTE_APP_USER}:${REMOTE_GROUP}' {} +"

echo "[0/7] Opening SSH connection, validating the server, and acquiring the deploy lock..."
setsid ssh -fN "${SSH_BASE_OPTS[@]}" "${REMOTE_USER}@${REMOTE_HOST}" </dev/null
ssh_sudo "set -euo pipefail; \
  [[ -d '${REMOTE_PATH}' ]] || { echo 'ERROR: Remote application path does not exist: ${REMOTE_PATH}'; exit 1; }; \
  required_commands='awk bash composer find getent grep install npm php rsync sha256sum sort'; \
  required_commands=\"\${required_commands} supervisorctl systemctl tr xargs\"; \
  for command in \${required_commands}; do \
    command -v \"\${command}\" >/dev/null \
      || { echo \"ERROR: Required remote command not found: \${command}\"; exit 1; }; \
  done; \
  id -u '${REMOTE_APP_USER}' >/dev/null; \
  getent group '${REMOTE_GROUP}' >/dev/null; \
  systemctl cat '${WEB_SERVICE}' >/dev/null \
    || { echo 'ERROR: Web service not found: ${WEB_SERVICE}'; exit 1; }; \
  if [[ '${PHP_FPM_SERVICE}' != 'auto' ]]; then \
    systemctl cat '${PHP_FPM_SERVICE}' >/dev/null \
      || { echo 'ERROR: PHP-FPM service not found: ${PHP_FPM_SERVICE}'; exit 1; }; \
  fi; \
  id -nG '${REMOTE_USER}' | tr ' ' '\n' | grep -Fxq '${REMOTE_GROUP}' \
    || { echo 'ERROR: ${REMOTE_USER} is not a member of ${REMOTE_GROUP}.'; exit 1; }; \
  if ! mkdir '${REMOTE_LOCK}' 2>/dev/null; then \
    echo 'ERROR: Another development deploy is running (${REMOTE_LOCK}).'; exit 1; \
  fi; \
  chown '${REMOTE_USER}:${REMOTE_GROUP}' '${REMOTE_LOCK}'; \
  if [[ -f '${REMOTE_PATH}/.env' ]]; then \
    chown '${REMOTE_APP_USER}:${REMOTE_GROUP}' '${REMOTE_PATH}/.env'; \
    chmod 640 '${REMOTE_PATH}/.env'; \
  fi; \
  rm -f '${REMOTE_PATH}/bootstrap/cache/'*.php"
REMOTE_LOCKED=1

echo "[1/7] Enabling maintenance mode..."
ssh_sudo_as_app "if [[ -f '${REMOTE_PATH}/artisan' ]]; then php '${REMOTE_PATH}/artisan' down --retry=60; fi"
MAINTENANCE_ENABLED=1

echo "[2/7] Synchronizing the local workspace in place..."
ssh_sudo "${SOURCE_PERMISSION_COMMAND}"
rsync -rz --delete --delete-delay --human-readable --info=progress2,stats2 \
  "${PROJECT_ROOT}/" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/" \
  -e "${RSYNC_SSH_COMMAND}" \
  --inplace --perms --chmod=Dg+s,ug+w,Fo-w,+X --no-owner --no-group --omit-dir-times \
  --exclude=/vendor \
  --exclude=/node_modules \
  --exclude=/storage \
  --exclude=/bootstrap/cache \
  --exclude=/public/build \
  --exclude=/public/hot \
  --exclude=/public/storage \
  --exclude=/.env \
  --exclude=/.env.backup \
  --exclude=/.env.production \
  --exclude=/.env.d \
  --exclude=/.git \
  --exclude=/.git-crypt \
  --exclude=/.deploy-dev.lock \
  --exclude=/.deploy-dev-assets.sha256 \
  --exclude=/.idea \
  --exclude=/.vscode \
  --exclude=/tests \
  --exclude='*.log' \
  --exclude=.phpunit.result.cache \
  --exclude=.phpunit.cache

echo "[3/7] Building and deploying the development environment..."
cd "${PROJECT_ROOT}"
ENV_SOURCE_DIR="${TEMP_DIR}/env.d"
COMPILED_ENV="${TEMP_DIR}/.env"
cp -a "${PROJECT_ROOT}/.env.d" "${ENV_SOURCE_DIR}"
# The database dev overlay targets local SQLite development. Remote development
# keeps the base MySQL/Redis infrastructure while applying the other dev overlays.
rm -f "${ENV_SOURCE_DIR}/database.env.dev"
php artisan env-builder:build \
  --source="${ENV_SOURCE_DIR}" \
  --dev \
  --output="${COMPILED_ENV}"
rsync -rz --chmod=F600 \
  -e "${RSYNC_SSH_COMMAND}" \
  "${COMPILED_ENV}" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/.env.deploying"

echo "[4/7] Installing changed dependencies, migrating, and building changed assets..."
ssh_sudo "set -euo pipefail; \
  env_candidate='${REMOTE_PATH}/.env.deploying'; \
  test -s \"\${env_candidate}\"; \
  grep -Eq '^APP_ENV=local$' \"\${env_candidate}\" \
    || { echo 'ERROR: Remote development APP_ENV must be local.'; exit 1; }; \
  grep -Eq '^QUEUE_CONNECTION=redis$' \"\${env_candidate}\" \
    || { echo 'ERROR: Remote development QUEUE_CONNECTION must be redis.'; exit 1; }; \
  grep -Eq '^DB_CONNECTION=mysql$' \"\${env_candidate}\" \
    || { echo 'ERROR: Remote development DB_CONNECTION must be mysql.'; exit 1; }; \
  for key in APP_KEY APP_URL CARONTE_URL CARONTE_APP_CN CARONTE_APP_SECRET \
    CARONTE_OIDC_CLIENT_ID CARONTE_OIDC_CLIENT_SECRET CARONTE_OIDC_REDIRECT_URI \
    DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do \
    grep -Eq \"^\${key}=[^[:space:]].*\$\" \"\${env_candidate}\" \
      && ! grep -Eq \"^\${key}=[[:space:]]*(\\\"\\\"|'')[[:space:]]*\$\" \"\${env_candidate}\" \
      || { echo \"ERROR: \${key} is missing or empty in the deployed .env.\"; exit 1; }; \
  done; \
  chown '${REMOTE_APP_USER}:${REMOTE_GROUP}' \"\${env_candidate}\"; \
  chmod 640 \"\${env_candidate}\"; \
  mv -f \"\${env_candidate}\" '${REMOTE_PATH}/.env'; \
  install -d -o '${REMOTE_APP_USER}' -g '${REMOTE_GROUP}' -m 2775 \
    '${REMOTE_PATH}/public/build' '${REMOTE_PATH}/bootstrap/cache' \
    '${REMOTE_PATH}/storage/framework/views' '${REMOTE_PATH}/storage/framework/cache/data' \
    '${REMOTE_PATH}/storage/framework/sessions' '${REMOTE_PATH}/storage/app/public' \
    '${REMOTE_PATH}/storage/logs'; \
  if [[ -d '${REMOTE_PATH}/vendor' ]]; then \
    chown -R '${REMOTE_APP_USER}:${REMOTE_GROUP}' '${REMOTE_PATH}/vendor'; \
  fi; \
  if [[ -d '${REMOTE_PATH}/node_modules/.vite' ]]; then \
    echo '==> Removing stale Vite dependency cache'; \
    rm -rf -- '${REMOTE_PATH}/node_modules/.vite'; \
  fi; \
  ${SOURCE_OWNERSHIP_COMMAND}"

ssh_sudo_as_app "set -euo pipefail; \
  cd '${REMOTE_PATH}'; \
  rm -f bootstrap/cache/*.php; \
  php artisan optimize:clear; \
  hash_files() { sha256sum \"\$@\" | sha256sum | awk '{print \$1}'; }; \
  composer_hash=\"\$(hash_files composer.json composer.lock)\"; \
  composer_stamp='vendor/.deploy-dev-dependencies.sha256'; \
  if [[ '${FORCE_DEPENDENCY_INSTALL}' == 'true' || ! -s vendor/autoload.php \
    || ! -f \"\${composer_stamp}\" || \"\$(<\"\${composer_stamp}\")\" != \"\${composer_hash}\" ]]; then \
    echo '==> Installing Composer dependencies'; \
    composer install --no-interaction --no-progress --prefer-dist; \
    printf '%s\n' \"\${composer_hash}\" >\"\${composer_stamp}\"; \
  else \
    echo '==> Composer dependencies are unchanged; skipping install'; \
  fi; \
  npm_hash=\"\$(hash_files package.json package-lock.json)\"; \
  npm_stamp='node_modules/.deploy-dev-dependencies.sha256'; \
  if [[ '${FORCE_DEPENDENCY_INSTALL}' == 'true' || ! -x node_modules/.bin/vite \
    || ! -f \"\${npm_stamp}\" || \"\$(<\"\${npm_stamp}\")\" != \"\${npm_hash}\" ]]; then \
    echo '==> Installing Node dependencies'; \
    npm ci --prefer-offline --no-audit --no-fund; \
    printf '%s\n' \"\${npm_hash}\" >\"\${npm_stamp}\"; \
  else \
    echo '==> Node dependencies are unchanged; skipping install'; \
  fi; \
  php artisan migrate --force; \
  asset_hash=\"\$({ \
    sha256sum package.json package-lock.json postcss.config.js tailwind.config.js vite.config.js .env; \
    find resources -type f -print0 | sort -z | xargs -0 sha256sum; \
  } | sha256sum | awk '{print \$1}')\"; \
  asset_stamp='.deploy-dev-assets.sha256'; \
  if [[ '${FORCE_ASSET_BUILD}' == 'true' || '${FORCE_DEPENDENCY_INSTALL}' == 'true' \
    || ! -f public/build/manifest.json \
    || ! -f \"\${asset_stamp}\" || \"\$(<\"\${asset_stamp}\")\" != \"\${asset_hash}\" ]]; then \
    echo '==> Building frontend assets'; \
    npm run build; \
    printf '%s\n' \"\${asset_hash}\" >\"\${asset_stamp}\"; \
  else \
    echo '==> Frontend inputs are unchanged; skipping build'; \
  fi; \
  php artisan config:cache; \
  php artisan route:clear; \
  php artisan view:cache"

echo "[5/7] Reloading services and restarting Laravel processes..."
ssh_sudo "set -euo pipefail; \
  php_fpm_service='${PHP_FPM_SERVICE}'; \
  if [[ \"\${php_fpm_service}\" == 'auto' ]]; then \
    php_fpm_service=\"\$( \
      systemctl list-units --type=service --state=active --no-legend 'php*-fpm.service' \
        | awk 'NR == 1 { print \$1 }' \
    )\"; \
  fi; \
  if [[ -n \"\${php_fpm_service}\" ]]; then \
    echo \"==> Reloading \${php_fpm_service}\"; \
    systemctl reload \"\${php_fpm_service}\"; \
  else \
    echo 'INFO: No active PHP-FPM service found; PHP is managed by the web service.'; \
  fi; \
  systemctl reload '${WEB_SERVICE}'; \
  supervisorctl reread; \
  supervisorctl update; \
  restart_supervisor_program() { \
    local program=\"\$1\"; \
    if ! supervisorctl status \"\${program}:*\" >/dev/null 2>&1; then \
      echo \"INFO: Supervisor program \${program} is not configured; skipping.\"; \
      return 0; \
    fi; \
    supervisorctl restart \"\${program}:*\" || supervisorctl start \"\${program}:*\"; \
  }; \
  restart_supervisor_program '${APP_NAME}-worker'; \
  restart_supervisor_program '${APP_NAME}-scheduler'"

echo "[6/7] Verifying application and process health..."
ssh_sudo_as_app "set -euo pipefail; \
  cd '${REMOTE_PATH}'; \
  php artisan --version >/dev/null; \
  php artisan migrate:status --no-ansi >/dev/null; \
  php artisan queue:monitor redis:default --max=1000000 >/dev/null; \
  php artisan route:list --path=/ --no-ansi | grep -qE 'GET|HEAD'; \
  test -f public/build/manifest.json"
ssh_sudo "set -euo pipefail; \
  validate_supervisor_program() { \
    local program=\"\$1\"; \
    local status; \
    local pids; \
    local pid; \
    status=\"\$(supervisorctl status \"\${program}:*\" 2>&1 || true)\"; \
    if [[ -z \"\${status}\" || \"\${status}\" == *'no such group'* \
      || \"\${status}\" == *'no such process'* ]]; then \
      echo \"INFO: Supervisor program \${program} is not configured; skipping validation.\"; \
      return 0; \
    fi; \
    printf '%s\n' \"\${status}\" \
      | awk 'NF { found=1; if (\$2 != \"RUNNING\") failed=1 } END { exit (!found || failed) }' \
      || { echo \"ERROR: Not every \${program} process is RUNNING.\"; return 1; }; \
    pids=\"\$(printf '%s\\n' \"\${status}\" \
      | awk '\$2 == \"RUNNING\" { for (i = 1; i <= NF; i++) { if (\$i == \"pid\") { gsub(/,/, \"\", \$(i + 1)); print \$(i + 1) } } }')\"; \
    [[ -n \"\${pids}\" ]] || { echo \"ERROR: No PIDs found for \${program}.\"; return 1; }; \
    for pid in \${pids}; do \
      [[ \"\${pid}\" =~ ^[0-9]+$ ]] \
        || { echo \"ERROR: Invalid PID for \${program}: \${pid}\"; return 1; }; \
      tr '\\0' ' ' < \"/proc/\${pid}/cmdline\" | grep -Fq '${REMOTE_PATH}/artisan' \
        || { echo \"ERROR: \${program} PID \${pid} is not running this application.\"; return 1; }; \
    done; \
  }; \
  validate_supervisor_program '${APP_NAME}-worker'; \
  validate_supervisor_program '${APP_NAME}-scheduler'"

echo "[7/7] Disabling maintenance mode..."
ssh_sudo_as_app "php '${REMOTE_PATH}/artisan' up"
MAINTENANCE_ENABLED=0

echo "Development deploy completed successfully without creating a release."
