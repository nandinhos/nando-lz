# Instalação

Dois modos de instalação, um único ponto de entrada.

```bash
./scripts/install.sh        # menu: 1) Local  2) Docker
./scripts/install.sh local  # direto no modo Local
./scripts/install.sh docker # direto no modo Docker
```

Todos os scripts usam `set -euo pipefail`, são idempotentes e **nenhum faz push**.

## Qual modo escolher

| Modo | Quando usar | Requisitos |
|------|-------------|------------|
| **Local** | Você já tem PHP, Composer, Node e PostgreSQL na máquina | PHP ≥ 8.3, ext-intl, Composer, Node 22, `psql` |
| **Docker** | Você não quer instalar PHP/PostgreSQL localmente | Docker + Docker Compose |

## Pré-requisitos (modo Local)

Verificados por `scripts/check-requirements.sh`:

- PHP ≥ 8.3
- Extensão `ext-intl`
- Composer
- Node 22
- `psql` (cliente PostgreSQL)
- Permissões de escrita em `storage` e `bootstrap/cache`

Exit codes do `check-requirements.sh`: `0` ok · `10` PHP · `11` ext-intl · `12` Composer · `13` Node · `14` psql · `15` permissões.

No modo **Docker** não é preciso PHP nem PostgreSQL locais — só Docker.

## O que cada modo faz

- **Local** (`install-local.sh`): check-requirements → `composer install` → `npm install` → bootstrap → oferece criar o superadmin. Detalhes em [LOCAL.md](LOCAL.md).
- **Docker** (`install-docker.sh`): sobe os containers; o entrypoint faz o bootstrap. Detalhes em [DOCKER.md](DOCKER.md).

## Banco de dados

O `.env.example` já vem configurado para PostgreSQL:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nando_lz
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Os testes usam um banco separado, `nando_lz_testing` (definido em `phpunit.xml`, também pgsql, com `RefreshDatabase`). O `.env` real **nunca** é versionado.

## Depois de instalar

Crie o primeiro admin:

```bash
php artisan superadmin:create
```

Ver [LOCAL.md](LOCAL.md) / [DOCKER.md](DOCKER.md) para os comandos completos e [TROUBLESHOOTING.md](TROUBLESHOOTING.md) se algo falhar.
