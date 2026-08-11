# Status do sistema

Atualizado em 2026-08-10.

## Situação

As correções do incidente do `Auto Update` foram implementadas no checkout local sobre `origin/main` em `61125d0`. Composer, build, sintaxe shell, Pint, migrations e Pest passaram; a confirmação final do fluxo de publicação ainda depende de um novo run no GitHub Actions.

## Automação de manutenção

- `ci.yml` usa PostgreSQL com usuário `postgres` e autenticação `trust` somente no serviço efêmero dos dois PHP da matriz; nenhuma senha é versionada.
- `auto-update.yml` executa `npm ci`, Pint, Pest e `npm audit --audit-level=high` como gates, evita concorrência, atualiza PRs existentes por número e sempre tenta registrar falhas.
- A criação de PR com `GITHUB_TOKEN` ainda requer que a política administrativa do repositório permita workflows criarem e aprovarem Pull Requests.
- O workflow `auto-merge.yml` foi suspenso até existir uma decisão aprovada sobre label, checks obrigatórios e autorização de merge.

Detalhes, evidências e pendências estão no [incidente](incidents/0001-auto-update-ci.md) e na [auditoria profunda](deep-analysis/README.md).
