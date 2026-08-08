#!/usr/bin/env bash

# shellcheck disable=SC2329

# Shared deployment validation and Supervisor helpers.

supervisorctl_bin() {
  if [[ -n "${SUPERVISORCTL_BIN:-}" ]]; then
    printf '%s' "${SUPERVISORCTL_BIN}"
    return 0
  fi

  command -v supervisorctl
}

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "ERROR: Required command not found: $1"
    exit 1
  fi
}

require_env_var() {
  local env_file="$1"
  local key="$2"

  if ! grep -Eq "^[[:space:]]*${key}=[[:space:]]*[^[:space:]].*$" "${env_file}"; then
    echo "ERROR: ${key} is missing or empty in ${env_file}."
    exit 1
  fi
}

supervisor_program_running() {
  local program="$1"
  local status
  local supervisorctl

  supervisorctl="$(supervisorctl_bin)"

  status="$(sudo -n "${supervisorctl}" status "${program}:*" 2>&1 || true)"
  awk -v program="${program}" '$1 ~ ("^" program "(_|:)") && $2 == "RUNNING" {found=1} END {exit(found ? 0 : 1)}' <<<"${status}"
}

show_process_diagnostics() {
  local program="$1"
  local log_file="$2"
  local supervisorctl

  supervisorctl="$(supervisorctl_bin)"

  echo "==> Supervisor status for ${program}"
  sudo -n "${supervisorctl}" status "${program}:*" 2>&1 || true
  if [[ -f "${log_file}" ]]; then
    echo "==> Recent output from ${log_file}"
    tail -n 80 "${log_file}" || true
  fi
}

restart_supervisor_program() {
  local program="$1"
  local log_file="$2"
  local supervisorctl

  supervisorctl="$(supervisorctl_bin)"

  echo "==> Restarting ${program}"
  if ! sudo -n "${supervisorctl}" restart "${program}:*"; then
    echo "==> Restart failed; attempting to start ${program}"
    sudo -n "${supervisorctl}" start "${program}:*"
  fi

  if ! supervisor_program_running "${program}"; then
    echo "ERROR: ${program} is not RUNNING."
    show_process_diagnostics "${program}" "${log_file}"
    exit 1
  fi
}

validate_laravel_runtime() {
  local app_path="$1"

  [[ -f "${app_path}/artisan" ]] || { echo "ERROR: Missing ${app_path}/artisan."; exit 1; }
  php "${app_path}/artisan" --version >/dev/null
  php "${app_path}/artisan" schedule:list >/dev/null

  if ! php "${app_path}/artisan" config:show queue | grep -Eq 'default.*redis|"default"[[:space:]]*=>[[:space:]]*"redis"'; then
    echo "ERROR: QUEUE_CONNECTION must resolve to redis."
    exit 1
  fi

  php "${app_path}/artisan" queue:monitor redis:default --max=1000000 >/dev/null
}
