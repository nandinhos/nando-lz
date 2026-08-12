# Contratos de interface do deploy

| Interface | Entrada | Sucesso | Falha segura |
|---|---|---|---|
| GitHub Actions → SSH | `deploy <SHA-40-hex>` | launcher aceita apenas SHA ancestral de `origin/main` | comando inválido ou SHA fora da `main` é recusado |
| Release → Laravel | checkout, `.env` e `storage` compartilhados | `composer install`, `npm ci`, build, migration e `optimize` | `current` permanece inalterado antes da ativação |
| Release → Nginx | symlink `current` | `php8.5-fpm` recarregado | aponta novamente para a release anterior |
| Nginx → health check | `https://nandolz.fssdev.com.br/up` com SNI local | resposta HTTP `200` | rollback automático de código após ativação |
| CI → árbitro de manutenção | SHA de um CI concluído, metadados e diff de PR | origem, escopo e todos os checks exigidos comprovados | não faz checkout, não mescla e abre issue quando o escopo é inválido |

O SSH valida a chave de host com `DEPLOY_SSH_KNOWN_HOSTS`; o workflow não usa `StrictHostKeyChecking=no` nem executa shell remoto arbitrário.
