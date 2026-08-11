# Metodologia e evidências

## Intenção

Explicar por que o `Auto Update #6` falhou e avaliar a qualidade operacional do sistema completo o suficiente para definir uma ordem segura de trabalho. Esta é uma auditoria, não uma implementação; nenhum arquivo de aplicação foi alterado.

## Especificação da auditoria

O alvo foi a versão efetiva do repositório remoto em `origin/main` (`61125d0`) e o caminho acionado por `schedule` nos seis runs semanais. O checkout local (`ea99876`) foi mantido sem fast-forward; diferenças entre ambos foram explicitamente comparadas.

## Áreas

| Área | Pergunta principal |
|---|---|
| `ci-auto-update` | O ciclo consegue executar, publicar, notificar e disparar o CI? |
| `config-database` | As credenciais, migrations e ambientes são coerentes? |
| `update-scripts` | Os scripts classificam, validam, reportam e retornam falhas corretamente? |
| `application` | Os fluxos HTTP, painéis e autenticação são coerentes? |
| `tests` | Os testes são executáveis, confiáveis e suficientes para os gates? |
| `dependencies-build` | A stack, locks, scripts e build são reproduzíveis? |
| `docker-installation` | Local, Docker e CI mantêm paridade operacional? |
| `docs-security-ops` | Documentação, permissões e automações refletem o código real? |

## Execução e evidências

- Scout local: manifests, árvore, `git status`, histórico, branches e instruções disponíveis.
- Código remoto: `git show origin/main:<path>` e comparação `HEAD..origin/main`.
- GitHub Actions: `gh run list`, `gh run view --json`, `gh run view --log` e annotations do job `93443379733`.
- GitHub configuration: `gh api` para permissões de workflow, proteção da `main`, branches, workflows, PRs e issues.
- Checks locais: `bash -n` nos scripts; `pest --list-tests`; `composer validate --strict`; `vendor/bin/pint --test`; `npm run build`.
- Documentação atual: consulta Context7 para a semântica de `GITHUB_TOKEN`, permissões de workflow e restrição administrativa de criação/aprovação de PRs.

## Verificação adversarial

Os achados `high` foram mantidos somente quando sobreviveram ao cruzamento entre código, logs reais e estado remoto. Quatro leitores read-only retornaram JSON estruturado para as áreas de banco, aplicação e scripts; tentativas adicionais via CLI foram bloqueadas por `HTTP 429` do provedor (sete casos) e uma foi interrompida por falta de resposta. A tentativa de iniciar um verificador nativo separado atingiu o limite de threads do ambiente.

Como compensação explícita, foi feita uma verificação manual independente dos caminhos de chamada: `update-stack.sh → pest → relatório → gh pr create/edit → issue`, `phpunit.xml → service PostgreSQL`, e `auto-merge.yml → branch protection`. Portanto, o relatório não apresenta “verificação por segundo agente” onde ela não ocorreu.

## Limitações

- O relatório não afirma que a suíte Pest passa: o ambiente local não foi reconstruído no SHA remoto nem recebeu o banco efêmero com as credenciais corrigidas.
- Bundles gerados em `public/js` e fontes vendorizadas não foram revisados linha a linha; foram tratados como artefatos de build.
- A configuração administrativa do GitHub foi observada via API, mas a origem organizacional exata da restrição de PR deve ser confirmada no painel de Settings.
- Nenhuma alteração, commit, push, PR, issue, migration ou reset foi executado.
