import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const expectedHost = process.env.EXPECTED_HOST || 'staging2.nuvanx.com';

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}

const viewports = [
  { key: 'desktop-1440x1100', label: 'Desktop 1440×1100', width: 1440, height: 1100 },
  { key: 'tablet-1024x768', label: 'Tablet 1024×768', width: 1024, height: 768 },
  { key: 'mobile-390x844', label: 'Mobile 390×844', width: 390, height: 844 },
];

const outputDir = path.resolve('scripts/staging2/block-c-artifacts');
const screenshotDir = path.join(outputDir, 'screenshots');
await fs.rm(outputDir, { recursive: true, force: true });
await fs.mkdir(screenshotDir, { recursive: true });

const shortContentRoutes = new Set([
  '/gracias/',
  '/politica-de-cookies-ue/',
  '/politica-privacidad/',
  '/aviso-legal/',
  '/politica-de-cookies/',
  '/mas-informacion-sobre-las-cookies/',
  '/casos-de-pacientes/',
]);

// Published CMS records may intentionally resolve through a non-200 frontend
// contract. Patient cases remain a strict 404 until editorial evidence is
// explicitly marked publication-ready by _nvx_cases_publication_ready=1.
const expectedStatusByRoute = new Map([
  ['/casos-de-pacientes/', 404],
]);

const normalizePath = (value) => {
  const url = new URL(value, `${baseUrl}/`);
  let pathname = url.pathname || '/';
  if (!pathname.endsWith('/')) pathname += '/';
  return pathname;
};

async function fetchPublishedPages() {
  const endpoint = `${baseUrl}/wp-json/wp/v2/pages?per_page=100&status=publish&orderby=id&order=asc&_fields=id,link,slug,title`;
  let lastError = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    try {
      const response = await fetch(endpoint, {
        headers: {
          'user-agent': 'NUVANX-BlockC-Registry/1.0',
          accept: 'application/json',
        },
      });
      if (response.status === 202 || response.headers.get('sg-captcha')) {
        lastError = new Error(`SiteGround Antibot challenged WordPress REST on attempt ${attempt}`);
      } else if (!response.ok) {
        lastError = new Error(`WordPress REST returned HTTP ${response.status}`);
      } else {
        const pages = await response.json();
        if (!Array.isArray(pages)) throw new Error('WordPress REST pages response is not an array');
        const normalized = pages.map((page) => ({
          id: Number(page.id),
          slug: page.slug || '',
          title: page.title?.rendered || '',
          url: page.link,
          path: normalizePath(page.link),
        }));
        const unique = new Set(normalized.map((page) => page.path));
        if (normalized.length !== 52) {
          throw new Error(`Block C requires 52 published pages; WordPress REST returned ${normalized.length}`);
        }
        if (unique.size !== 52) {
          throw new Error(`Block C requires 52 unique published paths; REST returned ${unique.size}`);
        }
        for (const page of normalized) {
          if (new URL(page.url).hostname !== expectedHost) {
            throw new Error(`Published page ${page.id} points outside staging2: ${page.url}`);
          }
        }
        return normalized;
      }
    } catch (error) {
      lastError = error;
    }
    await new Promise((resolve) => setTimeout(resolve, 2000 * attempt));
  }
  throw lastError || new Error('Unable to fetch published WordPress pages');
}

const publishedPages = await fetchPublishedPages();
const routes = publishedPages.map((page) => page.path);
await fs.writeFile(
  path.join(outputDir, 'published-pages.json'),
  `${JSON.stringify(publishedPages, null, 2)}\n`,
  'utf8'
);

function safeName(route) {
  if (route === '/') return 'home';
  return route.replace(/^\/+|\/+$/g, '').replace(/[^a-zA-Z0-9_-]+/g, '_') || 'route';
}

