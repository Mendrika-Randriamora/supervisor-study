#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Laravel fonctionne avec de vraies variables d'environnement (Docker), mais on
# garde un .env pour les commandes Artisan et la génération de la clé.
if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Base SQLite persistante.
sqlite_path="${DB_DATABASE:-/data/database.sqlite}"
mkdir -p "$(dirname "$sqlite_path")"
if [ ! -f "$sqlite_path" ]; then
    touch "$sqlite_path"
fi

chown -R www-data:www-data storage bootstrap/cache "$(dirname "$sqlite_path")"
chmod -R ug+rwX storage bootstrap/cache "$(dirname "$sqlite_path")"

# Clé d'application : fournie par l'environnement, sinon générée une seule fois
# et conservée dans le volume de données pour rester stable entre les redémarrages.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s /data/app.key ]; then
        php artisan key:generate --show --no-interaction > /data/app.key
    fi

    APP_KEY="$(tr -d '\r\n' < /data/app.key)"
    export APP_KEY
fi

php artisan package:discover --ansi >/dev/null

php artisan storage:link --force >/dev/null 2>&1 || true

# Applique les migrations manquantes puis met en cache la configuration.
php artisan migrate --force --no-interaction

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Les fichiers générés par les commandes ci-dessus appartiennent à root.
chown -R www-data:www-data storage bootstrap/cache "$(dirname "$sqlite_path")"

exec "$@"
