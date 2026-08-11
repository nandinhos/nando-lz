# Área: CI e Auto Update

## Resumo

O trigger e o provisionamento inicial funcionam, e os seis runs chegaram a commit/push. O caminho comum quebra em duas fronteiras: o Pest com banco incompatível e a criação de PR bloqueada pela política do GitHub. A notificação de falha também não sobrevive ao fluxo de erro.

## Implementado

- Schedule e `workflow_dispatch`: `.github/workflows/auto-update.yml:5-8`.
- Permissões declaradas para contents, PRs, issues e actions: `.github/workflows/auto-update.yml:10-15`.
- PostgreSQL com healthcheck: `.github/workflows/auto-update.yml:21-32`.
- Branch diária, commit e push: `.github/workflows/auto-update.yml:56-60,97-100`.
- Dispatch manual do CI após o PR: `.github/workflows/auto-update.yml:112-115`.

## Gaps

- **[⚪ médio] Concorrência ausente.** Não há `concurrency` para schedule e dispatch que disputem a mesma branch diária: `.github/workflows/auto-update.yml:17-20,60,99-100`.
- **[⚪ médio] O GitHub App não está conectado.** O helper declara a finalidade de abrir PRs, mas não é chamado pelo workflow: `scripts/github-app-auth.py:3-18`; `.github/workflows/auto-update.yml:67-69`.

## Flaws

- **[✅ confirmado / high] Criação de PR bloqueada.** O workflow usa `GH_TOKEN: ${{ github.token }}` e `gh pr create`: `.github/workflows/auto-update.yml:67-69,105-108`. Os seis runs registram `GitHub Actions is not permitted to create or approve pull requests (createPullRequest)`. A API informa `can_approve_pull_request_reviews=false`; `pull-requests: write` no YAML não supera essa política.
- **[✅ confirmado / high] Falha do ciclo não gera issue.** O step `cycle` usa `continue-on-error`, mas a issue depende de uma condição posterior: `.github/workflows/auto-update.yml:62-65,117-126`. Nos runs `30271282850`, `30819077861` e `31385014699`, o ciclo retornou 1 e o step de issue ficou `skipped`.
- **[✅ confirmado / medium] Fallback mascara a causa.** Qualquer erro de `gh pr create` cai em `gh pr edit`: `.github/workflows/auto-update.yml:104-110`. Os logs mostram a sequência de erro de permissão e depois `no pull requests found for branch`.
- **[corrigido por suspensão] Auto-merge verificava contrato errado.** O workflow foi removido até a proteção real da `main` (`PHP 8.3` e `PHP 8.4`) e a política de merge automático serem decididas.

## Veredito

**flawed**: a parte de execução local do ciclo é observável, mas a entrega, a notificação e o contrato de merge não são confiáveis.
