# Modo Local

Instalação sem Docker: usa o PHP, Composer, Node e PostgreSQL da sua máquina.

```bash
./scripts/install.sh local
# ou diretamente:
./scripts/install-local.sh
```

<p align="center">
  <img src="images/login-ops.png" alt="Página de login do painel ops após a instalação" width="600">
</p>

## Etapas do `install-local.sh`

1. **check-requirements** (`scripts/check-requirements.sh`) — verifica a **versão do PHP** (≥ 8.3.0), a extensão `ext-intl`, a **presença** de Composer, Node e `psql` (sem checar a versão destes) e as permissões de escrita em `storage` e `bootstrap/cache`.
2. `composer install`
3. `npm install`
4. **bootstrap** (`scripts/bootstrap-app.sh`)
5. Oferece **criar o superadmin**.

<details>
<summary><strong>Exit codes do <code>check-requirements.sh</code></strong></summary>

| Código | Significado |
|--------|-------------|
| `0` | Tudo ok |
| `10` | PHP ausente ou versão < 8.3.0 |
| `11` | Extensão `ext-intl` ausente |
| `12` | Composer não encontrado |
| `13` | Node não encontrado |
| `14` | Cliente PostgreSQL (`psql`) não encontrado |
| `15` | Sem permissão de escrita em `storage`/`bootstrap/cache` |

</details>

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
- Cria o usuário com `email_verified_at` **atribuído explicitamente** (o campo fica fora do `$fillable` por design) e acesso ao painel **ops**.

> [!IMPORTANT]
> Em `local`: senha mínima de 8 caracteres. **Fora de `local`**: senha forte obrigatória — mínimo 12 caracteres, maiúsculas + minúsculas, números e símbolos; senhas triviais são recusadas.

## Rodar a aplicação

```bash
php artisan serve       # http://127.0.0.1:8000
npm run dev             # assets em watch (desenvolvimento)
```

Painéis: `/ops`, `/admin` (default), `/support`.

## Comandos úteis

```bash
php artisan migrate            # aplica migrations
php artisan test               # roda a suíte Pest (30 casos expandidos, banco nando_lz_testing)
./vendor/bin/pest              # idem
./scripts/test-app.sh [args]   # roda o Pest repassando argumentos
./scripts/reset-app.sh         # DESTRUTIVO: migrate:fresh + limpa caches (pede confirmação)
```

> [!CAUTION]
> `reset-app.sh` **apaga todos os dados do banco** (`migrate:fresh`). Aceita `--force` (ou `FORCE=1`) para pular a confirmação — use com cuidado, principalmente em automações.
