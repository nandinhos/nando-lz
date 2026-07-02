#!/usr/bin/env bash
#
# test-app.sh — Roda a suíte Pest (PRD §5.4/§10). Repassa argumentos ao Pest.
# Uso: scripts/test-app.sh [args-do-pest]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [ -n "${ARTISAN:-}" ]; then
  # Modo Docker: delega ao artisan configurado.
  exec $ARTISAN test "$@"
fi

exec ./vendor/bin/pest "$@"
