#!/usr/bin/env bash
#
# install-docker.sh — Instalação via Docker, idempotente (PRD §5.4).
# Não exige PHP/PostgreSQL locais. O entrypoint do container faz o bootstrap
# (composer/npm install, chave, migrations); aqui só subimos e orientamos.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "== nando-lz · instalação Docker =="

command -v docker >/dev/null 2>&1 || { echo "Docker não encontrado" >&2; exit 20; }
docker compose version >/dev/null 2>&1 || { echo "'docker compose' (v2) não encontrado" >&2; exit 21; }

# .env do host (montado no container). Nunca sobrescreve; o DB_HOST é forçado
# para 'db' pelo próprio compose, então o mesmo .env serve para local e Docker.
[ -f .env ] || { cp .env.example .env; echo "→ .env criado a partir de .env.example"; }

echo "→ build e subida dos containers (o app se auto-bootstrapa)"
docker compose up -d --build

PORT="$(grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2)"
PORT="${PORT:-18000}"

echo "→ aguardando o app responder em http://localhost:$PORT …"
for _ in $(seq 1 45); do
  if curl -fsS "http://localhost:$PORT" >/dev/null 2>&1; then break; fi
  sleep 2
done

cat <<EOF

Pronto. App em http://localhost:$PORT  ·  Painéis: /ops /admin /support

Crie o primeiro admin e rode os testes:
  docker compose exec app php artisan superadmin:create
  docker compose exec app php artisan test
EOF
