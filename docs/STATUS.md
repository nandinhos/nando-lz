# Status do sistema

Atualizado em 2026-08-11.

## Situação

A `main` está publicada em produção no commit `fc39b93`: CI, deploy via SSH restrito e health check HTTPS passaram. A manutenção passa a usar PRs autônomas de escopo restrito, com merge e deploy somente depois dos gates independentes.

## Automação de manutenção

- `ci.yml` usa PostgreSQL com usuário `postgres` e autenticação `trust` somente no serviço efêmero dos dois PHP da matriz; nenhuma senha é versionada.
- `auto-update.yml` atualiza apenas locks Composer/NPM dentro das constraints, executa Composer audit, NPM audit, Pint, Pest e build; só classifica uma PR como candidata quando o diff é estritamente permitido.
- `autonomous-merge.yml` não faz checkout de PRs: valida origem/branch, escopo do diff, CI verde em PHP 8.3/8.4, auditoria de dependências e GitGuardian antes de solicitar merge por rebase com SHA fixado.
- Dependabot mantém somente GitHub Actions; majors, manifests, migrations, código e qualquer diff fora da política são bloqueados, documentados por issue e não chegam à `main`.

## Publicação em produção

- O deploy de `main` é disparado somente após a conclusão bem-sucedida de `CI`, pelo workflow [deploy-production.yml](../.github/workflows/deploy-production.yml).
- A VPS publica releases atômicas em `/var/www/nandolz.fssdev.com.br/releases`, mantém `.env` e `storage` em `shared/`, exige `200` em `/up` e restaura o código anterior se o health check falhar.
- O contrato, a recuperação e os secrets necessários estão em [DEPLOYMENT.md](DEPLOYMENT.md); a decisão está registrada no [ADR 0001](adr/0001-deploy-atomico-via-github-actions.md).

O contrato de autonomia está em [AUTO_UPDATE_POLICY.md](AUTO_UPDATE_POLICY.md) e a decisão de segurança em [ADR 0002](adr/0002-merge-autonomo-dependencias.md).

Detalhes, evidências e pendências estão no [incidente](incidents/0001-auto-update-ci.md) e na [auditoria profunda](deep-analysis/README.md).
