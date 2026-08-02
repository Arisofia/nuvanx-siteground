#!/usr/bin/env node

function stripTrailingSlashes(value) {
  let end = value.length;
  while (end > 0 && value.charAt(end - 1) === '/') {
    end -= 1;
  }
  return value.slice(0, end);
}

const baseUrl = stripTrailingSlashes(process.env.BASE_URL || 'https://staging2.nuvanx.com');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
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

async function fetchHtmlWithRetry(url, attempts = 5) {
  let lastError = new Error(`No response received from ${url}`);

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      const response = await fetch(url, {
        redirect: 'follow',
        signal: AbortSignal.timeout(15000),
        headers: {
          'cache-control': 'no-cache, no-store, max-age=0',
          pragma: 'no-cache',
          'user-agent': 'NUVANX-Rendered-Document-Acceptance/1.0'
        }
      });

      const html = await response.text();

      // Post-deploy cache purges can briefly return empty or truncated bodies.
      if (response.status >= 500) {
        lastError = new Error(`${url}: transient upstream status ${response.status}`);
      } else if (response.ok && (!/^<!doctype html>/iu.test(html.trimStart()) || html.length < 800)) {
        lastError = new Error(
          `${url}: incomplete HTML body after deploy (status=${response.status}, length=${html.length})`
        );
      } else {
        return { response, html };
      }
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));
    }

    if (attempt < attempts) await delay(attempt * 2500);
  }

  throw new Error(`${url}: request failed after ${attempts} attempts: ${lastError.message}`);
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
