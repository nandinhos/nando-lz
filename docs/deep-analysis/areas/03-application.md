# Área: Aplicação Laravel/Filament

## Resumo

O starter não contém domínio de negócio e a superfície HTTP principal é simples. Os fluxos de painel e autenticação estão implementados, mas qualquer usuário autenticado acessa os três painéis por decisão explícita do starter.

## Implementado

- Rota raiz e health endpoint: `routes/web.php:6-8`; `bootstrap/app.php:8-13`.
- Três painéis com login/perfil e middleware de sessão, CSRF e autenticação: `app/Providers/Filament/AdminPanelProvider.php:27-61`; equivalentes em `OpsPanelProvider.php` e `SupportPanelProvider.php`.
- Guard Eloquent/session: `config/auth.php:18-20,40-68`; `app/Models/User.php:15-18`.
- Logout por POST e testes de GET 405, encerramento e CSRF: `tests/Feature/SanityTest.php:94-110`.
- `Stack::snapshot()` degrada quando o banco está fora do ar: `app/Support/Stack.php:62-69`.

## Gaps

- **[⚪ médio] Não há RBAC entre painéis.** `User::canAccessPanel()` retorna `true`: `app/Models/User.php:22-29`. Isso é coerente com o starter sem resources, mas não é uma política suficiente quando surgirem dados reais.
- **[⚪ médio] Não há smoke browser separado.** A suíte usa requests/Livewire; não há evidência de Playwright ou de um navegador real nos gates.

## Flaws

- **[✅ confirmado / high] A aplicação não foi validada pelo run atual.** O Pest falha na conexão de banco antes dos testes de login/painel; a existência do código não equivale a uma execução verde na revisão `61125d0`.
- **[❌ refutado] “Os painéis não têm autenticação”.** Os providers registram `Authenticate`, sessão, CSRF e `authMiddleware`; os testes cobrem redirect de visitante: `app/Providers/Filament/AdminPanelProvider.php:48-61`; `tests/Feature/SanityTest.php:78-92`.

## Veredito

**partial**: a base HTTP é coerente para um starter, mas não deve ser confundida com autorização de produto e precisa de uma execução verde do CI para ser considerada verificada.
