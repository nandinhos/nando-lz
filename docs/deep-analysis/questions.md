# Perguntas para o mantenedor

Estas decisões não devem ser inferidas pelo agente:

1. A organização/repositório deve permitir que `GITHUB_TOKEN` crie PRs, ou o fluxo deve obrigatoriamente usar um GitHub App com instalação restrita?
2. O banco de testes deve usar o usuário genérico `postgres` em CI/Docker ou um usuário dedicado `nando_lz` provisionado de forma explícita? A escolha deve ser única e documentada.
3. A label `auto-merge` ainda faz parte da política? Se sim, quais checks exatos e qual condição de mudança autorizam a label?
4. Falha de `npm audit --audit-level=high` bloqueia a manutenção ou é apenas informativa? O código e a política hoje divergem.
5. `BLOCKED` deve impedir commit/PR no próprio `auto-update` ou apenas ser observado pelo `compat-watch`?
6. Os três painéis continuarão compartilhando autorização enquanto o starter não tiver domínio, ou já existe uma matriz de papéis planejada?
7. A documentação deve usar a contagem expandida do Pest (30 casos) ou uma contagem lógica de definições (18 `it`)?
8. O helper de GitHub App é protótipo local, ou deve virar parte executável do workflow? Se for executável, onde serão declarados `PyJWT`/`cryptography` e os secrets?
