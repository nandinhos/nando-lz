# Incidente 0001 — Auto Update sem publicação confiável

- Data da investigação: 2026-08-10
- Run de referência: [31385014699](https://github.com/nandinhos/nando-lz/actions/runs/31385014699)
- Status: corrigido e confirmado remotamente

## Sintoma

Os seis runs semanais do `auto-update.yml` terminavam com `exit code 1`, sem Pull Request e sem issue rastreadora. O run de referência falhou primeiro no Pest e depois na publicação do PR.

## Causa raiz

1. `phpunit.xml` usava o usuário `nando_lz` sem senha, enquanto os services PostgreSQL do CI criavam `postgres/postgres`.
2. O workflow tratava qualquer erro de `gh pr create` como se significasse que o PR já existia e tentava editar uma branch sem PR.
3. A issue de falha não sobrevivia à falha da etapa de publicação porque não usava uma condição pós-falha.
4. A matriz do CI estava bloqueada por regras de estilo em `tests/Feature/SanityTest.php`.
5. `auto-merge.yml` verificava um check chamado `tests` que não correspondia aos checks protegidos da `main`.

## Correção aplicada

- Unificação das credenciais de teste em `phpunit.xml`, `ci.yml` e `auto-update.yml`.
- Formatação de `tests/Feature/SanityTest.php` com Pint.
- Inclusão de `npm ci` no runner do auto-update.
- Pint e `npm audit --audit-level=high` passaram a ser gates reais em `scripts/update-stack.sh`.
- O update passou a rejeitar argumentos desconhecidos.
- O workflow ganhou `concurrency` e consulta um PR aberto antes de decidir entre editar e criar.
- A issue de falha usa `always()`, cria a label necessária e deixa falhas de notificação visíveis.
- O workflow de auto-merge foi removido até a política ser decidida e verificada.

## Prevenção

- O CI deve continuar usando a mesma matriz de variáveis de banco declarada no job.
- Um novo ciclo deve verificar tanto o resultado dos gates quanto o caminho de publicação.
- A política administrativa `can_approve_pull_request_reviews` do repositório precisa ser habilitada conscientemente antes de considerar a abertura automática de PR verificada.
- O auto-merge só pode ser reintroduzido com os nomes reais dos required checks e uma decisão explícita de segurança.

## Confirmação remota

- A [PR #7](https://github.com/nandinhos/nando-lz/pull/7) foi mergeada em `24c9abf` em 2026-08-11.
- O run de CI `31455478655` passou em PHP 8.3 e PHP 8.4; o GitGuardian também passou.
- A política do repositório confirmou `default_workflow_permissions=write` e `can_approve_pull_request_reviews=true`.

## Evidências locais

- `composer validate --strict`: passou.
- `npm run build`: passou.
- `bash -n scripts/*.sh`: passou.
- `vendor/bin/pint --test`: passou após a correção.
- `composer audit --no-interaction`: passou após atualizar Laravel/Filament e as dependências transitivas vulneráveis.
- `npm audit --audit-level=high`: passou após `npm audit fix` e `npm update`.
- Suíte completa do Pest: passou no Docker com PostgreSQL 16, 30 testes e 103 assertions.
