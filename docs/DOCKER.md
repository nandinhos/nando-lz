# Modo Docker

Instalação sem PHP nem PostgreSQL locais — só Docker + Docker Compose.

```bash
./scripts/install.sh docker
# ou diretamente:
./scripts/install-docker.sh
```

O `install-docker.sh` sobe os containers; o **entrypoint** faz o bootstrap. Nenhum script faz push.

## Serviços (`docker-compose.yml`)

- **app** — aplicação. Imagem construída a partir do `Dockerfile`.
- **db** — `postgres:16`, com volume `pgdata` e healthcheck.

O compose força `DB_HOST=db` e `PHP_CLI_SERVER_WORKERS=4`.

## Porta pública

A porta é **alta por padrão, para evitar conflitos**:

```
${APP_PORT:-18000}:8000
```

Ajuste via `APP_PORT` no `.env`. Ex.: `APP_PORT=18000` → a aplicação responde em **http://localhost:18000**.

## Dockerfile

- Base `php:8.4-cli-bookworm`.
- Extensões: `intl`, `pdo_pgsql`, `zip`.
- Node 22 e Composer.
- Usa `php artisan serve`.
- **Upgrade path para produção pesada:** php-fpm + nginx ou Laravel Octane. O `serve` é adequado para o starter, não para carga alta.

## Entrypoint (`docker/entrypoint.sh`)

Bootstrap idempotente, executado ao subir o container:

1. Instala dependências se faltarem.
2. Prepara `.env` e a chave (`APP_KEY`).
3. Espera o banco ficar disponível.
4. Roda as migrations.
5. Sobe o servidor em `0.0.0.0:8000`.

## Comandos

```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app php artisan superadmin:create
```

Regras do `superadmin:create` (idênticas ao modo Local): só roda enquanto não existir usuário; interativo por padrão; `--name --email --password` só em `local`/`dev`; senha forte fora de `local`. Ver [LOCAL.md](LOCAL.md).

## Se der erro de conexão ao banco

Erro "connection refused 127.0.0.1:5432" no Docker geralmente é o **allowlist do `php artisan serve`**: ele só repassa ao worker HTTP as variáveis do allowlist `ServeCommand::$passthroughVariables`. O `AppServiceProvider` estende esse allowlist com `DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` para garantir a paridade Local↔Docker. Detalhes e diagnóstico em [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

## pgvector (opcional)

Habilitável no serviço `db`. Casos de uso: embeddings / busca semântica. Não é usado por padrão. Passos completos (habilitar / usar / desabilitar) em [STACK.md](STACK.md).
