#!/usr/bin/env node

import { mkdir, writeFile } from 'node:fs/promises';
import process from 'node:process';
import { chromium } from '@playwright/test';
import {
  analyseHtml,
  isEdgeInterstitialResponse,
  selectBlockingFindings,
  INFRASTRUCTURE_CODES,
} from './audit-rendered-pages.mjs';

const ROUTES = [
  { path: '/', role: 'home', expectedTypes: ['MedicalClinic', 'Physician'] },
  { path: '/tratamientos/', role: 'treatments', expectedTypes: [] },
  { path: '/clinicas-de-medicina-estetica-nuvanx/', role: 'clinic-hub', expectedTypes: ['MedicalClinic'] },
  { path: '/medicina-estetica-chamberi/', role: 'clinic', expectedTypes: ['MedicalClinic'] },
  { path: '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/', role: 'clinic', expectedTypes: ['MedicalClinic'] },
  { path: '/equipo-medico/', role: 'team', expectedTypes: ['Physician'] },
  { path: '/endolift-facial-papada-mandibula/', role: 'treatment', expectedTypes: ['MedicalProcedure', 'Service'] },
  { path: '/endolaser-corporal-grasa-localizada/', role: 'treatment', expectedTypes: ['MedicalProcedure', 'Service'] },
  { path: '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', role: 'treatment', expectedTypes: ['MedicalProcedure', 'Service'] },
  { path: '/exion-btl/', role: 'treatment', expectedTypes: ['Service'] },
  { path: '/btl-exilite-ipl-madrid/', role: 'treatment', expectedTypes: ['Service'] },
  { path: '/contacto/', role: 'contact', expectedTypes: ['MedicalClinic'] },
  { path: '/madrid/valoracion/', role: 'conversion', expectedTypes: [] },
];

const ENVIRONMENTS = {
  production: {
    baseUrl: process.env.NVX_PRODUCTION_URL || 'https://nuvanx.com',
    username: '',
    password: '',
  },
  staging: {
    baseUrl: process.env.NVX_STAGING_URL || 'https://staging2.nuvanx.com',
    username: process.env.STAGING_BASIC_USER || '',
    password: process.env.STAGING_BASIC_PASSWORD || '',
  },
};

const MAX_ATTEMPTS = Math.max(1, Number(process.env.NVX_AUDIT_RETRIES || 4));
const BASE_DELAY_MS = Math.max(500, Number(process.env.NVX_AUDIT_RETRY_DELAY_MS || 3000));
const INTER_ROUTE_DELAY_MS = Math.max(0, Number(process.env.NVX_AUDIT_ROUTE_DELAY_MS || 750));

function normalizeHeaders(headers = {}) {
  return Object.fromEntries(Object.entries(headers).map(([key, value]) => [key.toLowerCase(), value]));
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function markdownReport(report) {
  const lines = [
    '# NUVANX rendered SEO/GEO audit',
    '',
    `Generated: ${report.generatedAt}`,
    `Renderer: ${report.renderer}`,
    `Enforcement: ${report.enforcement}`,
    '',
    `Critical findings: **${report.summary.critical}**`,
    `Infrastructure (non-blocking under critical): **${report.summary.infrastructure ?? 0}**`,
    `Blocking (policy): **${report.summary.blocking ?? 0}**`,
    `Warnings: **${report.summary.warning}**`,
    '',
    '| Environment | Path | HTTP | H1 | Noindex | Canonical | Critical | Warnings |',
    '|---|---|---:|---:|---|---|---:|---:|',
  ];

  for (const page of report.pages) {
    const critical = page.issues.filter((item) => item.severity === 'critical').length;
    const warning = page.issues.filter((item) => item.severity === 'warning').length;
    lines.push(`| ${page.environment} | \`${page.path}\` | ${page.status || 'ERR'} | ${page.h1Count ?? '-'} | ${page.noindex ?? '-'} | ${page.canonical || '—'} | ${critical} | ${warning} |`);
  }

  lines.push('', '## Findings', '');
  for (const page of report.pages) {
    if (!page.issues.length) continue;
    lines.push(`### ${page.environment}: ${page.path}`, '');
    for (const finding of page.issues) {
      lines.push(`- **${finding.severity.toUpperCase()} · ${finding.code}:** ${finding.message}`);
    }
    lines.push('');
  }
  return `${lines.join('\n')}\n`;
}

function edgeInterstitialResult({
  environment,
  route,
  requestedUrl,
  finalUrl,
  status,
  title,
  contentType,
  bodyTextLength,
  xRobotsTag,
  h1Count,
}) {
  return {
    environment,
    path: route.path,
    role: route.role,
    requestedUrl,
    finalUrl,
    status,
    title,
    titleLength: title.length,
    description: '',
    descriptionLength: 0,
    canonical: '',
    metaRobots: '',
    xRobotsTag,
    noindex: null,
    ogUrl: '',
    ogImage: '',
    h1Count,
    h1Texts: [],
    schemaTypes: [],
    issues: [{
      severity: 'critical',
      code: 'EDGE_INTERSTITIAL',
      message: `The browser did not receive the WordPress document (HTTP ${status}, content-type ${contentType || 'unknown'}, title ${title || 'empty'}, body text ${bodyTextLength} chars, url ${finalUrl || 'unknown'}).`,
    }],
  };
}

function isRetryableResult(result) {
  return (result.issues || []).some((item) => item.code === 'EDGE_INTERSTITIAL' || item.code === 'FETCH_ERROR');
}

async function renderPageOnce(browser, environment, route) {
  const config = ENVIRONMENTS[environment];
  const contextOptions = {
    locale: 'es-ES',
    timezoneId: 'Europe/Madrid',
    viewport: { width: 1440, height: 1000 },
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
  };
  if (config.username && config.password) {
    contextOptions.httpCredentials = { username: config.username, password: config.password };
  }

  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  const requestedUrl = new URL(route.path, config.baseUrl).toString();
  let latestDocumentStatus = 0;
  let latestDocumentHeaders = {};

  page.on('response', async (response) => {
    if (response.request().resourceType() !== 'document') return;
    latestDocumentStatus = response.status();
    latestDocumentHeaders = normalizeHeaders(await response.allHeaders().catch(() => response.headers()));
  });

  try {
    const response = await page.goto(requestedUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 90000,
    });
    if (response) {
      latestDocumentStatus = response.status();
      latestDocumentHeaders = normalizeHeaders(await response.allHeaders().catch(() => response.headers()));
    }

    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
    await page.waitForFunction(
      () => document.title.length > 0 && document.documentElement.outerHTML.length > 1000,
      null,
      { timeout: 20000 },
    ).catch(() => {});

    const html = await page.content();
    const title = await page.title();
    const bodyTextLength = await page.locator('body').innerText().then((value) => value.trim().length).catch(() => 0);
    const contentType = latestDocumentHeaders['content-type'] || '';
    const finalUrl = page.url();

    if (
      isEdgeInterstitialResponse({
        status: latestDocumentStatus,
        contentType,
        title,
        bodyTextLength,
        finalUrl,
        html,
      })
    ) {
      return edgeInterstitialResult({
        environment,
        route,
        requestedUrl,
        finalUrl,
        status: latestDocumentStatus,
        title,
        contentType,
        bodyTextLength,
        xRobotsTag: latestDocumentHeaders['x-robots-tag'] || '',
        h1Count: await page.locator('h1').count().catch(() => 0),
      });
    }

    return analyseHtml({
      html,
      status: latestDocumentStatus,
      headers: latestDocumentHeaders,
      finalUrl,
      environment,
      route,
    });
  } catch (error) {
    return {
      environment,
      path: route.path,
      role: route.role,
      requestedUrl,
      finalUrl: page.url(),
      status: latestDocumentStatus,
      title: '',
      titleLength: 0,
      description: '',
      descriptionLength: 0,
      canonical: '',
      metaRobots: '',
      xRobotsTag: latestDocumentHeaders['x-robots-tag'] || '',
      noindex: null,
      ogUrl: '',
      ogImage: '',
      h1Count: null,
      h1Texts: [],
      schemaTypes: [],
      issues: [{
        severity: 'critical',
        code: 'FETCH_ERROR',
        message: error instanceof Error ? error.message : String(error),
      }],
    };
  } finally {
    await context.close();
  }
}

