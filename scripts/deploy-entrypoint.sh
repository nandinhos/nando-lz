#!/usr/bin/env bash

set -Eeuo pipefail

# Launcher instalado em /usr/local/sbin/nandolz-deploy. A chave do GitHub
# é forçada a executar somente este arquivo (veja docs/DEPLOYMENT.md).

readonly REPOSITORY='https://github.com/nandinhos/nando-lz.git'
readonly FALLBACK_SCRIPT='/usr/local/lib/nando-lz/deploy-production.sh'
readonly ASSETS_DIR="${NANDOLZ_ASSETS_DIR:-/var/www/nandolz.fssdev.com.br/shared/assets}"

validate_assets_tar() {
    local tar_file="$1"
    local has_manifest=false
    local line entry_type entry_path

    gzip -t "$tar_file" || return 1

    while IFS= read -r line; do
        [ -n "$line" ] || continue
        entry_type="${line:0:1}"
        case "$entry_type" in
            -|d) ;;
            *)
                printf 'Pacote de assets contém tipo de entrada não permitido (%s).\n' "$entry_type" >&2
                return 1
                ;;
        esac

        # Em `tar -tvzf` para arquivos regulares (-) e diretórios (d), o caminho
        # vem após `<user>/<group> <size> <YYYY-MM-DD> <HH:MM> <path>`.
        entry_path="$(printf '%s\n' "$line" | sed -E 's/^[^[:space:]]+[[:space:]]+[^[:space:]]+[[:space:]]+[0-9]+[[:space:]]+[0-9]{4}-[0-9]{2}-[0-9]{2}[[:space:]]+[0-9]{2}:[0-9]{2}[[:space:]]+//')"
        entry_path="${entry_path#./}"

        if [ -z "$entry_path" ] || [[ "$entry_path" == /* ]] || [[ "$entry_path" =~ (^|/)\.\.(/|$) ]]; then
            printf 'Pacote de assets contém caminho não permitido: %s\n' "$entry_path" >&2
            return 1
        fi

        case "$entry_path" in
            public|public/|public/build|public/build/)
                [ "$entry_type" = 'd' ] || {
                    printf 'Entrada pai deve ser diretório: %s\n' "$entry_path" >&2
                    return 1
                }
                ;;
            public/build/manifest.json)
                [ "$entry_type" = '-' ] || {
                    printf 'Manifesto deve ser arquivo regular: %s\n' "$entry_path" >&2
                    return 1
                }
                has_manifest=true
                ;;
            public/build/*)
                ;;
            *)
                printf 'Pacote de assets contém entrada fora de public/build/: %s\n' "$entry_path" >&2
                return 1
                ;;
        esac
    done < <(tar -tvzf "$tar_file")

    if [ "$has_manifest" != true ]; then
        printf 'Pacote de assets não contém public/build/manifest.json.\n' >&2
        return 1
    fi
}

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

    if ! validate_assets_tar "$tmp_tar"; then
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
