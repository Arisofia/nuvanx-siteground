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
const criticalJs = /(ReferenceError|TypeError|SyntaxError|Uncaught|FacebookSignal|is not defined)/i;
fs.mkdirSync(out, { recursive: true });

/**
 * Adds a structured finding to a list.
 * @param {Array<Object>} list - The list receiving the finding.
 * @param {string} severity - The finding severity.
 * @param {string} code - The finding code.
 * @param {string} message - The finding message.
 * @param {Object} [details={}] - Additional finding properties.
 */
function add(list, severity, code, message, details = {}) { list.push({ severity, code, message, ...details }); }
/**
 * Collects all JSON-LD schema type values from a nested structure.
 * @param {*} value - The value to traverse.
 * @param {Set<string>} [set=new Set()] - The set to populate with schema types.
 * @returns {Set<string>} The set containing the collected schema types.
 */
function collectTypes(value, set = new Set()) {
  if (!value || typeof value !== 'object') return set;
  if (Array.isArray(value)) { value.forEach((item) => collectTypes(item, set)); return set; }
  const type = value['@type'];
  (Array.isArray(type) ? type : type ? [type] : []).forEach((item) => set.add(String(item)));
  Object.values(value).forEach((item) => collectTypes(item, set));
  return set;
}

/**
 * Audits a route at a specified viewport and captures a full-page screenshot.
 * @param {import('playwright').Browser} browser - Browser instance used to create the page context.
 * @param {string} slug - Route identifier used to classify results and name the screenshot.
 * @param {string} route - Route path to audit.
 * @param {string} viewport - Viewport label included in the result and screenshot name.
 * @param {number} width - Viewport width in pixels.
 * @param {number} height - Viewport height in pixels.
 * @return {object} Audit result containing route metadata, HTTP status, headers, rendered data, console errors, failed requests, and findings.
 */
