# Área: Scripts de atualização e resolução

## Resumo

Os scripts têm uma intenção boa de coletar falhas e gerar relatório, mas a classificação e o relatório não representam integralmente a política documentada.

## Implementado

- Coleta de falhas sem `set -e` e exit final: `scripts/update-stack.sh:14-15,28-34,75-100`.
- `--dry-run` evita atualizações de locks: `scripts/update-stack.sh:20-48`.
- Gates de Composer, audits, Pest e build: `scripts/update-stack.sh:50-70`.
- Resolver com timeouts, códigos de saída e JSON: `scripts/resolve-stack.sh:15-26,80-99`.
- `stack:sync --check`: `app/Console/Commands/SyncStackDocs.php:39-57`; teste: `tests/Feature/SanityTest.php:174-177`.

## Gaps

- **[✅ confirmado / medium] BLOCKED não interrompe o update.** `blocked_upstream` é produzido pelo resolver, mas o `auto-update` não o consome: `scripts/resolve-stack.sh:72-97`; `.github/workflows/auto-update.yml:83-100`.
- **[corrigido] `npm audit` agora é gate bloqueante.** O script usa `run "npm audit --audit-level=high"`, em paridade com a política; o lock atual foi atualizado e não reporta vulnerabilidades.
- **[✅ confirmado / medium] Relatório incompleto.** O script registra rótulos e snapshots, mas não dependências detalhadas, riscos, próximos passos, link de PR ou hash final: `scripts/update-stack.sh:75-96`; contrato em `docs/AUTO_UPDATE_POLICY.md:78-95`.
- **[✅ confirmado / low] Argumentos desconhecidos são aceitos.** Apenas o primeiro argumento exatamente igual a `--dry-run` tem efeito: `scripts/update-stack.sh:20-21`.

## Flaws

- **[✅ confirmado / medium] Resolução usa metadado travado para o pacote alvo.** A última versão estável vem da Packagist, mas a constraint `illuminate/*` vem de `filament/support` no lock atual: `scripts/resolve-stack.sh:44-62`. Uma mudança de compatibilidade na versão alvo só é descoberta indiretamente pelo Composer depois.
- **[✅ confirmado / medium] Status da landing pode mentir.** Relatórios falhos contêm `✅` de gates passados e `❌` do resultado; `Stack::lastUpdate()` testa `✅` primeiro: `scripts/update-stack.sh:80-84`; `app/Support/Stack.php:91-99`.
- **[✅ confirmado / medium] `app:setup` ignora erro do re-hash.** `exec('composer update --lock ...')` não captura o código de saída e o comando retorna sucesso: `app/Console/Commands/SetupProject.php:160-177`.

## Veredito

**partial/flawed**: a coleta básica existe, mas a política AUTO/REVIEW/BLOCKED ainda não é uma máquina de estados executável.
