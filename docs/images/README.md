# Imagens da documentação

Convenções para adicionar e manter screenshots em `docs/images/`.

## Convenções

- **Nome:** kebab-case descritivo, no padrão `contexto-detalhe.ext` (ex.: `login-ops.png`, `dashboard-admin.png`). Nada de `Screenshot 2026-07-02 at 10.32.11.png`.
- **Formato:** PNG (interfaces) ou WebP (quando o peso importar). Sem JPEG para UI — serrilha texto.
- **Captura:** viewport **1600×1000** com `deviceScaleFactor: 2` (@2x) — nítido em telas retina e no zoom do GitHub.
- **Peso:** otimize para **< 150 KB** quando possível (ex.: `oxipng`, `pngquant` ou export WebP). O GitHub carrega as imagens do README em todo clone da página.
- **Referências:** no `README.md` da raiz use `docs/images/arquivo.png`; dentro de `docs/` use `images/arquivo.png`.
- Sempre preencha o atributo `alt` descrevendo a tela.

## Imagens atuais e onde são usadas

| Arquivo | Conteúdo | Usada em |
|---------|----------|----------|
| `dashboard-ops.png` | Dashboard do painel ops (hero) | `README.md` (topo) |
| `login-ops.png` | Login `/ops/login` (Blue) | `README.md` (galeria), `docs/LOCAL.md` |
| `login-admin.png` | Login `/admin/login` (Amber) | `README.md` (galeria) |
| `login-support.png` | Login `/support/login` (Emerald) | `README.md` (galeria) |
| `dashboard-admin.png` | Dashboard do painel admin | `README.md` (colapsável), `docs/DOCKER.md` |
| `dashboard-support.png` | Dashboard do painel support | `README.md` (colapsável) |
| `profile.png` | Página de perfil (com 2FA opcional) | `README.md` (colapsável) |

## Como recapturar

Qualquer ferramenta serve — print manual do navegador (janela em 1600×1000) resolve. Para reproduzível, um exemplo com Playwright headless:

```js
// npx playwright screenshot não fixa deviceScaleFactor; use a API:
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({
    viewport: { width: 1600, height: 1000 },
    deviceScaleFactor: 2,
  });
  await page.goto('http://localhost:8000/ops/login');
  await page.screenshot({ path: 'docs/images/login-ops.png' });
  await browser.close();
})();
```

Para telas autenticadas (dashboards, perfil), faça login antes da captura (usuário criado com `php artisan superadmin:create`). Depois de recapturar, otimize o peso e confira como a imagem renderiza no README do GitHub.
