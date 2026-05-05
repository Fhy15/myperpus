#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure vendor exists (composer install was run in builder, but be resilient)
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || true
fi

# Ensure directories and permissions
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# Optionally run migrations if the environment variable is set
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force || true
fi

# Optimize caches for production when APP_ENV=production
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

# Create storage symlink if not present
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Start the Laravel development server (bind to $PORT for Railway)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
