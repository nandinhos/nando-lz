# Relatório técnico — Implantação inicial

- **Data:** 2026-07-02
- **Release:** `v1.0.0`
- **Commit conhecido-bom:** `6b673e3` (CI verde em PHP 8.3 e 8.4)
- **Repositório:** https://github.com/nandinhos/nando-lz

## 1. Resolução de compatibilidade (§4.1)

O Filament é o pacote limitante. A resolução, verificada na Packagist e implementada por `scripts/resolve-stack.sh`, fixou:

| Componente | Versão | Origem da decisão |
|-----------|--------|-------------------|
| Filament | 5.6.7 | última major estável (5.7.0-beta1 excluído por ser beta) |
| Laravel | 13.18.0 | maior major suportada pela constraint `illuminate/* = ^11.28\|^12.0\|^13.0` |
| Livewire | 4.3.3 | transitivo via Filament |
| Pest | 4.7 | última estável |
| PHP | `^8.3` | mínimo do Laravel 13 (lock fixado à plataforma 8.3 para valer em 8.3 e 8.4) |
| PostgreSQL | 16 | banco padrão |

`blocked_upstream = false` — sem janela de incompatibilidade no momento.

## 2. O que foi entregue

- 3 painéis Filament (`ops`/`admin`/`support`), cada um com login, perfil, tema próprio e o identificador de build no rodapé.
- Autenticação oficial sem registro público; `POST /logout` nativo (encerra sessão, invalida e regenera CSRF); GET → 405.
- `User` implementa `FilamentUser` (todos os autenticados acessam todos os painéis; sem regra de negócio).
- Comando `superadmin:create` com guarda de duplicidade, args só em local/dev e senha forte fora de local.
- Identificador de build (`App\Support\Build`) com precedência `APP_BUILD` → `build.json` → hash Git → `dev`.
- PostgreSQL por padrão no `.env.example`; banco de teste `nando_lz_testing`; pgvector documentado como opcional.
- Scripts idempotentes (`install`, `install-local`, `install-docker`, `check-requirements`, `bootstrap-app`, `reset-app`, `test-app`, `resolve-stack`, `update-stack`) — nenhum faz push.
- Docker (Dockerfile + compose + entrypoint auto-bootstrap); porta pública **alta por padrão** (`APP_PORT`=18000) para evitar conflitos.
- Workflows `ci.yml`, `auto-update.yml`, `compat-watch.yml` + `renovate.json` (Camada 1).
- Documentação completa (`README` + `docs/`) e primeiro relatório de ciclo (`docs/reports/auto-update/2026-07-01.md`).

## 3. Verificações executadas

| Verificação | Resultado |
|-------------|-----------|
| `composer validate --strict` | ✅ |
| Suíte Pest (20 testes, 44 asserts) sobre PostgreSQL | ✅ |
| Pint (estilo) | ✅ |
| Modo Docker: build, migrations no container, painéis 200 na porta 18000 | ✅ |
| Smoke HTTP dos 3 painéis (login e acesso autenticado) | ✅ |
| CI no GitHub (matriz PHP 8.3 × 8.4 × PostgreSQL) | ✅ verde |
| `.env` real fora do versionamento | ✅ (gitignored) |

## 4. Decisões técnicas registradas

- **`ServeCommand::$passthroughVariables` estendido** no `AppServiceProvider` com as variáveis `DB_*`. O `php artisan serve` só repassa ao worker HTTP as variáveis do allowlist; sem isso, o modo Docker (que injeta `DB_HOST=db` pelo ambiente do container) caía no `127.0.0.1` do `.env`. Garante paridade Local↔Docker.
- **`config.platform.php = 8.3.0`** no `composer.json`. Sem isso, resolvendo na máquina de dev (PHP 8.4.1), o Composer travava Symfony 8.1 (exige PHP ≥ 8.4.1) e quebrava o job PHP 8.3 do CI. Com a plataforma fixada, o lock usa Symfony 7.4 (php ≥ 8.2), válido em 8.3 e 8.4.
- **`tests/Unit/.gitkeep`** — o diretório vazio não era versionado e o `->in('Unit')` do Pest falhava no checkout limpo do CI.
- **Porta Docker alta (18000)** por padrão, a pedido, para não conflitar com outros serviços na VPS.
- **`postgres` no `.env.example`/CI** é o default de desenvolvimento (PRD §5.5), não um segredo — documentado em `.gitguardian.yaml`.

## 5. Critérios de aceite (§15)

Itens 1–16 atendidos. Item 17 (este relatório) concluído. Branch protection na `main` habilitada como último passo (PR obrigatório, CI obrigatório em `PHP 8.3`/`PHP 8.4`, histórico linear, sem force-push).

## 6. Próximos passos

- Habilitar o app **Renovate** no repositório (Camada 1 só age depois disso).
- O ciclo `auto-update.yml` roda toda segunda 08:00 (America/Sao_Paulo); pode ser disparado sob demanda via `workflow_dispatch`.
- Para deploy na VPS, ver a seção *Deploy na VPS* do `README.md` e `docs/DOCKER.md`.
