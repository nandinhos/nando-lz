# Área: Docker e instalação

## Resumo

O modo Docker é bem delimitado para desenvolvimento: container não-root, volume persistente, healthcheck e bootstrap idempotente. Há divergências inevitáveis entre o banco do Docker e o banco do PHPUnit que precisam ser resolvidas por uma fonte única.

## Implementado

- Toolchain PHP 8.4, Node 22, extensões e usuário não-root: `Dockerfile:1-32`.
- App e banco com volume, porta alta e `depends_on` saudável: `docker-compose.yml:9-47`.
- Entrypoint instala dependências, gera chave, builda, migra e serve: `docker/entrypoint.sh:10-29`.
- Banco de testes separado na primeira inicialização: `docker/pg-init.sql:1-6`.
- Instalação aguarda HTTP e expõe comandos de diagnóstico: `scripts/install-docker.sh:39-53`.

## Gaps

- **[✅ confirmado / medium] `pg-init.sql` só executa no primeiro volume.** Volumes existentes exigem procedimento manual: `docker/pg-init.sql:1-6`; `docs/DOCKER.md:35-47`.
- **[⚪ médio] Não há smoke de container no CI.** O CI usa service PostgreSQL, não `docker compose build/up`.
- **[⚪ baixo] Dockerfile serve com `php artisan serve`.** A própria documentação chama isso de caminho de desenvolvimento e deixa PHP-FPM/Nginx/Octane como upgrade: `Dockerfile:1-4`; `docs/DOCKER.md`.

## Flaws

- **[✅ confirmado / high] Os testes Docker herdam o mesmo risco de credenciais.** O compose usa defaults `postgres`, enquanto o PHPUnit força `nando_lz`: `docker-compose.yml:35-44`; `phpunit.xml:28-33`.
- **[⚪ médio] O instalador ignora falha do `app:setup`.** Em modo interativo, `scripts/install-docker.sh:56-65` usa `|| true` e depois pode executar `docker compose down -v`; isso merece confirmação de que a perda do volume é sempre intencional nesse ponto.

## Veredito

**partial**: o desenho Docker é coerente para dev, mas o contrato de teste e a ação destrutiva do rebrand precisam de guardas mais fortes.
