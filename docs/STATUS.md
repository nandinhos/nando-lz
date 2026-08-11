# Status do sistema

Atualizado em 2026-08-11.

## Situação

As correções do incidente do `Auto Update` foram implementadas no checkout local sobre `origin/main` em `61125d0`. Composer, build, sintaxe shell, Pint, migrations e Pest passaram; a confirmação final do fluxo de publicação ainda depende de um novo run no GitHub Actions.

## Automação de manutenção

- `ci.yml` usa PostgreSQL com usuário `postgres` e autenticação `trust` somente no serviço efêmero dos dois PHP da matriz; nenhuma senha é versionada.
- `auto-update.yml` executa `npm ci`, Pint, Pest e `npm audit --audit-level=high` como gates, evita concorrência, atualiza PRs existentes por número e sempre tenta registrar falhas.
- A criação de PR com `GITHUB_TOKEN` ainda requer que a política administrativa do repositório permita workflows criarem e aprovarem Pull Requests.
- O workflow `auto-merge.yml` foi suspenso até existir uma decisão aprovada sobre label, checks obrigatórios e autorização de merge.

## Publicação em produção

- O deploy de `main` é disparado somente após a conclusão bem-sucedida de `CI`, pelo workflow [deploy-production.yml](../.github/workflows/deploy-production.yml).
- A VPS publica releases atômicas em `/var/www/nandolz.fssdev.com.br/releases`, mantém `.env` e `storage` em `shared/`, exige `200` em `/up` e restaura o código anterior se o health check falhar.
- O contrato, a recuperação e os secrets necessários estão em [DEPLOYMENT.md](DEPLOYMENT.md); a decisão está registrada no [ADR 0001](adr/0001-deploy-atomico-via-github-actions.md).

Detalhes, evidências e pendências estão no [incidente](incidents/0001-auto-update-ci.md) e na [auditoria profunda](deep-analysis/README.md).
