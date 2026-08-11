# Área: Dependências e build

## Resumo

As constraints principais são coerentes com PHP 8.3+, Laravel 13 e Filament 5; o build local passa. O caminho de atualização executa Pint e auditorias, mas ainda depende de rede e de scripts Composer.

## Implementado

- Constraints e platform PHP: `composer.json:8-18,64-77`.
- Scripts de setup/test/build: `composer.json:20-62`; `package.json:1-18`.
- Node 22 e cache NPM no CI: `.github/workflows/ci.yml:49-52`.
- Build Vite reproduzível por `npm ci` no CI: `.github/workflows/ci.yml:85-88`; `vite.config.js:1-80`.
- `composer validate --strict` local passou; `npm run build` local passou.

## Gaps

- **[corrigido] O ciclo de update executa Pint.** `scripts/update-stack.sh` bloqueia o ciclo quando Pint falha.
- **[⚪ médio] Actions não estão pinadas por SHA.** Workflows usam tags móveis (`actions/checkout@v7`, `actions/setup-node@v6`, `actions/cache@v6`, `actions/github-script@v7`): `.github/workflows/*.yml`.
- **[⚪ médio] O helper Python não declara PyJWT/cryptography.** O script depende deles por import dinâmico: `scripts/github-app-auth.py:62-68`.

## Flaws

- **[corrigido e validado] A matriz real está verde.** A PR #7 passou em PHP 8.3 e 8.4, incluindo Pint, migrations, build e Pest.
- **[corrigido] Auditoria NPM agora bloqueia.** `scripts/update-stack.sh` trata `npm audit --audit-level=high` como gate e o `package-lock.json` foi atualizado sem vulnerabilidades.
- **[⚪ médio] O update é dependente de rede sem retry de alto nível.** Composer, NPM, Packagist e audits podem falhar em conjunto; o resolver tem timeout, mas não há backoff/diagnóstico persistido além do rótulo do gate: `scripts/resolve-stack.sh:25-28`; `scripts/update-stack.sh:33`.

## Veredito

**partial**: a stack instala, compila e passa na matriz de qualidade; restam pinagem por SHA e resiliência de rede.
