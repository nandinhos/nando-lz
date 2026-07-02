# nando-lz — imagem de toolchain PHP 8.4 para os modos dev/deploy (PRD §13).
# ponytail: usa `php artisan serve` (simples e funcional). Upgrade path para
# produção pesada: php-fpm + nginx ou Laravel Octane — documentado em docs/DOCKER.md.
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl gnupg \
        libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000
ENTRYPOINT ["entrypoint"]