async function gotoPlain(page, url) {
  let lastError = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    try {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 40000 });
      if (!response) return { response: null, attempt };
      const headers = await response.allHeaders();
      if (response.status() === 202 || headers['sg-captcha']) {
        if (attempt < 4) {
          await page.waitForTimeout(2500 * attempt);
          continue;
        }
      }
      return { response, attempt };
    } catch (error) {
      lastError = error;
      if (attempt === 4) throw error;
      await page.waitForTimeout(2200 * attempt);
    }
  }
  throw lastError || new Error(`Unable to navigate to ${url}`);
}

async function handleCookieConsent(page) {
  const selectors = [
    'button:has-text("Aceptar todo")',
    'button:has-text("Aceptar")',
    'button:has-text("Accept all")',
    'button:has-text("Accept")',
    '.cmplz-accept',
    '#cmplz-cookiebanner-container button.cmplz-accept',
  ];
  for (const selector of selectors) {
    try {
      const button = page.locator(selector).first();
      if (await button.isVisible({ timeout: 350 })) {
        await button.click({ timeout: 1500 });
        await page.waitForTimeout(150);
        return;
      }
    } catch {
      // Continue.
    }
  }
}

async function waitForVisualStability(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
  }).catch(() => {});
  await page.waitForTimeout(400);
}

async function activateLazyImages(page) {
  await page.evaluate(async () => {
    const scrollingElement = document.scrollingElement || document.documentElement;
    const maxY = Math.max(0, scrollingElement.scrollHeight - window.innerHeight);
    const step = Math.max(360, Math.floor(window.innerHeight * 0.8));
    for (let y = 0; y <= maxY; y += step) {
      window.scrollTo(0, y);
      await new Promise((resolve) => setTimeout(resolve, 45));
    }
    window.scrollTo(0, maxY);
    await new Promise((resolve) => setTimeout(resolve, 120));
    window.scrollTo(0, 0);
  }).catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
  await page.waitForTimeout(180);
}

