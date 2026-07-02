# Modo Docker

Instalação sem PHP nem PostgreSQL locais — só Docker + Docker Compose (v2).

```bash
./scripts/install.sh docker
# ou diretamente:
./scripts/install-docker.sh
```

O `install-docker.sh` sobe os containers e **espera o app responder** — até 5 minutos no primeiro boot, que compila vendor + assets dentro do container. Se o app não responder nesse prazo, o script **falha com exit `22`** e sugere acompanhar o bootstrap com `docker compose logs -f app` (nunca imprime sucesso falso). O **entrypoint** faz o bootstrap. Nenhum script faz push.

<p align="center">
  <img src="images/dashboard-admin.png" alt="Dashboard do painel admin rodando no modo Docker" width="600">
</p>

## Serviços (`docker-compose.yml`)

- **app** — aplicação. Imagem construída a partir do `Dockerfile`; roda como **usuário não-root** (ver abaixo).
- **db** — `postgres:16`, com volume `pgdata`, healthcheck e `docker/pg-init.sql` montado em `/docker-entrypoint-initdb.d/`.

Ambos usam `restart: unless-stopped` — em uma VPS, a aplicação sobrevive a reboots sem intervenção. O compose força `DB_HOST=db` e `PHP_CLI_SERVER_WORKERS=4`.

> [!IMPORTANT]
> O compose **não usa `env_file:` de propósito.** O `.env` chega ao Laravel pelo bind-mount e é lido em runtime. Injetá-lo como ambiente real do container **congelaria os valores na criação** e faria o ambiente real vencer os `<env>` do `phpunit.xml` — foi um bug crítico real: `php artisan test` no container apontava para o banco de dev e o **apagava**. Não adicione `env_file:` ao compose.

## Container não-root

O container roda como usuário `app`, com **UID configurável** (`build.args.UID`, padrão `1000` — o usuário típico do host):

- Arquivos criados no bind-mount (`vendor/`, `node_modules/`, `.env`, `storage/`) permanecem **editáveis no host**.
- A troca Docker ↔ Local não deixa arquivos root-owned para trás.
- Se o seu UID não for 1000: `UID=$(id -u) docker compose up -d --build`.

## Banco de teste (`nando_lz_testing`)

O arquivo `docker/pg-init.sql` cria o banco `nando_lz_testing` na **primeira inicialização** do volume `pgdata`. Com isso:

```bash
docker compose exec app php artisan test
```

é **seguro**: o `phpunit.xml` aponta os testes para `nando_lz_testing` (via `<env>`), totalmente isolado do banco de dev `nando_lz`.

> [!NOTE]
> Volume `pgdata` **pré-existente** (criado antes desta versão)? O init script não roda de novo — crie o banco uma vez:
> `docker compose exec db psql -U postgres -c 'CREATE DATABASE nando_lz_testing;'`

> [!CAUTION]
> `docker compose down -v` **apaga o volume `pgdata` — todo o banco de dados** (dev e teste). Para parar sem destruir dados, use `docker compose down` sem `-v`.

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
- Usuário não-root `app` (`ARG UID=1000`).
- Usa `php artisan serve`.
- **Upgrade path para produção pesada:** php-fpm + nginx ou Laravel Octane. O `serve` é adequado para o starter, não para carga alta.

O `.dockerignore` usa `*` + `!docker/`: o Dockerfile só copia `docker/entrypoint.sh` (o código chega por bind-mount), então o contexto de build cai de ~200 MB para **poucos KB**.

## Entrypoint (`docker/entrypoint.sh`)

Bootstrap idempotente, executado ao subir o container:

1. Instala dependências se faltarem.
2. Prepara `.env` e a chave (`APP_KEY`).
3. Espera o banco ficar disponível.
4. Roda as migrations.
5. Sobe o servidor em `0.0.0.0:8000`.

Os marcadores de progresso são **arquivos finais** (`vendor/autoload.php`, `node_modules/.package-lock.json`, `public/build/manifest.json`), não diretórios: uma instalação interrompida deixa o diretório pela metade mas não o arquivo-marcador, então a reexecução **retoma do ponto certo**.

## Comandos

```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan test              # seguro — banco nando_lz_testing
docker compose exec app php artisan superadmin:create
```

Regras do `superadmin:create` (idênticas ao modo Local): só roda enquanto não existir usuário; interativo por padrão; `--name --email --password` só em `local`/`dev`; senha forte fora de `local`. Ver [LOCAL.md](LOCAL.md).

## Se der erro de conexão ao banco

Erro "connection refused 127.0.0.1:5432" no Docker geralmente é o **allowlist do `php artisan serve`**: ele só repassa ao worker HTTP as variáveis do allowlist `ServeCommand::$passthroughVariables`. O `AppServiceProvider` estende esse allowlist com `DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` para garantir a paridade Local↔Docker. Detalhes e diagnóstico em [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

## pgvector (opcional)

Não é usado por padrão. Para habilitar no modo Docker, troque a imagem do serviço `db` para `pgvector/pgvector:pg16` (drop-in, mesma major do PostgreSQL) e crie a extensão. Passos completos (Docker e Local, habilitar / usar / desabilitar) em [STACK.md](STACK.md).
