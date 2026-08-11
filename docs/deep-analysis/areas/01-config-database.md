# Área: Configuração e banco

## Resumo

O diagnóstico original encontrou conflito entre `phpunit.xml` e os services PostgreSQL. O CI e o Auto Update agora usam as mesmas variáveis de banco e autenticação `trust` apenas nos serviços efêmeros.

## Implementado

- PostgreSQL padrão e credenciais de exemplo: `.env.example:34-39`.
- PHPUnit direciona testes ao banco `nando_lz_testing`: `phpunit.xml:28-33`.
- Feature tests usam `RefreshDatabase`: `tests/Pest.php:6-10`.
- Service PostgreSQL 16 e database de teste: `.github/workflows/auto-update.yml:21-32`; `.github/workflows/ci.yml:26-37`.
- Docker força `DB_HOST=db` e cria o banco de testes na primeira inicialização: `docker-compose.yml:24-44`; `docker/pg-init.sql:1-6`.

## Gaps

- **[✅ confirmado / medium] Healthcheck parcial.** O healthcheck valida `postgres`, não as credenciais efetivas do PHPUnit: `.github/workflows/auto-update.yml:31`; `phpunit.xml:32`.
- **[⚪ médio] Provisionamento de `nando_lz` ausente.** Se a decisão for manter esse usuário para paridade com produção, faltam criação de role e senha em CI/Docker.
- **[⚪ médio] Seeder não é idempotente.** `DatabaseSeeder` sempre cria `test@example.com`, enquanto email é único: `database/seeders/DatabaseSeeder.php:20-23`; `database/migrations/0001_01_01_000000_create_users_table.php:14-21`.

## Flaws

- **[corrigido e validado] Usuário e senha incompatíveis.** PHPUnit, CI e Auto Update convergem em `postgres`/`nando_lz_testing`; os serviços efêmeros usam `trust`, sem senha versionada.
- **[corrigido e validado] Gate antes da aplicação.** A PR #7 passou nos dois jobs PHP após migrations, build e Pest.

## Veredito

**corrigido para CI e Auto Update**: a fonte de credenciais é única e a matriz remota comprovou a conexão real.
