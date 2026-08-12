# ADR 0003 — Release automatizada após produção saudável

- **Status:** accepted
- **Date:** `2026-08-12`
- **Owner:** manutenção do repositório

## Context

O repositório já valida candidatos de dependências por CI, os mescla com escopo fechado e os publica por deploy atômico com health check HTTPS. Sem uma release correspondente, porém, o histórico SemVer fica atrasado e não oferece um ponto imutável para cada atualização autônoma já comprovada em produção.

## Options considered

| Option | Pros | Cons |
|---|---|---|
| **A — Publicar PATCH após deploy bem-sucedido** | A tag representa um estado implantado, saudável e recuperável; não adiciona acesso à VPS. | Uma release é criada para cada candidato autônomo elegível. |
| B — Criar tag ao mesclar a PR | Implementação curta. | A tag pode apontar para código que ainda não chegou à produção. |
| C — Manter releases manuais | Controle editorial máximo. | Deixa o SemVer atrasado e exige intervenção rotineira. |

## Decision

Adotar A. `publish-release.yml` é disparado após `Deploy production` bem-sucedido e procura a PR mesclada correspondente ao SHA. Ele só publica automaticamente quando a PR ainda possui o selo `autonomous-candidate`, calcula o próximo `PATCH` a partir da última release estável, confere que o SHA pertence à `main`, exige uma execução de deploy concluída com sucesso e recusa tags duplicadas. O mesmo workflow oferece publicação manual com tag explícita para marcos `MINOR`/`MAJOR` ou recuperações, sob as mesmas provas de produção.

## Consequences

Cada atualização autônoma passa a ter uma GitHub Release e tag imutável que aponta para o mesmo SHA implantado. Merges humanos e mudanças fora do escopo não recebem release automática. O `CHANGELOG.md` continua registrando marcos editoriais; as notas da GitHub Release preservam o vínculo com o deploy e a PR que a originou.

## Trigger to revisit

Revisar se atualizações de dependência se tornarem frequentes demais para uma release por ciclo, se a equipe passar a exigir artefatos anexos assinados, se houver pré-releases/canais estáveis, ou se o SemVer precisar ser calculado automaticamente a partir de convenções de commit.
