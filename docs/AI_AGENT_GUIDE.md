# Guia do Agente de IA — Contrato Operacional (§12.1)

Este documento é o **contrato** que qualquer agente de IA (ou humano) deve seguir ao manter o nando-lz. O starter é técnico, público e mantido por automação: o valor dele é permanecer sempre uma base conhecida-boa. As regras abaixo existem para garantir isso.

## Os 14 deveres

1. **Ler toda a documentação antes de alterar qualquer arquivo.** Comece por este guia, `STACK.md`, `VERSION_POLICY.md` e `AUTO_UPDATE_POLICY.md`.
2. **Executar a resolução de compatibilidade do §4.1 antes de instalar ou atualizar** qualquer dependência (`scripts/resolve-stack.sh`).
3. **Usar somente versões estáveis.** Nada de alpha/beta/RC/dev/nightly sem autorização humana registrada em issue.
4. **Não instalar pacotes sem justificativa registrada** (no relatório do ciclo ou em issue).
5. **Preservar a estrutura limpa** do starter — não adicionar scaffolding especulativo.
6. **Não criar regra de negócio.** O starter não tem domínio.
7. **Não transformar o starter em produto** (sem SaaS, checkout, pagamento, convite, multitenancy; a landing é só vitrine técnica do starter).
8. **Manter os 3 painéis funcionando** (`ops`, `admin`, `support`) — login, perfil, 2FA opcional e build id no rodapé.
9. **Rodar a suíte de testes após qualquer alteração** (`php artisan test` / `./vendor/bin/pest` — 30 casos expandidos).
10. **Registrar decisões no relatório do ciclo** em `docs/reports/auto-update/YYYY-MM-DD.md`.
11. **Jamais sobrescrever o `.env` real.** `bootstrap-app.sh` só cria `.env` se faltar; `.env` real nunca é versionado.
12. **Manter paridade Local↔Docker.** O que funciona em Local funciona em Docker e vice-versa (ver allowlist do `serve` em `TROUBLESHOOTING.md`).
13. **Não criar PRDs de produto nem `bizagents.md`.**
14. **Jamais alterar a `main` diretamente.** Toda mudança vai por branch + PR; nada de push direto, nada de merge de item REVIEW/BLOCKED.

## Fluxo do ciclo (§7.3) — ordem fixa dos gates

1. Sincronizar a `main` e criar branch `maintenance/auto-update-YYYY-MM-DD`.
2. Rodar `scripts/resolve-stack.sh` e **classificar** a mudança (§7.2: AUTO / REVIEW / BLOCKED).
3. Aplicar **apenas** a classe permitida (AUTO na branch; REVIEW gera relatório e PR de revisão; BLOCKED não gera PR).
4. `composer validate --strict`.
5. `composer audit` (falha = bloqueio) e `npm audit --audit-level=high`.
6. Migrations em banco efêmero limpo (serviço PostgreSQL).
7. Suíte Pest completa (30 casos expandidos).
8. Build de assets.
9. Smoke HTTP dos 3 painéis (200/302 autenticado) — coberto pela suíte Pest.
10. Validar `POST /logout` (encerra sessão, regenera CSRF).
11. Gerar o relatório do ciclo.
12. Commit + abrir PR com o relatório no corpo.
13. **Nunca fazer merge** com qualquer falha/risco, nem de itens REVIEW/BLOCKED.

Detalhes das classes de mudança, camadas de automação e critérios de merge automático: `AUTO_UPDATE_POLICY.md`.

## Notas operacionais (invariantes recentes — não regredir)

- **Docker sem `env_file:`.** O `.env` chega ao Laravel via bind-mount. Injetá-lo como ambiente real do container congela valores e faz os `<env>` do `phpunit.xml` serem ignorados — bug crítico real: `php artisan test` no container apagava o banco de dev. Nunca adicionar `env_file:` ao compose.
- **Banco de teste no Docker.** `docker/pg-init.sql` cria `nando_lz_testing` na primeira inicialização do volume `pgdata`; `docker compose exec app php artisan test` é seguro e isolado.
- **Container não-root.** O app roda como usuário `app` com UID configurável (`build.args.UID`, padrão 1000) — não reintroduzir root no container nem quebrar a editabilidade do bind-mount no host.
- **PR do ciclo.** O `auto-update.yml` não abre PR se só o relatório mudou; usa `--force-with-lease` (re-run seguro no mesmo dia); cria-ou-atualiza o PR; e dispara `gh workflow run ci.yml --ref <branch>` após abrir (pushes com `GITHUB_TOKEN` não disparam workflows — sem isso o PR fica preso em "Expected").
- **Renovate no sábado**, agente na segunda — não realinhar as agendas para o mesmo dia.
- **`superadmin:create`** atribui `email_verified_at` explicitamente; o campo fica fora do `$fillable` por design.

## Scripts (ponteiros)

- `scripts/resolve-stack.sh` — resolução de compatibilidade do §4.1; saída JSON; curl com timeouts explícitos.
- `scripts/update-stack.sh [--dry-run]` — ponto de entrada do ciclo; roda os gates e gera o relatório **mesmo em falha** (usa `set -uo pipefail`, sem `-e`, de propósito). Nunca cria branch, commita ou faz push (isso é do workflow).
- `scripts/bootstrap-app.sh [--no-build]` — bootstrap idempotente (`.env`, chave, migrations, build).
- `scripts/reset-app.sh [--force]` — destrutivo (`migrate:fresh` + limpa caches).
- `scripts/test-app.sh [args]` — roda o Pest.
- `scripts/install.sh` / `install-local.sh` / `install-docker.sh` / `check-requirements.sh` — instalação.

Detalhes de cada script: `INSTALLATION.md`, `LOCAL.md`, `DOCKER.md`.

## Mantenedor × usuário

A automação de manutenção (`auto-update.yml`, `compat-watch.yml`, `renovate.json`, `resolve-stack.sh`, `update-stack.sh`) é do **mantenedor** do `nando-lz` — serve para manter o *starter* evergreen. Quem clona para um projeto novo roda `php artisan app:setup` (wizard de rebrand), que renomeia a identidade e **desanexa** essa automação, preservando o CI. Ver [MAINTAINER.md](MAINTAINER.md).