async function collectGeometry(page) {
  return page.evaluate(() => {
    const vw = window.innerWidth;
    const doc = document.documentElement;
    const body = document.body;
    const visible = (el) => {
      if (!el) return false;
      const style = getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number.parseFloat(style.opacity || '1') > 0.01 && rect.width > 1 && rect.height > 1;
    };

    const overflowAmount = Math.max(doc.scrollWidth, body?.scrollWidth || 0) - vw;
    const culprits = [];
    if (overflowAmount > 2) {
      for (const el of document.querySelectorAll('body *')) {
        if (!visible(el)) continue;
        const r = el.getBoundingClientRect();
        if (r.right > vw + 2 || r.left < -2 || r.width > vw + 2) {
          culprits.push({
            tag: el.tagName,
            id: el.id || '',
            className: typeof el.className === 'string' ? el.className.slice(0, 180) : '',
            left: Math.round(r.left),
            right: Math.round(r.right),
            width: Math.round(r.width),
          });
          if (culprits.length >= 12) break;
        }
      }
    }

    const header = document.querySelector('header, .nvx-site-header, .nvx-header');
    const footer = document.querySelector('footer, .nvx-site-footer, .nvx-footer');
    const main = document.querySelector('main#nvx-main, main, [role="main"]');
    const hero = document.querySelector('.nvx-home-hero, .nvx-brand-hero, .nvx-blog-hero, .nvx-page-header, .nvx-section-intro, .nvx-catalog__intro, .nvx-strategy-intro, [class*="hero"]');
    const nav = document.querySelector('header nav, .nvx-site-header nav, .nvx-header nav, .nvx-primary-nav');
    const video = document.querySelector('.nvx-home-hero video, video');
    const navToggleSelector = 'button[aria-label*="menu" i], button[data-nvx-menu-toggle], .nvx-menu-toggle, .nav-toggle, button[aria-expanded]';
    const navToggleVisible = Array.from(document.querySelectorAll(navToggleSelector)).some(visible);

    const h1s = Array.from(document.querySelectorAll('h1')).filter(visible);
    const h1 = h1s[0] || null;
    const h1Rect = h1?.getBoundingClientRect() || null;
    const h1Style = h1 ? getComputedStyle(h1) : null;
    const h1Clipped = Boolean(
      h1 &&
        ((h1.scrollWidth > h1.clientWidth + 2 && ['hidden', 'clip'].includes(h1Style.overflowX)) ||
          (h1.scrollHeight > h1.clientHeight + 2 && ['hidden', 'clip'].includes(h1Style.overflowY)))
    );

    const visibleCtas = Array.from(
      document.querySelectorAll('a.nvx-btn, a.nvx-button, a.nvx-brand-btn, button.nvx-btn, button.nvx-button, button.nvx-brand-btn, .nvx-brand-actions a, .nvx-actions a, a[href*="valoracion"], a[href*="wa.me"], a[href*="whatsapp"]')
    ).filter(visible);

    const invalidCtas = visibleCtas
      .filter((el) => {
        if (el.tagName === 'BUTTON') return false;
        const href = (el.getAttribute('href') || '').trim();
        return !href || href === '#';
      })
      .slice(0, 10)
      .map((el) => (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120));

    const brokenImages = Array.from(document.images)
      .filter((img) => visible(img) && img.complete && img.naturalWidth === 0 && Boolean(img.currentSrc || img.getAttribute('src')))
      .slice(0, 12)
      .map((img) => img.currentSrc || img.src || img.alt || '(unknown image)');

    const unresolvedLazyImages = Array.from(document.images)
      .filter((img) => {
        if (!visible(img) || img.naturalWidth > 0 || img.currentSrc) return false;
        return Boolean(
          img.getAttribute('data-src') ||
          img.getAttribute('data-lazy-src') ||
          img.getAttribute('data-original') ||
          img.getAttribute('data-srcset')
        );
      })
      .slice(0, 12)
      .map((img) =>
        img.getAttribute('data-src') ||
        img.getAttribute('data-lazy-src') ||
        img.getAttribute('data-original') ||
        img.getAttribute('data-srcset') ||
        img.alt ||
        '(unknown lazy image)'
      );

    const visibleSections = main
      ? Array.from(main.querySelectorAll('section, article')).filter(visible)
      : [];

    const rectData = (el) => {
      const r = el?.getBoundingClientRect();
      return r ? { width: Math.round(r.width), height: Math.round(r.height), left: Math.round(r.left), right: Math.round(r.right), top: Math.round(r.top), bottom: Math.round(r.bottom) } : null;
    };

    const mainText = (main?.innerText || '').replace(/\s+/g, ' ').trim();
    const bodyStyle = getComputedStyle(document.body);

    return {
      viewportWidth: vw,
      documentScrollWidth: Math.max(doc.scrollWidth, body?.scrollWidth || 0),
      horizontalOverflowPx: Math.max(0, Math.round(overflowAmount)),
      overflowCulprits: culprits,
      headerVisible: visible(header),
      headerRect: rectData(header),
      footerVisible: visible(footer),
      footerRect: rectData(footer),
      mainVisible: visible(main),
      mainTextLength: mainText.length,
      visibleH1Count: h1s.length,
      h1Text: (h1?.textContent || '').replace(/\s+/g, ' ').trim(),
      h1Rect: rectData(h1),
      h1Clipped,
      heroVisible: visible(hero),
      heroRect: rectData(hero),
      visibleCtaCount: visibleCtas.length,
      invalidCtas,
      brokenImages,
      unresolvedLazyImages,
      fontsStatus: document.fonts?.status || 'unknown',
      bodyFontFamily: bodyStyle.fontFamily || '',
      bodyFontSize: bodyStyle.fontSize || '',
      visibleSectionCount: visibleSections.length,
      navVisible: visible(nav),
      navToggleVisible,
      videoVisible: visible(video),
      videoRect: rectData(video),
    };
  });
}

