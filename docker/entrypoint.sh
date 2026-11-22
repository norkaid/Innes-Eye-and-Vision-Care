#!/bin/sh
# Prepares the app every time the container starts, then runs Apache.
set -e
cd /var/www/html

# Folders the app writes to (they live on a Docker volume and may start empty).
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Application key: use APP_KEY from the environment, or create one once and keep it on the volume.
if [ -z "$APP_KEY" ]; then
    if [ ! -s storage/app/.app_key ]; then
        php -r "echo 'base64:'.base64_encode(random_bytes(32));" > storage/app/.app_key
        echo "Created a new application key (kept in the storage volume)."
    fi
    APP_KEY="$(cat storage/app/.app_key)"
    export APP_KEY
fi

# SQLite: create the database file on the volume if it does not exist yet.
if [ "${DB_CONNECTION:-mysql}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/data/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    [ -f "$DB_FILE" ] || touch "$DB_FILE"
fi

# MySQL: wait until the database accepts connections.
if [ "${DB_CONNECTION:-mysql}" = "mysql" ] || [ "${DB_CONNECTION}" = "mariadb" ]; then
    echo "Waiting for the database at ${DB_HOST:-db}..."
    i=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Throwable $e) { exit(1); }'; do
        i=$((i+1))
        [ "$i" -ge 60 ] && echo "Database not reachable – check the DB_ settings." && exit 1
        sleep 2
    done
fi

chown -R www-data:www-data storage bootstrap/cache database

php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
# Demo content (for trying the system only) is loaded once, never when APP_ENV=production.
if [ "${SEED_DEMO_DATA:-false}" = "true" ] && [ ! -f storage/app/.demo_seeded ]; then
    if php artisan db:seed --class=DemoContentSeeder --force; then
        touch storage/app/.demo_seeded
    else
        echo "Demo data not loaded (it is never loaded when APP_ENV=production)."
    fi
fi
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
