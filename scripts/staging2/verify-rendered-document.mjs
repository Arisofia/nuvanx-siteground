#!/usr/bin/env node

import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);

function stripTrailingSlashes(value) {
  let end = value.length;
  while (end > 0 && value.charAt(end - 1) === '/') {
    end -= 1;
  }
  return value.slice(0, end);
}

const baseUrl = stripTrailingSlashes(process.env.BASE_URL || 'https://staging2.nuvanx.com');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
// When set (e.g. nvx-staging2), HTML is fetched via SSH curl on the SiteGround
// host so GitHub runner IPs are not blocked by edge WAF (403/202 placeholders).
const sshFetchHost = (process.env.SSH_FETCH_HOST || '').trim();
const routes = [
  '/',
  '/contacto/',
  '/soluciones-medicas/',
  '/madrid/valoracion/',
  '/medicina-estetica/',
  '/medicina-estetica-laser/',
  '/equipo-medico/',
  '/clinicas-de-medicina-estetica-nuvanx/'
];
const canonicalStylesheetHandles = [
  'nvx-tokens',
  'nvx-base',
  'nvx-layout',
  'nvx-components',
  'nvx-patterns',
  'nvx-header',
  'nvx-footer',
  'nvx-accessibility-governance'
];
const expectedShaPattern = /^[0-9a-f]{40}$/u;
const titlePattern = /<title\b[^>]*>([^<]*)<\/title>/giu;
const descriptionPattern = /<meta\b(?=[^>]*\bname\s*=\s*(["'])description\1)[^>]*>/giu;
const canonicalPattern = /<link\b(?=[^>]*\brel\s*=\s*(["'])canonical\1)[^>]*>/giu;
const contractPattern = /<meta\b(?=[^>]*\bname\s*=\s*(["'])nvx-document-contract\1)[^>]*>/giu;
const mainOpenPattern = /<main\b[^>]*>/iu;
const mainClosePattern = /<\/main>/iu;
const shaPattern = /<meta\b[^>]*\bname=["']nvx-deploy-sha["'][^>]*\bcontent=["']([0-9a-f]{40})["'][^>]*>/iu;
const evidenceImagePattern = /<img\b[^>]*\bclass=["'][^"']*nvx-home-evidence__image[^"']*["'][^>]*>/iu;
const hubspotScriptPattern = /<script\b[^>]*\bsrc=["'][^"']*(?:hsforms\.net|hsforms\.com|hs-scripts\.com)[^"']*["'][^>]*>/iu;

if (!expectedShaPattern.test(expectedSha)) {
  throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
}

function count(html, pattern) {
  return (html.match(pattern) || []).length;
}

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

function attribute(tag, name) {
  const pattern = new RegExp(String.raw`\b${name}\s*=\s*(["'])([^"']*)\1`, 'iu');
  const match = pattern.exec(tag);
  return match ? match[2] : '';
}

function matchAll(html, pattern) {
  return [...html.matchAll(pattern)];
}

function delay(milliseconds) {
  return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

function shellSingleQuote(value) {
  return `'${String(value).replace(/'/g, `'\\''`)}'`;
}

function parseCurlHeaderBlock(headerText) {
  const headers = new Map();
  for (const line of headerText.split(/\r?\n/u)) {
    const separator = line.indexOf(':');
    if (separator <= 0) continue;
    const name = line.slice(0, separator).trim().toLowerCase();
    const value = line.slice(separator + 1).trim();
    if (name) headers.set(name, value);
  }
  return {
    get(name) {
      return headers.get(String(name).toLowerCase()) || null;
    }
  };
}

function makeResponse(status, headerText) {
  return {
    status,
    ok: status >= 200 && status < 300,
    headers: parseCurlHeaderBlock(headerText)
  };
}

/**
 * Fetch rendered HTML from the SiteGround host so the request never leaves
 * GitHub-hosted runner IPs (SiteGround edge returns 403/202 to those IPs).
 *
 * Do not set a custom User-Agent on the remote curl: SiteGround returns a
 * static 403 Forbidden page (~75KB, title "403 - Forbidden") for bot-like and
 * many non-default UAs even from origin. curl's default UA returns 200 with
 * the real WordPress document.
 */
async function fetchHtmlViaSsh(url) {
  // Markers must not start with "-" as the printf format string: bash printf
  // treats leading dashes as options (error: "printf: --: invalid option").
  const remoteScript = [
    'set -euo pipefail',
    'tmp=$(mktemp)',
    'hdr=$(mktemp)',
    // No -A: default curl UA is required for SiteGround origin 200 responses.
    `code=$(curl -sS -L --max-time 25 -H ${shellSingleQuote(
      'cache-control: no-cache, no-store, max-age=0'
    )} -H ${shellSingleQuote(
      'accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8'
    )} -o "$tmp" -D "$hdr" -w '%{http_code}' ${shellSingleQuote(url)})`,
    'printf \'%s\\n\' "$code"',
    'printf \'%s\\n\' \'NVX_HDR_BEGIN\'',
    'cat "$hdr"',
    'printf \'%s\\n\' \'NVX_BODY_BEGIN\'',
    'cat "$tmp"',
    'rm -f "$tmp" "$hdr"'
  ].join('; ');

  const { stdout } = await execFileAsync('ssh', [sshFetchHost, remoteScript], {
    maxBuffer: 12 * 1024 * 1024,
    timeout: 45000,
    windowsHide: true
  });

  const headerMarker = 'NVX_HDR_BEGIN\n';
  const bodyMarker = 'NVX_BODY_BEGIN\n';
  const headerIndex = stdout.indexOf(headerMarker);
  const bodyIndex = stdout.indexOf(bodyMarker);
  if (headerIndex < 0 || bodyIndex < 0 || bodyIndex < headerIndex) {
    throw new Error(`${url}: malformed SSH curl response envelope`);
  }

  const statusLine = stdout.slice(0, headerIndex).trim();
  const status = Number.parseInt(statusLine, 10);
  if (!Number.isFinite(status)) {
    throw new Error(`${url}: missing HTTP status from SSH curl (got ${JSON.stringify(statusLine)})`);
  }

  const headerText = stdout.slice(headerIndex + headerMarker.length, bodyIndex);
  const html = stdout.slice(bodyIndex + bodyMarker.length);
  return { response: makeResponse(status, headerText), html };
}

async function fetchHtmlDirect(url) {
  const response = await fetch(url, {
    redirect: 'follow',
    signal: AbortSignal.timeout(20000),
    headers: {
      'cache-control': 'no-cache, no-store, max-age=0',
      pragma: 'no-cache',
      'user-agent': 'Mozilla/5.0 (compatible; NUVANX-Rendered-Document-Acceptance/1.2; +https://nuvanx.com)',
      accept: 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8'
    }
  });
  const html = await response.text();
  return { response, html };
}

function isCompleteDocument(html) {
  return /^<!doctype html>/iu.test(html.trimStart()) && html.length >= 800;
}

function isRetryableStatus(status) {
  return status === 202 || status === 203 || status === 403 || status === 429 || status >= 500;
}

async function fetchHtmlWithRetry(url, attempts = 6) {
  let lastError = new Error(`No response received from ${url}`);
  const transport = sshFetchHost ? 'ssh' : 'direct';

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      const { response, html } = sshFetchHost ? await fetchHtmlViaSsh(url) : await fetchHtmlDirect(url);
      const complete = isCompleteDocument(html);

      if (isRetryableStatus(response.status)) {
        lastError = new Error(
          `${url}: transient ${transport} status ${response.status} (length=${html.length})`
        );
      } else if (response.ok && !complete) {
        lastError = new Error(
          `${url}: incomplete HTML body after deploy (status=${response.status}, length=${html.length})`
        );
      } else if (response.ok && complete) {
        return { response, html };
      } else {
        lastError = new Error(`${url}: unexpected status ${response.status} (length=${html.length})`);
      }
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
    }

    if (attempt < attempts) await delay(attempt * 2000);
  }

  throw new Error(`${url}: request failed after ${attempts} ${transport} attempts: ${lastError.message}`);
}

async function verifyRoute(route) {
  const { response, html } = await fetchHtmlWithRetry(`${baseUrl}${route}`);

  assert(response.ok, `${route}: expected 2xx, received ${response.status}`);
  assert(/^<!doctype html>/iu.test(html.trimStart()), `${route}: missing HTML doctype`);
  assert(/<html\b[^>]*\blang=["']es(?:-ES)?["']/iu.test(html), `${route}: missing Spanish html lang`);
  assert(count(html, /<meta\b[^>]*\bname=["']viewport["'][^>]*>/giu) === 1, `${route}: viewport must appear exactly once`);

  const headHtml = (/<head\b[^>]*>([\s\S]*?)<\/head>/iu.exec(html) || [])[1] || html;

  const titles = matchAll(headHtml, titlePattern);
  assert(titles.length === 1, `${route}: title must appear exactly once`);
  assert(titles[0][1].trim().length > 0, `${route}: title must be non-empty`);

  const descriptions = matchAll(headHtml, descriptionPattern);
  assert(descriptions.length === 1, `${route}: meta description must appear exactly once`);
  assert(attribute(descriptions[0][0], 'content').trim().length >= 40, `${route}: meta description is missing or too short`);

  const canonicals = matchAll(headHtml, canonicalPattern);
  assert(canonicals.length === 1, `${route}: canonical must appear exactly once`);
  assert(attribute(canonicals[0][0], 'href').startsWith(baseUrl), `${route}: canonical must use the staging2 host`);

  assert(count(html, contractPattern) === 1, `${route}: document contract marker missing`);
  const mainOpen = mainOpenPattern.exec(html);
  const mainClose = mainClosePattern.exec(html);
  assert(
    mainOpen && mainClose && mainClose.index > mainOpen.index + mainOpen[0].length,
    `${route}: main landmark is empty or missing`
  );
  const mainBody = html.slice(mainOpen.index + mainOpen[0].length, mainClose.index);
  assert(/\S/u.test(mainBody), `${route}: main landmark is empty or missing`);
  assert(!html.includes('NUVANX_STRATEGY_PAGE:'), `${route}: unresolved CMS strategy marker leaked into public HTML`);
  assert(!html.includes('FacebookSignal'), `${route}: retired FacebookSignal runtime leaked into public HTML`);

  for (const handle of canonicalStylesheetHandles) {
    assert(html.includes(handle), `${route}: canonical stylesheet '${handle}' missing`);
  }
  assert(html.includes('nvx-runtime-governance'), `${route}: global runtime governance script missing`);

  if (html.includes('googlesitekit-consent-mode')) {
    assert(html.includes('_googlesitekitConsentCategoryMap'), `${route}: Site Kit consent script exists without its category map`);
    assert(html.includes('_googlesitekitConsents'), `${route}: Site Kit consent script exists without its defaults`);
  }

  const xRobots = response.headers.get('x-robots-tag') || '';
  assert(/noindex/iu.test(xRobots), `${route}: staging2 X-Robots-Tag noindex guard missing`);
  assert(/<meta\b(?=[^>]*\bname=["']robots["'])[^>]*noindex[^>]*>/iu.test(html), `${route}: staging2 robots meta noindex guard missing`);

  const shaMatch = shaPattern.exec(html);
  assert(shaMatch, `${route}: immutable deploy SHA marker missing`);
  assert(shaMatch[1] === expectedSha, `${route}: deployed SHA ${shaMatch[1]} does not match ${expectedSha}`);

  if ('/soluciones-medicas/' === route) {
    assert(html.includes('nvx-solutions-page'), `${route}: canonical solutions hierarchy missing`);
    assert(html.includes('nvx-soluciones-medicas.css'), `${route}: solutions stylesheet missing`);
  }

  if ('/' === route) {
    const evidence = evidenceImagePattern.exec(html);
    assert(evidence, `${route}: home evidence image missing`);
    assert(/\bwidth=["']\d+["']/iu.test(evidence[0]), `${route}: home evidence image width missing`);
    assert(/\bheight=["']\d+["']/iu.test(evidence[0]), `${route}: home evidence image height missing`);
  }

  assert(
    !hubspotScriptPattern.test(html),
    `${route}: HubSpot embed must not load before explicit user intent`
  );

  return { route, sha: shaMatch[1], title: titles[0][1].trim() };
}

const results = [];
const failures = [];
for (const route of routes) {
  try {
    results.push(await verifyRoute(route));
  } catch (error) {
    failures.push(error instanceof Error ? error.message : `${route}: ${String(error)}`);
  }
}

if (failures.length) {
  throw new Error(`Rendered acceptance failed:\n- ${failures.join('\n- ')}`);
}

const deployedShas = new Set(results.map((result) => result.sha));
assert(deployedShas.size === 1, `Routes serve different deployment SHAs: ${[...deployedShas].join(', ')}`);
console.log(JSON.stringify({ baseUrl, deployedSha: results[0].sha, routes: results }, null, 2));
