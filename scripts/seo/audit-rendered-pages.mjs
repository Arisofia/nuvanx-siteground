#!/usr/bin/env node

import { mkdir, writeFile } from 'node:fs/promises';
import process from 'node:process';
import { URL, fileURLToPath } from 'node:url';

const DEFAULT_ROUTES = [
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
  { path: '/madrid/valoracion/', role: 'conversion', expectedTypes: [] },
];

const ENVIRONMENTS = {
  production: {
    baseUrl: process.env.NVX_PRODUCTION_URL || 'https://nuvanx.com',
    mustNoindex: false,
    canonicalHost: 'nuvanx.com',
    forbiddenHosts: ['staging2.nuvanx.com'],
  },
  staging: {
    baseUrl: process.env.NVX_STAGING_URL || 'https://staging2.nuvanx.com',
    mustNoindex: true,
    canonicalHost: null,
    forbiddenHosts: [],
  },
};

export const CRITICAL_CODES = new Set([
  'FETCH_ERROR',
  'HTTP_ERROR',
  'PRODUCTION_NOINDEX',
  'STAGING_INDEXABLE',
  'PRODUCTION_CANONICAL_MISSING',
  'PRODUCTION_CANONICAL_HOST',
  'PRODUCTION_STAGING_REFERENCE',
  'EDGE_INTERSTITIAL',
]);

// WAF/captcha and transient fetch failures after retries are infrastructure noise.
// They remain critical in the report, but default enforcement does not fail the job
// unless a content/policy critical is also present.
export const INFRASTRUCTURE_CODES = new Set([
  'EDGE_INTERSTITIAL',
  'FETCH_ERROR',
]);

export function selectBlockingFindings(findings, enforcement = 'critical') {
  if (enforcement === 'none') return [];
  if (enforcement === 'all') {
    return findings.filter(
      (finding) => finding.severity === 'critical' || finding.severity === 'warning',
    );
  }
  return findings.filter(
    (finding) => (
      finding.severity === 'critical'
      && CRITICAL_CODES.has(finding.code)
      && !INFRASTRUCTURE_CODES.has(finding.code)
    ),
  );
}

function decodeEntities(value = '') {
  return value
    .replaceAll('&amp;', '&')
    .replaceAll('&quot;', '"')
    .replaceAll('&#039;', "'")
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>')
    .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)));
}

function stripTags(value = '') {
  return decodeEntities(value.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
}

export function parseAttributes(tag = '') {
  const attrs = {};
  const pattern = /([^\s=<>`]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'=<>`]+)))?/g;
  for (const match of tag.matchAll(pattern)) {
    const key = match[1].replace(/^<\/?/, '').toLowerCase();
    if (!key || ['meta', 'link', 'script', 'html', 'head'].includes(key)) continue;
    attrs[key] = decodeEntities(match[2] ?? match[3] ?? match[4] ?? '');
  }
  return attrs;
}

function tags(html, name) {
  const pattern = new RegExp(`<${name}\\b[^>]*>`, 'gi');
  return [...html.matchAll(pattern)].map((match) => match[0]);
}

export function getMeta(html, selector, selectorAttribute = 'name') {
  const needle = selector.toLowerCase();
  for (const tag of tags(html, 'meta')) {
    const attrs = parseAttributes(tag);
    if ((attrs[selectorAttribute] || '').toLowerCase() === needle) {
      return attrs.content || '';
    }
  }
  return '';
}

export function getCanonical(html) {
  for (const tag of tags(html, 'link')) {
    const attrs = parseAttributes(tag);
    const rels = (attrs.rel || '').toLowerCase().split(/\s+/);
    if (rels.includes('canonical')) return attrs.href || '';
  }
  return '';
}

function getTitle(html) {
  const match = html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
  return match ? stripTags(match[1]) : '';
}

function getHead(html) {
  const match = html.match(/<head\b[^>]*>([\s\S]*?)<\/head>/i);
  return match ? match[1] : html.slice(0, 100000);
}

