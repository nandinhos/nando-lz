# Roadmap priorizado

Os buckets respondem a “quando corrigir”; a severidade nos arquivos de área responde a “quão grave é”. Esforço: `S` pequeno, `M` médio, `L` grande, `XL` muito grande.

## Execução em 2026-08-11

Os itens P0 de banco, Pint, publicação idempotente, notificação pós-falha e suspensão do auto-merge foram mergeados em `24c9abf` pela PR #7. O CI remoto passou em PHP 8.3, PHP 8.4 e GitGuardian; a política administrativa de PRs do GitHub Actions também foi confirmada.

## P0 — antes de confiar no sistema

| Item | Por que | Esforço |
|---|---|---|
| Alinhar PostgreSQL de teste | **Implementado.** `DB_HOST`, `DB_DATABASE` e `DB_USERNAME` estão alinhados em CI, Auto Update e PHPUnit; os serviços efêmeros usam `trust` sem senha versionada. | S |
| Restabelecer o gate Pint | **Implementado.** `SanityTest.php` foi formatado e Pint entrou no ciclo automático. | S |
| Corrigir publicação de PR | **Implementado e validado remotamente.** O preflight, a idempotência e a política administrativa foram confirmados na PR #7. | M |
| Garantir notificação de falha | **Implementado.** A issue usa condição pós-falha e falhas de notificação não são ocultadas. | S |
| Suspender ou corrigir auto-merge | **Suspenso.** O workflow foi removido até validar checks, label e política de merge. | M |

## P1 — logo depois do desbloqueio

| Item | Por que | Esforço |
|---|---|---|
| Materializar AUTO/REVIEW/BLOCKED | `blocked_upstream` não é consumido pelo `auto-update`; a label `auto-merge` nunca é criada; a política documentada e a execução divergem. | M |
| Igualar gates do ciclo e do CI | **Parcial.** O ciclo agora executa Pint e auditorias; migrations continuam cobertas pelo Pest e pelo step explícito do CI. | M |
| Definir política de `npm audit` | **Implementado.** Vulnerabilidades high agora bloqueiam o ciclo e o lock atual está limpo. | S |
| Tratar concorrência e branches órfãs | **Parcial.** `concurrency` foi adicionado; a triagem/limpeza das cinco branches históricas continua pendente. | S |
| Integrar o GitHub App, se essa for a decisão | O helper `scripts/github-app-auth.py` existe, mas não é chamado pelo workflow e não possui dependências Python declaradas. | M |

## P2 — robustez e manutenção

| Item | Por que | Esforço |
|---|---|---|
| Tornar o relatório estruturado | Registrar dependências alteradas, detalhes de falhas, próximos passos, link do PR e hash final do commit, conforme a política. | M |
| Corrigir o status da landing | **Implementado em `59cadfe`.** O monitor usa `Resultado geral` do relatório e não os ícones dos gates individuais. | S |
| Tornar `stack:sync --check` estrito | Falhar quando marcadores estão ausentes/malformados, em vez de considerar o README sincronizado. | S |
| Validar argumentos e retornos | `update-stack.sh` ignora argumentos desconhecidos e `app:setup` ignora o retorno de `composer update --lock`. | S |
| Atualizar documentação de testes | Há números conflitantes: `pest --list-tests` enumera 30 casos, enquanto vários docs ainda dizem 22/53 asserts. | S |
| Decidir RBAC dos painéis | Hoje `User::canAccessPanel()` retorna `true` para todos; isso é coerente com o starter vazio, mas precisa ser decidido antes de adicionar recursos reais. | M |

## Quick wins — menos de 1 hora

- Corrigir a formatação de `tests/Feature/SanityTest.php` com Pint e reexecutar a matriz.
- Definir as variáveis de banco explicitamente no job em vez de depender da combinação `.env` + `phpunit.xml`.
- Alterar a etapa de issue para uma condição pós-falha e tornar o erro primário de `gh pr create` visível.
- Trocar `requiredChecks = ['tests']` pelos dois contextos reais ou remover `auto-merge.yml` até a política ficar pronta.
- Fechar/arquivar as branches órfãs somente após preservar seus relatórios.