async function findVisibleLocator(page, selector) {
  const locator = page.locator(selector);
  const count = await locator.count();
  for (let i = 0; i < count; i += 1) {
    const candidate = locator.nth(i);
    if (await candidate.isVisible().catch(() => false)) return candidate;
  }
  return null;
}

async function testResponsiveMenu(page, viewport, geometry, issues) {
  const compactNavigationExpected = viewport.width <= 480 || (!geometry.navVisible && geometry.navToggleVisible);
  if (!compactNavigationExpected) return;

  const label = viewport.width <= 480 ? 'Mobile' : `Tablet ${viewport.width}px`;
  const toggle = await findVisibleLocator(
    page,
    'button[aria-label*="menu" i], button[data-nvx-menu-toggle], .nvx-menu-toggle, .nav-toggle, button[aria-expanded]'
  );
  if (!toggle) {
    issues.push(`${label}: no visible menu toggle found`);
    return;
  }

  try {
    const beforeExpanded = await toggle.getAttribute('aria-expanded');
    const beforeVisibleMenuItems = await page.locator('header nav a:visible, .nvx-mobile-nav a:visible, .nvx-mobile-menu a:visible, [data-nvx-mobile-menu] a:visible').count();
    await toggle.click({ timeout: 2500 });
    await page.waitForTimeout(220);
    const afterExpanded = await toggle.getAttribute('aria-expanded');
    const afterVisibleMenuItems = await page.locator('header nav a:visible, .nvx-mobile-nav a:visible, .nvx-mobile-menu a:visible, [data-nvx-mobile-menu] a:visible').count();

    const ariaOpened = beforeExpanded !== 'true' && afterExpanded === 'true';
    const linksExposed = afterVisibleMenuItems > beforeVisibleMenuItems && afterVisibleMenuItems > 0;
    if (!ariaOpened && !linksExposed) {
      issues.push(`${label}: menu toggle did not expose navigation`);
    }
    if (afterVisibleMenuItems === 0) {
      issues.push(`${label}: compact navigation opened without visible links`);
    }

    await page.keyboard.press('Escape').catch(() => {});
    await page.waitForTimeout(100);
  } catch (error) {
    issues.push(`${label}: menu toggle interaction failed: ${error.message}`);
  }
}

const browser = await chromium.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const results = [];
const matrix = new Map(routes.map((route) => [route, {}]));
let passCount = 0;
let fixCount = 0;
let blockedCount = 0;