async function renderPage(browser, environment, route) {
  let last;
  for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt += 1) {
    last = await renderPageOnce(browser, environment, route);
    if (!isRetryableResult(last)) {
      if (attempt > 1) {
        console.warn(`Recovered ${environment}${route.path} on attempt ${attempt}/${MAX_ATTEMPTS}`);
      }
      return last;
    }
    if (attempt < MAX_ATTEMPTS) {
      const delay = BASE_DELAY_MS * attempt;
      console.warn(
        `Retry ${attempt}/${MAX_ATTEMPTS} for ${environment}${route.path} after ${last.issues[0]?.code} (wait ${delay}ms)`,
      );
      await sleep(delay);
    }
  }
  return last;
}

async function run() {
  const environments = (process.env.NVX_AUDIT_ENVIRONMENTS || 'production,staging')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean);
  const enforcement = process.env.NVX_SEO_ENFORCE || 'critical';
  const browser = await chromium.launch({ headless: true });
  const pages = [];

  try {
    for (const environment of environments) {
      if (!ENVIRONMENTS[environment]) throw new Error(`Unsupported environment: ${environment}`);
      for (const route of ROUTES) {
        pages.push(await renderPage(browser, environment, route));
        if (INTER_ROUTE_DELAY_MS > 0) {
          await sleep(INTER_ROUTE_DELAY_MS);
        }
      }
    }
  } finally {
    await browser.close();
  }

  const findings = pages.flatMap((page) => page.issues);
  const infrastructure = findings.filter(
    (finding) => finding.severity === 'critical' && INFRASTRUCTURE_CODES.has(finding.code),
  );
  const blocking = selectBlockingFindings(findings, enforcement);
  const report = {
    generatedAt: new Date().toISOString(),
    renderer: 'playwright-chromium',
    enforcement,
    environments,
    summary: {
      pages: pages.length,
      critical: findings.filter((item) => item.severity === 'critical').length,
      warning: findings.filter((item) => item.severity === 'warning').length,
      infrastructure: infrastructure.length,
      blocking: blocking.length,
      retries: MAX_ATTEMPTS,
    },
    pages,
  };

  await mkdir('qa/seo-geo', { recursive: true });
  await writeFile('qa/seo-geo/rendered-audit.json', `${JSON.stringify(report, null, 2)}\n`);
  await writeFile('qa/seo-geo/rendered-audit.md', markdownReport(report));
  console.log(JSON.stringify(report.summary));

  if (infrastructure.length && !blocking.length && enforcement === 'critical') {
    console.warn(
      `SEO/GEO browser gate soft-pass: ${infrastructure.length} infrastructure finding(s) (edge/WAF) after retries; no content policy criticals.`,
    );
  }
  if (blocking.length) {
    console.error(`SEO/GEO browser gate failed with ${blocking.length} blocking finding(s).`);
    process.exitCode = 1;
  }
}

run().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
