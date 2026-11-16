FROM php:8.3-cli-alpine

# Ekstensi PostgreSQL untuk PHP
RUN apk add --no-cache git unzip postgresql-dev \
 && docker-php-ext-install pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Buat skeleton Laravel segar, lalu timpa dengan kode admin kita (folder overlay/)
RUN composer create-project laravel/laravel:^12.0 /app --no-interaction --prefer-dist \
 && rm -f /app/.env

WORKDIR /app
COPY overlay/ /app/
COPY entrypoint.sh /entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_NAME="Warkop Admin" \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8000/up >/dev/null || exit 1

CMD ["sh", "/entrypoint.sh"]