for (const viewport of viewports) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    screen: { width: viewport.width, height: viewport.height },
    deviceScaleFactor: 1,
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 NUVANX-BlockC/1.0',
  });

  for (let index = 0; index < publishedPages.length; index += 1) {
    const pageRecord = publishedPages[index];
    const route = pageRecord.path;
    const expectedHttpStatus = expectedStatusByRoute.get(route) || 200;
    const url = `${baseUrl}${route}`;
    const page = await context.newPage();
    const consoleErrors = [];
    const networkErrors = [];
    const imageHttpErrors = [];
    const productionMediaLeaks = [];
    const issues = [];
    const blockers = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        const text = msg.text();
        if (!/Failed to load resource/i.test(text)) consoleErrors.push(text);
      }
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));
    page.on('requestfailed', (request) => {
      const target = request.url();
      if (target.startsWith(baseUrl)) {
        networkErrors.push(`${target}: ${request.failure()?.errorText || 'request failed'}`);
      }
      if (request.resourceType() === 'image') {
        let hostname = '';
        try {
          hostname = new URL(target).hostname;
        } catch {
          hostname = '';
        }
        if (hostname === expectedHost) {
          imageHttpErrors.push(`${target}: ${request.failure()?.errorText || 'request failed'}`);
        }
      }
    });
    page.on('response', (resourceResponse) => {
      const request = resourceResponse.request();
      if (request.resourceType() !== 'image') return;
      const target = resourceResponse.url();
      let parsed = null;
      try {
        parsed = new URL(target);
      } catch {
        return;
      }
      if (parsed.hostname === expectedHost && resourceResponse.status() >= 400) {
        imageHttpErrors.push(`${target}: HTTP ${resourceResponse.status()}`);
      }
      if (
        (parsed.hostname === 'nuvanx.com' || parsed.hostname === 'www.nuvanx.com') &&
        parsed.pathname.includes('/wp-content/uploads/')
      ) {
        productionMediaLeaks.push(target);
      }
    });

    let response = null;
    let fatal = null;
    let headers = {};
    let geometry = null;
    let finalUrl = url;
    let metaSha = '';
    let screenshot = '';

    try {
      const navResult = await gotoPlain(page, url);
      response = navResult.response;
      headers = response ? await response.allHeaders() : {};
      finalUrl = page.url();

      if (headers['sg-captcha'] || response?.status() === 202) {
        blockers.push('SiteGround Antibot challenge prevented visual validation');
      } else if (!response) {
        blockers.push('Navigation returned no HTTP response');
      } else if (response.status() !== expectedHttpStatus) {
        blockers.push(`Expected final HTTP ${expectedHttpStatus}, got ${response.status()}`);
      }

      if (new URL(finalUrl).hostname !== expectedHost) {
        blockers.push(`Final hostname ${new URL(finalUrl).hostname} != ${expectedHost}`);
      }

      if (blockers.length === 0) {
        await handleCookieConsent(page);
        await waitForVisualStability(page);
        await activateLazyImages(page);

        metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
        if (metaSha !== expectedSha) blockers.push(`Deployment SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`);

        const robots = (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
        const xRobots = headers['x-robots-tag'] || '';
        if (!robots.toLowerCase().includes('noindex') && !xRobots.toLowerCase().includes('noindex')) {
          blockers.push('Staging noindex protection missing');
        }

        geometry = await collectGeometry(page);

        if (!geometry.headerVisible) issues.push('Header is not visibly rendered');
        if (!geometry.footerVisible) issues.push('Footer is not visibly rendered');
        if (!geometry.mainVisible) issues.push('Main content is not visibly rendered');
        if (geometry.visibleH1Count !== 1) issues.push(`Expected 1 visible H1, found ${geometry.visibleH1Count}`);
        if (!geometry.h1Text) issues.push('H1 is empty or unreadable');
        if (geometry.h1Clipped) issues.push('H1 is clipped/truncated by its container');
        if (geometry.h1Rect && (geometry.h1Rect.left < -2 || geometry.h1Rect.right > viewport.width + 2)) issues.push('H1 extends outside viewport');
        if (geometry.horizontalOverflowPx > 2) issues.push(`Horizontal viewport overflow: ${geometry.horizontalOverflowPx}px`);
        if (geometry.headerRect && (geometry.headerRect.left < -2 || geometry.headerRect.right > viewport.width + 2)) issues.push('Header extends outside viewport bounds');
        if (geometry.footerRect && (geometry.footerRect.left < -2 || geometry.footerRect.right > viewport.width + 2)) issues.push('Footer extends outside viewport bounds');
        if (!geometry.heroVisible && !shortContentRoutes.has(route)) issues.push('Hero/intro is not visibly rendered');
        if (geometry.visibleCtaCount === 0 && !shortContentRoutes.has(route)) issues.push('No visible CTA found');
        if (geometry.invalidCtas.length > 0) issues.push(`Invalid visible CTA href (#/empty): ${geometry.invalidCtas.join(' | ')}`);
        if (geometry.brokenImages.length > 0) issues.push(`Broken visible images: ${geometry.brokenImages.join(' | ')}`);
        if (geometry.unresolvedLazyImages.length > 0) issues.push(`Lazy images unresolved after full-page activation: ${geometry.unresolvedLazyImages.join(' | ')}`);
        if (imageHttpErrors.length > 0) issues.push(`Image request errors: ${[...new Set(imageHttpErrors)].slice(0, 8).join(' | ')}`);
        if (productionMediaLeaks.length > 0) issues.push(`Staging media leaked to production host: ${[...new Set(productionMediaLeaks)].slice(0, 8).join(' | ')}`);
        if (geometry.fontsStatus !== 'loaded') issues.push(`Fonts did not reach loaded state (${geometry.fontsStatus})`);
        if (!geometry.bodyFontFamily) issues.push('Body computed font-family is empty');
        if (geometry.mainTextLength < 80 && !shortContentRoutes.has(route)) issues.push(`Main readable text unexpectedly short (${geometry.mainTextLength} chars)`);
        if (geometry.visibleSectionCount < 2 && geometry.mainTextLength < 400 && !shortContentRoutes.has(route)) issues.push(`Later sections may be missing; only ${geometry.visibleSectionCount} visible semantic sections and ${geometry.mainTextLength} chars`);
        if (viewport.width >= 1024 && !geometry.navVisible && !geometry.navToggleVisible) issues.push('Desktop/tablet header navigation or menu toggle is not visible');
        await testResponsiveMenu(page, viewport, geometry, issues);
        if (route === '/') {
          if (!geometry.videoVisible) issues.push('Home hero video is not visible');
          if (geometry.videoRect && (geometry.videoRect.width < 100 || geometry.videoRect.height < 100)) issues.push(`Home hero video renders too small (${geometry.videoRect.width}×${geometry.videoRect.height})`);
        }
        if (consoleErrors.length > 0) issues.push(`${consoleErrors.length} browser console error(s)`);
        if (networkErrors.length > 0) issues.push(`${networkErrors.length} same-origin network error(s)`);
      }

      const shotName = `${String(index + 1).padStart(2, '0')}-${safeName(route)}--${viewport.key}.jpg`;
      screenshot = path.relative(outputDir, path.join(screenshotDir, shotName));
      await page.screenshot({ path: path.join(screenshotDir, shotName), type: 'jpeg', quality: 72, fullPage: true });
    } catch (error) {
      fatal = error instanceof Error ? error.message : String(error);
      blockers.push(`Fatal browser validation error: ${fatal}`);
      try {
        const shotName = `${String(index + 1).padStart(2, '0')}-${safeName(route)}--${viewport.key}--fatal.jpg`;
        screenshot = path.relative(outputDir, path.join(screenshotDir, shotName));
        await page.screenshot({ path: path.join(screenshotDir, shotName), type: 'jpeg', quality: 72, fullPage: true });
      } catch {
        // Ignore capture failure after fatal browser error.
      }
    }

    let status = 'PASS';
    if (blockers.length > 0) {
      status = 'BLOCKED';
      blockedCount += 1;
    } else if (issues.length > 0) {
      status = 'FIX';
      fixCount += 1;
    } else {
      passCount += 1;
    }

    const result = {
      pageId: pageRecord.id,
      title: pageRecord.title,
      route,
      viewport,
      status,
      expectedHttpStatus,
      httpStatus: response?.status() || 0,
      finalUrl,
      metaSha,
      blockers,
      issues,
      geometry,
      consoleErrors: consoleErrors.slice(0, 30),
      networkErrors: networkErrors.slice(0, 30),
      imageHttpErrors: [...new Set(imageHttpErrors)].slice(0, 30),
      productionMediaLeaks: [...new Set(productionMediaLeaks)].slice(0, 30),
      screenshot,
      fatal,
    };
    results.push(result);
    matrix.get(route)[viewport.key] = status;

    console.log(`[${results.length}/156] ${status} ${viewport.label} #${pageRecord.id} ${route} HTTP ${response?.status() || 0}/${expectedHttpStatus}`);
    for (const message of blockers) console.error(`  BLOCKED: ${message}`);
    for (const message of issues) console.error(`  FIX: ${message}`);

    await page.close();
    await new Promise((resolve) => setTimeout(resolve, 120));
  }

  await context.close();
}

