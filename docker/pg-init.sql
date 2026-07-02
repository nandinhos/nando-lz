-- Executado apenas na PRIMEIRA inicialização do volume pgdata.
-- Cria o banco de teste usado por phpunit.xml (nando_lz_testing), permitindo
-- `docker compose exec app php artisan test` sem tocar no banco de dev.
-- Volume já existente? Rode manualmente:
--   docker compose exec db psql -U postgres -c 'CREATE DATABASE nando_lz_testing;'
CREATE DATABASE nando_lz_testing;
