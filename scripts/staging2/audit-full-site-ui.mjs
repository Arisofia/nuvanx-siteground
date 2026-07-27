#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { randomInt } from 'node:crypto';
import {
  viewports,
  sleep,
  safeName,
  locateChrome,
  CDPSession,
  waitForChrome,
  createTarget,
  closeSession,
  captureViewport,
} from './visual-qa-common.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/full-site-ui-audit';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36';
const horizontalOverflowTolerance = 2;
const maxRedirectHops = 8;
const restPerPage = Math.max(1, Number(process.env.NVX_AUDIT_REST_PER_PAGE || 100) || 100);
const restMaxPages = Math.max(1, Number(process.env.NVX_AUDIT_REST_MAX_PAGES || 50) || 50);
const minDiscoveredRoutes = Math.max(1, Number(process.env.NVX_AUDIT_MIN_ROUTES || 20) || 20);
const seedRoutes = String(process.env.NVX_AUDIT_SEED_ROUTES || '/,/blog/')
  .split(',')
  .map((value) => value.trim())
  .filter(Boolean)
  .map((value) => (value.endsWith('/') || value === '/' ? value : `${value}/`));
/**
 * Explicit allowlist for intentional internal redirects only.
 * Keys and values are normalized pathnames (trailing slash).
 * REST-discovered routes should normally resolve without redirects.
 */
const authorizedRedirects = new Map([
  // ['/ruta-antigua/', '/ruta-canonica/'],
]);
const canonicalPalette = [
  [247, 247, 245], [241, 241, 239], [17, 17, 17], [28, 28, 30],
  [229, 229, 227], [206, 206, 206], [82, 82, 82], [92, 92, 92],
  [193, 166, 141], [255, 255, 255], [0, 0, 0],
];

