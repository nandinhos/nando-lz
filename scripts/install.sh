#!/usr/bin/env bash
#
# install.sh — Entrada única de instalação (PRD §5.4/§13).
# Menu: 1) Local  2) Docker. Aceita também: install.sh [local|docker].
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

choice="${1:-}"
if [ -z "$choice" ]; then
  echo "nando-lz — escolha o modo de instalação:"
  echo "  1) Local  (PHP + PostgreSQL na máquina)"
  echo "  2) Docker (containers; sem PHP/PostgreSQL locais)"
  read -r -p "Opção [1/2]: " opt
  case "$opt" in 1) choice=local ;; 2) choice=docker ;; *) echo "Opção inválida" >&2; exit 1 ;; esac
fi

case "$choice" in
  local)  exec bash scripts/install-local.sh ;;
  docker) exec bash scripts/install-docker.sh ;;
  *) echo "Uso: install.sh [local|docker]" >&2; exit 1 ;;
esac
