#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const outputDir = process.env.VISUAL_QA_DIR || 'staging2-visual-qa';
if (baseUrl !== 'https://staging2.nuvanx.com') throw new Error(`Refusing unexpected BASE_URL: ${baseUrl}`);
if (!/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
fs.mkdirSync(outputDir, { recursive: true });

const routes = [
  ['soluciones-medicas', '/soluciones-medicas/'],
  ['protocolos-signature', '/protocolos-signature/'],
  ['contour-architecture', '/remodelacion-corporal-laser-madrid/'],
  ['post-maternity', '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'],
  ['profile-definition', '/papada-definicion-mandibular-madrid/'],
  ['skin-architecture', '/calidad-piel-firmeza-luminosidad-madrid/'],
  ['surface-renewal', '/cicatrices-acne-poros-textura-madrid/'],
  ['tone-correction', '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'],
  ['abdomen-flancos', '/grasa-localizada-abdomen-flancos-madrid/'],
  ['brazos', '/flacidez-grasa-localizada-brazos-madrid/'],
  ['espalda-sujetador', '/grasa-espalda-zona-sujetador-madrid/'],
  ['muslos-subgluteo', '/flacidez-muslos-internos-subgluteo-madrid/'],
  ['rodillas', '/tratamiento-rodillas-grasa-flacidez-madrid/'],
  ['contorno-masculino', '/contorno-corporal-masculino-madrid/'],
  ['por-que-nuvanx', '/por-que-nuvanx/'],
  ['inversion', '/inversion-medicina-estetica/'],
];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), captures: [], navigation: {}, findings: [] };
const fail = (scope, message) => report.findings.push(`${scope}: ${message}`);

const browser = await chromium.launch({
  headless: true,
  args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'],
});

async function createContext(viewport) {
  return browser.newContext({
    viewport,
    deviceScaleFactor: 1,
    locale: 'es-ES',
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/149.0.0.0 Safari/537.36',
    extraHTTPHeaders: { 'x-nuvanx-qa': 'staging2-visual-acceptance' },
  });
}

async function inspectRoute(name, route, mode, viewport) {
  const context = await createContext(viewport);
  const page = await context.newPage();
  const url = `${baseUrl}${route}`;
  const result = { name, route, mode, viewport, url };
  try {
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 60_000 });
    result.status = response?.status() || 0;
    await page.waitForTimeout(700);
    result.h1_count = await page.locator('h1').count();
    result.h1 = result.h1_count ? (await page.locator('h1').first().innerText()).trim() : '';
    result.deploy_sha = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '');
    result.body_text = (await page.locator('body').innerText()).slice(0, 500);
    result.viewport_width = await page.evaluate(() => window.innerWidth);
    result.scroll_width = await page.evaluate(() => document.documentElement.scrollWidth);
    if (result.status !== 200) fail(`${mode} ${route}`, `HTTP ${result.status}`);
    if (result.h1_count !== 1) fail(`${mode} ${route}`, `expected one H1, found ${result.h1_count}`);
    if (result.deploy_sha !== expectedSha) fail(`${mode} ${route}`, `deploy SHA ${result.deploy_sha || 'absent'} does not match`);
    if (/403\s*-\s*Forbidden|Access to this page is forbidden/i.test(result.body_text)) fail(`${mode} ${route}`, 'forbidden response rendered');
    if (result.scroll_width > result.viewport_width + 2) fail(`${mode} ${route}`, `horizontal overflow ${result.scroll_width}px > ${result.viewport_width}px`);
    const fileName = `${name}-${mode}.png`;
    await page.screenshot({ path: path.join(outputDir, fileName), fullPage: true });
    result.screenshot = fileName;
  } catch (error) {
    result.error = error.message;
    fail(`${mode} ${route}`, error.message);
  }
  report.captures.push(result);
  await context.close();
}

for (const [name, route] of routes) {
  await inspectRoute(name, route, 'desktop', { width: 1920, height: 1080 });
  await inspectRoute(name, route, 'mobile', { width: 375, height: 812 });
}

async function captureNavigation() {
  const desktop = await createContext({ width: 1920, height: 1080 });
  const desktopPage = await desktop.newPage();
  try {
    await desktopPage.goto(`${baseUrl}/`, { waitUntil: 'networkidle', timeout: 60_000 });
    const protocolLink = desktopPage.getByRole('link', { name: 'Protocolos Signature', exact: true }).first();
    await protocolLink.hover();
    await desktopPage.waitForTimeout(400);
    const visibleSubmenus = await desktopPage.locator('.nvx-nav__item--mega > .sub-menu:visible, .nvx-menu--mega > .sub-menu:visible').count();
    report.navigation.desktop_visible_mega_menus = visibleSubmenus;
    if (visibleSubmenus < 1) fail('desktop navigation', 'mega-menu did not become visible on hover');
    await desktopPage.screenshot({ path: path.join(outputDir, 'navigation-desktop-mega.png'), fullPage: false });
  } catch (error) {
    report.navigation.desktop_error = error.message;
    fail('desktop navigation', error.message);
  }
  await desktop.close();

  const mobile = await createContext({ width: 375, height: 812 });
  const mobilePage = await mobile.newPage();
  try {
    await mobilePage.goto(`${baseUrl}/`, { waitUntil: 'networkidle', timeout: 60_000 });
    await mobilePage.locator('.nvx-hamburger').click();
    await mobilePage.waitForSelector('.nvx-mobile-nav[aria-hidden="false"], .nvx-mobile-nav.is-open', { timeout: 10_000 });
    const drawerOverflow = await mobilePage.locator('.nvx-mobile-nav').evaluate((node) => node.scrollWidth > node.clientWidth + 2);
    if (drawerOverflow) fail('mobile navigation', 'drawer has horizontal overflow');
    await mobilePage.screenshot({ path: path.join(outputDir, 'navigation-mobile-drawer.png'), fullPage: false });
    const protocolItem = mobilePage.locator('.nvx-mobile-nav li').filter({ hasText: 'Protocolos Signature' }).first();
    const toggle = protocolItem.locator('.nvx-mobile-nav__toggle').first();
    if (await toggle.count()) {
      await toggle.click();
      const expanded = await toggle.getAttribute('aria-expanded');
      report.navigation.mobile_protocols_expanded = expanded;
      if (expanded !== 'true') fail('mobile navigation', 'Protocolos Signature accordion did not expand');
      await mobilePage.screenshot({ path: path.join(outputDir, 'navigation-mobile-protocols-open.png'), fullPage: false });
    } else {
      fail('mobile navigation', 'Protocolos Signature accordion toggle was not found');
    }
  } catch (error) {
    report.navigation.mobile_error = error.message;
    fail('mobile navigation', error.message);
  }
  await mobile.close();
}

await captureNavigation();
await browser.close();
report.ok = report.findings.length === 0;
fs.writeFileSync(path.join(outputDir, 'report.json'), `${JSON.stringify(report, null, 2)}\n`);
if (!report.ok) {
  console.error(`VISUAL_QA_FAILED findings=${report.findings.length}`);
  for (const finding of report.findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('VISUAL_QA_OK');
