# Modo Local

Instalação sem Docker: usa o PHP, Composer, Node e PostgreSQL da sua máquina.

```bash
./scripts/install.sh local
# ou diretamente:
./scripts/install-local.sh
```

## Etapas do `install-local.sh`

1. **check-requirements** (`scripts/check-requirements.sh`) — verifica PHP ≥ 8.3, `ext-intl`, Composer, Node 22, `psql` e permissões de escrita em `storage` e `bootstrap/cache`.
   - Exit codes: `0` ok · `10` PHP · `11` ext-intl · `12` Composer · `13` Node · `14` psql · `15` permissões.
2. `composer install`
3. `npm install`
4. **bootstrap** (`scripts/bootstrap-app.sh`)
5. Oferece **criar o superadmin**.

## Bootstrap (`bootstrap-app.sh [--no-build]`)

Idempotente. Pode rodar de novo sem quebrar:

- Cria `.env` **só se faltar** (nunca sobrescreve um `.env` existente).
- Gera `APP_KEY` **só se estiver vazia**.
- Roda as migrations.
- Faz o build de assets (pule com `--no-build`).
- Respeita a variável `ARTISAN` (usada pelo modo Docker).

## Banco de dados

Confira as credenciais no `.env` (herdadas de `.env.example`, PostgreSQL):

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nando_lz
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Crie o banco `nando_lz` (e o `nando_lz_testing`, usado pelos testes) antes de migrar, se ainda não existirem. Ver [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

## Criar o superadmin

```bash
php artisan superadmin:create
```

- Só roda enquanto **não existir nenhum usuário** (os demais são criados pelo painel).
- Interativo por padrão. Os argumentos `--name --email --password` só são aceitos em ambiente `local`/`dev`.
- Em `local`: senha mínima de 8 caracteres.
- Fora de `local`: senha forte obrigatória (mín. 12 caracteres, maiúsculas + minúsculas, números e símbolos) — senhas triviais são recusadas.
- Cria o usuário com `email_verified_at` preenchido e acesso ao painel **ops**.

## Rodar a aplicação

```bash
php artisan serve       # http://127.0.0.1:8000
npm run dev             # assets em watch (desenvolvimento)
```

Painéis: `/ops`, `/admin` (default), `/support`.

## Comandos úteis

```bash
php artisan migrate            # aplica migrations
php artisan test               # roda a suíte Pest
./vendor/bin/pest              # idem
./scripts/test-app.sh [args]   # roda o Pest repassando argumentos
./scripts/reset-app.sh         # DESTRUTIVO: migrate:fresh + limpa caches (pede confirmação)
```

`reset-app.sh` aceita `--force` (ou `FORCE=1`) para pular a confirmação. Use com cuidado — apaga os dados.
