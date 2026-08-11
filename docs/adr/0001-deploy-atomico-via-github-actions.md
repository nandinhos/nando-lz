# ADR 0001 — Deploy atômico via GitHub Actions e SSH restrito

- **Status:** accepted
- **Date:** `2026-08-11`
- **Owner:** manutenção do repositório

## Context

A produção em `nandolz.fssdev.com.br` usa Nginx + PHP 8.5-FPM e já mantém releases com `current`, `shared/.env` e `shared/storage`. A release ativa estava desatualizada e o repositório não possuía fluxo de publicação. A análise de operação está em [docs/deep-analysis/areas/07-docs-security-ops.md](../deep-analysis/areas/07-docs-security-ops.md).

## Options considered

| Option | Pros | Cons |
|---|---|---|
| **A — GitHub Actions + SSH restrito + releases atômicas** | Reaproveita a infraestrutura existente, não expõe endpoint novo e permite rollback de código. | Exige secret SSH e disciplina de migrations compatíveis. |
| B — Webhook HTTP próprio na VPS | Pode receber eventos diretamente do GitHub. | Cria serviço público, autenticação e superfície operacional novos. |
| C — `git pull` na release ativa | Implementação curta. | Mistura arquivos gerados, não é atômico e não oferece rollback confiável. |

## Decision

Adotar a opção A. O workflow é iniciado somente depois de `CI` verde na `main`; sua chave Ed25519 é forçada no servidor a executar `deploy <SHA>`. O script cria a nova release, executa manutenção/migrations/otimização, troca o symlink e exige health check HTTPS antes de concluir.

## Consequences

O deploy de commits mesclados passa a ser automático, sem webhook HTTP público e sem acesso SSH genérico do GitHub. O monitor passa a refletir a release publicada por meio de `build.json` e dos relatórios presentes na `main`. A operação precisa manter migrations retrocompatíveis enquanto rollback de código for desejável.

## Trigger to revisit

Revisar quando houver segundo ambiente, necessidade de acesso interativo pelo CI, proibição de secrets SSH em runners hospedados, ou quando a VPS hospedar carga suficiente para exigir isolamento do PHP-FPM.
