#!/usr/bin/env bash

set -Eeuo pipefail

# Launcher instalado em /usr/local/sbin/nandolz-deploy. A chave do GitHub
# é forçada a executar somente este arquivo (veja docs/DEPLOYMENT.md).

readonly REPOSITORY='https://github.com/nandinhos/nando-lz.git'
readonly FALLBACK_SCRIPT='/usr/local/lib/nando-lz/deploy-production.sh'
readonly ASSETS_DIR='/var/www/nandolz.fssdev.com.br/shared/assets'

original_command="${SSH_ORIGINAL_COMMAND:-}"

if [[ "$original_command" =~ ^upload-assets[[:space:]]+([0-9a-f]{40})$ ]]; then
    readonly ASSET_SHA="${BASH_REMATCH[1]}"
    install -d -m 755 "$ASSETS_DIR"
    tmp_tar="$(mktemp "$ASSETS_DIR/.tmp-$ASSET_SHA.XXXXXX")"
    trap 'rm -f -- "$tmp_tar"' EXIT

    head -c 52428801 > "$tmp_tar"
    if [ "$(wc -c < "$tmp_tar")" -gt 52428800 ] || [ ! -s "$tmp_tar" ]; then
        printf 'Pacote de assets inválido ou acima de 50MB.\n' >&2
        exit 67
    fi

    gzip -t "$tmp_tar"
    if tar -tzf "$tmp_tar" | grep -Eq '(^/|\.\.|^\./\.\.)'; then
        printf 'Pacote de assets contém caminho não permitido.\n' >&2
        exit 68
    fi
    if ! tar -tzf "$tmp_tar" | grep -Eq '^(public/build/|\./public/build/)'; then
        printf 'Pacote de assets não contém public/build/.\n' >&2
        exit 68
    fi

    mv -f "$tmp_tar" "$ASSETS_DIR/$ASSET_SHA.tar.gz"
    chmod 644 "$ASSETS_DIR/$ASSET_SHA.tar.gz"
    ls -1t "$ASSETS_DIR"/*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm -f --
    printf 'Assets recebidos para %s.\n' "$ASSET_SHA"
    exit 0
fi

if ! [[ "$original_command" =~ ^deploy[[:space:]]+([0-9a-f]{40})$ ]]; then
    printf 'Comando SSH recusado. Use: deploy <SHA-completo-da-main> ou upload-assets <SHA-completo-da-main>.\n' >&2
    exit 64
fi

readonly DEPLOY_SHA="${BASH_REMATCH[1]}"
source_dir="$(mktemp -d /tmp/nando-lz-deploy-source.XXXXXX)"

cleanup() {
    rm -rf --one-file-system -- "$source_dir"
}

trap cleanup EXIT

git clone --quiet --no-checkout "$REPOSITORY" "$source_dir"
git -C "$source_dir" fetch --quiet origin main
git -C "$source_dir" fetch --quiet origin "$DEPLOY_SHA"

if ! git -C "$source_dir" merge-base --is-ancestor "$DEPLOY_SHA" origin/main; then
    printf 'O SHA solicitado não pertence à main remota.\n' >&2
    exit 65
fi

git -C "$source_dir" checkout --quiet --detach "$DEPLOY_SHA"

if [ -x "$source_dir/scripts/deploy-production.sh" ]; then
    exec "$source_dir/scripts/deploy-production.sh" "$DEPLOY_SHA"
fi

if [ -x "$FALLBACK_SCRIPT" ]; then
    exec "$FALLBACK_SCRIPT" "$DEPLOY_SHA"
fi

printf 'Nenhum script de deploy disponível para o SHA solicitado.\n' >&2
exit 66
