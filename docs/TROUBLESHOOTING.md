# Troubleshooting

Problemas comuns e como resolver.

## `ext-intl` ausente

O `check-requirements.sh` sai com código **11** quando falta a extensão `intl`. Instale-a no seu PHP e rode a instalação de novo.

- Debian/Ubuntu: `sudo apt install php8.3-intl` (ajuste a versão do PHP).
- No modo Docker isso não ocorre — a imagem já traz `intl`.

## Porta ocupada

A porta pública do Docker é **alta por padrão** (`${APP_PORT:-18000}:8000`) justamente para evitar conflitos. Se ainda assim estiver ocupada, defina outra no `.env`:

```
APP_PORT=18001
```

E suba de novo (`docker compose up -d`). No modo Local, o `php artisan serve` usa 8000; use `--port=` para trocar.

## Erro de conexão ao banco no Docker

Sintoma: "connection refused 127.0.0.1:5432" dentro do container.

Causa: `php artisan serve` só repassa ao worker HTTP as variáveis do allowlist `ServeCommand::$passthroughVariables`. As credenciais de banco que vêm do **ambiente do container** (e não do `.env`) não seriam repassadas por padrão. O `AppServiceProvider` estende esse allowlist com `DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` para garantir a paridade Local↔Docker.

Se você viu esse erro, confirme que o `AppServiceProvider` ainda estende o allowlist e que o compose está forçando `DB_HOST=db`.

## `composer.lock` fora de sincronia

Sintoma: `composer validate --strict` reclama que o lock está desatualizado após editar o `composer.json`.

Correção:

```bash
composer update --lock
```

Isso ressincroniza o lock com o `composer.json` sem atualizar dependências além do necessário.

## Senha recusada pelo `superadmin:create` fora de `local`

**Isso é por design.** Fora do ambiente `local`, o comando exige senha forte (mín. 12 caracteres, com maiúsculas + minúsculas, números e símbolos) e recusa senhas triviais. Em `local`, o mínimo é 8. Use uma senha que atenda ao requisito — não é bug.

Lembre também que o comando só roda enquanto **não existir nenhum usuário** e que `--name --email --password` só são aceitos em `local`/`dev`.

## Banco de teste `nando_lz_testing` não existe

Os testes usam o banco `nando_lz_testing` (definido em `phpunit.xml`, pgsql, com `RefreshDatabase`). Se ele não existir, crie-o:

```sql
CREATE DATABASE nando_lz_testing;
```

No CI, o serviço PostgreSQL 16 já provê esse banco. Localmente, crie-o uma vez antes de rodar `php artisan test`.

## Renovate não abre PRs

O Renovate **requer que o app seja habilitado no repositório, no GitHub**. Sem essa habilitação, o `renovate.json` não tem efeito e nenhum PR é aberto. Habilite o app Renovate na organização/repositório. Ver [AUTO_UPDATE_POLICY.md](AUTO_UPDATE_POLICY.md).
