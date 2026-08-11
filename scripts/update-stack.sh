#!/usr/bin/env bash
#
# update-stack.sh — Ponto de entrada do ciclo do agente (PRD §7).
#
# Roda os GATES do §7.3 (subconjunto local: resolução, validação, audit,
# estilo, testes/migrations, build) e gera o relatório do ciclo. NUNCA cria branch,
# NUNCA faz commit e NUNCA faz push — isso é responsabilidade do workflow
# auto-update.yml (Camada 3). O smoke HTTP dos 3 painéis (§7.3 gate 9) é
# coberto pela suíte Pest.
#
# Uso: scripts/update-stack.sh [--dry-run]
#   --dry-run  não altera composer.lock/package-lock (só valida o estado atual).
#
# Exit: 0 tudo verde · 1 alguma gate falhou (relatório mesmo assim é gerado).
set -uo pipefail   # sem -e: queremos coletar falhas e ainda gerar o relatório

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1

DRY=0
case "${1:-}" in
  "") ;;
  --dry-run) DRY=1 ;;
  *) echo "Uso: $0 [--dry-run]" >&2; exit 2 ;;
esac

DATE="$(date +%F)"
REPORT_DIR="docs/reports/auto-update"
REPORT="$REPORT_DIR/$DATE.md"
mkdir -p "$REPORT_DIR"

FAILED=0
STEPS=""
run() { # run "rótulo" comando…
  local label="$1"; shift
  echo "── $label"
  if "$@"; then STEPS+="- ✅ $label\n"; else STEPS+="- ❌ $label\n"; FAILED=1; fi
}

# Antes (versões travadas hoje).
BEFORE="$(./scripts/resolve-stack.sh 2>/dev/null || echo '{}')"

# §7.3(2) resolução de compatibilidade.
run "resolve-stack (§4.1)" ./scripts/resolve-stack.sh >/dev/null

# §7.3(3) aplicar apenas patch/minor dentro das constraints (classe AUTO).
if [ "$DRY" -eq 0 ]; then
  run "composer update (patch/minor)" composer update --no-interaction --prefer-dist
  [ -f package-lock.json ] && run "npm update (lock)" npm update
  # Docs sempre exatas: sincroniza os números do README com a stack instalada.
  run "sincronizar README (stack:sync)" php artisan stack:sync
fi

# §7.3(4) composer validate --strict.
run "composer validate --strict" composer validate --strict

# §7.3(5) auditorias de segurança.
run "composer audit" composer audit
if [ -f package-lock.json ]; then
  run "npm audit --audit-level=high" npm audit --audit-level=high
fi

# §7.3(6+7+9) estilo, migrations em banco limpo + suíte Pest + smoke dos painéis.
run "Pint (estilo de código)" ./vendor/bin/pint --test
run "pest (migrations, painéis, logout, superadmin)" ./vendor/bin/pest

# §7.3(8) build de assets. Só roda se houver node_modules instalado
# (working tree do agente não tem; release de produção tem).
if [ -d node_modules ] && [ -f package-lock.json ]; then
  run "build de assets" npm run build
else
  STEPS+="- ⏭ build de assets pulado (node_modules ausente — contexto working tree)\n"
  echo "── build de assets pulado (node_modules ausente)"
fi

AFTER="$(./scripts/resolve-stack.sh 2>/dev/null || echo '{}')"
COMMIT="$(git rev-parse --short HEAD 2>/dev/null || echo 'sem-git')"

# §7.3(11) relatório do ciclo (§9).
{
  echo "# Relatório de manutenção — $DATE"
  echo
  echo "- Modo: $([ "$DRY" -eq 1 ] && echo 'dry-run (sem alterar locks)' || echo 'aplicação de patch/minor')"
  echo "- Resultado geral: $([ "$FAILED" -eq 0 ] && echo '✅ verde' || echo '❌ com falhas')"
  echo "- Commit base: \`$COMMIT\`"
  echo
  echo "## Gates executados (§7.3)"
  echo -e "$STEPS"
  echo "## Stack — antes"
  echo '```json'; echo "$BEFORE"; echo '```'
  echo "## Stack — depois"
  echo '```json'; echo "$AFTER"; echo '```'
  echo
  echo "## Classificação (§7.2)"
  echo "- Mudanças de \`composer.json\` (constraint): revisar como **REVIEW** (major)."
  echo "- Somente \`composer.lock\`/\`package-lock.json\`: **AUTO**."
  echo "- Bloqueio upstream: ver campo \`blocked_upstream\` acima."
  echo
  echo "> Gerado por scripts/update-stack.sh. Este script não faz merge nem push."
} > "$REPORT"

echo
echo "Relatório: $REPORT"
exit "$FAILED"