async function inspect(browser, slug, route, viewport, width, height) {
  const context = await browser.newContext({ viewport: { width, height }, locale: 'es-ES', reducedMotion: 'reduce', colorScheme: 'light' });
  const page = await context.newPage();
  const findings = [];
  const consoleErrors = [];
  const failedRequests = [];
  page.on('console', (msg) => { if (msg.type() === 'error') { consoleErrors.push(msg.text()); if (criticalJs.test(msg.text())) add(findings, 'critical', 'console-error', msg.text()); } });
  page.on('pageerror', (error) => add(findings, 'critical', 'page-error', error.message));
  page.on('requestfailed', (request) => {
    const url = request.url();
    if (!/(google|facebook|doubleclick|hubspot|hs-scripts|clarity)\./i.test(url) && request.resourceType() !== 'media') {
      failedRequests.push(url); add(findings, 'warning', 'request-failed', url);
    }
  });

  const url = `${baseUrl}${route}`;
  let response;
  try { response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 }); await page.waitForTimeout(1500); }
  catch (error) { add(findings, 'critical', 'navigation-failed', error.message); }
  const status = response?.status() ?? null;
  const headers = response ? await response.allHeaders() : {};
  if (!status || status >= 400) add(findings, 'critical', 'http-status', `HTTP ${status ?? 'none'}`);

  let rendered = null;
  if (status && status < 400) {
    rendered = await page.evaluate(() => {
      const meta = (selector) => document.querySelector(selector)?.getAttribute('content')?.trim() || '';
      const h1s = [...document.querySelectorAll('h1')].map((node) => node.textContent?.trim() || '');
      const ids = [...document.querySelectorAll('[id]')].map((node) => node.id).filter(Boolean);
      const duplicateIds = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))];
      const missingAlt = [...document.images].filter((img) => !img.hasAttribute('alt') && img.getAttribute('role') !== 'presentation' && img.getAttribute('aria-hidden') !== 'true').map((img) => img.currentSrc || img.src);
      const rect = (selector) => { const node = document.querySelector(selector); if (!node) return null; const box = node.getBoundingClientRect(); return [Math.round(box.width), Math.round(box.height)]; };
      const smallControls = [...document.querySelectorAll('button,input[type="submit"],[role="button"],summary,.joinchat__button')]
        .filter((node) => { const box = node.getBoundingClientRect(); const style = getComputedStyle(node); return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0 && (box.width < 44 || box.height < 44); })
        .slice(0, 15).map((node) => { const box = node.getBoundingClientRect(); return { tag: node.tagName, className: String(node.className || ''), width: Math.round(box.width), height: Math.round(box.height) }; });
      return {
        title: document.title.trim(), description: meta('meta[name="description"]'), robots: meta('meta[name="robots"]'),
        canonical: document.querySelector('link[rel="canonical"]')?.href || '', h1s, duplicateIds, missingAlt,
        bodyFont: getComputedStyle(document.body).fontFamily,
        h1Font: document.querySelector('h1') ? getComputedStyle(document.querySelector('h1')).fontFamily : '',
        overflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - document.documentElement.clientWidth,
        jsonLd: [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => node.textContent || ''),
        smallControls, joinchatButton: rect('.joinchat__button'), joinchatIcon: rect('.joinchat__open__icon'), inlineWhatsapp: rect('.icon-whatsapp'),
        captcha: /robot challenge|sgcaptcha/i.test(document.body.innerText),
      };
    });

    if (rendered.captcha) add(findings, 'critical', 'siteground-captcha', 'Bot challenge rendered.');
    if (!rendered.title) add(findings, 'critical', 'missing-title', 'Empty title.');
    if (!rendered.description) add(findings, 'warning', 'missing-description', 'Empty meta description.');
    if (rendered.h1s.length !== 1) add(findings, 'critical', 'h1-count', `Expected 1 H1, found ${rendered.h1s.length}.`, { h1s: rendered.h1s });
    if (!/noindex/i.test(rendered.robots) && !/noindex/i.test(headers['x-robots-tag'] || '')) add(findings, 'critical', 'staging-indexable', 'Staging2 lacks noindex.');
    if (!rendered.canonical) add(findings, 'critical', 'missing-canonical', 'Canonical missing.');
    else { try { const host = new URL(rendered.canonical).hostname; if (![canonicalHost, `www.${canonicalHost}`].includes(host)) add(findings, 'warning', 'canonical-host', host); } catch { add(findings, 'critical', 'invalid-canonical', rendered.canonical); } }
    if (rendered.overflow > 2) add(findings, 'critical', 'horizontal-overflow', `${rendered.overflow}px.`);
    if (rendered.duplicateIds.length) add(findings, 'critical', 'duplicate-ids', rendered.duplicateIds.join(', '));
    if (rendered.missingAlt.length) add(findings, 'warning', 'missing-alt', `${rendered.missingAlt.length} images.`, { images: rendered.missingAlt.slice(0, 15) });
    if (!/Manrope/i.test(rendered.bodyFont)) add(findings, 'warning', 'body-font', rendered.bodyFont);
    if (rendered.h1s.length && !/Playfair Display/i.test(rendered.h1Font)) add(findings, 'warning', 'heading-font', rendered.h1Font);
    if (rendered.smallControls.length) add(findings, 'warning', 'small-controls', `${rendered.smallControls.length} controls.`, { controls: rendered.smallControls });
    const size = (actual, expected, code) => { if (actual && (Math.abs(actual[0] - expected) > 1 || Math.abs(actual[1] - expected) > 1)) add(findings, 'warning', code, `${actual[0]}×${actual[1]} expected ${expected}×${expected}.`); };
    size(rendered.joinchatButton, 48, 'joinchat-frame-size'); size(rendered.joinchatIcon, 24, 'joinchat-icon-size'); size(rendered.inlineWhatsapp, 16, 'inline-whatsapp-size');
    const schemas = [];
    rendered.jsonLd.forEach((text, index) => { try { schemas.push(JSON.parse(text)); } catch (error) { add(findings, 'critical', 'invalid-jsonld', `Block ${index}: ${error.message}`); } });
    rendered.schemaTypes = [...collectTypes(schemas)].sort(); delete rendered.jsonLd;
    if (['home', 'contacto', 'clinicas'].includes(slug) && !rendered.schemaTypes.includes('MedicalClinic')) add(findings, 'warning', 'schema-medical-clinic', 'MedicalClinic missing.');
    if (['endolift', 'laser', 'medicina-estetica'].includes(slug) && !rendered.schemaTypes.includes('MedicalProcedure')) add(findings, 'warning', 'schema-medical-procedure', 'MedicalProcedure missing.');
    await page.screenshot({ path: path.join(out, `${slug}-${viewport}.png`), fullPage: true, animations: 'disabled' });
  }
  await context.close();
  return { slug, route, viewport, url, status, headers: { 'x-robots-tag': headers['x-robots-tag'] || '' }, rendered, consoleErrors, failedRequests, findings };
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
const results = [];
try {
  for (const [slug, route] of config.routes)
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
          rendered: null,
          consoleErrors: [],
          failedRequests: [],
          findings: [{ severity: 'critical', code: 'audit-exception', message: error.message }],
        });
      }
    }
} finally {
  await browser.close();
}

const findings = results.flatMap((result) => result.findings.map((finding) => ({ slug: result.slug, route: result.route, viewport: result.viewport, ...finding })));
const critical = findings.filter((item) => item.severity === 'critical');
const warnings = findings.filter((item) => item.severity === 'warning');
const report = { baseUrl, expectedSha: expectedSha || null, generatedAt: new Date().toISOString(), summary: { routes: config.routes.length, runs: results.length, critical: critical.length, warnings: warnings.length, result: critical.length ? 'FAIL' : 'PASS_WITH_WARNINGS' }, findings, results };
fs.writeFileSync(path.join(out, 'report.json'), `${JSON.stringify(report, null, 2)}\n`);
const md = ['# NUVANX Staging2 · Rendered QA', '', `- URL: \`${baseUrl}\``, `- SHA esperado: \`${expectedSha || 'no indicado'}\``, `- Ejecuciones: **${results.length}**`, `- Críticos: **${critical.length}**`, `- Advertencias: **${warnings.length}**`, `- Resultado: **${report.summary.result}**`, '', '## Críticos', '', ...(critical.length ? critical.map((x) => `- **${x.slug}/${x.viewport}/${x.code}:** ${x.message}`) : ['- Ninguno.']), '', '## Advertencias', '', ...(warnings.length ? warnings.map((x) => `- **${x.slug}/${x.viewport}/${x.code}:** ${x.message}`) : ['- Ninguna.']), '', 'El artefacto incluye capturas completas desktop/móvil y `report.json`.', ''].join('\n');
fs.writeFileSync(path.join(out, 'report.md'), md); console.log(md);
if (critical.length) process.exit(1);