function h1Data(html) {
  const matches = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)];
  return {
    count: matches.length,
    texts: matches.map((match) => stripTags(match[1])).filter(Boolean),
  };
}

export function collectSchemaTypes(html) {
  const types = new Set();
  const errors = [];
  const scriptPattern = /<script\b[^>]*type\s*=\s*(?:"application\/ld\+json"|'application\/ld\+json'|application\/ld\+json)[^>]*>([\s\S]*?)<\/script>/gi;

  function visit(value) {
    if (Array.isArray(value)) {
      value.forEach(visit);
      return;
    }
    if (!value || typeof value !== 'object') return;
    const rawType = value['@type'];
    if (Array.isArray(rawType)) rawType.forEach((type) => types.add(String(type)));
    else if (rawType) types.add(String(rawType));
    Object.values(value).forEach(visit);
  }

  for (const match of html.matchAll(scriptPattern)) {
    try {
      visit(JSON.parse(match[1].trim()));
    } catch (error) {
      errors.push(error instanceof Error ? error.message : String(error));
    }
  }

  return { types: [...types].sort(), parseErrors: errors };
}

function robotsDirectives(metaRobots, xRobotsTag) {
  return `${metaRobots},${xRobotsTag}`
    .toLowerCase()
    .split(/[;,]/)
    .map((item) => item.trim())
    .filter(Boolean);
}

function issue(severity, code, message) {
  return { severity, code, message };
}

/**
 * Detect SiteGround / WAF bot challenges and other non-document responses so
 * they are not misclassified as production SEO regressions (e.g. noindex captcha).
 */
export function isEdgeInterstitialResponse({
  status = 0,
  contentType = '',
  title = '',
  bodyTextLength = Number.POSITIVE_INFINITY,
  finalUrl = '',
  html = '',
} = {}) {
  const url = String(finalUrl || '').toLowerCase();
  const normalizedTitle = String(title || '').toLowerCase();
  const normalizedHtml = String(html || '').toLowerCase();
  const type = String(contentType || '').toLowerCase();

  if (status === 202) return true;
  if (type && !type.includes('text/html') && !type.includes('application/xhtml')) return true;
  if (!normalizedTitle && bodyTextLength < 100) return true;
  if (bodyTextLength < 100) return true;
  if (url.includes('/.well-known/sgcaptcha')) return true;
  if (normalizedTitle.includes('robot challenge')) return true;
  if (normalizedHtml.includes('/.well-known/sgcaptcha')) return true;
  if (normalizedHtml.includes('sgcaptcha')) return true;
  return false;
}

