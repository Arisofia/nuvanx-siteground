#!/usr/bin/env node

import { chromium } from '@playwright/test';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectNoindex = process.env.EXPECT_NOINDEX === 'true';
const canonicalHost = process.env.CANONICAL_HOST || 'nuvanx.com';
const expectedDeploySha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const username = process.env.BASIC_USER || '';
const password = process.env.BASIC_PASSWORD || '';
const attempts = Math.max(1, Number(process.env.VERIFY_RETRIES || 3));

if (expectedDeploySha && !/^[0-9a-f]{40}$/.test(expectedDeploySha)) {
  throw new Error(`EXPECTED_DEPLOY_SHA must be a full lowercase commit SHA: ${expectedDeploySha}`);
}

const routes = [
  { path: '/', h1: 'Medicina estética láser en Madrid', copy: 'Equipo médico hospitalario. Tecnología certificada. Resultados naturales.', schema: ['MedicalClinic', 'Physician'] },
  { path: '/contacto/', h1: 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya', schema: ['MedicalClinic'], requireOgImage: true },
  { path: '/medicina-estetica-chamberi/', h1: 'Medicina estética en Chamberí con dirección médica', schema: ['MedicalClinic'] },
  { path: '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/', h1: 'Medicina estética en Goya con misma dirección médica que Chamberí', schema: ['MedicalClinic'] },
  { path: '/endolift-facial-papada-mandibula/', h1: 'Endolift® en Madrid: papada, mandíbula y cuello sin quirófano', schema: ['MedicalProcedure', 'Service'] },
  { path: '/endolaser-corporal-grasa-localizada/', h1: 'Endoláser corporal en Madrid: grasa localizada y mejor contorno', schema: ['MedicalProcedure', 'Service'] },
  { path: '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', h1: 'Láser CO₂ fraccionado en Madrid: textura, poros y cicatrices de acné', schema: ['MedicalProcedure', 'Service'] },
  { path: '/exion-btl/', h1: 'EXION® BTL en Madrid', schema: ['Service'] },
  { path: '/labios-acido-hialuronico-madrid/', h1: 'Ácido hialurónico en labios en Madrid', copy: 'Revisión médica pendiente.', schema: ['MedicalProcedure', 'Service', 'FAQPage'] },
  { path: '/rinomodelacion-sin-cirugia-madrid/', h1: 'Rinomodelación con ácido hialurónico en Madrid', copy: 'Revisión médica pendiente.', schema: ['MedicalProcedure', 'Service', 'FAQPage'] },
  { path: '/ojeras-surco-lagrimal-madrid/', h1: 'Tratamiento de ojeras y surco lagrimal en Madrid', copy: 'Revisión médica pendiente.', schema: ['MedicalProcedure', 'Service', 'FAQPage'] },
  { path: '/bioestimuladores-colageno-madrid/', h1: 'Bioestimuladores de colágeno en Madrid', copy: 'Revisión médica pendiente.', schema: ['MedicalProcedure', 'Service', 'FAQPage'] },
];

const forbiddenClaims = ['3.500+', '3,500+', '4.8/5', '4,8/5', '89% Repite tratamiento', '89% repite tratamiento'];
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function schemaTypesFromScripts(scripts) {
  const types = new Set();
  const visit = (value) => {
    if (Array.isArray(value)) return value.forEach(visit);
    if (!value || typeof value !== 'object') return;
    const raw = value['@type'];
    if (Array.isArray(raw)) raw.forEach((type) => types.add(String(type)));
    else if (raw) types.add(String(raw));
    Object.values(value).forEach(visit);
  };
  for (const script of scripts) {
    try { visit(JSON.parse(script)); } catch { /* reported separately */ }
  }
  return [...types];
}

async function inspectPage(browser, route) {
  const contextOptions = {
    locale: 'es-ES',
    timezoneId: 'Europe/Madrid',
    viewport: { width: 1440, height: 1000 },
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
  };
  if (username && password) contextOptions.httpCredentials = { username, password };
  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  const url = `${baseUrl}${route.path}`;
  let result = { status: 0, html: '', title: '', bodyText: '', edge: true };

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => null);
    const status = response?.status() || 0;
    if (status === 200) await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
    const html = await page.content().catch(() => '');
    const title = await page.title().catch(() => '');
    const bodyText = await page.locator('body').innerText().catch(() => '');
    const edge = status === 202 || /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`);
    result = { status, html, title, bodyText, edge };
    if (!edge && status === 200) break;
    if (attempt < attempts) await sleep(800 * attempt);
  }

  const errors = [];
  if (result.edge) errors.push(`edge interstitial/status ${result.status}`);
  if (result.status !== 200) errors.push(`HTTP ${result.status}`);
  if (!result.title.trim()) errors.push('missing title');

  const deployedSha = ((await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '').trim().toLowerCase();
  if (expectedDeploySha && deployedSha !== expectedDeploySha) {
    errors.push(`deploy SHA mismatch: expected ${expectedDeploySha}, received ${deployedSha || 'missing'}`);
  } else if (deployedSha && !/^[0-9a-f]{40}$/.test(deployedSha)) {
    errors.push(`invalid deploy SHA marker: ${deployedSha}`);
  }

  const h1s = await page.locator('h1').allInnerTexts().catch(() => []);
  if (h1s.length !== 1) errors.push(`expected one H1, found ${h1s.length}`);
  if ((h1s[0] || '').trim() !== route.h1) errors.push(`unexpected H1: ${(h1s[0] || '').trim()}`);
  if (route.copy && !result.bodyText.includes(route.copy)) errors.push(`missing canonical copy: ${route.copy}`);

  const robots = (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
  const isNoindex = robots.toLowerCase().includes('noindex');
  if (expectNoindex !== isNoindex) errors.push(`robots mismatch: ${robots || 'missing'}`);

  const canonical = (await page.locator('link[rel="canonical"]').getAttribute('href').catch(() => '')) || '';
  if (expectNoindex) {
    if (canonical) errors.push(`staging canonical leak: ${canonical}`);
  } else if (!canonical) {
    errors.push('missing canonical');
  } else {
    try {
      const canonicalUrl = new URL(canonical);
      if (canonicalUrl.hostname !== canonicalHost) errors.push(`canonical host ${canonicalUrl.hostname}`);
      if (canonicalUrl.pathname !== route.path) errors.push(`canonical path ${canonicalUrl.pathname}`);
    } catch { errors.push(`invalid canonical: ${canonical}`); }
  }

  const description = (await page.locator('meta[name="description"]').getAttribute('content').catch(() => '')) || '';
  if (!description.trim()) errors.push('missing meta description');

  const schemaNodes = page.locator('script[type="application/ld+json"]');
  const schemaCount = await schemaNodes.count();
  const schemaPayloads = await schemaNodes.allTextContents().catch(() => []);
  const yoastCount = await page.locator('script.yoast-schema-graph[type="application/ld+json"]').count().catch(() => 0);
  if (schemaCount !== 1) errors.push(`expected one JSON-LD block, found ${schemaCount}`);
  if (yoastCount !== 1) errors.push(`expected one Yoast graph, found ${yoastCount}`);
  const parseErrors = schemaPayloads.filter((payload) => {
    try { JSON.parse(payload); return false; } catch { return true; }
  }).length;
  if (parseErrors) errors.push(`${parseErrors} invalid JSON-LD payload(s)`);
  const schemaTypes = schemaTypesFromScripts(schemaPayloads);
  for (const type of route.schema) if (!schemaTypes.includes(type)) errors.push(`missing schema type ${type}`);

  const ogImage = (await page.locator('meta[property="og:image"]').getAttribute('content').catch(() => '')) || '';
  if (route.requireOgImage && !ogImage.includes('consulta-medica-personalizada-nuvanx-madrid.webp')) {
    errors.push(`missing canonical contact og:image: ${ogImage || 'none'}`);
  }

  const trustBadgeCount = await page.locator('.nvx-trust-badges').count().catch(() => 0);
  if (trustBadgeCount > 0) errors.push(`unverified trust badge blocks present: ${trustBadgeCount}`);

  const blackoutClassCount = await page.locator('body.nvx-hero-blackout').count().catch(() => 0);
  if (expectNoindex && blackoutClassCount > 0) errors.push('staging hero blackout class is still active');

  for (const claim of forbiddenClaims) if (result.bodyText.includes(claim)) errors.push(`forbidden claim present: ${claim}`);

  await context.close();
  return {
    path: route.path,
    status: result.status,
    title: result.title,
    deployedSha,
    h1: (h1s[0] || '').trim(),
    robots,
    canonical,
    schemaCount,
    schemaTypes,
    ogImage,
    trustBadgeCount,
    blackoutClassCount,
    errors,
  };
}

const browser = await chromium.launch({ headless: true });
const pages = [];
for (const route of routes) pages.push(await inspectPage(browser, route));
await browser.close();

for (const page of pages) {
  console.log(`${page.errors.length ? 'FAIL' : 'PASS'} ${page.path} · HTTP ${page.status} · deploy ${page.deployedSha || 'missing'} · H1 ${page.h1} · robots ${page.robots} · schema ${page.schemaCount} · trust badges ${page.trustBadgeCount} · blackout ${page.blackoutClassCount}`);
  for (const error of page.errors) console.error(`  - ${error}`);
}

const failures = pages.flatMap((page) => page.errors.map((error) => `${page.path}: ${error}`));
if (failures.length) {
  console.error(`P0 deployment contract failed with ${failures.length} finding(s).`);
  process.exitCode = 1;
} else {
  console.log(`P0 deployment contract passed for ${baseUrl}.`);
}
