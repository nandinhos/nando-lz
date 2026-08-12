#!/usr/bin/env bash

set -Eeuo pipefail

# Confirma que uma branch criada pelo ciclo próprio só carrega alterações de
# dependências travadas e documentação gerada. O workflow privilegiado nunca
# executa o conteúdo da PR; esta verificação é a fronteira de escopo antes do
# rótulo autonomous-candidate ser aplicado.

if [ "$#" -ne 2 ]; then
    printf 'Uso: %s <base-ref> <candidate-ref>.\n' "$0" >&2
    exit 64
fi

readonly BASE_REF="$1"
readonly CANDIDATE_REF="$2"

base_sha="$(git rev-parse "$BASE_REF")"
candidate_sha="$(git rev-parse "$CANDIDATE_REF")"
merge_base="$(git merge-base "$base_sha" "$candidate_sha")"

changed_lock=false
changed_report=''
invalid=false

while IFS=$'\t' read -r status path; do
    [ -n "$path" ] || continue

    case "$status" in
        A|M) ;;
        *)
            printf 'Alteração não permitida (%s): %s\n' "$status" "$path" >&2
            invalid=true
            continue
            ;;
    esac

    case "$path" in
        composer.lock|package-lock.json)
            changed_lock=true
            ;;
        README.md)
            ;;
        docs/reports/auto-update/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9].md)
            changed_report="$path"
            ;;
        *)
            printf 'Arquivo fora do escopo autônomo: %s\n' "$path" >&2
            invalid=true
            ;;
    esac
done < <(git diff --name-status "$merge_base" "$candidate_sha")

if [ "$changed_lock" != true ]; then
    printf 'A atualização autônoma precisa alterar ao menos um lock file.\n' >&2
    invalid=true
fi

if [ -z "$changed_report" ]; then
    printf 'A atualização autônoma precisa registrar um relatório datado.\n' >&2
    invalid=true
elif ! git show "$candidate_sha:$changed_report" | grep --fixed-strings --quiet -- '- Resultado geral: ✅ verde'; then
    printf 'O relatório não comprova resultado geral verde: %s\n' "$changed_report" >&2
    invalid=true
fi

if [ "$invalid" = true ]; then
    exit 1
fi

printf 'Candidato autônomo validado: %s\n' "$candidate_sha"
