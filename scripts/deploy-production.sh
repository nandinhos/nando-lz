#!/usr/bin/env bash

set -Eeuo pipefail

# Publica uma release imutável na VPS que atende nandolz.fssdev.com.br.
# Este script é executado pelo launcher restrito em /usr/local/sbin/nandolz-deploy.

readonly APP_ROOT='/var/www/nandolz.fssdev.com.br'
readonly RELEASES_DIR="$APP_ROOT/releases"
readonly SHARED_DIR="$APP_ROOT/shared"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly REPOSITORY='https://github.com/nandinhos/nando-lz.git'
readonly PHP_BINARY='/usr/bin/php8.5'
readonly HEALTH_HOST='nandolz.fssdev.com.br'
readonly KEEP_RELEASES=5

release_dir=''
previous_release=''
maintenance_enabled=false
release_activated=false

fail() {
    local exit_code="$?"

    trap - ERR
    printf 'Deploy falhou (código %s).\n' "$exit_code" >&2

    if [ "$release_activated" = true ]; then
        printf 'Restaurando release anterior: %s\n' "$previous_release" >&2
        switch_current "$previous_release" || true
        systemctl reload php8.5-fpm || true
    fi

    if [ "$maintenance_enabled" = true ]; then
        "$PHP_BINARY" "$previous_release/artisan" up || true
    fi

    if [ -n "$release_dir" ]; then
        printf 'A release não publicada foi preservada para diagnóstico: %s\n' "$release_dir" >&2
    fi

    exit "$exit_code"
}

switch_current() {
    local target="$1"
    local next_link="$APP_ROOT/.current-next-$$"

    ln -s "$target" "$next_link"
    mv -Tf "$next_link" "$CURRENT_LINK"
}

check_health() {
    curl --fail --silent --show-error \
        --retry 5 \
        --retry-delay 2 \
        --connect-timeout 5 \
        --max-time 15 \
        --resolve "$HEALTH_HOST:443:127.0.0.1" \
        "https://$HEALTH_HOST/up" \
        >/dev/null
}

cleanup_old_releases() {
    local active_release index name candidate

    active_release="$(readlink -f "$CURRENT_LINK")"
    index=0

    while IFS= read -r name; do
        [ -n "$name" ] || continue
        index=$((index + 1))
        [ "$index" -le "$KEEP_RELEASES" ] && continue

        candidate="$RELEASES_DIR/$name"
        [ "$candidate" = "$active_release" ] && continue
        [ -d "$candidate" ] || continue

        if ! rm -rf --one-file-system -- "$candidate"; then
            printf 'Não foi possível limpar a release antiga: %s\n' "$candidate" >&2
        fi
    done < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort -r)
}

trap fail ERR

if [ "$#" -ne 1 ] || ! [[ "$1" =~ ^[0-9a-f]{40}$ ]]; then
    printf 'Uso: %s <SHA-completo-da-main>.\n' "$0" >&2
    exit 64
fi

readonly DEPLOY_SHA="$1"
readonly SHORT_SHA="${DEPLOY_SHA:0:8}"

[ "$(id -u)" -eq 0 ] || {
    printf 'O deploy precisa ser executado como root para recarregar o PHP-FPM.\n' >&2
    exit 77
}

for required_path in "$RELEASES_DIR" "$SHARED_DIR/storage" "$SHARED_DIR/.env" "$CURRENT_LINK"; do
    [ -e "$required_path" ] || {
        printf 'Pré-requisito ausente: %s\n' "$required_path" >&2
        exit 78
    }
done

previous_release="$(readlink -f "$CURRENT_LINK")"
[ -f "$previous_release/artisan" ] || {
    printf 'A release ativa não possui artisan: %s\n' "$previous_release" >&2
    exit 78
}

exec 9>"$APP_ROOT/deploy.lock"
flock -n 9 || {
    printf 'Já existe um deploy em andamento.\n' >&2
    exit 75
}

release_dir="$RELEASES_DIR/$(date -u '+%Y%m%d_%H%M%S')_$SHORT_SHA"
[ ! -e "$release_dir" ] || {
    printf 'O diretório de release já existe: %s\n' "$release_dir" >&2
    exit 73
}

printf 'Preparando release %s.\n' "$DEPLOY_SHA"
git clone --quiet --no-checkout "$REPOSITORY" "$release_dir"
git -C "$release_dir" fetch --quiet origin "$DEPLOY_SHA"
git -C "$release_dir" checkout --quiet --detach "$DEPLOY_SHA"

[ "$(git -C "$release_dir" rev-parse HEAD)" = "$DEPLOY_SHA" ] || {
    printf 'A release preparada não corresponde ao SHA solicitado.\n' >&2
    exit 65
}

ln -s "$SHARED_DIR/.env" "$release_dir/.env"
rm -rf --one-file-system -- "$release_dir/storage"
ln -s "$SHARED_DIR/storage" "$release_dir/storage"
printf '{\n  "build": "%s"\n}\n' "$SHORT_SHA" > "$release_dir/build.json"

COMPOSER_ALLOW_SUPERUSER=1 "$PHP_BINARY" /usr/local/bin/composer install \
    --working-dir="$release_dir" \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

npm --prefix "$release_dir" ci --no-audit --no-fund
npm --prefix "$release_dir" run build

"$PHP_BINARY" "$previous_release/artisan" down --render=errors::503 --retry=60
maintenance_enabled=true

"$PHP_BINARY" "$release_dir/artisan" migrate --force
"$PHP_BINARY" "$release_dir/artisan" optimize

find "$release_dir" -xdev -type d -exec chown www-data:www-data {} +
find "$release_dir" -xdev -type f -exec chown www-data:www-data {} +

switch_current "$release_dir"
release_activated=true
systemctl reload php8.5-fpm

"$PHP_BINARY" "$release_dir/artisan" up
maintenance_enabled=false

check_health
"$PHP_BINARY" "$release_dir/artisan" queue:restart

if "$PHP_BINARY" "$release_dir/artisan" list --raw | grep --fixed-strings --quiet 'horizon:terminate'; then
    "$PHP_BINARY" "$release_dir/artisan" horizon:terminate
fi

cleanup_old_releases
printf 'Deploy concluído: %s\n' "$DEPLOY_SHA"
