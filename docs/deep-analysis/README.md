# Auditoria profunda do nando-lz

Auditoria read-only do sistema e do incidente do workflow `Auto Update`, com foco na versão efetiva de `origin/main` em `61125d0` e no run `31385014699` de 2026-08-10.

> Este conjunto registra o baseline anterior à implementação. O estado pós-correção está em [docs/STATUS.md](../STATUS.md) e o postmortem está em [incidents/0001-auto-update-ci.md](../incidents/0001-auto-update-ci.md).

## Índice

- [Resumo executivo](00-executive-summary.md)
- [Metodologia e evidências](01-methodology.md)
- [Roadmap priorizado](roadmap.md)
- [Mapa de fluxos](flows.md)
- [Perguntas para o mantenedor](questions.md)
- [Áreas auditadas](areas/)

## Como regenerar

Este relatório foi produzido a partir de uma auditoria read-only. Para atualizar a evidência:

1. sincronize as referências sem alterar o working tree: `git fetch origin main`;
2. confirme o SHA auditado com `git rev-parse origin/main`;
3. liste os runs: `gh run list --workflow auto-update.yml --limit 10`;
4. inspecione o run e os logs: `gh run view <run-id> --json jobs` e `gh run view <run-id> --log`;
5. confira permissões e proteção: `gh api repos/<owner>/<repo>/actions/permissions/workflow` e `gh api repos/<owner>/<repo>/branches/main/protection`;
6. reexecute os checks locais apropriados antes de atualizar as conclusões.

Não substitua a evidência dos runs por uma nova execução local: o checkout pode estar em outro SHA e o ambiente local não é equivalente ao runner do GitHub.
