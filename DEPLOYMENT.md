# Deployment MyPerpus

Checklist singkat untuk production.

## 1. Environment

```bash
cp .env.production.example .env
php artisan key:generate
```

Isi semua secret di `.env`: database, mail, `APP_URL`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`.

Wajib untuk production:

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
FILESYSTEM_DISK=local
```

## 2. Install dependency

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```


## 3. Database

```bash
php artisan migrate --force
php artisan db:seed --force
```

Seeder production hanya membuat role dan admin dari `ADMIN_EMAIL`/`ADMIN_PASSWORD`. Data demo dan password `password` hanya dibuat pada environment non-production.

## 4. Cache production

```bash
docker compose exec php php artisan optimize:clear
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
docker compose exec php php artisan event:cache
```

## 5. Storage dan permission

```bash
php artisan storage:link
chown www-data:www-data .env storage bootstrap/cache
chmod 640 .env
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Untuk Docker development dengan bind mount dari host, `.env` perlu bisa dibaca container PHP. Jika container tidak bisa membaca `APP_KEY`, gunakan `chmod 644 .env` di mesin lokal saja.

PDF buku baru disimpan di disk private `local` dan dibuka lewat route terautentikasi `/baca-online/{buku}/pdf`. Konfigurasi Nginx dan Apache di repo juga menolak akses langsung ke `/storage/buku/pdf` dan `/storage/sample-books`.

Jika ada PDF lama di `storage/app/public/buku/pdf`, pindahkan ke `storage/app/private/buku/pdf` dengan path relatif yang sama lalu hapus salinan publiknya memakai user/permission yang benar.

## 6. Worker dan scheduler

Jalankan queue worker lewat supervisor/systemd:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Tambahkan scheduler:

```cron
* * * * * cd /path/to/myperpus_full && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Audit sebelum go-live

```bash
composer audit
npm audit --omit=dev
php artisan test
```

Pastikan web server hanya mengarah ke folder `public`, HTTPS aktif, `.env` tidak ikut repository, dan `public/hot` tidak ada di server production.

## Railway (Docker) notes

If you deploy to Railway using the included `Dockerfile`, Railway will build the image using the Dockerfile steps. Key notes:

- The Dockerfile performs a multi-stage build (node build, composer install, final PHP image). Use the repository Dockerfile and set Railway to deploy from Dockerfile.
- Railway containers are ephemeral. Do not rely on local `storage` for long-term file persistence; configure `FILESYSTEM_DISK` to point to S3/Spaces if you need persistence.
- Expose port `8080` in Railway service settings (the Dockerfile uses `PORT=8080`).
- If build fails due to permissions or missing directories (e.g. errors creating `storage/logs`), ensure the Dockerfile creates `storage` directories before `composer install`. The Dockerfile in this repo already pre-creates `storage/logs` and sets permissive write mode during build to avoid such build-time failures.
- If you prefer Railway to run migrations on first start, set environment variable `RUN_MIGRATIONS=true` (start script checks it). Be cautious: running migrations automatically in production may be undesirable without backups.

Railway build troubleshooting tips:

- If you see `composer` errors about running as root, it's usually a warning. The Dockerfile sets `COMPOSER_ALLOW_SUPERUSER=1` for the build stage to suppress plugin restrictions when necessary.
- If `composer install` still fails during Railway build, capture the full build logs from Railway and search for the first error; often it's a missing PHP extension or permission on `storage` (both addressed by this Dockerfile).

