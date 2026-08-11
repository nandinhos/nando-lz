# Resumo executivo

> Este é o diagnóstico do estado anterior à correção. Consulte [docs/STATUS.md](../STATUS.md) para o estado atual e [incidents/0001-auto-update-ci.md](../incidents/0001-auto-update-ci.md) para as correções aplicadas.

## Veredito

O starter Laravel/Filament tem uma base funcional e relativamente bem documentada, mas o sistema de manutenção automática não está operacional nem pronto para ser tratado como gate de qualidade. O run `31385014699` não falhou por uma única causa: o gate Pest foi executado com credenciais PostgreSQL incompatíveis e, depois, a publicação falhou porque o GitHub bloqueia PRs criados pelo `GITHUB_TOKEN`. A própria notificação de falha foi pulada. Em paralelo, o CI da `main` está vermelho por Pint e o workflow de auto-merge procura um check que não existe na proteção da branch.

A tela enviada mostra apenas o sintoma final (`exit code 1`). O erro causal está nos logs: `SQLSTATE[08006] ... password authentication failed for user "nando_lz"`, `Tests: 28 failed, 2 passed`, seguido de `GitHub Actions is not permitted to create or approve pull requests (createPullRequest)` e `no pull requests found for branch`.

## Escopo e números

- SHA remoto auditado: `origin/main` em `61125d0`; o checkout local estava em `ea99876`, dois commits atrás.
- Runs `Auto Update` auditados: 6, todos falhos (`28798906358`, `29253599609`, `29744819649`, `30271282850`, `30819077861`, `31385014699`).
- Áreas auditadas: 8.
- Branches remotas de manutenção órfãs observadas: 5, de 2026-07-06 a 2026-08-10.
- PRs e issues abertas para esses ciclos: 0.
- `pest --list-tests` local: 30 casos expandidos.
- Checks locais executados: `composer validate --strict` passou; `npm run build` passou; `vendor/bin/pint --test` falhou; a suíte Pest não foi declarada verde porque o ambiente local não foi alinhado ao SHA remoto e ao PostgreSQL do CI.

## Top 5 riscos

1. **P0 — Banco de teste incompatível.** `phpunit.xml` força `DB_USERNAME=nando_lz`, mas os services do workflow criam apenas `postgres/postgres`; os três runs em `61125d0` falharam no Pest.
2. **P0 — Publicação de PR bloqueada.** A API do repositório retorna `default_workflow_permissions=read` e `can_approve_pull_request_reviews=false`; o YAML pede `pull-requests: write`, mas isso não habilita a política administrativa que permite `createPullRequest`.
3. **P0 — Falhas não são notificadas.** O step de issue não usa uma condição que sobreviva a falhas anteriores; nos runs com Pest falho ele aparece como `skipped`. O fallback de `gh pr create` também aborta antes da notificação.
4. **P0 — CI da `main` não está verde.** O run `29749040557`, no SHA `61125d0`, falhou em `vendor/bin/pint --test` para `tests/Feature/SanityTest.php` em PHP 8.3 e 8.4.
5. **P1 — Auto-merge não está conectado ao contrato real.** `auto-merge.yml` verifica somente `tests`, enquanto a proteção da `main` exige `PHP 8.3` e `PHP 8.4`; além disso, nenhum caminho atual adiciona a label `auto-merge`.

## O que está sólido

- Os três painéis Filament (`/ops`, `/admin`, `/support`) têm login, sessão, CSRF e middleware de autenticação.
- Logout por GET é rejeitado e o POST valida encerramento de sessão e regeneração do CSRF.
- O script de atualização coleta falhas e gera relatório mesmo quando um gate falha.
- O `composer validate`, o build local e a separação conceitual entre banco de desenvolvimento e banco de testes estão implementados.
- `.env`, vendor, builds e chaves privadas têm proteção no `.gitignore`; não foi encontrado `.env` versionado.

## Decisão recomendada

Antes de permitir qualquer merge automático, alinhe as credenciais do banco, faça o CI voltar a verde, escolha conscientemente entre habilitar `GITHUB_TOKEN` ou integrar o GitHub App e corrija a notificação de incidentes. Até esses itens serem verificados em um novo run completo, a manutenção deve ser considerada **bloqueada**.
