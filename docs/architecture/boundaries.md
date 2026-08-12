# Limites de publicação

| Limite | Responsabilidade atual | Seam e gatilho de extração |
|---|---|---|
| GitHub Actions | Aguarda `CI` verde na `main`, entrega apenas o SHA ao servidor. | Migrar para runner privado ou OIDC se a política impedir secret SSH em runner hospedado. |
| Árbitro de manutenção | Lê metadados e diffs pela API; aceita apenas bots/branches/diffs previstos e pede merge por rebase com SHA fixado. | Separar em GitHub App de menor privilégio se houver mais de um repositório ou necessidade de auditoria central. |
| Launcher SSH | Restringe a chave a `deploy <SHA>` e verifica que o SHA pertence à `main`. | Criar usuário de deploy dedicado quando a VPS deixar de permitir o comando forçado no `root`. |
| Script de release | Prepara release, ativa manutenção, migra, troca `current`, verifica `/up` e faz rollback de código. | Extrair para ferramenta de releases se houver segundo ambiente ou mais de uma aplicação com o mesmo ciclo. |
| Nginx + PHP-FPM | Resolve `current/public` e executa a release ativa. | Introduzir pool/host dedicado se métricas mostrarem contenção com os outros sites da VPS. |

O limite importante é que nenhuma etapa altera a release ativa antes de a nova estar montada com dependências e assets. `shared/.env` e `shared/storage` permanecem pertencentes à operação, não ao checkout Git.
