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
