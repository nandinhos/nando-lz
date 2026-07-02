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

# Usuário não-root com o UID do host (padrão 1000): tudo que o container criar
# no bind-mount (vendor/, node_modules/, .env, storage/) continua editável no
# host, e a troca Docker↔Local não deixa arquivos root-owned para trás.
ARG UID=1000
RUN useradd -m -u "${UID}" app

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER app

EXPOSE 8000
ENTRYPOINT ["entrypoint"]
