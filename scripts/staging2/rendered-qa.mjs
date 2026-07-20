#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright';

const config = JSON.parse(fs.readFileSync(new URL('./rendered-qa-routes.json', import.meta.url), 'utf8'));
const baseUrl = (process.env.BASE_URL || config.baseUrl).replace(/\/$/, '');
const out = path.resolve(process.env.QA_OUTPUT_DIR || 'qa-artifacts/staging2-rendered');
const expectedSha = process.env.EXPECTED_DEPLOY_SHA || '';
const canonicalHost = process.env.EXPECTED_CANONICAL_HOST || 'nuvanx.com';
const viewports = [['desktop', 1440, 1100], ['mobile', 390, 844]];
const protectedReviewSlugs = new Set(['liposculpt-review', 'v-lift-review']);
const criticalJs = /(ReferenceError|TypeError|SyntaxError|Uncaught|FacebookSignal|is not defined)/i;
fs.mkdirSync(out, { recursive: true });

/** Add a structured finding to a list. */
function add(list, severity, code, message, details = {}) {
  list.push({ severity, code, message, ...details });
}

/** Collect all JSON-LD schema type values from a nested structure. */
function collectTypes(value, set = new Set()) {
  if (!value || typeof value !== 'object') return set;
  if (Array.isArray(value)) {
    value.forEach((item) => collectTypes(item, set));
    return set;
  }
  const type = value['@type'];
  (Array.isArray(type) ? type : type ? [type] : []).forEach((item) => set.add(String(item)));
  Object.values(value).forEach((item) => collectTypes(item, set));
  return set;
}

/** Capture consent evidence and then dismiss the banner for clean visual QA. */
async function captureAndDismissConsent(page, slug, viewport) {
  const deny = page.getByRole('button', { name: /denegar/i }).first();
  const visible = await deny.isVisible().catch(() => false);
  if (!visible) return { visible: false, dismissed: false };

  await page.screenshot({
    path: path.join(out, `${slug}-${viewport}-consent.png`),
    fullPage: false,
    animations: 'disabled',
  });
  await deny.click();
  await page.waitForTimeout(250);
  return { visible: true, dismissed: !(await deny.isVisible().catch(() => false)) };
}

/** Trigger lazy assets throughout the document and return to the top. */
async function hydratePage(page) {
  await page.evaluate(async () => {
    const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
    const step = Math.max(400, Math.floor(window.innerHeight * 0.8));
    const maximum = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);

    for (let y = 0; y < maximum; y += step) {
      window.scrollTo(0, y);
      await sleep(80);
    }
    window.scrollTo(0, maximum);
    await sleep(120);

    await Promise.all(
      [...document.images].map(async (image) => {
        if (!image.complete) {
          await new Promise((resolve) => {
            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', resolve, { once: true });
            setTimeout(resolve, 3000);
          });
        }
        if (typeof image.decode === 'function') await image.decode().catch(() => {});
      }),
    );

    window.scrollTo(0, 0);
    await sleep(120);
  });
}

