# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto adota [Versionamento Semântico](https://semver.org/lang/pt-BR/) próprio, independente das versões da stack.

## [1.1.0] - 2026-07-02

### Corrigido

- **Crítico:** removido o uso de `env_file:` no `docker-compose.yml` — injetar o `.env` como ambiente real do container congelava os valores e fazia os `<env>` do `phpunit.xml` serem ignorados; `php artisan test` no container apontava para o banco de dev e o **apagava**. O `.env` agora é lido só via bind-mount e os testes rodam isolados em `nando_lz_testing`.
- `install-docker.sh` não imprime mais sucesso falso: espera até 5 min no primeiro boot e, se o app não responder, falha com exit `22` sugerindo `docker compose logs -f app`.
- `auto-update.yml` dispara `ci.yml` explicitamente após abrir o PR — pushes com `GITHUB_TOKEN` não disparam workflows e o PR ficava preso em "Expected" nos required checks.
- `compat-watch.yml` usa a major **aguardada** correta na issue rastreadora (a última estável do Laravel que o Filament ainda não suporta) e faz bootstrap do label `blocked-upstream`.
- `Build::id()` ignora valor não-escalar em `build.json` (proteção contra derrubar os painéis no cast).

### Alterado

- **Container não-root:** o app roda como usuário `app` com UID configurável (`build.args.UID`, padrão 1000) — arquivos criados no bind-mount permanecem editáveis no host e a troca Docker↔Local não deixa arquivos root-owned.
- `restart: unless-stopped` nos serviços `app` e `db`; `.dockerignore` (`*` + `!docker/`) reduz o contexto de build a poucos KB; entrypoint com marcadores por **arquivo final** — instalação interrompida retoma corretamente.
- **CI sem warnings de Node 20:** `actions/checkout@v7`, `actions/setup-node@v6` e `actions/cache@v6`, com cache do Composer por hash do lock + matriz PHP.
- Renovate agendado para o **sábado** (sem colidir com o ciclo do agente na segunda); bloqueio de majors escopado a composer/npm; GitHub Actions em grupo próprio **com majors permitidos**.
- `auto-update.yml`: não abre PR se só o relatório mudou; `git push --force-with-lease` (re-run seguro no mesmo dia); cria-ou-atualiza o PR; bootstrap idempotente de labels.
- `resolve-stack.sh` com timeouts de rede explícitos (`--connect-timeout 10 --max-time 60`).

### Adicionado

- `docker/pg-init.sql` cria o banco de teste `nando_lz_testing` na primeira inicialização do volume `pgdata`.
- **2 testes de login** por credenciais válidas/inválidas na página de login do Filament (`Livewire::test` + `fillForm`) — suíte agora com **22 testes (53 asserts)**; o teste de senha forte passou a distinguir a regra forte da fraca (`password123` passa em `local`, falha fora).
- `superadmin:create` atribui `email_verified_at` explicitamente (campo fora do `$fillable` por design).

## [1.0.0] - 2026-07-01

### Adicionado

- Base inicial do starter técnico: **Laravel 13.18** + **Filament 5.6** (Filament como pacote limitante), Livewire 4.3 (transitivo), Pest 4.7, PHP `^8.3`, PostgreSQL 16, Node 22.
- **3 painéis Filament** sem domínio: `ops` (`/ops`, Blue), `admin` (`/admin`, Amber, default) e `support` (`/support`, Emerald), cada um com login (sem registro público), página de perfil e 2FA opcional.
- **Autenticação** oficial do Filament com `POST /{painel}/logout` nativo (encerra a sessão, invalida-a e regenera o CSRF); logout via GET não existe (HTTP 405).
- Comando **`php artisan superadmin:create`** para bootstrap do primeiro admin: bloqueia duplicidade, interativo por padrão, exige senha forte fora de `local`, cria o usuário verificado com acesso ao painel ops.
- **Identificador de build** no rodapé da sidebar (`App\Support\Build::id()`, precedência `APP_BUILD` → `build.json` → hash Git → `dev`).
- **Suíte Pest mínima** (`tests/Feature/SanityTest.php`, 20 testes) cobrindo app, painéis, autenticação, logout, `superadmin:create` e migrations.
- Configuração **PostgreSQL** no `.env.example`; banco de testes separado `nando_lz_testing`.
- **Modos de instalação Local e Docker**, idempotentes (`install.sh`, `install-local.sh`, `install-docker.sh`, `check-requirements.sh`, `bootstrap-app.sh`, `reset-app.sh`, `test-app.sh`).
- Scripts de manutenção **`resolve-stack.sh`** (resolução de compatibilidade §4.1, saída JSON) e **`update-stack.sh`** (ciclo do agente, gera relatório).
- **Workflows GitHub Actions**: `ci.yml` (gate universal, matriz PHP 8.3/8.4 × PostgreSQL 16), `auto-update.yml` (ciclo semanal do agente, abre PR, nunca faz merge) e `compat-watch.yml` (vigia a janela de incompatibilidade upstream).
- **Renovate** (`renovate.json`, `rangeStrategy: update-lockfile`, majors desabilitados, semanal).
- **Documentação** completa em `docs/` e primeiro **relatório de ciclo** de exemplo em `docs/reports/auto-update/`.

[1.1.0]: https://github.com/nandinhos/nando-lz/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/nandinhos/nando-lz/releases/tag/v1.0.0