await browser.close();

const totalCases = publishedPages.length * viewports.length;
if (totalCases !== 156) {
  console.error(`Expected 156 Block C cases, got ${totalCases}`);
  process.exit(1);
}

const matrixRows = [
  '| # | WP ID | URL | 1440×1100 | 1024×768 | 390×844 |',
  '|---:|---:|---|---:|---:|---:|',
];
publishedPages.forEach((page, index) => {
  const row = matrix.get(page.path);
  matrixRows.push(`| ${index + 1} | ${page.id} | \`${page.path}\` | ${row['desktop-1440x1100']} | ${row['tablet-1024x768']} | ${row['mobile-390x844']} |`);
});

const issueRows = results
  .filter((item) => item.status !== 'PASS')
  .map((item) => {
    const details = [...item.blockers, ...item.issues].join('; ').replaceAll('|', '\\|');
    return `| ${item.pageId} | \`${item.route}\` | ${item.viewport.label} | ${item.status} | ${details} | \`${item.screenshot || ''}\` |`;
  });

const summary = [
  '# NUVANX Staging2 — Block C Visual QA',
  '',
  `Expected staging SHA: \`${expectedSha}\``,
  `Published WordPress pages: ${publishedPages.length}`,
  `Viewports: ${viewports.map((v) => v.label).join(', ')}`,
  `Total cases: ${totalCases}`,
  `PASS: ${passCount}`,
  `FIX: ${fixCount}`,
  `BLOCKED: ${blockedCount}`,
  'Intentional frontend 404: `/casos-de-pacientes/` until `_nvx_cases_publication_ready=1`.',
  '',
  '## Matrix',
  '',
  ...matrixRows,
  '',
  '## Findings',
  '',
  '| WP ID | URL | Viewport | Status | Finding | Screenshot |',
  '|---:|---|---|---|---|---|',
  ...(issueRows.length ? issueRows : ['| — | — | — | PASS | No findings | — |']),
  '',
].join('\n');

