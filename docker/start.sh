#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# If `DATABASE_URL` is provided (e.g. Railway) but `DB_HOST` is empty,
# parse it into DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE
# so the start script and migration wait logic work correctly.
if [ -z "${DB_HOST:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
  echo "Parsing DATABASE_URL into DB_* env vars..."
  eval "$(php -r '$u=getenv("DATABASE_URL"); $p=parse_url($u); if($p){ $out=""; if(isset($p["host"])) $out .= "DB_HOST={$p["host"]}\n"; if(isset($p["port"])) $out .= "DB_PORT={$p["port"]}\n"; if(isset($p["user"])) $out .= "DB_USERNAME={$p["user"]}\n"; if(isset($p["pass"])) $out .= "DB_PASSWORD={$p["pass"]}\n"; if(isset($p["path"])) $out .= "DB_DATABASE=".ltrim($p["path"],"/")."\n"; echo $out; }')"
fi

# Generate APP_KEY if missing or invalid in production
if [ "${APP_ENV:-production}" = "production" ] && ( [ -z "${APP_KEY:-}" ] || [ "${APP_KEY:0:7}" != "base64:" ] || [ ${#APP_KEY} -ne 51 ] ); then
  echo "APP_KEY invalid or missing, generating new one..."
  export APP_KEY=$(php artisan key:generate --show --no-interaction --quiet)
  echo "New APP_KEY: ${APP_KEY:0:20}..."
fi

# Ensure vendor exists (composer install was run in builder, but be resilient)
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || true
fi

# Ensure directories and permissions
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# Railway MySQL var mapping (Railway uses MYSQLHOST, Laravel expects DB_HOST)
export DB_CONNECTION=mysql
export DB_HOST="${MYSQLHOST:-${DB_HOST:-127.0.0.1}}"
export DB_PORT="${MYSQLPORT:-${DB_PORT:-3306}}"
export DB_DATABASE="${MYSQLDATABASE:-${DB_DATABASE:-railway}}"
export DB_USERNAME="${MYSQLUSER:-${DB_USERNAME:-root}}"
export DB_PASSWORD="${MYSQLPASSWORD:-${DB_PASSWORD}}"

# Optionally run migrations if the environment variable is set
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "RUN_MIGRATIONS=true — checking environment..."
  echo "DB: $DB_HOST:$DB_PORT/$DB_DATABASE"
  # Ensure PDO MySQL extension is available
  php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" >/dev/null 2>&1
  if [ $? -ne 0 ]; then
    echo "pdo_mysql extension not loaded. Skipping automatic migrations."
  else
    # Wait for DB to be reachable (tries for up to 60s)
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
      if [ "${MIGRATE_FRESH:-false}" = "true" ]; then
        echo "MIGRATE_FRESH=true — running migrate:fresh (will drop all tables)"
        php artisan migrate:fresh --force || true
      else
        php artisan migrate --force || true
      fi

      # Seed the database (DatabaseSeeder may require ADMIN_EMAIL/ADMIN_PASSWORD in production)
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


#!/bin/sh

# 🔥 WAIT ENV READY
php artisan config:clear
php artisan cache:clear

# 🔥 BARU CACHE SETELAH ENV MASUK
php artisan config:cache

# RUN SERVER
php artisan serve --host=0.0.0.0 --port=${PORT}