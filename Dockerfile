# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# 1. Assets frontend (Bootstrap, Bootstrap Icons, Chart.js) via Vite
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------------------------------------------------------------------------
# 2. Dépendances PHP de production (sans les paquets de développement)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-progress \
        --no-interaction \
        --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# 3. Image d'exécution : Apache + PHP 8.5 + SQLite
# ---------------------------------------------------------------------------
FROM php:8.5-apache AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8000 \
    APP_LOCALE=fr \
    APP_FALLBACK_LOCALE=fr \
    APP_TIMEZONE=UTC \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning

RUN a2enmod rewrite headers expires \
    && rm -f /etc/apache2/sites-available/*.conf /etc/apache2/sites-enabled/*.conf

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/security.conf /etc/apache2/conf-available/zz-security.conf
COPY docker/php/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# Code applicatif + dépendances PHP, puis assets compilés.
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && a2enconf zz-security \
    && ln -sf /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        /data \
    && chown -R www-data:www-data storage bootstrap/cache database /data \
    && chmod -R ug+rwX storage bootstrap/cache database /data

# Données persistantes : base SQLite et fichiers privés (PDF, images, annotations).
VOLUME ["/data", "/var/www/html/storage/app"]

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