/** Audit a route at one viewport and capture consent plus clean screenshots. */
async function inspect(browser, slug, route, viewport, width, height) {
  const context = await browser.newContext({
    viewport: { width, height },
    locale: 'es-ES',
    reducedMotion: 'reduce',
    colorScheme: 'light',
  });
  const page = await context.newPage();
  const findings = [];
  const consoleErrors = [];
  const failedRequests = [];

  page.on('console', (message) => {
    if (message.type() !== 'error') return;
    consoleErrors.push(message.text());
    if (criticalJs.test(message.text())) add(findings, 'critical', 'console-error', message.text());
  });
  page.on('pageerror', (error) => add(findings, 'critical', 'page-error', error.message));
  page.on('requestfailed', (request) => {
    const failedUrl = request.url();
    if (!/(google|facebook|doubleclick|hubspot|hs-scripts|clarity)\./i.test(failedUrl) && request.resourceType() !== 'media') {
      failedRequests.push(failedUrl);
      add(findings, 'warning', 'request-failed', failedUrl);
    }
  });

  const url = `${baseUrl}${route}`;
  let response;
  try {
    response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(1500);
  } catch (error) {
    add(findings, 'critical', 'navigation-failed', error.message);
  }

  const status = response?.status() ?? null;
  const headers = response ? await response.allHeaders() : {};
  if (!status || status >= 400) add(findings, 'critical', 'http-status', `HTTP ${status ?? 'none'}`);

  let rendered = null;
  let consent = { visible: false, dismissed: false };
  if (status && status < 400) {
    consent = await captureAndDismissConsent(page, slug, viewport);
    if (consent.visible && !consent.dismissed) add(findings, 'critical', 'consent-not-dismissed', 'The consent dialog remained visible after Denegar.');

    await hydratePage(page);

    rendered = await page.evaluate(() => {
      const meta = (selector) => document.querySelector(selector)?.getAttribute('content')?.trim() || '';
      const h1s = [...document.querySelectorAll('h1')].map((node) => node.textContent?.trim() || '');
      const ids = [...document.querySelectorAll('[id]')].map((node) => node.id).filter(Boolean);
      const duplicateIds = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
      const missingAlt = [...document.images]
        .filter((image) => !image.hasAttribute('alt') && image.getAttribute('role') !== 'presentation' && image.getAttribute('aria-hidden') !== 'true')
        .map((image) => image.currentSrc || image.src);
      const rect = (selector) => {
        const node = document.querySelector(selector);
        if (!node) return null;
        const box = node.getBoundingClientRect();
        return [Math.round(box.width), Math.round(box.height)];
      };
      const smallControls = [...document.querySelectorAll('button,input[type="submit"],[role="button"],summary,.joinchat__button')]
        .filter((node) => {
          const box = node.getBoundingClientRect();
          const style = getComputedStyle(node);
          return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0 && (box.width < 44 || box.height < 44);
        })
        .slice(0, 15)
        .map((node) => {
          const box = node.getBoundingClientRect();
          return { tag: node.tagName, className: String(node.className || ''), width: Math.round(box.width), height: Math.round(box.height) };
        });

      return {
        title: document.title.trim(),
        description: meta('meta[name="description"]'),
        robots: meta('meta[name="robots"]'),
        canonical: document.querySelector('link[rel="canonical"]')?.href || '',
        h1s,
        duplicateIds,
        missingAlt,
        bodyFont: getComputedStyle(document.body).fontFamily,
        h1Font: document.querySelector('h1') ? getComputedStyle(document.querySelector('h1')).fontFamily : '',
        overflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - document.documentElement.clientWidth,
        jsonLd: [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => node.textContent || ''),
        smallControls,
        joinchatButton: rect('.joinchat__button'),
        joinchatIcon: rect('.joinchat__open__icon'),
        inlineWhatsapp: rect('.icon-whatsapp'),
        captcha: /robot challenge|sgcaptcha/i.test(document.body.innerText),
      };
    });

    const protectedReview = protectedReviewSlugs.has(slug);
    if (rendered.captcha) add(findings, 'critical', 'siteground-captcha', 'Bot challenge rendered.');
    if (!rendered.title) add(findings, 'critical', 'missing-title', 'Empty title.');
    if (!protectedReview && !rendered.description) add(findings, 'warning', 'missing-description', 'Empty meta description.');
    if (rendered.h1s.length !== 1) add(findings, 'critical', 'h1-count', `Expected 1 H1, found ${rendered.h1s.length}.`, { h1s: rendered.h1s });
    if (!/noindex/i.test(rendered.robots) && !/noindex/i.test(headers['x-robots-tag'] || '')) add(findings, 'critical', 'staging-indexable', 'Staging2 lacks noindex.');

    if (protectedReview) {
      if (rendered.canonical) add(findings, 'warning', 'protected-review-canonical', rendered.canonical);
    } else if (!rendered.canonical) {
      add(findings, 'critical', 'missing-canonical', 'Canonical missing.');
    } else {
      try {
        const host = new URL(rendered.canonical).hostname;
        if (![canonicalHost, `www.${canonicalHost}`].includes(host)) add(findings, 'warning', 'canonical-host', host);
      } catch {
        add(findings, 'critical', 'invalid-canonical', rendered.canonical);
      }
    }

    if (rendered.overflow > 2) add(findings, 'critical', 'horizontal-overflow', `${rendered.overflow}px.`);
    if (rendered.duplicateIds.length) add(findings, 'critical', 'duplicate-ids', rendered.duplicateIds.join(', '));
    if (rendered.missingAlt.length) add(findings, 'warning', 'missing-alt', `${rendered.missingAlt.length} images.`, { images: rendered.missingAlt.slice(0, 15) });
    if (!/Manrope/i.test(rendered.bodyFont)) add(findings, 'warning', 'body-font', rendered.bodyFont);
    if (rendered.h1s.length && !/Playfair Display/i.test(rendered.h1Font)) add(findings, 'warning', 'heading-font', rendered.h1Font);
    if (rendered.smallControls.length) add(findings, 'warning', 'small-controls', `${rendered.smallControls.length} controls.`, { controls: rendered.smallControls });

    const size = (actual, expected, code) => {
      if (actual && (Math.abs(actual[0] - expected) > 1 || Math.abs(actual[1] - expected) > 1)) {
        add(findings, 'warning', code, `${actual[0]}×${actual[1]} expected ${expected}×${expected}.`);
      }
    };
    size(rendered.joinchatButton, 48, 'joinchat-frame-size');
    size(rendered.joinchatIcon, 24, 'joinchat-icon-size');
    size(rendered.inlineWhatsapp, 16, 'inline-whatsapp-size');

    const schemas = [];
    rendered.jsonLd.forEach((text, index) => {
      try {
        schemas.push(JSON.parse(text));
      } catch (error) {
        add(findings, 'critical', 'invalid-jsonld', `Block ${index}: ${error.message}`);
      }
    });
    rendered.schemaTypes = [...collectTypes(schemas)].sort();
    delete rendered.jsonLd;

    if (['home', 'contacto', 'clinicas'].includes(slug) && !rendered.schemaTypes.includes('MedicalClinic')) {
      add(findings, 'warning', 'schema-medical-clinic', 'MedicalClinic missing.');
    }
    if (slug === 'endolift' && !rendered.schemaTypes.includes('MedicalProcedure')) {
      add(findings, 'warning', 'schema-medical-procedure', 'MedicalProcedure missing.');
    }

    await page.screenshot({
      path: path.join(out, `${slug}-${viewport}.png`),
      fullPage: true,
      animations: 'disabled',
    });
  }

  await context.close();
  return {
    slug,
    route,
    viewport,
    url,
    status,
    headers: { 'x-robots-tag': headers['x-robots-tag'] || '' },
    consent,
    rendered,
    consoleErrors,
    failedRequests,
    findings,
  };
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
const results = [];
try {
  for (const [slug, route] of config.routes) {
    for (const [viewport, width, height] of viewports) {
      console.log(`AUDIT ${slug} ${viewport}`);
      try {
        results.push(await inspect(browser, slug, route, viewport, width, height));
      } catch (error) {
        results.push({
          slug,
          route,
          viewport,
          url: `${baseUrl}${route}`,
          status: null,
          headers: {},
          consent: { visible: false, dismissed: false },
          rendered: null,
          consoleErrors: [],
          failedRequests: [],
          findings: [{ severity: 'critical', code: 'audit-exception', message: error.message }],
        });
      }
    }
  }
} finally {
  await browser.close();
}

const findings = results.flatMap((entry) => entry.findings.map((finding) => ({ slug: entry.slug, route: entry.route, viewport: entry.viewport, ...finding })));
const critical = findings.filter((item) => item.severity === 'critical');
const warnings = findings.filter((item) => item.severity === 'warning');
const result = critical.length ? 'FAIL' : (warnings.length ? 'PASS_WITH_WARNINGS' : 'PASS');
const report = {
  baseUrl,
  expectedSha: expectedSha || null,
  generatedAt: new Date().toISOString(),
  summary: { routes: config.routes.length, runs: results.length, critical: critical.length, warnings: warnings.length, result },
  findings,
  results,
};
fs.writeFileSync(path.join(out, 'report.json'), `${JSON.stringify(report, null, 2)}\n`);

const md = [
  '# NUVANX Staging2 · Rendered QA',
  '',
  `- URL: \`${baseUrl}\``,
  `- SHA esperado: \`${expectedSha || 'no indicado'}\``,
  `- Ejecuciones: **${results.length}**`,
  `- Críticos: **${critical.length}**`,
  `- Advertencias: **${warnings.length}**`,
  `- Resultado: **${report.summary.result}**`,
  '',
  '## Críticos',
  '',
  ...(critical.length ? critical.map((item) => `- **${item.slug}/${item.viewport}/${item.code}:** ${item.message}`) : ['- Ninguno.']),
  '',
  '## Advertencias',
  '',
  ...(warnings.length ? warnings.map((item) => `- **${item.slug}/${item.viewport}/${item.code}:** ${item.message}`) : ['- Ninguna.']),
  '',
  'El artefacto incluye capturas de consentimiento, capturas limpias desktop/móvil y `report.json`.',
  '',
].join('\n');
fs.writeFileSync(path.join(out, 'report.md'), md);
console.log(md);
if (critical.length) process.exit(1);
