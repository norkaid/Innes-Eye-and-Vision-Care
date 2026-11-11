#!/usr/bin/env bash
# Update script for shared hosting (e.g. Hostinger). Run from the project folder over SSH:
#   bash deploy.sh
set -e
php artisan down --retry=30 || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RoleSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
echo "Deployed."