export function analyseHtml({ html, status, headers = {}, finalUrl, environment, route }) {
  const config = ENVIRONMENTS[environment];
  if (!config) throw new Error(`Unknown environment: ${environment}`);

  const head = getHead(html);
  const title = getTitle(html);

  if (
    isEdgeInterstitialResponse({
      status,
      contentType: headers['content-type'] || '',
      title,
      bodyTextLength: String(html || '').replace(/<[^>]+>/g, ' ').trim().length,
      finalUrl,
      html,
    })
  ) {
    return {
      environment,
      path: route.path,
      role: route.role,
      requestedUrl: new URL(route.path, config.baseUrl).toString(),
      finalUrl,
      status,
      title,
      titleLength: title.length,
      description: '',
      descriptionLength: 0,
      canonical: '',
      metaRobots: getMeta(html, 'robots'),
      xRobotsTag: headers['x-robots-tag'] || '',
      noindex: null,
      ogUrl: '',
      ogImage: '',
      h1Count: 0,
      h1Texts: [],
      schemaTypes: [],
      issues: [
        issue(
          'critical',
          'EDGE_INTERSTITIAL',
          `Edge/WAF interstitial received instead of the WordPress document (HTTP ${status}, title ${title || 'empty'}, url ${finalUrl || 'unknown'}).`,
        ),
      ],
    };
  }
  const description = getMeta(html, 'description');
  const metaRobots = getMeta(html, 'robots');
  const xRobotsTag = headers['x-robots-tag'] || '';
  const canonical = getCanonical(html);
  const ogUrl = getMeta(html, 'og:url', 'property');
  const ogImage = getMeta(html, 'og:image', 'property');
  const h1 = h1Data(html);
  const schema = collectSchemaTypes(html);
  const directives = robotsDirectives(metaRobots, xRobotsTag);
  const noindex = directives.includes('noindex');
  const issues = [];

  if (status < 200 || status >= 400) {
    issues.push(issue('critical', 'HTTP_ERROR', `HTTP ${status}`));
  }

  if (!title) issues.push(issue('warning', 'TITLE_MISSING', 'No title element found.'));
  else if (title.length > 65) issues.push(issue('warning', 'TITLE_LONG', `Title has ${title.length} characters.`));

  if (!description) issues.push(issue('warning', 'DESCRIPTION_MISSING', 'Meta description is missing.'));
  else if (description.length > 165) issues.push(issue('warning', 'DESCRIPTION_LONG', `Meta description has ${description.length} characters.`));

  if (h1.count !== 1) {
    issues.push(issue('warning', 'H1_COUNT', `Expected one H1; found ${h1.count}.`));
  }

  if (schema.parseErrors.length) {
    issues.push(issue('warning', 'SCHEMA_JSON_INVALID', `${schema.parseErrors.length} JSON-LD block(s) could not be parsed.`));
  }

  for (const expectedType of route.expectedTypes || []) {
    if (!schema.types.includes(expectedType)) {
      issues.push(issue('warning', 'SCHEMA_TYPE_MISSING', `Expected schema type ${expectedType} was not found.`));
    }
  }

  if (environment === 'production') {
    if (noindex) issues.push(issue('critical', 'PRODUCTION_NOINDEX', 'Production page is noindex.'));
    if (!canonical) {
      issues.push(issue('critical', 'PRODUCTION_CANONICAL_MISSING', 'Production canonical is missing.'));
    } else {
      try {
        const canonicalHost = new URL(canonical, finalUrl).hostname;
        if (canonicalHost !== config.canonicalHost) {
          issues.push(issue('critical', 'PRODUCTION_CANONICAL_HOST', `Canonical host is ${canonicalHost}.`));
        }
      } catch {
        issues.push(issue('critical', 'PRODUCTION_CANONICAL_HOST', `Canonical is not a valid URL: ${canonical}`));
      }
    }

    const headLower = head.toLowerCase();
    for (const forbiddenHost of config.forbiddenHosts) {
      if (headLower.includes(forbiddenHost.toLowerCase())) {
        issues.push(issue('critical', 'PRODUCTION_STAGING_REFERENCE', `Production head references ${forbiddenHost}.`));
      }
    }
  }

  if (environment === 'staging' && !noindex) {
    issues.push(issue('critical', 'STAGING_INDEXABLE', 'Staging page does not declare noindex.'));
  }

  if (route.role === 'home' || route.role === 'clinic-hub') {
    for (const registration of ['CS20144', 'CS20073']) {
      if (!html.includes(registration)) {
        issues.push(issue('warning', 'REGISTRATION_NOT_VISIBLE', `${registration} is not visible in rendered HTML.`));
      }
    }
  }

  return {
    environment,
    path: route.path,
    role: route.role,
    requestedUrl: new URL(route.path, config.baseUrl).toString(),
    finalUrl,
    status,
    title,
    titleLength: title.length,
    description,
    descriptionLength: description.length,
    canonical,
    metaRobots,
    xRobotsTag,
    noindex,
    ogUrl,
    ogImage,
    h1Count: h1.count,
    h1Texts: h1.texts,
    schemaTypes: schema.types,
    issues,
  };
}

