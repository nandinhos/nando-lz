# Status do sistema

Atualizado em 2026-08-11.

## Situação

As correções do incidente do `Auto Update` foram mergeadas pela [PR #7](https://github.com/nandinhos/nando-lz/pull/7) em `24c9abf`. O CI remoto passou em PHP 8.3 e 8.4, e o GitGuardian também passou. A execução seca do ciclo confirmou Composer, auditorias, Pint, Pest e build verdes.

## Automação de manutenção

- `ci.yml` usa PostgreSQL com usuário `postgres` e autenticação `trust` somente no serviço efêmero dos dois PHP da matriz; nenhuma senha é versionada.
- `auto-update.yml` executa `npm ci`, Pint, Pest e `npm audit --audit-level=high` como gates, evita concorrência, atualiza PRs existentes por número e sempre tenta registrar falhas.
- A política administrativa agora permite `GITHUB_TOKEN` com escrita e criação/aprovação de PRs; o workflow mantém o preflight explícito para tornar uma futura regressão visível.
- O workflow `auto-merge.yml` foi suspenso até existir uma decisão aprovada sobre label, checks obrigatórios e autorização de merge.
- O monitor da landing lê o campo `Resultado geral` do relatório mais recente, sem inferir sucesso apenas por gates individuais verdes.

Detalhes, evidências e pendências estão no [incidente](incidents/0001-auto-update-ci.md) e na [auditoria profunda](deep-analysis/README.md).