if (baseUrl !== 'https://staging2.nuvanx.com') throw new Error(`Refusing unexpected BASE_URL: ${baseUrl}`);
if (!/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
if (typeof WebSocket !== 'function') throw new Error('Node.js WebSocket support is required.');
fs.mkdirSync(evidenceDir, { recursive: true });

const critical = [];
const warnings = [];
const report = {
  base_url: baseUrl,
  expected_sha: expectedSha,
  generated_at: new Date().toISOString(),
  horizontal_overflow_tolerance_px: horizontalOverflowTolerance,
  discovery: {
    pages: 0,
    posts: 0,
    categories: 0,
    total_routes: 0,
  },
  routes: [],
  results: [],
  critical,
  warnings,
};
const fail = (scope, message) => critical.push(`${scope}: ${message}`);
const warn = (scope, message) => warnings.push(`${scope}: ${message}`);

function normalizePathname(pathname) {
  if (!pathname || pathname === '/') return '/';
  return pathname.endsWith('/') ? pathname : `${pathname}/`;
}

function isSuccessfulHttpStatus(status) {
  return Number.isInteger(status) && status >= 200 && status < 400;
}

function assertDocumentNavigation(route, navigationMeta) {
  if (navigationMeta?.httpStatus === null || navigationMeta?.httpStatus === undefined) {
    throw new Error('Main document HTTP response was not recorded.');
  }
  if (!isSuccessfulHttpStatus(navigationMeta.httpStatus)) {
    throw new Error(
      `Main document HTTP ${navigationMeta.httpStatus} for ${navigationMeta.finalUrl || route}`,
    );
  }
  if (navigationMeta.redirectChain.length > maxRedirectHops) {
    throw new Error(`Redirect chain too long (${navigationMeta.redirectChain.length} hops)`);
  }

  let finalUrl;
  try {
    finalUrl = new URL(navigationMeta.finalUrl || `${baseUrl}${route}`);
  } catch {
    throw new Error(`Invalid final URL: ${navigationMeta.finalUrl || '(empty)'}`);
  }
  if (finalUrl.origin !== new URL(baseUrl).origin) {
    throw new Error(`Cross-origin navigation to ${finalUrl.href}`);
  }

  const requested = normalizePathname(route);
  const finalPath = normalizePathname(finalUrl.pathname);
  const redirected = requested !== finalPath;
  if (redirected) {
    const allowedTarget = authorizedRedirects.get(requested);
    if (allowedTarget !== finalPath) {
      throw new Error(`Unexpected internal redirect ${requested} -> ${finalPath}`);
    }
  }

  return {
    httpStatus: navigationMeta.httpStatus,
    finalUrl: finalUrl.href,
    redirected,
    redirectChain: navigationMeta.redirectChain,
  };
}

async function openPage(port, route, viewport) {
  const target = await createTarget(port);
  const session = new CDPSession(target.webSocketDebuggerUrl);
  await session.connect();
  await session.send('Page.enable');
  await session.send('Runtime.enable');
  await session.send('Network.enable');
  await session.send('Network.setUserAgentOverride', {
    userAgent,
    acceptLanguage: 'es-ES,es;q=0.9,en;q=0.7',
    platform: viewport.mobile ? 'Android' : 'Windows',
  });
  await session.send('Emulation.setDeviceMetricsOverride', {
    width: viewport.width,
    height: viewport.height,
    deviceScaleFactor: 1,
    mobile: viewport.mobile,
    screenWidth: viewport.width,
    screenHeight: viewport.height,
  });

  // Collect Document traffic first; after Page.navigate we keep only the top-level frame.
  const documentRequests = [];
  const documentResponses = [];
  const unsubscribe = [
    session.on('Network.requestWillBeSent', (params) => {
      if (params.type === 'Document') documentRequests.push(params);
    }),
    session.on('Network.responseReceived', (params) => {
      if (params.type === 'Document') documentResponses.push(params);
    }),
  ];

  const navigation = await session.send('Page.navigate', { url: `${baseUrl}${route}` });
  if (navigation.errorText) {
    for (const stop of unsubscribe) stop();
    throw new Error(`Navigation failed: ${navigation.errorText}`);
  }

  const topFrameId = navigation.frameId || null;
  const topLoaderId = navigation.loaderId || null;

  const isTopLevelDocument = (params) => {
    if (topFrameId && params.frameId) return params.frameId === topFrameId;
    if (topLoaderId && params.loaderId) return params.loaderId === topLoaderId;
    // Fall back to base-origin documents only (never accept bare iframe origins).
    const candidateUrl = params.response?.url || params.request?.url || '';
    try {
      return new URL(candidateUrl).origin === new URL(baseUrl).origin;
    } catch {
      return false;
    }
  };

  let state = null;
  try {
    for (let attempt = 1; attempt <= 60; attempt += 1) {
      state = await session.call(() => ({
        ready: document.readyState,
        sha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
        body: Boolean(document.body),
      }));
      if (state.ready === 'complete' && state.sha === expectedSha && state.body) break;
      if (attempt === 20 || attempt === 40) await session.send('Page.reload', { ignoreCache: true });
      await sleep(500);
    }
    if (state?.ready !== 'complete' || !state?.body || state?.sha !== expectedSha) {
      const issues = [];
      if (!state) issues.push('state absent');
      else {
        if (state.ready !== 'complete') issues.push(`readyState=${state.ready}`);
        if (!state.body) issues.push('body missing');
        if (state.sha !== expectedSha) issues.push(`SHA ${state.sha || 'absent'} instead of ${expectedSha}`);
      }
      throw new Error(issues.join(', '));
    }

    const topRequests = documentRequests.filter(isTopLevelDocument);
    const topResponses = documentResponses.filter(isTopLevelDocument);
    const lastResponse = topResponses.at(-1) || null;
    const lastRequest = topRequests.at(-1) || null;
    const navigationMeta = {
      httpStatus: lastResponse?.response?.status ?? null,
      finalUrl: lastResponse?.response?.url || lastRequest?.request?.url || null,
      redirectChain: [],
      loaderId: lastResponse?.loaderId || topLoaderId,
      frameId: topFrameId,
    };
    const effectiveLoaderId = lastResponse?.loaderId || topLoaderId;
    for (const request of topRequests) {
      if (request.redirectResponse && request.loaderId === effectiveLoaderId) {
        navigationMeta.redirectChain.push({
          url: request.redirectResponse.url,
          status: request.redirectResponse.status,
        });
      }
    }

    session.navigation = assertDocumentNavigation(route, navigationMeta);

    await session.evaluate(`new Promise((resolve) => {
      const finish = () => setTimeout(resolve, 350);
      if (document.fonts?.ready) document.fonts.ready.then(finish, finish); else finish();
    })`);
    return session;
  } finally {
    for (const stop of unsubscribe) stop();
  }
}

async function discoverRoutes(session) {
  const discovery = await session.call(async (seed, perPage, maxPages) => {
    const discovered = new Set(seed);
    const counts = { pages: 0, posts: 0, categories: 0, skipped_links: 0 };

    async function fetchWpCollectionBrowser(endpoint) {
      const collected = [];
      let page = 1;
      let keepGoing = true;
      while (keepGoing && page <= maxPages) {
        const separator = endpoint.includes('?') ? '&' : '?';
        const response = await fetch(
          `${endpoint}${separator}per_page=${perPage}&page=${page}`,
          { credentials: 'same-origin' },
        );
        if (!response.ok) {
          throw new Error(
            `REST collection failed: ${endpoint}, page=${page}, status=${response.status}`,
          );
        }
        const items = await response.json();
        if (!Array.isArray(items)) {
          throw new TypeError(`REST collection returned non-array payload: ${endpoint}, page=${page}`);
        }
        if (items.length === 0) break;

        collected.push(...items);

        const header = response.headers.get('X-WP-TotalPages');
        if (header === null) {
          // Header stripped by proxy/CORS: stop only when the page is short.
          keepGoing = items.length >= perPage;
        } else {
          const totalPages = Number(header);
          if (!Number.isFinite(totalPages) || totalPages < 1) {
            keepGoing = items.length >= perPage;
          } else {
            keepGoing = page < Math.min(totalPages, maxPages);
          }
        }
        page += 1;
      }
      return collected;
    }

    function addDiscoveredLink(link) {
      try {
        const url = new URL(link, location.origin);
        if (url.origin !== location.origin) return;
        discovered.add(url.pathname.endsWith('/') ? url.pathname : `${url.pathname}/`);
      } catch {
        counts.skipped_links += 1;
      }
    }

    const collections = [
      ['pages', '/wp-json/wp/v2/pages?status=publish&_fields=link'],
      ['posts', '/wp-json/wp/v2/posts?status=publish&_fields=link'],
      ['categories', '/wp-json/wp/v2/categories?hide_empty=true&_fields=link'],
    ];

    for (const [key, endpoint] of collections) {
      const items = await fetchWpCollectionBrowser(endpoint);
      counts[key] = items.length;
      for (const item of items) addDiscoveredLink(item.link);
    }

    const routes = Array.from(discovered).sort((a, b) => a.localeCompare(b, 'es'));
    return { routes, counts };
  }, seedRoutes, restPerPage, restMaxPages);

  if (!Array.isArray(discovery?.routes)) {
    throw new TypeError('WordPress route discovery returned an invalid payload.');
  }
  report.discovery = {
    pages: discovery.counts?.pages || 0,
    posts: discovery.counts?.posts || 0,
    categories: discovery.counts?.categories || 0,
    skipped_links: discovery.counts?.skipped_links || 0,
    total_routes: discovery.routes.length,
    min_routes: minDiscoveredRoutes,
    seed_routes: seedRoutes,
  };
  if (discovery.routes.length < minDiscoveredRoutes) {
    throw new Error(
      `WordPress route discovery returned only ${discovery.routes.length} routes (min=${minDiscoveredRoutes}, pages=${report.discovery.pages}, posts=${report.discovery.posts}, categories=${report.discovery.categories}).`,
    );
  }
  if (report.discovery.pages < 1) {
    throw new Error('WordPress pages collection returned zero published pages.');
  }
  return discovery.routes;
}

async function inspectPage(session) {
  return session.call((palette) => {
    const visible = (element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && rect.width > 0 && rect.height > 0;
    };
    const familyHas = (family, expected) => String(family || '').toLowerCase().includes(expected.toLowerCase());
    const rgb = (value) => {
      const match = String(value || '').match(/rgba?\((\d+)[, ]+(\d+)[, ]+(\d+)(?:[, /]+([\d.]+))?\)/i);
      return match ? [Number(match[1]), Number(match[2]), Number(match[3]), match[4] === undefined ? 1 : Number(match[4])] : null;
    };
    const approvedColor = (value) => {
      const parsed = rgb(value);
      if (!parsed || parsed[3] < 0.08) return true;
      return palette.some((candidate) => candidate.every((channel, index) => Math.abs(channel - parsed[index]) <= 3));
    };
    const unique = (values) => Array.from(new Set(values));
    const selectorName = (node, classLimit = 2) => {
      const classes = String(node.className || '').trim().split(/\s+/).filter(Boolean).slice(0, classLimit);
      return node.tagName.toLowerCase() + (node.id ? `#${node.id}` : '') + (classes.length ? '.' + classes.join('.') : '');
    };
    const accessibleName = (node) => {
      const labelledBy = (node.getAttribute('aria-labelledby') || '')
        .split(/\s+/)
        .filter(Boolean)
        .map((id) => document.getElementById(id)?.textContent || '')
        .join(' ')
        .trim();
      const labels = node.labels ? Array.from(node.labels).map((label) => label.textContent || '').join(' ').trim() : '';
      const iconAlt = node.querySelector('img[alt]')?.getAttribute('alt')?.trim() || '';
      return (
        node.getAttribute('aria-label')
        || labelledBy
        || labels
        || node.getAttribute('title')
        || node.getAttribute('placeholder')
        || node.textContent
        || iconAlt
        || node.value
        || ''
      ).trim();
    };
    const main = document.querySelector('main, .nvx-main, .site-main');
    const header = document.querySelector('#nvx-header, header[role="banner"], body > header');
    const footer = document.querySelector('footer');
    const h1 = Array.from(document.querySelectorAll('h1')).filter(visible);
    const headings = Array.from(document.querySelectorAll('main h1, main h2, main h3, .nvx-main h1, .nvx-main h2, .nvx-main h3')).filter(visible);
    const headingFontMismatches = headings
      .filter((node) => !familyHas(getComputedStyle(node).fontFamily, 'Playfair Display'))
      .slice(0, 12)
      .map((node) => selectorName(node));
    const ids = Array.from(document.querySelectorAll('[id]')).map((node) => node.id).filter(Boolean);
    const duplicateIds = unique(ids.filter((id, index) => ids.indexOf(id) !== index));
    const shells = Array.from(document.querySelectorAll('.nvx-shell, .nvx-brand-section__inner, .nvx-brand-hero__inner, .nvx-page-hero__inner, .nvx-hero__inner, .nvx-blog-archive__hero-inner, .nvx-blog-article__shell'))
      .filter(visible)
      .map((node) => {
        const rect = node.getBoundingClientRect();
        return {
          selector: selectorName(node, 3),
          left: Number(rect.left.toFixed(2)),
          right: Number((innerWidth - rect.right).toFixed(2)),
          width: Number(rect.width.toFixed(2)),
        };
      });
    const shellIssues = shells.filter((item) => item.width > innerWidth + 0.5 || item.left < -0.5 || item.right < -0.5 || Math.abs(item.left - item.right) > 3);
    const controls = Array.from(document.querySelectorAll('main .nvx-button, main .nvx-btn, main .nvx-brand-btn, main .wp-block-button__link, .nvx-main .nvx-button, .nvx-main .nvx-btn, .nvx-main .nvx-brand-btn'))
      .filter(visible)
      .map((node) => {
        const rect = node.getBoundingClientRect();
        const style = getComputedStyle(node);
        return {
          text: (node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
          width: Number(rect.width.toFixed(2)),
          height: Number(rect.height.toFixed(2)),
          font: style.fontFamily,
          radius: Number.parseFloat(style.borderRadius) || 0,
        };
      });
    const controlIssues = controls.filter((item) => item.height < 44 || item.width > innerWidth + 0.5 || !familyHas(item.font, 'Manrope') || item.radius < 20);
    const missingAlt = Array.from(document.querySelectorAll('main img, .nvx-main img'))
      .filter((node) => visible(node) && !node.hasAttribute('alt') && node.getAttribute('role') !== 'presentation')
      .slice(0, 20)
      .map((node) => node.currentSrc || node.src || 'image');
    const oversizedMedia = Array.from(document.querySelectorAll('main img, main video, main iframe, .nvx-main img, .nvx-main video, .nvx-main iframe'))
      .filter(visible)
      .filter((node) => {
        const rect = node.getBoundingClientRect();
        return rect.left < -0.5 || rect.right > innerWidth + 0.5;
      }).length;
    const levels = headings.map((node) => Number(node.tagName.slice(1)));
    const headingJumps = levels.slice(1).filter((level, index) => level - levels[index] > 1).length;
    const emptyParagraphs = Array.from(document.querySelectorAll('main p, .nvx-main p'))
      .filter((node) => visible(node) && !(node.textContent || '').trim() && !node.querySelector('img,svg,video,iframe,input,button,a')).length;
    const emptyAnchorParagraphs = Array.from(document.querySelectorAll('main a > p, .nvx-main a > p'))
      .filter((node) => !(node.textContent || '').trim()).length;
    const unnamedControls = Array.from(document.querySelectorAll('button, input[type="button"], input[type="submit"], input[type="checkbox"], a.nvx-button, a.nvx-btn, a.nvx-brand-btn, a.wp-block-button__link'))
      .filter(visible)
      .filter((node) => !accessibleName(node))
      .map((node) => selectorName(node))
      .slice(0, 20);
    const overflowingElements = Array.from(document.querySelectorAll('body *'))
      .filter(visible)
      .map((node) => {
        const rect = node.getBoundingClientRect();
        return {
          selector: selectorName(node, 3),
          left: Number(rect.left.toFixed(2)),
          right: Number(rect.right.toFixed(2)),
          width: Number(rect.width.toFixed(2)),
        };
      })
      .filter((item) => item.left < -0.5 || item.right > innerWidth + 0.5)
      .sort((a, b) => Math.max(b.right - innerWidth, -b.left) - Math.max(a.right - innerWidth, -a.left))
      .slice(0, 20);
    const colorNodes = Array.from(document.querySelectorAll('main h1, main h2, main h3, main p, main li, main section, main article, main aside, .nvx-main h1, .nvx-main h2, .nvx-main h3, .nvx-main p, .nvx-main li, .nvx-main section, .nvx-main article, .nvx-main aside'))
      .filter(visible)
      .slice(0, 1200);
    const offPalette = [];
    for (const node of colorNodes) {
      const style = getComputedStyle(node);
      for (const [property, value] of [['color', style.color], ['background', style.backgroundColor]]) {
        if (!approvedColor(value)) offPalette.push(property + ':' + value);
      }
    }
    return {
      url: location.href,
      title: document.title,
      bodyClass: document.body?.className || '',
      h1: h1.map((node) => (node.textContent || '').replace(/\s+/g, ' ').trim()),
      mainVisible: Boolean(main && visible(main)),
      headerVisible: Boolean(header && visible(header)),
      footerVisible: Boolean(footer && visible(footer)),
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      governanceStylesheet: Array.from(document.styleSheets).some((sheet) => String(sheet.href || '').includes('nvx-full-site-ui-governance.css')),
      bodyFont: getComputedStyle(document.body).fontFamily,
      fontsReady: document.fonts?.status === 'loaded',
      playfairLoaded: document.fonts?.check('16px "Playfair Display"') ?? false,
      manropeLoaded: document.fonts?.check('16px Manrope') ?? false,
      overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      documentWidth: document.documentElement.scrollWidth,
      viewportWidth: document.documentElement.clientWidth,
      overflowingElements,
      headingFontMismatches,
      duplicateIds,
      shellIssues: shellIssues.slice(0, 12),
      controls: controls.length,
      controlIssues: controlIssues.slice(0, 12),
      missingAlt,
      oversizedMedia,
      headingJumps,
      emptyParagraphs,
      emptyAnchorParagraphs,
      unnamedControls,
      offPalette: unique(offPalette).slice(0, 20),
    };
  }, canonicalPalette);
}

function evaluateResult(scope, result) {
  if (result.httpStatus !== undefined && !isSuccessfulHttpStatus(result.httpStatus)) {
    fail(scope, `HTTP ${result.httpStatus} at ${result.finalUrl || result.url || 'unknown'}`);
  }
  if (result.deploySha !== expectedSha) fail(scope, `served SHA ${result.deploySha || 'absent'} instead of ${expectedSha}`);
  if (!result.governanceStylesheet) fail(scope, 'terminal UI governance stylesheet is not loaded');
  if (!result.mainVisible) fail(scope, 'main content is missing or hidden');
  if (!result.headerVisible) fail(scope, 'global header is missing or hidden');
  if (!result.footerVisible) fail(scope, 'global footer is missing or hidden');
  if (result.h1.length !== 1 || !result.h1[0]) fail(scope, `expected one visible H1, found ${JSON.stringify(result.h1)}`);
  if (result.overflow > horizontalOverflowTolerance) {
    fail(scope, `horizontal overflow is ${result.overflow}px; sources=${JSON.stringify(result.overflowingElements)}`);
  }
  if (!result.fontsReady || !result.playfairLoaded || !result.manropeLoaded) fail(scope, 'canonical web fonts did not finish loading');
  if (!String(result.bodyFont).toLowerCase().includes('manrope')) fail(scope, `body font is ${result.bodyFont}`);
  if (result.headingFontMismatches.length) fail(scope, `non-canonical heading fonts: ${result.headingFontMismatches.join(', ')}`);
  if (result.duplicateIds.length) fail(scope, `duplicate IDs: ${result.duplicateIds.join(', ')}`);
  if (result.shellIssues.length) fail(scope, `misaligned or oversized shells: ${JSON.stringify(result.shellIssues)}`);
  if (result.controlIssues.length) fail(scope, `conversion controls violate size/type/radius contract: ${JSON.stringify(result.controlIssues)}`);
  if (result.oversizedMedia) fail(scope, `${result.oversizedMedia} media elements exceed the viewport`);
  if (result.emptyAnchorParagraphs) fail(scope, `${result.emptyAnchorParagraphs} empty paragraphs remain inside links`);
  if (result.unnamedControls.length) fail(scope, `controls have no accessible name: ${result.unnamedControls.join(', ')}`);
  if (result.missingAlt.length) warn(scope, `${result.missingAlt.length} visible images have no alt attribute`);
  if (result.headingJumps) warn(scope, `${result.headingJumps} heading-level jumps detected`);
  if (result.emptyParagraphs) warn(scope, `${result.emptyParagraphs} visible empty paragraphs remain`);
  if (result.offPalette.length) warn(scope, `non-canonical computed colors: ${result.offPalette.join(', ')}`);
}

async function auditRoute(port, route, viewport) {
  const scope = `${route} ${viewport.name}`;
  let session;
  const result = { route, viewport: viewport.name };
  try {
    session = await openPage(port, route, viewport);
    if (session.navigation) Object.assign(result, session.navigation);
    Object.assign(result, await inspectPage(session));
    evaluateResult(scope, result);
    if (critical.some((finding) => finding.startsWith(`${scope}:`))) {
      const screenshot = path.join(evidenceDir, `${safeName(route)}-${viewport.name}-failure.png`);
      await captureViewport(session, screenshot);
      result.failure_screenshot = path.basename(screenshot);
    }
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.results.push(result);
}

function writeSummary() {
  const lines = [
    '# Staging2 full-site UI audit',
    '',
    `- SHA: \`${expectedSha}\``,
    `- REST pages: **${report.discovery.pages}**`,
    `- REST posts: **${report.discovery.posts}**`,
    `- REST categories: **${report.discovery.categories}**`,
    `- Public routes discovered: **${report.discovery.total_routes || report.routes.length}**`,
    `- Viewport audits: **${report.results.length}**`,
    `- Horizontal overflow tolerance: **${horizontalOverflowTolerance}px**`,
    `- Critical findings: **${critical.length}**`,
    `- Warnings: **${warnings.length}**`,
    '',
    '## Critical findings',
    ...(critical.length ? critical.map((item) => `- ${item}`) : ['- None']),
    '',
    '## Warnings',
    ...(warnings.length ? warnings.map((item) => `- ${item}`) : ['- None']),
    '',
  ];
  fs.writeFileSync(path.join(evidenceDir, 'summary.md'), lines.join('\n'));
  fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
}

const chromePath = locateChrome();
report.chrome = chromePath;
const port = randomInt(9801, 9999);
const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-full-ui-'));
let chrome;

try {
  chrome = spawn(chromePath, [
    '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu',
    '--no-first-run', '--no-default-browser-check', '--hide-scrollbars',
    `--remote-debugging-port=${port}`, `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: ['ignore', 'ignore', 'ignore'] });

  await new Promise((resolve, reject) => {
    chrome.once('spawn', resolve);
    chrome.once('error', reject);
  });

  await waitForChrome(port);
  const discoverySession = await openPage(port, '/', viewports[0]);
  try {
    report.routes = await discoverRoutes(discoverySession);
  } finally {
    await closeSession(discoverySession);
  }
  for (const route of report.routes) {
    for (const viewport of viewports) await auditRoute(port, route, viewport);
  }
} catch (error) {
  fail(
    'audit runtime',
    error instanceof Error ? error.message : String(error),
  );
} finally {
  if (chrome?.exitCode === null && chrome?.signalCode === null) {
    chrome.kill('SIGTERM');
    const killTimer = setTimeout(() => {
      if (chrome?.exitCode === null && chrome?.signalCode === null) chrome.kill('SIGKILL');
    }, 5000);
    killTimer.unref?.();
  }
  await sleep(250);
  fs.rmSync(profileDir, { recursive: true, force: true });
  writeSummary();
}

if (critical.length) {
  console.error(`FULL_SITE_UI_AUDIT_FAILED routes=${report.routes.length} checks=${report.results.length} critical=${critical.length} warnings=${warnings.length}`);
  for (const finding of critical) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(
  `FULL_SITE_UI_AUDIT_OK routes=${report.routes.length} pages=${report.discovery.pages} posts=${report.discovery.posts} categories=${report.discovery.categories} checks=${report.results.length} warnings=${warnings.length} sha=${expectedSha}`,
);
