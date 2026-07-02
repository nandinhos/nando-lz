# Stack e Compatibilidade

## Stack de referência (§3)

Travada e verificada. **Filament é o pacote limitante:** a major do Laravel é derivada do que o Filament estável suporta.

| Componente | Versão | Observação |
|-----------|--------|------------|
| Laravel | 13.18.0 | major derivada do Filament |
| Filament | 5.6.7 | pacote limitante |
| Livewire | 4.3.3 | transitivo via Filament — **nunca fixar direto** |
| Pest | 4.7 | testes |
| PHP | `^8.3` | dev rodou em 8.4 |
| PostgreSQL | 16 | |
| Node | 22 | |

Versões instáveis (alpha/beta/RC/dev/nightly) são **proibidas sem autorização humana em issue**. Exemplo: o Filament `5.7.0-beta1` existe hoje, mas está fora do alvo.

## Resolução de compatibilidade (§4)

### Filament limitante

Não se escolhe a major do Laravel de forma independente. Escolhe-se a maior versão estável do Filament e deriva-se dela a stack. Assim o starter nunca fica preso a uma combinação que o Filament ainda não suporta.

### Ordem de resolução (§4.1)

Implementada por `scripts/resolve-stack.sh`:

1. **Filament limitante** — parte da versão estável do Filament.
2. **Constraint `illuminate/*`** lida do `composer.lock`.
3. **Maior major suportada do Laravel** dentro dessa constraint.
4. **PHP mínimo** compatível.
5. **Livewire / Pest** compatíveis.
6. **PostgreSQL**.

### Janela de incompatibilidade (§4.2)

Quando sai uma nova major do Laravel mas o Filament estável ainda não a suporta, entra-se numa **janela de incompatibilidade**. Nesse período **não se atualiza** para a major nova — espera-se o upstream. O workflow `compat-watch.yml` vigia essa janela semanalmente e mantém uma issue rastreadora (`blocked-upstream`); quando o Filament liberar, a issue é fechada. Ver `AUTO_UPDATE_POLICY.md`.

## Como o `resolve-stack.sh` funciona

Ponto único de resolução da stack. Saída em **JSON**. Exit codes: `0` ok · `1` erro de rede/parse · `2` PHP indisponível.

Campos do JSON incluem:

- `filament`
- `laravel` — com `illuminate_constraint`, `max_supported_major`, `target_major`
- `livewire`
- `pest`
- `php`
- `postgres`
- `blocked_upstream` — verdadeiro quando há incompatibilidade upstream (§4.2)

Esse JSON alimenta a classificação de mudanças (§7.2) e os workflows de automação.

## Identificador de build na sidebar (§5.6)

Todos os painéis exibem o identificador de build no rodapé da sidebar. Ele confirma visualmente qual versão está implantada.

Classe: `App\Support\Build::id()`. **Precedência** (o primeiro que resolver vence):

1. `config('app.build')` (env `APP_BUILD`)
2. Arquivo `build.json` na raiz (chave `build`)
3. Hash curto (8 caracteres) do commit Git
4. `'dev'`

Registrado uma única vez para todos os painéis via render hook `PanelsRenderHook::SIDEBAR_FOOTER` no `AppServiceProvider`.

## pgvector (opcional)

Não há estrutura multitenant e o pgvector não é usado por padrão.

**Habilitar** (no banco PostgreSQL):

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

**Casos de uso:** embeddings / busca semântica, com uma coluna do tipo `vector`.

**Desabilitar:**

```sql
DROP EXTENSION vector;
```

## 2FA opcional (app autenticador)

Desabilitado por padrão — é **opt-in**. Para habilitar em um painel:

1. Descomente, no PanelProvider desejado, a linha:
   ```php
   ->multiFactorAuthentication(\Filament\Auth\MultiFactor\App\AppAuthentication::make())
   ```
2. Faça o model `User` implementar o contrato `Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication`.
3. Adicione as colunas necessárias via migration.

## Autenticação (contexto)

Autenticação oficial do Filament. O model `User` implementa `Filament\Models\Contracts\FilamentUser` com `canAccessPanel()` retornando `true` — todo usuário autenticado acessa todos os painéis. **Não há papéis nem permissões**; restrinja aqui ao introduzir regra de negócio. Sem página pública de registro. Logout é `POST /{painel}/logout` (nativo do Filament; encerra a sessão, invalida-a e regenera o token CSRF). Logout via GET não existe (HTTP 405).
