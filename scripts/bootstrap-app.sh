#!/usr/bin/env bash
#
# bootstrap-app.sh — Prepara a aplicação de forma IDEMPOTENTE (PRD §5.4).
# Reexecutável sem quebrar: .env só é criado se faltar, chave só é gerada se
# vazia, migrations usam --force. Não faz push nem cria usuário.
#
# Uso: scripts/bootstrap-app.sh [--no-build]
# Respeita ARTISAN (ex.: "docker compose exec -T app php artisan") para o modo Docker.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ARTISAN="${ARTISAN:-php artisan}"
BUILD=1
[ "${1:-}" = "--no-build" ] && BUILD=0

# .env nunca sobrescrito (§14.6 / AI_AGENT_GUIDE).
if [ ! -f .env ]; then
  cp .env.example .env
  echo "→ .env criado a partir de .env.example"
else
  echo "→ .env já existe (preservado)"
fi

# APP_KEY só é gerada se estiver vazia.
if grep -qE '^APP_KEY=.+' .env; then
  echo "→ APP_KEY já definida"
else
  $ARTISAN key:generate --force
fi

echo "→ migrations"
$ARTISAN migrate --force

if [ "$BUILD" -eq 1 ]; then
  echo "→ build de assets"
  npm run build
fi

echo "Bootstrap concluído. Crie o primeiro admin com: $ARTISAN superadmin:create"
