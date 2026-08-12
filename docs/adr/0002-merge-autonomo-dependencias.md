# ADR 0002 — Merge autônomo de dependências com escopo fechado

- **Status:** accepted
- **Date:** `2026-08-11`
- **Owner:** manutenção do repositório

## Context

O repositório precisa permanecer atualizado e publicado sem revisão humana rotineira. O CI, a branch protection e o deploy atômico já existem, mas a política anterior deixava toda PR de manutenção aguardando merge manual. A superfície crítica é o token com poder de mesclar: ele não pode executar código de uma PR não confiável nem aceitar mudanças estruturais mascaradas de atualização.

## Options considered

| Option | Pros | Cons |
|---|---|---|
| **A — Árbitro próprio com origem e diff fechados** | Reaproveita CI/deploy, não executa código da PR privilegiadamente e deixa toda exceção rastreável. | Não automatiza majors, manifests ou código. |
| B — Auto-merge nativo para qualquer PR de bot | Configuração curta. | Não valida o escopo real nem separa mudanças seguras das estruturais. |
| C — Revisão humana de toda atualização | Menor risco imediato. | Não atende ao requisito de operação autossustentável. |

## Decision

Adotar A. `auto-update.yml` produz somente updates de locks; Dependabot produz somente alterações literais de referências de GitHub Actions. `autonomous-merge.yml` aceita apenas os bots e padrões de branch esperados, revalida o diff pela API, exige CI e auditoria verdes e faz merge por rebase com `--match-head-commit`. O workflow não faz checkout da PR e falha fechada: escopo/estado inesperado gera label e issue, sem merge.

## Consequences

Patch/minor dentro das constraints e updates de Actions passam a chegar à produção automaticamente depois de todas as evidências. Majors, mudanças em `composer.json`, `package.json`, workflows além de refs, scripts, migrations, aplicação e documentação de arquitetura exigem tratamento excepcional fora do fluxo autônomo. O deploy só recebe commits já mesclados e aprovados pela proteção da `main`.

## Trigger to revisit

Revisar se o volume de exceções BLOCKED ultrapassar três ciclos consecutivos, se houver incidente causado por update autônomo, se a equipe quiser automatizar majors com uma suite de compatibilidade adicional, ou se múltiplos repositórios exigirem um GitHub App com auditoria central.
