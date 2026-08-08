#!/usr/bin/env bash

# shellcheck disable=SC2034

# Shared deploy defaults. Environment variables can override any value.

: "${APP_NAME:=pos-faro}"
: "${APP_ROOT:=/ometra/${APP_NAME}}"
: "${DEPLOY_USER:=deploy}"
: "${WEB_GROUP:=www-data}"
: "${PHP_VERSION:=8.4}"
: "${KEEP_RELEASES:=5}"

# App deploy defaults.
: "${REPO_URL:=https://github.com/gruelasjr/pos.git}"
: "${BRANCH:=main}"
: "${SOURCE_ARCHIVE:=}"

# Initial server setup defaults.
: "${DOMAIN:=pos.example.com}"
: "${CERTBOT_EMAIL:=domains@ometra.mx}"
: "${CERTBOT_STAGING:=false}"
: "${ENABLE_CERTBOT:=true}"
: "${SUDOERS_FILE:=/etc/sudoers.d/${APP_NAME}-deploy}"
: "${APP_EXEC_ROOT:=${APP_ROOT}/current}"
: "${WEB_USER:=www-data}"
: "${QUEUE_TIMEOUT:=120}"
