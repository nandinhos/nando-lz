#!/usr/bin/env bash
#
# check-requirements.sh — Verifica pré-requisitos do modo LOCAL (PRD §5.4).
# Idempotente e sem efeitos colaterais. Exit codes distintos por falha:
#   0 ok   10 PHP ausente/versão   11 ext-intl   12 Composer
#   13 Node   14 PostgreSQL (psql)   15 permissões de escrita
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_MIN="8.3.0"
ok()   { printf '  \033[32m✓\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✗\033[0m %s\n' "$1" >&2; }

echo "Verificando pré-requisitos (modo local)…"

command -v php >/dev/null 2>&1 || { fail "PHP não encontrado"; exit 10; }
php -r "exit(version_compare(PHP_VERSION, '$PHP_MIN', '>=') ? 0 : 1);" \
  || { fail "PHP >= $PHP_MIN exigido (atual: $(php -r 'echo PHP_VERSION;'))"; exit 10; }
ok "PHP $(php -r 'echo PHP_VERSION;')"

php -m | grep -qi '^intl$' || { fail "extensão ext-intl ausente (exigida pelo Filament)"; exit 11; }
ok "ext-intl"

command -v composer >/dev/null 2>&1 || { fail "Composer não encontrado"; exit 12; }
ok "Composer $(composer --version 2>/dev/null | awk '{print $3}')"

command -v node >/dev/null 2>&1 || { fail "Node não encontrado"; exit 13; }
ok "Node $(node -v)"

command -v psql >/dev/null 2>&1 || { fail "cliente PostgreSQL (psql) não encontrado"; exit 14; }
ok "psql $(psql --version | awk '{print $3}')"

# Permissões de escrita nos diretórios que o Laravel precisa gravar.
for dir in storage bootstrap/cache; do
  [ -w "$dir" ] || { fail "sem permissão de escrita em $dir"; exit 15; }
done
ok "permissões de escrita (storage, bootstrap/cache)"

echo "Todos os pré-requisitos atendidos."
