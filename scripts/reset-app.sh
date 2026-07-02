#!/usr/bin/env bash
#
# reset-app.sh — Recria o banco em estado limpo (PRD §5.4). DESTRUTIVO.
# Exige confirmação, salvo com --force ou FORCE=1 (usado por CI/agente).
#
# Uso: scripts/reset-app.sh [--force]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ARTISAN="${ARTISAN:-php artisan}"

if [ "${1:-}" != "--force" ] && [ "${FORCE:-0}" != "1" ]; then
  read -r -p "Isso APAGA todos os dados do banco. Continuar? [s/N] " ans
  case "$ans" in [sS]) ;; *) echo "Abortado."; exit 0 ;; esac
fi

echo "→ migrate:fresh"
$ARTISAN migrate:fresh --force

echo "→ limpando caches"
$ARTISAN optimize:clear

echo "Reset concluído."
