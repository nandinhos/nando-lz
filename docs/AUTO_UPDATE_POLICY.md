# Política de Auto-Atualização

Como o nando-lz se mantém *evergreen* sem intervenção manual constante — e onde o humano ainda é obrigatório.

## Automação em 3 camadas (§6)

### Camada 1 — Renovate

Arquivo `renovate.json`:

- `rangeStrategy: update-lockfile` — atualiza **só o lock**, preservando as constraints do `composer.json`.
- **Majors desabilitados** aqui.
- Agenda **semanal**, segunda de manhã, timezone `America/Sao_Paulo`.
- Labels `auto-update` e `dependencies`; alertas de vulnerabilidade com label `security`.

> **Requer habilitar o app Renovate no repositório, no GitHub.** Sem isso, o Renovate não abre PRs.

### Camada 2 — Agente de IA

Workflow `auto-update.yml`. **Decide e documenta**, mas **não faz merge**. Roda `scripts/update-stack.sh`, classifica a mudança e abre PR.

### Camada 3 — CI + branch protection

Workflow `ci.yml` é o **gate universal**, complementado por **branch protection na `main`**. Nenhuma mudança entra na `main` sem CI verde.

## Classes de mudança (§7.2)

| Classe | O que é | Ação | Merge |
|--------|---------|------|-------|
| **AUTO** | patch, minor compatível, correção de segurança, lock file, dependência dev, ajuste documental | aplicar na branch, validar, abrir PR | automático **somente se**: AUTO pura + CI verde + zero mudança de constraint + política `auto-merge` habilitada no repo |
| **REVIEW** | major de Laravel/Filament/PHP/Livewire; troca/remoção de pacote; mudança estrutural de auth/painéis; base image Docker com breaking change; migração manual | branch + relatório + PR `needs-human-approval` | **exclusivamente humano** |
| **BLOCKED** | incompatibilidade upstream (§4.2) | **sem PR** — issue rastreadora + monitoramento semanal | — |

## Gates obrigatórios (§7.3) — ordem fixa

1. Sincronizar a `main` e criar branch `maintenance/auto-update-YYYY-MM-DD`.
2. `resolve-stack.sh` + classificar.
3. Aplicar só a classe permitida.
4. `composer validate --strict`.
5. `composer audit` (**falha = bloqueio**) e `npm audit --audit-level=high`.
6. Migrations em banco efêmero limpo (serviço PostgreSQL).
7. Suíte Pest completa.
8. Build de assets.
9. Smoke HTTP dos 3 painéis (200/302 autenticado) — coberto pela suíte Pest.
10. Validar `POST /logout`.
11. Gerar o relatório.
12. Commit + abrir PR com o relatório no corpo.
13. **Nunca fazer merge** com qualquer falha/risco ou item REVIEW/BLOCKED.

## Cadência e cron (§7.1)

Workflows em `.github/workflows/`:

- **`ci.yml`** — gate universal. Dispara em push (`main` e `maintenance/**`), `pull_request` e manual. Matriz **PHP 8.3 e 8.4 × PostgreSQL 16** (banco `nando_lz_testing`). Passos: `composer validate --strict`, install, `key:generate`, `vendor/bin/pint --test`, `migrate --force`, `npm ci` + `npm run build`, `./vendor/bin/pest`.
- **`auto-update.yml`** — ciclo semanal do agente. Cron `0 11 * * 1` (UTC) = **segunda 08:00 America/Sao_Paulo**; também `workflow_dispatch`. Cria branch `maintenance/auto-update-YYYY-MM-DD`, roda `scripts/update-stack.sh` e **abre PR (nunca faz merge)**. Labels: `auto-update` sempre; `needs-human-approval` se o `composer.json` mudou (major/constraint = REVIEW) ou se o ciclo falhou. Falha do ciclo gera issue `maintenance-failure`. Permissões: contents / pull-requests / issues write.
- **`compat-watch.yml`** — vigia a janela de incompatibilidade (§4.2). Semanal. Roda `resolve-stack.sh`; se `blocked_upstream` for verdadeiro, cria/atualiza a issue rastreadora `compat: aguardando Filament x Laravel N` (label `blocked-upstream`); quando o upstream liberar, fecha a issue.

## Relatórios (§9)

Cada ciclo gera `docs/reports/auto-update/YYYY-MM-DD.md` com:

- data;
- matriz de versões antes → depois;
- dependências atualizadas / mantidas;
- classificação (§7.2);
- comandos executados;
- resultado de testes / audits / smoke;
- falhas / riscos;
- próximos passos;
- hash do commit;
- link do PR.

Já existe um primeiro relatório de exemplo em `docs/reports/auto-update/`.

O script `update-stack.sh [--dry-run]` gera o relatório mesmo em falha (exit `1`); com `--dry-run` não altera locks. Ele **nunca** cria branch, commita ou faz push — isso é responsabilidade do workflow.
