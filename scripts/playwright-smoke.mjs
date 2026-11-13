import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const baseUrl = (process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const email = process.env.SMOKE_EMAIL || 'smoke@nando-lz.test';
const password = process.env.SMOKE_PASSWORD || 'Smoke-Test-123!';
const panels = (process.env.SMOKE_PANELS || 'admin,ops,support')
  .split(',')
  .map((p) => p.trim())
  .filter(Boolean);
const artifactDir = process.env.PLAYWRIGHT_ARTIFACT_DIR || path.resolve('storage/logs/playwright');

fs.mkdirSync(artifactDir, { recursive: true });

async function runPanelSmoke(browser, panel) {
  const context = await browser.newContext();
  const page = await context.newPage();

  const pageErrors = [];
  const consoleErrors = [];
  const failedRequests = [];
  const httpErrors = [];

  page.on('pageerror', (err) => {
    pageErrors.push(err.message);
  });

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });

  page.on('requestfailed', (req) => {
    failedRequests.push(`${req.method()} ${req.url()} (${req.failure()?.errorText || 'failed'})`);
  });

  page.on('response', (res) => {
    if (res.status() >= 400) {
      httpErrors.push(`${res.status()} ${res.request().method()} ${res.url()}`);
    }
  });

  try {
    console.log(`\n[smoke:${panel}] 1/4 Login em ${baseUrl}/${panel}/login`);
    await page.goto(`${baseUrl}/${panel}/login`, { waitUntil: 'networkidle' });

    const emailInput = page.locator('input[type="email"]').first();
    const passwordInput = page.locator('input[type="password"]').first();
    await emailInput.fill(email);
    await passwordInput.fill(password);

    await Promise.all([
      page.waitForURL(
        (url) =>
          (url.pathname === `/${panel}` || url.pathname.startsWith(`/${panel}/`)) &&
          !url.pathname.startsWith(`/${panel}/login`),
        { timeout: 15000 }
      ),
      page.locator('button[type="submit"]').first().click(),
    ]);
    await page.waitForLoadState('networkidle');

    if (page.url().includes(`/${panel}/login`)) {
      throw new Error(`Permaneceu na tela de login após submit em /${panel}/login`);
    }

    console.log(`[smoke:${panel}] 2/4 Dashboard carregado (${page.url()})`);
    await page.waitForSelector('main, [role="main"], .fi-main', { timeout: 10000 });

    console.log(`[smoke:${panel}] 3/4 Perfil em ${baseUrl}/${panel}/profile`);
    await page.goto(`${baseUrl}/${panel}/profile`, { waitUntil: 'networkidle' });
    if (page.url().includes(`/${panel}/login`)) {
      throw new Error(`Redirecionado para login ao acessar /${panel}/profile`);
    }
    await page.locator('main form, .fi-main form').first().waitFor({ state: 'visible', timeout: 10000 });

    console.log(`[smoke:${panel}] 4/4 Logout`);
    const userMenuButton = page.locator('.fi-user-menu-trigger, .fi-user-menu button, [aria-label*="User"], [aria-label*="usuário"]').first();
    await userMenuButton.click();

    const logoutButton = page.locator('form[action*="logout"] button').first();
    await Promise.all([
      page.waitForURL((url) => url.pathname.includes(`/${panel}/login`), { timeout: 15000 }),
      logoutButton.click(),
    ]);
    await page.waitForLoadState('networkidle');

    if (pageErrors.length || consoleErrors.length || failedRequests.length || httpErrors.length) {
      throw new Error(
        [
          pageErrors.length ? `pageErrors: ${pageErrors.join(' | ')}` : null,
          consoleErrors.length ? `consoleErrors: ${consoleErrors.join(' | ')}` : null,
          failedRequests.length ? `failedRequests: ${failedRequests.join(' | ')}` : null,
          httpErrors.length ? `httpErrors: ${httpErrors.join(' | ')}` : null,
        ]
          .filter(Boolean)
          .join('\n')
      );
    }

    await page.screenshot({ path: path.join(artifactDir, `${panel}-ok.png`), fullPage: false });
    console.log(`[smoke:${panel}] OK`);
  } catch (err) {
    await page.screenshot({ path: path.join(artifactDir, `${panel}-error.png`), fullPage: true }).catch(() => {});
    throw new Error(`[smoke:${panel}] Falhou: ${err.message}`);
  } finally {
    await context.close();
  }
}

async function main() {
  const browser = await chromium.launch({ headless: true });
  try {
    for (const panel of panels) {
      await runPanelSmoke(browser, panel);
    }
    console.log('\nTodos os painéis passaram no smoke Playwright.');
  } finally {
    await browser.close();
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
