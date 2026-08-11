# Área: CI e Auto Update

## Resumo

O diagnóstico original encontrou falhas de banco, publicação e notificação. Elas foram corrigidas e validadas remotamente na PR #7; o auto-merge continua suspenso por decisão explícita.

## Implementado

- Schedule e `workflow_dispatch`: `.github/workflows/auto-update.yml:5-8`.
- Permissões declaradas para contents, PRs, issues e actions: `.github/workflows/auto-update.yml:10-15`.
- PostgreSQL com healthcheck: `.github/workflows/auto-update.yml:21-32`.
- Branch diária, commit e push: `.github/workflows/auto-update.yml:56-60,97-100`.
- Dispatch manual do CI após o PR: `.github/workflows/auto-update.yml:112-115`.

## Gaps

- **[corrigido] Concorrência.** O workflow usa `concurrency` para serializar os ciclos automáticos.
- **[⚪ médio] O GitHub App não está conectado.** O helper declara a finalidade de abrir PRs, mas não é chamado pelo workflow: `scripts/github-app-auth.py:3-18`; `.github/workflows/auto-update.yml:67-69`.

## Flaws

- **[corrigido e validado] Criação de PR.** O preflight falha explicitamente sem a política necessária; a API agora confirma `can_approve_pull_request_reviews=true`.
- **[corrigido] Falha do ciclo gera issue.** A condição usa `always()` e registra falha de publicação ou de ciclo.
- **[corrigido] Fallback de PR.** O workflow consulta PR aberta antes de decidir entre editar e criar.
- **[corrigido por suspensão] Auto-merge verificava contrato errado.** O workflow foi removido até a proteção real da `main` (`PHP 8.3` e `PHP 8.4`) e a política de merge automático serem decididas.

## Veredito

**corrigido e validado**: entrega, notificação e preflight foram confirmados; o auto-merge segue suspenso até uma nova decisão de segurança.
