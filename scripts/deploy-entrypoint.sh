#!/usr/bin/env bash

set -Eeuo pipefail

# Launcher instalado em /usr/local/sbin/nandolz-deploy. A chave do GitHub
# é forçada a executar somente este arquivo (veja docs/DEPLOYMENT.md).

readonly REPOSITORY='https://github.com/nandinhos/nando-lz.git'
readonly FALLBACK_SCRIPT='/usr/local/lib/nando-lz/deploy-production.sh'

original_command="${SSH_ORIGINAL_COMMAND:-}"

if ! [[ "$original_command" =~ ^deploy[[:space:]]+([0-9a-f]{40})$ ]]; then
    printf 'Comando SSH recusado. Use: deploy <SHA-completo-da-main>.\n' >&2
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
