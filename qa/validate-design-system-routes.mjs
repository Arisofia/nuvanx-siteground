import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const USER = process.env.STAGING_BASIC_USER || '';
const PASS = process.env.STAGING_BASIC_PASSWORD || '';

if (!USER || !PASS) {
  throw new Error('STAGING_BASIC_USER and STAGING_BASIC_PASSWORD are required.');
}

const ROUTES = [
  '/',
  '/medicina-estetica-laser/',
  '/endolift-facial-papada-mandibula/',
  '/endolaser-corporal-grasa-localizada/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  '/equipo-medico/',
  '/medicina-estetica-chamberi/',
  '/madrid/valoracion/',
  '/contacto/',
];

const VIEWPORTS = [
  { width: 1440, height: 900, tag: '1440' },
  { width: 1024, height: 768, tag: '1024' },
  { width: 390, height: 844, tag: '390' },
];

const OUT = path.join(__dirname, 'screenshots', 'route-matrix');
const RESULTS_DIR = path.join(__dirname, 'results');
fs.mkdirSync(OUT, { recursive: true });
fs.mkdirSync(RESULTS_DIR, { recursive: true });

const browser = await chromium.launch({ headless: true });
const cases = [];

function slug(route) {
  return route === '/' ? 'home' : route.replace(/^\/+|\/+$/g, '').replace(/[^a-z0-9]+/gi, '-');
}

try {
  for (const route of ROUTES) {
    for (const viewport of VIEWPORTS) {
      const context = await browser.newContext({
        viewport: { width: viewport.width, height: viewport.height },
        deviceScaleFactor: 1,
        httpCredentials: { username: USER, password: PASS },
      });
      const page = await context.newPage();
      const consoleErrors = [];
      const networkErrors = [];

      page.on('console', (message) => {
        if (message.type() === 'error') consoleErrors.push(message.text());
      });
      page.on('pageerror', (error) => consoleErrors.push(error.message));
      page.on('response', (response) => {
        if (
          response.status() >= 400 &&
          !/google|facebook|doubleclick|googletagmanager|connect\.facebook/i.test(response.url())
        ) {
          networkErrors.push({ status: response.status(), url: response.url() });
        }
      });

      let status = null;
      let metrics = null;
      let error = null;

      try {
        const response = await page.goto(new URL(route, BASE).toString(), {
          waitUntil: 'domcontentloaded',
          timeout: 90000,
        });
        status = response?.status() ?? null;
        await page.waitForSelector('main', { timeout: 30000 });
        await page.evaluate(async () => {
          if (document.fonts?.ready) await document.fonts.ready;
          window.scrollTo(0, 0);
        });
        await page.waitForTimeout(400);

        metrics = await page.evaluate(() => ({
          mainCount: document.querySelectorAll('main').length,
          overflow: document.documentElement.scrollWidth - window.innerWidth,
          scrollY: window.scrollY,
          bodyFont: getComputedStyle(document.body).fontFamily,
          h1Count: document.querySelectorAll('main h1').length,
        }));

        await page.screenshot({
          path: path.join(OUT, `${slug(route)}_${viewport.tag}.png`),
          fullPage: false,
        });
      } catch (caught) {
        error = caught.message;
        await page
          .screenshot({
            path: path.join(OUT, `${slug(route)}_${viewport.tag}_diagnostic.png`),
            fullPage: true,
          })
          .catch(() => {});
      } finally {
        await context.close();
      }

      const pass =
        !error &&
        status === 200 &&
        metrics?.mainCount === 1 &&
        Math.abs(metrics?.overflow ?? 999) <= 1 &&
        metrics?.scrollY === 0 &&
        consoleErrors.length === 0 &&
        networkErrors.length === 0;

      cases.push({
        route,
        viewport,
        status,
        metrics,
        consoleErrors,
        networkErrors,
        error,
        status_label: pass ? 'PASS' : 'FAIL',
      });
    }
  }
} finally {
  await browser.close();
}

const failed = cases.filter((item) => item.status_label === 'FAIL');
const report = {
  generated_at: new Date().toISOString(),
  base_url: BASE,
  summary: { total: cases.length, passed: cases.length - failed.length, failed: failed.length },
  cases,
};

fs.writeFileSync(
  path.join(RESULTS_DIR, 'design-system-route-validation.json'),
  `${JSON.stringify(report, null, 2)}\n`
);

console.log(JSON.stringify(report.summary));
if (failed.length) process.exit(1);
