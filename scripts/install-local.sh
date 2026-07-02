#!/usr/bin/env bash
#
# install-local.sh — Instalação LOCAL, idempotente (PRD §5.4). Sem Docker.
# Reexecutável: dependências reinstalam, .env/chave/migrations são preservados.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "== nando-lz · instalação local =="

bash scripts/check-requirements.sh

echo "→ composer install"
composer install --no-interaction --prefer-dist

echo "→ npm install"
npm install

# .env + chave antes do wizard (que edita APP_NAME/DB_DATABASE no .env).
[ -f .env ] || cp .env.example .env
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force

# Personalização (rebrand) — só num clone ainda não personalizado e com TTY.
if [ -t 0 ] && grep -q '"name": "nandinhos/nando-lz"' composer.json; then
  read -r -p "Personalizar este projeto agora (nome, pacote, banco)? [S/n] " ans
  case "${ans:-s}" in [nN]) echo "Pulei — rode depois: php artisan app:setup" ;;
    *) php artisan app:setup || true ;; esac
fi

bash scripts/bootstrap-app.sh

# Bootstrap do primeiro admin (o comando se autoprotege contra duplicidade).
if [ -t 0 ]; then
  read -r -p "Criar o primeiro superadmin agora? [S/n] " ans
  case "${ans:-s}" in [nN]) echo "Pulei. Rode depois: php artisan superadmin:create" ;;
    *) php artisan superadmin:create || true ;; esac
else
  echo "Modo não-interativo: crie o admin com 'php artisan superadmin:create'."
fi

cat <<'EOF'

Pronto. Suba a aplicação com:
  php artisan serve        # http://127.0.0.1:8000
  npm run dev              # (opcional) HMR de assets

Painéis: /ops  /admin  /support
EOF
