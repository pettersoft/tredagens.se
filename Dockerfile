# syntax=docker/dockerfile:1

# ---------- base: shared Apache + PHP setup ----------
FROM php:8.3-apache AS base

RUN a2enmod rewrite headers expires remoteip \
    && docker-php-ext-install opcache

# Apache listens on 8080 so it can run as a non-root user.
# Caddy reaches the container as tredagens-web:8080 on the "proxy" network.
RUN echo 'Listen 8080' > /etc/apache2/ports.conf \
    && a2disconf other-vhosts-access-log

COPY docker/apache/security.conf /etc/apache2/conf-available/zz-security.conf
COPY docker/apache/site.conf /etc/apache2/sites-available/000-default.conf
RUN a2enconf zz-security

# Writable home for the SQLite statistics database. Mounted as a named volume;
# Docker seeds a new volume with this directory's www-data ownership.
RUN mkdir -p /var/www/data && chown www-data:www-data /var/www/data && chmod 750 /var/www/data

EXPOSE 8080

# ---------- dev: xdebug, source bind-mounted ----------
FROM base AS dev

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# ---------- prod: code baked in, hardened, non-root ----------
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/prod.ini /usr/local/etc/php/conf.d/zz-prod.ini

# Owned by root: www-data can read but not modify the site. Permissions are
# set explicitly so they don't depend on the builder or the checkout's umask.
COPY --chown=root:root src/ /var/www/html/
RUN chown root:root /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} +

USER www-data

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost:8080/ || exit 1
