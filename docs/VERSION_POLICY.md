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

- Uma atualização da classe **AUTO** recebe release **PATCH automática** depois que seu merge, CI e deploy em produção forem bem-sucedidos. O workflow calcula a próxima tag a partir da última release estável e publica notas geradas pelo GitHub, com vínculo ao deploy comprovado.
- A automação só considera PRs marcadas como `autonomous-candidate`; merges humanos, documentação e mudanças estruturais não geram tag acidentalmente.
- Releases excepcionais de `PATCH`, `MINOR` ou `MAJOR` usam o gatilho manual `Publish release`, com tag explícita e o SHA já implantado. O workflow recusa tag inválida, duplicada, SHA fora da `main` ou SHA sem deploy bem-sucedido.
- **Todo release aponta para um commit da `main` com CI e deploy verdes**; o health check HTTPS faz parte do deploy.
- Usuários clonam sempre um **estado conhecido-bom** (a tag mais recente ou a `main` verde).
- O identificador de build no rodapé da sidebar **confirma visualmente** qual versão está implantada (ver §5.6 em [STACK.md](STACK.md)).

> [!NOTE]
> O `CHANGELOG.md` continua sendo o histórico editorial dos marcos do starter. As GitHub Releases são o registro cronológico e imutável de cada atualização autônoma publicada.

## Rollback (§11)

Se uma atualização quebrar algo depois de mesclada:

1. **Reverta o PR de manutenção** que introduziu o problema.
2. Gere uma **tag de correção** a partir da `main` já revertida (com CI verde).
3. Confira o build id no rodapé para validar a versão implantada.

O histórico de versões fica em [CHANGELOG.md](../CHANGELOG.md) (Keep a Changelog + SemVer).
