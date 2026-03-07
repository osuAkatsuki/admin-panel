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
  akatsuki vault get admin-panel $APP_ENV -o .env
  source .env
fi

# Create session directory for fallback
mkdir -p /tmp/php_sessions
chmod 755 /tmp/php_sessions

# Template nginx configs with APP_PORT
envsubst '${APP_PORT}' < /etc/nginx/templates/nginx.conf.template > /etc/nginx/nginx.conf
mkdir -p /etc/nginx/sites-enabled
for tmpl in /etc/nginx/templates/sites-enabled/*.conf; do
  envsubst '${APP_PORT}' < "$tmpl" > "/etc/nginx/sites-enabled/$(basename "$tmpl")"
done

service php7.2-fpm start

exec nginx -g "daemon off;"
