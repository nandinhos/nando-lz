# Troubleshooting

Problemas comuns e como resolver.

- [`ext-intl` ausente](#ext-intl-ausente)
- [Porta ocupada](#porta-ocupada)
- [`install-docker.sh` falhou com exit 22](#install-dockersh-falhou-com-exit-22)
- [Erro de conexão ao banco no Docker](#erro-de-conexão-ao-banco-no-docker)
- [`composer.lock` fora de sincronia](#composerlock-fora-de-sincronia)
- [Senha recusada pelo `superadmin:create`](#senha-recusada-pelo-superadmincreate-fora-de-local)
- [Banco de teste `nando_lz_testing` não existe](#banco-de-teste-nando_lz_testing-não-existe)
- [Dependabot não abre PRs](#dependabot-não-abre-prs)

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

## `install-docker.sh` falhou com exit 22

O script espera **até 5 minutos** pelo primeiro boot (que compila vendor + assets dentro do container). Se o app não responder nesse prazo, ele falha com exit `22` em vez de imprimir sucesso falso.

Acompanhe o bootstrap em tempo real:

```bash
docker compose logs -f app
```

Causas típicas: rede lenta no `composer install`/`npm install` do primeiro boot (basta esperar e rodar o script de novo — o entrypoint retoma de onde parou), erro de migração ou banco fora do ar (o log mostra).

## Erro de conexão ao banco no Docker

Sintoma: "connection refused 127.0.0.1:5432" dentro do container.

Causa: `php artisan serve` só repassa ao worker HTTP as variáveis do allowlist `ServeCommand::$passthroughVariables`. As credenciais de banco que vêm do **ambiente do container** (e não do `.env`) não seriam repassadas por padrão. O `AppServiceProvider` estende esse allowlist com `DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` para garantir a paridade Local↔Docker.

Se você viu esse erro, confirme que o `AppServiceProvider` ainda estende o allowlist e que o compose está forçando `DB_HOST=db`.

> [!IMPORTANT]
> Não "resolva" adicionando `env_file:` ao `docker-compose.yml`. O `.env` é lido pelo Laravel via bind-mount; injetá-lo como ambiente real do container congela os valores e faz os `<env>` do `phpunit.xml` serem ignorados — o sintoma clássico é `php artisan test` apontar para o banco de dev e apagá-lo. Ver [DOCKER.md](DOCKER.md).

## `composer.lock` fora de sincronia

Sintoma: `composer validate --strict` reclama que o lock está desatualizado após editar o `composer.json`.

Correção:

```bash
composer update --lock
```

Isso ressincroniza o lock com o `composer.json` sem atualizar dependências além do necessário.

## Senha recusada pelo `superadmin:create` fora de `local`

**Isso é por design.** Fora do ambiente `local`, o comando exige senha forte (mín. 12 caracteres, com maiúsculas + minúsculas, números e símbolos) e recusa senhas triviais. Em `local`, o mínimo é 8. Use uma senha que atenda ao requisito — não é bug (há inclusive um teste que valida essa distinção: `password123` passa na regra de `local` e falha na regra forte).

Lembre também que o comando só roda enquanto **não existir nenhum usuário** e que `--name --email --password` só são aceitos em `local`/`dev`.

## Banco de teste `nando_lz_testing` não existe

Os testes usam o banco `nando_lz_testing` (definido em `phpunit.xml`, pgsql, com `RefreshDatabase`).

- **Local:** crie-o uma vez antes de rodar `php artisan test`:

    ```sql
    CREATE DATABASE nando_lz_testing;
    ```

- **Docker:** `docker/pg-init.sql` cria o banco automaticamente na **primeira inicialização** do volume `pgdata`. Se o seu volume é **pré-existente** (criado antes desse script), o init não roda de novo — crie manualmente:

    ```bash
    docker compose exec db psql -U postgres -c 'CREATE DATABASE nando_lz_testing;'
    ```

- **CI:** o serviço PostgreSQL 16 já provê esse banco.

> [!CAUTION]
> `docker compose down -v` apaga o volume `pgdata` inteiro — **os bancos de dev e de teste somem juntos**. Na próxima subida, o `pg-init.sql` recria o de teste, mas os dados de dev se perdem. Prefira `docker compose down` sem `-v`.

## Dependabot não abre PRs

Confirme a existência de `.github/dependabot.yml` na `main` e se a configuração do GitHub não desativou Dependabot security/updates para o repositório. A agenda configurada para GitHub Actions é semanal aos sábados; fora dela, use `Dependabot Updates` no GitHub Actions ou aguarde o próximo ciclo. Ver [AUTO_UPDATE_POLICY.md](AUTO_UPDATE_POLICY.md).