await fs.writeFile(path.join(outputDir, 'block-c-results.json'), `${JSON.stringify(results, null, 2)}\n`);
await fs.writeFile(path.join(outputDir, 'block-c-matrix.md'), `${matrixRows.join('\n')}\n`);
await fs.writeFile(path.join(outputDir, 'block-c-summary.md'), `${summary}\n`);

const csvHeader = ['wp_id', 'title', 'route', 'viewport', 'width', 'height', 'status', 'expected_http_status', 'http_status', 'final_url', 'meta_sha', 'horizontal_overflow_px', 'h1', 'issues', 'screenshot'];
const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""').replaceAll('\n', ' ')}"`;
const csv = [csvHeader.map(csvEscape).join(',')];
for (const item of results) {
  csv.push([
    item.pageId,
    item.title,
    item.route,
    item.viewport.label,
    item.viewport.width,
    item.viewport.height,
    item.status,
    item.expectedHttpStatus,
    item.httpStatus,
    item.finalUrl,
    item.metaSha,
    item.geometry?.horizontalOverflowPx ?? '',
    item.geometry?.h1Text ?? '',
    [...item.blockers, ...item.issues].join('; '),
    item.screenshot,
  ].map(csvEscape).join(','));
}
await fs.writeFile(path.join(outputDir, 'block-c-results.csv'), `${csv.join('\n')}\n`);

console.log(`BLOCK_C_TOTAL=${totalCases}`);
console.log(`BLOCK_C_PASS=${passCount}`);
console.log(`BLOCK_C_FIX=${fixCount}`);
console.log(`BLOCK_C_BLOCKED=${blockedCount}`);

if (blockedCount > 0 || fixCount > 0) process.exit(1);
