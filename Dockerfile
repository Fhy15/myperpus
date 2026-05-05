# Multi-stage Dockerfile for Laravel + Vite
# Stages: node builder -> composer builder -> final PHP image

#### Node builder: build front-end assets
FROM node:18-bullseye-slim AS node_builder
WORKDIR /app
ENV PATH /app/node_modules/.bin:$PATH
COPY package*.json ./
RUN npm ci --silent
COPY . .
RUN npm run build --silent

#### Composer builder: install PHP dependencies
FROM composer:2 AS composer_builder
# Use the final app path so any scripts that rely on base_path() write to the same location
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
# Ensure storage/bootstrap dirs exist so composer scripts can write logs during install
RUN mkdir -p storage/logs bootstrap/cache && chmod -R 0777 storage bootstrap/cache || true
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --optimize

#### Final image: PHP runtime
FROM php:8.4-cli-bullseye
WORKDIR /var/www/html

# System deps and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
  libpng-dev libjpeg-dev libfreetype6-dev libzip-dev zip unzip git curl libonig-dev libxml2-dev \
  default-libmysqlclient-dev default-mysql-client \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install pdo pdo_mysql gd zip bcmath sockets opcache \
  && pecl install redis && docker-php-ext-enable redis \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy application files from composer builder (use same app path)
COPY --from=composer_builder /var/www/html/ /var/www/html/

# Copy built frontend assets from node builder
COPY --from=node_builder /app/public/build /var/www/html/public/build
COPY --from=node_builder /app/public/asset /var/www/html/public/asset

ENV COMPOSER_ALLOW_SUPERUSER=1

# Entrypoint script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh


RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chmod -R 777 storage bootstrap/cache

# File permissions for webserver user
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache || true
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

ENV PORT 8080
EXPOSE 8080
ENV APP_ENV=production
ENV APP_DEBUG=false
CMD ["start.sh"]
