#!/bin/sh
#
# entrypoint do container app — bootstrap idempotente (PRD §5.4/§13).
# Torna `docker compose up -d` autossuficiente: instala deps se faltarem,
# prepara .env/chave, espera o banco, migra e sobe o servidor.
set -e
cd /var/www/html

[ -d vendor ] || composer install --no-interaction --prefer-dist
[ -d node_modules ] || npm install
[ -f .env ] || cp .env.example .env
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force
[ -d public/build ] || npm run build

# Espera o banco ficar pronto (migrate falha silenciosamente até conectar).
i=0
until php artisan migrate --force >/dev/null 2>&1; do
  i=$((i + 1))
  [ "$i" -ge 30 ] && { echo "banco indisponível após 60s" >&2; php artisan migrate --force; exit 1; }
  echo "aguardando o banco… ($i)"
  sleep 2
done
echo "migrations aplicadas."

exec php artisan serve --host=0.0.0.0 --port=8000
