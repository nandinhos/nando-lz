# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto adota [Versionamento Semântico](https://semver.org/lang/pt-BR/) próprio, independente das versões da stack.

## [1.5.0] - 2026-07-02

### Adicionado

- **`php artisan stack:sync`**: mantém os badges e a tabela de stack do README **exatos** com a stack instalada, gerados de fonte única (`composer.lock` para Laravel/Filament/Livewire/Pest, `composer.json` para o PHP, `docker-compose.yml` para o PostgreSQL, `ci.yml` para o Node), entre marcadores `<!-- stack:… -->`.
- **Guarda de drift no CI**: teste Pest roda `stack:sync --check` e falha se o README divergir da stack — nenhum bump de versão passa sem o README bater exato. O `update-stack.sh` roda `stack:sync` a cada ciclo. Suíte: **26 testes**.

### Alterado

- README com badges/tabela de stack agora sincronizados por comando (fim do drift de versões documentadas). Landing e monitor já eram exatos (leem do `composer.lock` em runtime).

### Adicionado

- **Detecção de portas** (`App\Support\Ports`): varre o que está ativo no host (banco, sistema, outros serviços) via bind de socket e **sugere uma porta alta livre**. O wizard `app:setup` usa isso no campo `APP_PORT`, e o `install-docker.sh` checa a porta (via `/dev/tcp`) antes do `up`, oferecendo a próxima livre. +2 testes (suíte: **25 testes / 70 asserts**).
- **`app:setup --preview`**: mostra o plano completo (arquivos a reescrever, arquivos a remover, porta, git) sem alterar nada. O modo interativo também exibe o plano antes de confirmar.

### Alterado

- **`app:setup` reversível por padrão**: as mudanças ficam no working tree — desfaça tudo com `git restore .`. O reset do histórico virou **opt-in** (`--reset-git`) e avisado como irreversível (antes era o padrão).
- O wizard agora coleta e grava a **porta** (`APP_PORT`) no `.env`.

## [1.3.0] - 2026-07-02

### Adicionado

- **Wizard `php artisan app:setup`** (Laravel Prompts): personaliza um clone em projeto próprio — reescreve o pacote Composer, `APP_NAME`, banco de dados (e `*_testing`), URL do repositório e todas as referências ao starter, de uma vez. Suporta modo não-interativo (opções + `--no-interaction`) para CI/scripts.
- **Separação mantenedor × usuário**: o wizard **desanexa a automação de manutenção** (auto-update, compat-watch, Renovate, resolve/update-stack) num projeto novo, mantendo só o CI. Modos: `detach` (padrão), `renovate` (mantém Renovate + CI) e `maintainer` (não mexe em nada). Documentado em `docs/MAINTAINER.md`.
- `install-local.sh` e `install-docker.sh` oferecem o wizard automaticamente num clone ainda não personalizado.

### Alterado

- **Landing dirigida por config**: a marca e a URL do repositório vêm de `config('app.name')` e `config('app.github_url')` (novo `APP_GITHUB_URL`) via `App\Support\Stack` — renomear o projeto repersonaliza a welcome automaticamente, sem editar a view.
- O teste da welcome tolera a ausência de relatórios (projeto que desanexou a automação) — suíte com **23 testes / 59 asserts**.

## [1.2.0] - 2026-07-02

### Adicionado

- **Landing page** na rota inicial (`welcome`), abstraída de design próprio (Claude Design): dark/light com persistência, PT/EN, botões de copiar, e conteúdo fiel às features reais do starter.
- **Monitor de atualização** no hero: terminal com as **versões realmente instaladas** (lidas do `composer.lock` e do runtime via `App\Support\Stack`), a **última atualização aplicada** (relatório mais recente de `docs/reports/auto-update/`, com link e veredito), a release do starter (CHANGELOG) e o build id.
- Teste de sanidade da welcome: valida que a página exibe as versões reais do lock, o build id e a data do último ciclo (suíte: **23 testes / 59 asserts**).

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
