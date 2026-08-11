# Área: Configuração e banco

## Resumo

Há uma separação conceitual correta entre banco de desenvolvimento e banco de testes, mas uma mudança recente colocou `phpunit.xml` em conflito com o usuário provisionado pelos workflows. Esse é o primeiro defeito causal do run atual.

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

- **[✅ confirmado / high] Usuário e senha incompatíveis.** `phpunit.xml:31-33` força `DB_USERNAME=nando_lz` e não define `DB_PASSWORD`; o service cria `POSTGRES_USER=postgres` e `POSTGRES_PASSWORD=postgres`: `.github/workflows/auto-update.yml:23-27`. Os runs atuais registram `Role "nando_lz" does not exist` e `password authentication failed`.
- **[✅ confirmado / high] O gate falha antes de validar a aplicação.** `scripts/update-stack.sh:60-61` executa Pest, mas a conexão morre antes das migrations e dos fluxos HTTP. No run `31385014699`, foram observadas 28 falhas e 2 sucessos.

## Veredito

**flawed**: a solução deve escolher uma fonte única de credenciais e validar a conexão real, não apenas o healthcheck do processo PostgreSQL.