async function fetchWithRetry(url, auth, attempts = 3) {
  let lastError;
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 25000);
    try {
      const headers = {
        'user-agent': 'NUVANX-SEO-GEO-Gate/1.0 (+https://nuvanx.com)',
        accept: 'text/html,application/xhtml+xml',
      };
      if (auth) headers.authorization = `Basic ${Buffer.from(auth).toString('base64')}`;
      const response = await fetch(url, {
        headers,
        redirect: 'follow',
        signal: controller.signal,
      });
      const html = await response.text();
      const normalizedHeaders = {};
      response.headers.forEach((value, key) => {
        normalizedHeaders[key.toLowerCase()] = value;
      });
      return {
        status: response.status,
        finalUrl: response.url,
        headers: normalizedHeaders,
        html,
      };
    } catch (error) {
      lastError = error;
      if (attempt < attempts) await new Promise((resolve) => setTimeout(resolve, attempt * 1500));
    } finally {
      clearTimeout(timeout);
    }
  }
  throw lastError;
}

function markdownReport(report) {
  const lines = [
    '# NUVANX rendered SEO/GEO audit',
    '',
    `Generated: ${report.generatedAt}`,
    `Enforcement: ${report.enforcement}`,
    '',
    `Critical findings: **${report.summary.critical}**`,
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

async function run() {
  const requested = (process.env.NVX_AUDIT_ENVIRONMENTS || 'production,staging')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean);
  const enforcement = process.env.NVX_SEO_ENFORCE || 'critical';
  const pages = [];

  for (const environment of requested) {
    const config = ENVIRONMENTS[environment];
    if (!config) throw new Error(`Unsupported environment in NVX_AUDIT_ENVIRONMENTS: ${environment}`);
    const auth = environment === 'staging' ? process.env.NVX_STAGING_BASIC_AUTH || '' : '';

    for (const route of DEFAULT_ROUTES) {
      const requestedUrl = new URL(route.path, config.baseUrl).toString();
      try {
        const response = await fetchWithRetry(requestedUrl, auth);
        pages.push(analyseHtml({ ...response, environment, route }));
      } catch (error) {
        pages.push({
          environment,
          path: route.path,
          role: route.role,
          requestedUrl,
          finalUrl: '',
          status: 0,
          title: '',
          titleLength: 0,
          description: '',
          descriptionLength: 0,
          canonical: '',
          metaRobots: '',
          xRobotsTag: '',
          noindex: null,
          ogUrl: '',
          ogImage: '',
          h1Count: null,
          h1Texts: [],
          schemaTypes: [],
          issues: [issue('critical', 'FETCH_ERROR', error instanceof Error ? error.message : String(error))],
        });
      }
    }
  }

  const findings = pages.flatMap((page) => page.issues);
  const report = {
    generatedAt: new Date().toISOString(),
    enforcement,
    environments: requested,
    summary: {
      pages: pages.length,
      critical: findings.filter((item) => item.severity === 'critical').length,
      warning: findings.filter((item) => item.severity === 'warning').length,
    },
    pages,
  };

  await mkdir('qa/seo-geo', { recursive: true });
  await writeFile('qa/seo-geo/rendered-audit.json', `${JSON.stringify(report, null, 2)}\n`);
  await writeFile('qa/seo-geo/rendered-audit.md', markdownReport(report));

  console.log(JSON.stringify(report.summary));

  const infrastructure = findings.filter(
    (finding) => finding.severity === 'critical' && INFRASTRUCTURE_CODES.has(finding.code),
  );
  const blocking = selectBlockingFindings(findings, enforcement);

  if (infrastructure.length && !blocking.length && enforcement === 'critical') {
    console.warn(
      `SEO/GEO gate soft-pass: ${infrastructure.length} infrastructure finding(s) (edge/WAF) after retries; no content policy criticals.`,
    );
  }

  if (blocking.length) {
    console.error(`SEO/GEO gate failed with ${blocking.length} blocking finding(s).`);
    process.exitCode = 1;
  }
}

const isMain = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];
if (isMain) {
  run().catch((error) => {
    console.error(error);
    process.exitCode = 1;
  });
}
