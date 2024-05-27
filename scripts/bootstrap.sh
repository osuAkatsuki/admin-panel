#!/usr/bin/env bash
set -eo pipefail

if [ -z "$APP_ENV" ]; then
  echo "Please set APP_ENV"
  exit 1
fi

if [ -z "$APP_COMPONENT" ]; then
  echo "Please set APP_COMPONENT"
  exit 1
fi

if [[ $PULL_SECRETS_FROM_VAULT -eq 1 ]]; then
  # TODO: revert to $APP_ENV
  akatsuki vault get performance-service production-k8s -o .env
  source .env
fi

# TODO: await deps

exec nginx -g daemon off;
