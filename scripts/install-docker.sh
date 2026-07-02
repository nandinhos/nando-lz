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

# `|| true`: sem a linha APP_PORT no .env, o grep retorna 1 e o set -e mataria o script.
PORT="$(grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2 || true)"
PORT="${PORT:-18000}"

# Detecção de conflito de porta (via /dev/tcp do bash — sem ferramenta externa):
# se algo já escuta na porta, sugere a próxima alta livre antes do `up`.
port_busy() { (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null && { exec 3>&- 3<&-; return 0; } || return 1; }
if port_busy "$PORT"; then
  FREE="$PORT"; for _ in $(seq 1 500); do FREE=$((FREE + 1)); port_busy "$FREE" || break; done
  echo "⚠ porta $PORT ocupada no host. Porta alta livre sugerida: $FREE"
  if [ -t 0 ]; then
    read -r -p "Usar a porta $FREE? [S/n] " ans
    case "${ans:-s}" in [nN]) echo "  Mantendo $PORT (pode falhar no up). Ajuste APP_PORT no .env." ;;
      *) sed -i.bak "s/^APP_PORT=.*/APP_PORT=$FREE/" .env && rm -f .env.bak; PORT="$FREE"; echo "→ APP_PORT=$FREE gravado no .env" ;; esac
  else
    echo "  Modo não-interativo: ajuste APP_PORT no .env para evitar conflito." >&2
  fi
fi

echo "→ build e subida dos containers (o app se auto-bootstrapa)"
docker compose up -d --build

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
    *) {
         docker compose exec app php artisan app:setup || true
         echo "→ recriando containers para aplicar o novo banco…"
         docker compose down -v && docker compose up -d
       } ;;
  esac
fi

URL="http://localhost:${PORT}/"
# Link clicável via OSC 8 (iTerm2/GNOME Terminal/Windows Terminal/WezTerm).
# Em terminais sem suporte, o escape é ignorado e o texto puro aparece.
LINK_TEXT="http://localhost:${PORT}/"
printf '\n'
printf '\033]8;;%s\033\\%s\033]8;;\033\\\n' "$URL" "$LINK_TEXT"
printf 'Pronto. App no link acima  ·  Painéis: /ops /admin /support\n\n'
printf 'Crie o primeiro admin e rode os testes:\n'
printf '  docker compose exec app php artisan superadmin:create\n'
printf '  docker compose exec app php artisan test\n'
