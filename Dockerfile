FROM php:8.4-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1

WORKDIR /usr/src/app

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        gettext-base \
        git \
        unzip \
        python3-venv \
        $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# 8.4.27 fixes CVE-2026-109618 and CVE-2026-109619; refuse to build anything older
RUN php -r 'exit(version_compare(PHP_VERSION, "8.4.27", ">=") ? 0 : 1);'

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# Unused by the app, and an attack surface PHP recommends disabling (GHSA-6wmf-4rv5-p3rp)
RUN echo "session.upload_progress.enabled = 0" > "$PHP_INI_DIR/conf.d/zz-admin-panel.ini"
# Keep the container environment (e.g. VAULT_TOKEN) out of PHP workers, and only accept
# FastCGI from this container's nginx rather than the whole Docker network
RUN printf "[www]\nclear_env = yes\nlisten = 127.0.0.1:9000\n" > /usr/local/etc/php-fpm.d/zzz-admin-panel.conf

RUN python3 -m venv /opt/akatsuki-cli \
    && /opt/akatsuki-cli/bin/pip install git+https://github.com/osuAkatsuki/akatsuki-cli \
    && ln -s /opt/akatsuki-cli/bin/akatsuki /usr/local/bin/akatsuki

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY . .
COPY scripts /scripts

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction

COPY nginx/nginx.conf /etc/nginx/templates/nginx.conf.template
COPY nginx/fastcgi.conf /etc/nginx/fastcgi.conf
COPY nginx/sites-enabled/ /etc/nginx/templates/sites-enabled/

ENTRYPOINT [ "/scripts/bootstrap.sh" ]
