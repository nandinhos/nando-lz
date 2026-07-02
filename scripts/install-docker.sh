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

# `|| true`: sem a linha APP_PORT no .env, o grep retorna 1 e o set -e mataria o script.
PORT="$(grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2 || true)"
PORT="${PORT:-18000}"

# Primeiro boot compila vendor+assets no container — dê tempo de sobra.
echo "→ aguardando o app responder em http://localhost:$PORT (até 5 min no primeiro boot)…"
UP=0
for _ in $(seq 1 150); do
  if curl -fsS "http://localhost:$PORT" >/dev/null 2>&1; then UP=1; break; fi
  sleep 2
done

if [ "$UP" -ne 1 ]; then
  echo "✗ o app não respondeu em http://localhost:$PORT." >&2
  echo "  Acompanhe o bootstrap com: docker compose logs -f app" >&2
  exit 22
fi

# Personalização (rebrand) dentro do container — só num clone não personalizado.
if [ -t 0 ] && grep -q '"name": "nandinhos/nando-lz"' composer.json; then
  read -r -p "Personalizar este projeto agora (nome, pacote, banco)? [S/n] " ans
  case "${ans:-s}" in
    [nN]) echo "Pulei — rode depois: docker compose exec app php artisan app:setup" ;;
    *) docker compose exec app php artisan app:setup || true
       echo "→ recriando containers para aplicar o novo banco…"
       docker compose down -v && docker compose up -d ;;
  esac
fi

cat <<EOF

Pronto. App em http://localhost:$PORT  ·  Painéis: /ops /admin /support

Crie o primeiro admin e rode os testes:
  docker compose exec app php artisan superadmin:create
  docker compose exec app php artisan test
EOF
