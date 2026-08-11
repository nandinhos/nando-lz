# Dados e releases

O deploy não cria um novo modelo de dados. Ele preserva duas áreas persistentes fora das releases:

| Recurso | Proprietário | Regra |
|---|---|---|
| Banco PostgreSQL | Laravel/migrations | `php artisan migrate --force` é executado em manutenção e deve manter compatibilidade com a release anterior. |
| `shared/.env` | Operação da VPS | Nunca é copiado para Git nem reescrito pelo workflow. |
| `shared/storage` | Aplicação em execução | É compartilhado por todas as releases para logs, sessões, cache e arquivos. |

O rollback automático restaura somente o symlink de código. Uma migration incompatível exige avaliação operacional antes de qualquer rollback manual.
