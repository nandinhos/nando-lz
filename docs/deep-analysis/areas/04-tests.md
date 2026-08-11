# Área: Testes e gates

## Resumo

Os testes cobrem uma quantidade útil de fluxos de sanidade, com 30 casos expandidos no checkout local. Ainda assim, o CI atual está vermelho por Pint e o Auto Update usa um banco incompatível; os testes não servem hoje como prova de qualidade do SHA remoto.

## Implementado

- Suites Unit/Feature: `phpunit.xml:7-18`.
- Isolamento de Feature tests com `RefreshDatabase`: `tests/Pest.php:6-10`.
- Cobertura dos três painéis, login, logout, superadmin, migration, `stack:sync` e `app:setup`: `tests/Feature/SanityTest.php:15-215`.
- Teste unitário real de detecção de portas e limpeza de socket: `tests/Unit/PortsTest.php:5-30`.

## Gaps

- **[✅ confirmado / medium] Não há testes automatizados para os workflows shell.** Não há cobertura de `update-stack.sh`, `resolve-stack.sh`, criação de PR, `blocked_upstream` ou notificação de issue.
- **[✅ confirmado / medium] Não há teste do contrato de auto-merge.** O nome dos checks da matriz e o gate do workflow não têm uma asserção que evite drift.
- **[⚪ médio] Não há verificação browser.** A cobertura HTTP usa requests/Livewire, não renderização em navegador.

## Flaws

- **[✅ confirmado / high] CI vermelho por Pint.** O run `29749040557` falhou em PHP 8.3 e 8.4 com `fully_qualified_strict_types`, `no_extra_blank_lines`, `ordered_imports` e `blank_line_between_import_groups` em `tests/Feature/SanityTest.php`. O check local `vendor/bin/pint --test` reproduziu a falha.
- **[✅ confirmado / high] O run de manutenção não alcança o contrato de testes.** Em `31385014699`, Pest terminou `28 failed, 2 passed`; o erro era de autenticação PostgreSQL, não uma regressão funcional isolada.
- **[❌ refutado] “A suíte tem somente 22 casos no estado atual”.** `./vendor/bin/pest --list-tests` enumerou 30 testes expandidos no checkout; os docs ainda divergem e isso é um problema documental, não uma ausência de casos.

## Veredito

**partial/flawed**: a cobertura funcional é boa para um starter, mas os gates de estilo, infraestrutura e automação não estão regression-clean.
