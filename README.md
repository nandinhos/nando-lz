# nando-lz

[![CI](https://github.com/nandinhos/nando-lz/actions/workflows/ci.yml/badge.svg)](https://github.com/nandinhos/nando-lz/actions/workflows/ci.yml)
[![Auto Update](https://github.com/nandinhos/nando-lz/actions/workflows/auto-update.yml/badge.svg)](https://github.com/nandinhos/nando-lz/actions/workflows/auto-update.yml)
![Laravel](https://img.shields.io/badge/Laravel-13.18-FF2D20?logo=laravel)
![Filament](https://img.shields.io/badge/Filament-5.6-FFAA00)
![PHP](https://img.shields.io/badge/PHP-%5E8.3-777BB4?logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)
![License](https://img.shields.io/badge/license-MIT-green)

Starter kit técnico **público** e *evergreen* para novos projetos **Laravel + Filament**: limpo, genérico, reproduzível e **permanentemente atualizado por automação**. Clone, rode um único script e tenha, em minutos, uma aplicação funcional na última versão estável e mutuamente compatível de toda a stack.

> **É uma fundação técnica, não um produto.** Não há SaaS, landing page, checkout, pagamento, convite, multitenancy nem qualquer regra de negócio. Só estrutura conhecida-boa para você construir por cima.

---

## Sumário

- [Por que existe](#por-que-existe)
- [O que já vem pronto](#o-que-já-vem-pronto)
- [Pré-requisitos](#pré-requisitos)
- [Início rápido](#início-rápido)
- [Stack](#stack)
- [Os 3 painéis Filament](#os-3-painéis-filament)
- [Autenticação e primeiro admin](#autenticação-e-primeiro-admin)
- [Identificador de build](#identificador-de-build)
- [Estrutura do repositório](#estrutura-do-repositório)
- [Scripts](#scripts)
- [Testes](#testes)
- [Deploy na VPS](#deploy-na-vps)
- [Automação de manutenção](#automação-de-manutenção)
- [Versionamento e releases](#versionamento-e-releases)
- [Documentação](#documentação)
- [Restrições do projeto](#restrições-do-projeto)
- [Licença](#licença)

---

## Por que existe

Manter um projeto Laravel + Filament sempre atualizado é trabalhoso: as versões precisam ser **mutuamente compatíveis**, e o Filament costuma ser o pacote que limita qual major do Laravel se pode usar. O `nando-lz` resolve isso de duas formas:

1. **Base conhecida-boa** — um estado sempre estável, testado e taggeado (SemVer), do qual qualquer projeto novo pode partir.
2. **Automação em três camadas** — Renovate (patch/minor), um agente de IA (majors e decisões) e CI (gate universal) mantêm a stack atualizada sem merge cego. Ver [automação](#automação-de-manutenção).

---

## O que já vem pronto

- ✅ **Laravel 13** na última estável suportada pelo Filament.
- ✅ **Filament 5** com **três painéis**: `ops`, `admin`, `support` (cada um com tema próprio).
- ✅ **Autenticação** oficial do Filament, **sem registro público**, com página de perfil e **2FA opcional** (opt-in).
- ✅ **`POST /logout`** nativo (nunca GET) — encerra a sessão, invalida-a e regenera o token CSRF.
- ✅ Comando **`superadmin:create`** para o primeiro administrador, com guarda de duplicidade e senha forte.
- ✅ **Pest** com suíte mínima de sanidade (20 testes) sobre **PostgreSQL**.
- ✅ **Dois modos de instalação idempotentes**: Local e Docker, ambos por um único script.
- ✅ **Identificador de build** no rodapé da sidebar de todos os painéis.
- ✅ **Resolvedor de compatibilidade** (`resolve-stack.sh`) e **ciclo de atualização** (`update-stack.sh`).
- ✅ **CI/CD**: workflows `ci`, `auto-update` e `compat-watch` + configuração do Renovate.
- ✅ **Documentação** para humanos e para agentes de IA, e relatórios versionados de cada ciclo.

---

## Pré-requisitos

**Modo Local:** PHP `>= 8.3` com `ext-intl`, Composer, Node 22, PostgreSQL 16 (o `check-requirements.sh` valida tudo).

**Modo Docker:** apenas Docker + `docker compose` (v2). Não exige PHP nem PostgreSQL na máquina.

---

## Início rápido

```bash
git clone https://github.com/nandinhos/nando-lz.git
cd nando-lz
./scripts/install.sh        # menu: 1) Local  2) Docker
```

O instalador é interativo e também aceita o modo direto: `./scripts/install.sh local` ou `./scripts/install.sh docker`.

**Comandos do dia a dia:**

| Ação | Local | Docker |
|------|-------|--------|
| Subir a aplicação | `php artisan serve` | `docker compose up -d` |
| Migrations | `php artisan migrate` | `docker compose exec app php artisan migrate` |
| Testes | `php artisan test` | `docker compose exec app php artisan test` |
| Primeiro admin | `php artisan superadmin:create` | `docker compose exec app php artisan superadmin:create` |
| Assets (HMR) | `npm run dev` | — |

No modo Docker a porta pública é **alta por padrão** (`18000`) para evitar conflitos — acesse `http://localhost:18000`. No modo Local, `php artisan serve` usa `http://127.0.0.1:8000`.

---

## Stack

Travada e verificada em 2026-07-01. **O Filament é o pacote limitante:** a major do Laravel é derivada do que o Filament estável suporta — nunca escolhida isoladamente.

| Componente | Versão | Observação |
|-----------|--------|------------|
| Laravel | 13.18.0 | major derivada do Filament |
| Filament | 5.6.7 | pacote limitante |
| Livewire | 4.3.3 | transitivo via Filament — **nunca fixar direto** |
| Pest | 4.7 | framework único de testes |
| PHP | `^8.3` | validado em 8.3 e 8.4 no CI |
| PostgreSQL | 16 | banco padrão; pgvector opcional |
| Node | 22 | build de assets |

Versões instáveis (`alpha`/`beta`/`RC`/`dev`/`nightly`) são **proibidas sem autorização humana em issue**. A resolução completa (ordem §4.1, janela de incompatibilidade §4.2) está em [docs/STACK.md](docs/STACK.md) e é implementada por [`scripts/resolve-stack.sh`](scripts/resolve-stack.sh), que emite um JSON com a stack atual, o alvo e um flag `blocked_upstream`.

---

## Os 3 painéis Filament

Apenas estrutura inicial, sem domínio de negócio. Cada painel tem login (sem registro público), página de perfil e 2FA opcional, e exibe o identificador de build no rodapé da sidebar.

| Painel | Rota | Tema | Propósito futuro |
|--------|------|------|------------------|
| `ops` | `/ops` | Blue | Administração global |
| `admin` | `/admin` | Amber | Aplicação principal (painel default) |
| `support` | `/support` | Emerald | Suporte e manutenção |

Todo usuário autenticado acessa os três painéis — `User::canAccessPanel()` retorna `true`, pois não há papéis nem permissões. **É aqui que você restringe o acesso ao introduzir regra de negócio.**

---

## Autenticação e primeiro admin

Autenticação oficial do Filament, **sem página pública de registro**. O logout é sempre `POST /{painel}/logout` (GET retorna **HTTP 405** — proteção CSRF) e, nativamente, encerra a sessão, invalida-a e regenera o token CSRF.

O primeiro administrador é criado por comando:

```bash
php artisan superadmin:create
```

- **Bootstrap único:** só roda enquanto não existir nenhum usuário; os demais são criados pelo painel.
- **Interativo por padrão.** Os argumentos `--name --email --password` só são aceitos em `local`/`dev`.
- **Senha forte fora de `local`:** mínimo 12 caracteres com maiúsculas, minúsculas, números e símbolos (recusa senhas triviais). Em `local`, mínimo 8.

---

## Identificador de build

Todos os painéis mostram, no rodapé da sidebar, o identificador do build — útil para confirmar visualmente a versão implantada. Resolvido por `App\Support\Build::id()` na seguinte precedência:

1. `config('app.build')` (variável de ambiente `APP_BUILD`);
2. arquivo `build.json` na raiz (chave `build`), gerado no build;
3. hash curto do commit Git;
4. `dev`.

---

## Estrutura do repositório

```
app/
  Console/Commands/CreateSuperAdmin.php   Comando superadmin:create
  Models/User.php                         Implementa FilamentUser
  Providers/AppServiceProvider.php        Rodapé de build + paridade serve/Docker
  Providers/Filament/                     OpsPanelProvider, AdminPanelProvider, SupportPanelProvider
  Support/Build.php                       Resolução do identificador de build (§5.6)
docker/
  entrypoint.sh                           Bootstrap idempotente do modo Docker
scripts/                                  Instalação e manutenção (ver abaixo)
tests/Feature/SanityTest.php              Suíte Pest de sanidade
docs/                                     Documentação + docs/reports/auto-update/ (relatórios de ciclo)
.github/workflows/                        ci.yml · auto-update.yml · compat-watch.yml
Dockerfile · docker-compose.yml           Modo Docker
renovate.json                             Camada 1 da automação
.env.example                              PostgreSQL por padrão
```

---

## Scripts

Todos em `scripts/`, com `set -euo pipefail`, **idempotentes** e sem `git push` embutido.

| Script | Função |
|--------|--------|
| `install.sh` | Entrada única — menu `1) Local  2) Docker` |
| `install-local.sh` | Instalação Local (sem Docker) |
| `install-docker.sh` | Instalação via Docker (sem PHP/PostgreSQL locais) |
| `check-requirements.sh` | Valida PHP, `ext-intl`, Composer, Node, psql e permissões (exit codes 10–15) |
| `bootstrap-app.sh` | `.env`, chave, migrations e build — idempotente (`--no-build`, respeita `ARTISAN`) |
| `reset-app.sh` | Recria o banco limpo (destrutivo; `--force`) |
| `test-app.sh` | Roda o Pest |
| `resolve-stack.sh` | Resolvedor de compatibilidade (§4.1), saída JSON |
| `update-stack.sh` | Ciclo do agente (§7): gates + relatório (`--dry-run`); **não faz push** |

---

## Testes

Suíte Pest mínima de sanidade sobre PostgreSQL (banco de teste `nando_lz_testing`, via `RefreshDatabase`):

```bash
php artisan test        # ou ./vendor/bin/pest
```

Valida: a aplicação sobe; a rota inicial responde; login de cada painel; painéis exigem autenticação; usuário autenticado acessa cada painel e vê o build no rodapé; logout não é GET (405); `POST /logout` encerra a sessão e regenera o CSRF; `superadmin:create` cria/recusa-trivial/bloqueia-duplicidade; migrations em banco limpo.

---

## Deploy na VPS

O modo Docker é o caminho mais direto e reproduzível para uma VPS.

```bash
git clone https://github.com/nandinhos/nando-lz.git
cd nando-lz
cp .env.example .env
# Ajuste para produção:
#   APP_ENV=production, APP_DEBUG=false, APP_URL=https://seu-dominio
#   APP_PORT= (porta alta livre na VPS, ex.: 18000)
#   DB_PASSWORD= (senha forte)
docker compose up -d --build
docker compose exec app php artisan superadmin:create   # senha forte obrigatória fora de local
```

O `entrypoint` do container faz o bootstrap sozinho (instala dependências, gera a chave se faltar, espera o banco, migra e sobe o servidor). A aplicação escuta na porta `APP_PORT` (alta por padrão); coloque um **reverse proxy** (Nginx/Caddy/Traefik) na frente para TLS e domínio.

Notas de produção:

- Defina `APP_BUILD` (ou gere `build.json`) para o rodapé da sidebar refletir a versão implantada.
- `php artisan serve` é simples e funcional; para carga pesada, o upgrade path é php-fpm + Nginx ou Laravel Octane (ver [docs/DOCKER.md](docs/DOCKER.md)).
- Prefira volumes gerenciados para o PostgreSQL (o compose já usa o volume `pgdata`).

Detalhes completos em [docs/DOCKER.md](docs/DOCKER.md) e [docs/INSTALLATION.md](docs/INSTALLATION.md).

---

## Automação de manutenção

Manutenção em **três camadas** (nenhuma faz merge sem CI verde):

| Camada | Responsável | Papel |
|--------|-------------|-------|
| 1 — Renovate | `renovate.json` | PRs de **patch/minor** (só o lock, preserva constraints); majors ignorados |
| 2 — Agente de IA | `auto-update.yml` | Ciclo semanal: resolve, aplica, valida, **abre PR** — decide e documenta, **nunca faz merge** |
| 3 — CI | `ci.yml` + branch protection | **Gate universal**: nada entra na `main` sem verde |

As mudanças são classificadas em **AUTO** (patch/minor/lock — PR, merge automático só sob condições estritas), **REVIEW** (majors, troca de pacote, mudança estrutural — `needs-human-approval`) e **BLOCKED** (incompatibilidade upstream — sem PR, issue rastreadora). Cada ciclo gera um relatório em `docs/reports/auto-update/`. Detalhes em [docs/AUTO_UPDATE_POLICY.md](docs/AUTO_UPDATE_POLICY.md).

> O Renovate exige habilitar o app no repositório (GitHub). Sem isso, a Camada 1 fica inerte.

---

## Versionamento e releases

O starter tem **SemVer próprio** (`v1.0.0`, …), independente das versões da stack. Todo merge na `main` com CI verde vira tag, então dá para clonar sempre um estado conhecido-bom. Rollback = revert do PR de manutenção + tag de correção. Ver [docs/VERSION_POLICY.md](docs/VERSION_POLICY.md).

---

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| [docs/INSTALLATION.md](docs/INSTALLATION.md) | Visão geral dos dois modos |
| [docs/LOCAL.md](docs/LOCAL.md) | Modo Local em detalhe |
| [docs/DOCKER.md](docs/DOCKER.md) | Modo Docker em detalhe |
| [docs/STACK.md](docs/STACK.md) | Stack, resolução de compatibilidade, build id, pgvector, 2FA |
| [docs/VERSION_POLICY.md](docs/VERSION_POLICY.md) | Política de versões, SemVer, releases e rollback |
| [docs/AUTO_UPDATE_POLICY.md](docs/AUTO_UPDATE_POLICY.md) | Automação em 3 camadas, classes, gates |
| [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Problemas comuns |
| [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md) | Contrato operacional do agente de manutenção |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de versões |

---

## Restrições do projeto

Este repositório **não** contém regra de negócio e não deve virar produto. Em resumo: nada de SaaS/landing/checkout/pagamento/convite, nada de multitenancy, nada de pacote sem justificativa registrada, nada de versão instável sem autorização em issue, `.env` real nunca versionado, sem logout via GET, e **nenhum merge na `main` sem CI verde**. A lista completa e o contrato do agente estão em [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md).

---

## Licença

[MIT](LICENSE).
