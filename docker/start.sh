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
  echo "RUN_MIGRATIONS=true — checking environment..."
  # Ensure PDO MySQL extension is available
  php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" >/dev/null 2>&1
  if [ $? -ne 0 ]; then
    echo "pdo_mysql extension not loaded. Skipping automatic migrations."
  else
    # Wait for DB to be reachable (tries for up to 60s)
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    DB_RETRIES=60
    i=0
    echo "Waiting for database ${DB_HOST}:${DB_PORT} (timeout ${DB_RETRIES}s)"
    while ! php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); } catch (Exception \$e) { exit(1); } exit(0);" >/dev/null 2>&1; do
      i=$((i+1))
      if [ $i -ge $DB_RETRIES ]; then
        echo "Timed out waiting for database. Skipping migrations."
        break
      fi
      sleep 1
    done

    if [ $i -lt $DB_RETRIES ]; then
      echo "Database available — running migrations and seeders"
      php artisan migrate --force || true
      php artisan db:seed --force || true
    fi
  fi
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
