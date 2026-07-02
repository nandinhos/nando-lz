# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto adota [Versionamento Semântico](https://semver.org/lang/pt-BR/) próprio, independente das versões da stack.

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

[1.0.0]: https://github.com/nandinhos/nando-lz/releases/tag/v1.0.0
