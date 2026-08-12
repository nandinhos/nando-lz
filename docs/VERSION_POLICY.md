# Política de Versões

## Decisão de upgrade

O que atualizar e como depende da classe da mudança (detalhe completo das classes em [AUTO_UPDATE_POLICY.md](AUTO_UPDATE_POLICY.md)):

| Tipo de mudança | Tratamento |
|-----------------|------------|
| **Patch / minor compatível** | Autônomo (classe AUTO). Aplica na branch, valida, passa por CI/árbitro e mescla por rebase sem revisão humana. |
| **Major** (Laravel, Filament, PHP, Livewire) | Revisão humana (classe REVIEW). Branch + relatório + PR `needs-human-approval`. Merge exclusivamente humano. |
| **Incompatibilidade upstream** | Bloqueado (classe BLOCKED). Sem PR; issue rastreadora + monitoramento semanal. |

A resolução da stack (Filament limitante) roda antes de qualquer upgrade — ver [STACK.md](STACK.md).

## Exclusão de versões instáveis

Nada de alpha/beta/RC/dev/nightly **sem autorização humana registrada em issue**. Exemplo: o Filament `5.7.0-beta1` existe hoje, mas está fora do alvo. Só versões estáveis entram na stack por padrão.

## SemVer próprio do starter

O nando-lz tem **versionamento semântico próprio** (ex.: `v1.0.0`), **independente** das versões da stack (Laravel/Filament/etc.). A versão do starter descreve o estado do próprio repositório, não das dependências.

## Releases e tags

- Releases SemVer são **taggeadas manualmente** (`gh release create vX.Y.Z`) pelo mantenedor ou pelo agente, ao fechar um **conjunto relevante de mudanças** — não há mecanismo automático de tag por merge.
- **Todo release aponta para um commit da `main` com CI verde** (a branch protection garante que nada entra na `main` sem verde).
- Usuários clonam sempre um **estado conhecido-bom** (a tag mais recente ou a `main` verde).
- O identificador de build no rodapé da sidebar **confirma visualmente** qual versão está implantada (ver §5.6 em [STACK.md](STACK.md)).

> [!NOTE]
> Merges de manutenção (patch/minor de lock) podem se acumular entre releases — a tag marca um estado revisado e nomeado, não cada merge individual.

## Rollback (§11)

Se uma atualização quebrar algo depois de mesclada:

1. **Reverta o PR de manutenção** que introduziu o problema.
2. Gere uma **tag de correção** a partir da `main` já revertida (com CI verde).
3. Confira o build id no rodapé para validar a versão implantada.

O histórico de versões fica em [CHANGELOG.md](../CHANGELOG.md) (Keep a Changelog + SemVer).
