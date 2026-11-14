# ---------- Build del frontend con Vite ----------
FROM node:20-alpine AS build-frontend
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci && npm run build

# ---------- PHP ----------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y git unzip libzip-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

COPY --from=build-frontend /app/public/build ./public/build

ENV PORT=10000
EXPOSE 10000

# Render: migra y sirve
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
