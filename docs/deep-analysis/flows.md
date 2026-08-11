# Mapa de fluxos

| Fluxo | Status | Trigger | Chamadas principais | Estado/efeito | Evidência e observação |
|---|---|---|---|---|---|
| Auto Update semanal | **flawed** | `schedule`/`workflow_dispatch` | checkout → Composer/Node → `update-stack.sh` → commit/push → PR | locks, README, relatório e branch são criados; PR não | `.github/workflows/auto-update.yml:5-8,50-115`; seis runs falhos |
| Gate Pest PostgreSQL | **flawed** | `./vendor/bin/pest` | PHPUnit → `phpunit.xml` → PostgreSQL service → `RefreshDatabase` | não chega a validar aplicação quando `nando_lz` não existe | `phpunit.xml:28-33`; `.github/workflows/auto-update.yml:23-32`; logs dos runs atuais |
| Publicação de PR | **flawed** | mudança fora de `docs/reports` | `gh pr create`; fallback `gh pr edit` | push existe, PR não existe | `.github/workflows/auto-update.yml:78-110`; erro `createPullRequest` |
| Dispatch do CI | **partial** | após PR | `gh workflow run ci.yml --ref branch` | não é alcançado após falha de PR | `.github/workflows/auto-update.yml:112-115`; `.github/workflows/ci.yml:8` |
| Notificação de incidente | **flawed** | falha do ciclo | `gh issue create` | step skipped; nenhum issue para os seis runs | `.github/workflows/auto-update.yml:117-126`; jobs dos runs |
| Landing/status da stack | **partial** | `GET /` | `routes/web.php` → `Stack::snapshot()` → Blade | rota degrada sem banco, mas pode sinalizar relatório falho como OK | `routes/web.php:6-8`; `app/Support/Stack.php:62-99`; `welcome.blade.php:209-225` |
| Login e painéis | **partial** | `/ops`, `/admin`, `/support` | Filament session/auth/CSRF | código e testes cobrem o fluxo, mas o run atual não prova execução verde | `app/Providers/Filament/*PanelProvider.php`; `tests/Feature/SanityTest.php:78-110` |
| Rebrand `app:setup` | **partial** | comando Artisan | reescrita → remoção da manutenção → `composer update --lock` | preview é reversível; falha do re-hash é ignorada | `app/Console/Commands/SetupProject.php:141-177` |
