# Deploy de produção

## Contrato operacional

| Item | Valor |
|---|---|
| Aplicação | `nandolz.fssdev.com.br` |
| Health check | `https://nandolz.fssdev.com.br/up` |
| Raiz de releases | `/var/www/nandolz.fssdev.com.br` |
| Runtime | Nginx + PHP 8.5-FPM |
| Gatilho | conclusão bem-sucedida do workflow `CI` na `main` |
| Autorização | chave SSH exclusiva, forçada a `deploy <SHA>` |

O workflow [deploy-production.yml](../.github/workflows/deploy-production.yml) não executa em Pull Requests e não possui permissão de escrita no repositório. Ele recebe os secrets apenas do Environment `production` e solicita ao servidor a publicação do SHA exato aprovado pelo CI.

## Sequência de publicação

1. O launcher `/usr/local/sbin/nandolz-deploy` valida o comando SSH e confirma que o SHA pertence à `main` remota.
2. [scripts/deploy-production.sh](../scripts/deploy-production.sh) prepara uma nova release em `releases/<timestamp>_<sha>` sem alterar `current`.
3. A release recebe os links para `shared/.env` e `shared/storage`, instala dependências de produção, gera assets e grava `build.json` com o SHA curto.
4. A aplicação entra brevemente em manutenção; migrations e `php artisan optimize` são executados antes da troca do symlink `current`.
5. O PHP-FPM é recarregado, a aplicação volta a aceitar tráfego e o deploy exige `200` em `/up` via HTTPS/TLS.
6. Se qualquer etapa posterior à troca falhar, `current` volta para a release anterior e o PHP-FPM é recarregado. A release que falhou permanece para diagnóstico.
7. O script conserva as cinco releases mais recentes. O rollback restaura código; migrations de banco devem preservar compatibilidade retroativa até a próxima publicação validada.

## Bootstrap da chave e dos secrets

Os valores são exclusivos do Environment `production` e nunca entram no repositório:

| Secret | Finalidade |
|---|---|
| `DEPLOY_SSH_HOST` | Endereço da VPS |
| `DEPLOY_SSH_PORT` | Porta SSH não padrão |
| `DEPLOY_SSH_USER` | Usuário do deploy, hoje `root` com comando forçado |
| `DEPLOY_SSH_PRIVATE_KEY` | Chave Ed25519 exclusiva do workflow |
| `DEPLOY_SSH_KNOWN_HOSTS` | Chave pública do host para validação estrita de SSH |

A chave pública recebe as restrições `command="/usr/local/sbin/nandolz-deploy"`, `no-pty`, `no-port-forwarding`, `no-agent-forwarding`, `no-X11-forwarding` e `no-user-rc`. Ela não serve como shell remoto geral.

## Operação e recuperação

- Para publicar novamente um commit já presente na `main`, execute `Deploy production` manualmente no GitHub Actions. O servidor continuará aceitando somente SHA ancestral da `main`.
- Em caso de falha, consulte o log do workflow e a release preservada em `releases/`; não execute `git pull` sobre `current`.
- Para rollback manual de código, aponte `current` para uma release anterior e recarregue `php8.5-fpm`. Avalie migrations antes disso: o mecanismo automático não faz downgrade de banco.
