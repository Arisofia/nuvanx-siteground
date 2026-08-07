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

// Canonical Block C viewports. Do not replace with legacy QA sizes.
const viewports = [
  { key: 'desktop-1440x1100', label: 'Desktop 1440×1100', width: 1440, height: 1100 },
  { key: 'tablet-1024x768', label: 'Tablet 1024×768', width: 1024, height: 768 },
  { key: 'mobile-390x844', label: 'Mobile 390×844', width: 390, height: 844 },
];

const routesPath = new URL(
  '../../wp-content/themes/nuvanx-medical/inc/data/routes.json',
  import.meta.url
);
const routesConfig = JSON.parse(await fs.readFile(routesPath, 'utf8'));
const routes = Object.keys(routesConfig);

if (routes.length !== 52) {
  console.error(`Block C requires exactly 52 routes; routes.json contains ${routes.length}.`);
  process.exit(1);
}

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
]);

const routeAliases = new Set(
  Object.entries(routesConfig)
    .filter(([, config]) => Boolean(config?.route_alias))
    .map(([route]) => route)
);

function safeName(route) {
  if (route === '/') return 'home';
  return route.replace(/^\/+|\/+$/g, '').replace(/[^a-zA-Z0-9_-]+/g, '_') || 'route';
}

async function gotoPlain(page, url) {
  let lastError = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    try {
      const response = await page.goto(url, {
        waitUntil: 'domcontentloaded',
        timeout: 40000,
      });
      if (!response) return { response: null, attempt };
      const headers = await response.allHeaders();
      const captcha = headers['sg-captcha'] || '';
      if (response.status() === 202 || captcha) {
        if (attempt < 4) {
          await page.waitForTimeout(3000 * attempt);
          continue;
        }
      }
      return { response, attempt };
    } catch (error) {
      lastError = error;
      if (attempt === 4) throw error;
      await page.waitForTimeout(2500 * attempt);
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
      if (await button.isVisible({ timeout: 400 })) {
        await button.click({ timeout: 1500 });
        await page.waitForTimeout(200);
        return;
      }
    } catch {
      // Continue to next selector.
    }
  }
}

async function waitForVisualStability(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
  }).catch(() => {});
  await page.waitForTimeout(450);
}

async function collectGeometry(page) {
  return page.evaluate(() => {
    const vw = window.innerWidth;
    const doc = document.documentElement;
    const body = document.body;
    const overflowAmount = Math.max(doc.scrollWidth, body?.scrollWidth || 0) - vw;

    const visible = (el) => {
      if (!el) return false;
      const style = getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return (
        style.display !== 'none' &&
        style.visibility !== 'hidden' &&
        Number.parseFloat(style.opacity || '1') > 0.01 &&
        rect.width > 1 &&
        rect.height > 1
      );
    };

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
          if (culprits.length >= 10) break;
        }
      }
    }

    const header = document.querySelector('header, .nvx-site-header, .nvx-header');
    const footer = document.querySelector('footer, .nvx-site-footer, .nvx-footer');
    const main = document.querySelector('main#nvx-main, main, [role="main"]');
    const h1s = Array.from(document.querySelectorAll('h1')).filter(visible);
    const h1 = h1s[0] || null;
    const h1Rect = h1?.getBoundingClientRect() || null;
    const h1Style = h1 ? getComputedStyle(h1) : null;
    const h1Clipped = Boolean(
      h1 &&
        ((h1.scrollWidth > h1.clientWidth + 2 && ['hidden', 'clip'].includes(h1Style.overflowX)) ||
          (h1.scrollHeight > h1.clientHeight + 2 && ['hidden', 'clip'].includes(h1Style.overflowY)))
    );

    const hero = document.querySelector(
      '.nvx-home-hero, .nvx-brand-hero, .nvx-blog-hero, .nvx-page-header, .nvx-strategy-intro, [class*="hero"]'
    );
    const visibleCtas = Array.from(
      document.querySelectorAll(
        'a.nvx-btn, a.nvx-button, a.nvx-brand-btn, button.nvx-btn, button.nvx-button, button.nvx-brand-btn, .nvx-brand-actions a, .nvx-actions a, a[href*="valoracion"], a[href*="wa.me"], a[href*="whatsapp"]'
      )
    ).filter(visible);

    const invalidCtas = visibleCtas
      .filter((el) => {
        if (el.tagName === 'BUTTON') return false;
        const href = (el.getAttribute('href') || '').trim();
        return !href || href === '#';
      })
      .slice(0, 10)
      .map((el) => (el.textContent || '').trim().slice(0, 120));

    const brokenImages = Array.from(document.images)
      .filter((img) => visible(img) && img.complete && img.naturalWidth === 0)
      .slice(0, 10)
      .map((img) => img.currentSrc || img.src || img.alt || '(unknown image)');

    const visibleSections = main
      ? Array.from(main.querySelectorAll(':scope > section, :scope > article, :scope > div')).filter(visible)
      : [];

    const headerRect = header?.getBoundingClientRect() || null;
    const footerRect = footer?.getBoundingClientRect() || null;
    const heroRect = hero?.getBoundingClientRect() || null;
    const mainText = (main?.innerText || '').replace(/\s+/g, ' ').trim();

    const nav = document.querySelector('header nav, .nvx-site-header nav, .nvx-header nav, .nvx-primary-nav');
    const mobileToggle = Array.from(
      document.querySelectorAll(
        'button[aria-label*="menu" i], button[data-nvx-menu-toggle], .nvx-menu-toggle, .nav-toggle, button[aria-expanded]'
      )
    ).find(visible);

    const video = document.querySelector('.nvx-home-hero video, video');
    const videoRect = video?.getBoundingClientRect() || null;

    return {
      viewportWidth: vw,
      documentScrollWidth: Math.max(doc.scrollWidth, body?.scrollWidth || 0),
      horizontalOverflowPx: Math.max(0, Math.round(overflowAmount)),
      overflowCulprits: culprits,
      headerVisible: visible(header),
      headerRect: headerRect
        ? { width: Math.round(headerRect.width), height: Math.round(headerRect.height), left: Math.round(headerRect.left), right: Math.round(headerRect.right) }
        : null,
      footerVisible: visible(footer),
      footerRect: footerRect
        ? { width: Math.round(footerRect.width), height: Math.round(footerRect.height), left: Math.round(footerRect.left), right: Math.round(footerRect.right) }
        : null,
      mainVisible: visible(main),
      mainTextLength: mainText.length,
      visibleH1Count: h1s.length,
      h1Text: (h1?.textContent || '').replace(/\s+/g, ' ').trim(),
      h1Rect: h1Rect
        ? { width: Math.round(h1Rect.width), height: Math.round(h1Rect.height), left: Math.round(h1Rect.left), right: Math.round(h1Rect.right) }
        : null,
      h1Clipped,
      heroVisible: visible(hero),
      heroRect: heroRect
        ? { width: Math.round(heroRect.width), height: Math.round(heroRect.height), left: Math.round(heroRect.left), right: Math.round(heroRect.right) }
        : null,
      visibleCtaCount: visibleCtas.length,
      invalidCtas,
      brokenImages,
      fontsStatus: document.fonts?.status || 'unknown',
      bodyFontFamily: getComputedStyle(document.body).fontFamily || '',
      visibleSectionCount: visibleSections.length,
      navVisible: visible(nav),
      mobileToggleVisible: visible(mobileToggle),
      videoVisible: visible(video),
      videoRect: videoRect
        ? { width: Math.round(videoRect.width), height: Math.round(videoRect.height) }
        : null,
    };
  });
}

async function testMobileMenu(page, viewport, issues) {
  if (viewport.width > 480) return;
  const toggle = page
    .locator(
      'button[aria-label*="menu" i], button[data-nvx-menu-toggle], .nvx-menu-toggle, .nav-toggle, button[aria-expanded]'
    )
    .filter({ visible: true })
    .first();
  if ((await toggle.count()) === 0 || !(await toggle.isVisible().catch(() => false))) {
    issues.push('Mobile: no visible menu toggle found');
    return;
  }
  try {
    const before = await toggle.getAttribute('aria-expanded');
    await toggle.click({ timeout: 2500 });
    await page.waitForTimeout(200);
    const after = await toggle.getAttribute('aria-expanded');
    const visibleMenuItems = await page
      .locator('header nav a:visible, .nvx-mobile-menu a:visible, [data-nvx-mobile-menu] a:visible')
      .count();
    if (before === after && visibleMenuItems === 0) {
      issues.push('Mobile: menu toggle did not expose navigation');
    }
    await page.keyboard.press('Escape').catch(() => {});
  } catch (error) {
    issues.push(`Mobile: menu toggle interaction failed: ${error.message}`);
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
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 NUVANX-BlockC/1.0',
  });

  for (let index = 0; index < routes.length; index += 1) {
    const route = routes[index];
    const url = `${baseUrl}${route}`;
    const page = await context.newPage();
    const consoleErrors = [];
    const networkErrors = [];
    const issues = [];
    const blockers = [];

    page.on('console', (msg) => {
      if (msg.type() === 'error') consoleErrors.push(msg.text());
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));
    page.on('requestfailed', (request) => {
      const target = request.url();
      // Staging intentionally blocks productive third-party integrations. Only
      // same-origin failures are visual-QA blocking here.
      if (target.startsWith(baseUrl)) {
        networkErrors.push(`${target}: ${request.failure()?.errorText || 'request failed'}`);
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
      const nav = await gotoPlain(page, url);
      response = nav.response;
      headers = response ? await response.allHeaders() : {};
      finalUrl = page.url();

      if (headers['sg-captcha'] || response?.status() === 202) {
        blockers.push('SiteGround Antibot challenge prevented visual validation');
      } else if (!response) {
        blockers.push('Navigation returned no HTTP response');
      } else if (response.status() !== 200) {
        blockers.push(`Expected final HTTP 200, got ${response.status()}`);
      }

      const finalHost = new URL(finalUrl).hostname;
      if (finalHost !== expectedHost) {
        blockers.push(`Final hostname ${finalHost} != ${expectedHost}`);
      }

      if (blockers.length === 0) {
        await handleCookieConsent(page);
        await waitForVisualStability(page);

        metaSha =
          (await page
            .locator('meta[name="nvx-deploy-sha"]')
            .getAttribute('content')
            .catch(() => '')) || '';
        if (metaSha !== expectedSha) {
          blockers.push(`Deployment SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`);
        }

        const robots =
          (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
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
        if (geometry.h1Rect && (geometry.h1Rect.left < -2 || geometry.h1Rect.right > viewport.width + 2)) {
          issues.push('H1 extends outside the viewport');
        }
        if (geometry.horizontalOverflowPx > 2) {
          issues.push(`Horizontal viewport overflow: ${geometry.horizontalOverflowPx}px`);
        }
        if (geometry.headerRect && (geometry.headerRect.left < -2 || geometry.headerRect.right > viewport.width + 2)) {
          issues.push('Header extends outside viewport bounds');
        }
        if (geometry.footerRect && (geometry.footerRect.left < -2 || geometry.footerRect.right > viewport.width + 2)) {
          issues.push('Footer extends outside viewport bounds');
        }
        if (!geometry.heroVisible && !shortContentRoutes.has(route)) issues.push('Hero/intro is not visibly rendered');
        if (geometry.visibleCtaCount === 0 && !shortContentRoutes.has(route)) issues.push('No visible CTA found');
        if (geometry.invalidCtas.length > 0) issues.push(`Invalid visible CTA href (#/empty): ${geometry.invalidCtas.join(' | ')}`);
        if (geometry.brokenImages.length > 0) issues.push(`Broken visible images: ${geometry.brokenImages.join(' | ')}`);
        if (geometry.fontsStatus !== 'loaded') issues.push(`Fonts did not reach loaded state (${geometry.fontsStatus})`);
        if (!geometry.bodyFontFamily) issues.push('Body computed font-family is empty');
        if (geometry.mainTextLength < 80 && !shortContentRoutes.has(route)) issues.push(`Main readable text unexpectedly short (${geometry.mainTextLength} chars)`);
        if (geometry.visibleSectionCount < 2 && !shortContentRoutes.has(route)) issues.push(`Later sections may be missing; only ${geometry.visibleSectionCount} visible top-level main sections/wrappers`);

        if (viewport.width >= 1024 && !geometry.navVisible) {
          issues.push('Desktop/tablet header navigation is not visible');
        }
        if (viewport.width <= 480 && !routeAliases.has(route)) {
          await testMobileMenu(page, viewport, issues);
        }

        if (route === '/') {
          if (!geometry.videoVisible) issues.push('Home hero video is not visible');
          if (geometry.videoRect && (geometry.videoRect.width < 100 || geometry.videoRect.height < 100)) {
            issues.push(`Home hero video renders too small (${geometry.videoRect.width}×${geometry.videoRect.height})`);
          }
        }

        if (consoleErrors.length > 0) {
          issues.push(`${consoleErrors.length} browser console error(s)`);
        }
        if (networkErrors.length > 0) {
          issues.push(`${networkErrors.length} same-origin network error(s)`);
        }
      }

      const shotName = `${String(index + 1).padStart(2, '0')}-${safeName(route)}--${viewport.key}.jpg`;
      screenshot = path.relative(outputDir, path.join(screenshotDir, shotName));
      await page.screenshot({
        path: path.join(screenshotDir, shotName),
        type: 'jpeg',
        quality: 72,
        fullPage: true,
      });
    } catch (error) {
      fatal = error instanceof Error ? error.message : String(error);
      blockers.push(`Fatal browser validation error: ${fatal}`);
      try {
        const shotName = `${String(index + 1).padStart(2, '0')}-${safeName(route)}--${viewport.key}--fatal.jpg`;
        screenshot = path.relative(outputDir, path.join(screenshotDir, shotName));
        await page.screenshot({
          path: path.join(screenshotDir, shotName),
          type: 'jpeg',
          quality: 72,
          fullPage: true,
        });
      } catch {
        // No screenshot possible after fatal navigation/browser failure.
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

    const item = {
      route,
      viewport,
      status,
      httpStatus: response?.status() || 0,
      finalUrl,
      metaSha,
      blockers,
      issues,
      geometry,
      consoleErrors: consoleErrors.slice(0, 30),
      networkErrors: networkErrors.slice(0, 30),
      screenshot,
      fatal,
    };
    results.push(item);
    matrix.get(route)[viewport.key] = status;

    console.log(
      `[${results.length}/${routes.length * viewports.length}] ${status} ${viewport.label} ${route}`
    );
    for (const message of blockers) console.error(`  BLOCKED: ${message}`);
    for (const message of issues) console.error(`  FIX: ${message}`);

    await page.close();
    await new Promise((resolve) => setTimeout(resolve, 120));
  }

  await context.close();
}

await browser.close();

const totalCases = routes.length * viewports.length;
const matrixRows = [
  '| # | URL | 1440×1100 | 1024×768 | 390×844 |',
  '|---:|---|---:|---:|---:|',
];
routes.forEach((route, index) => {
  const row = matrix.get(route);
  matrixRows.push(
    `| ${index + 1} | \`${route}\` | ${row['desktop-1440x1100']} | ${row['tablet-1024x768']} | ${row['mobile-390x844']} |`
  );
});

const issueRows = results
  .filter((item) => item.status !== 'PASS')
  .map((item) => {
    const details = [...item.blockers, ...item.issues].join('; ').replaceAll('|', '\\|');
    return `| \`${item.route}\` | ${item.viewport.label} | ${item.status} | ${details} | \`${item.screenshot || ''}\` |`;
  });

const summary = [
  '# NUVANX Staging2 — Block C Visual QA',
  '',
  `Expected staging SHA: \`${expectedSha}\``,
  `Routes: ${routes.length}`,
  `Viewports: ${viewports.map((v) => v.label).join(', ')}`,
  `Total cases: ${totalCases}`,
  `PASS: ${passCount}`,
  `FIX: ${fixCount}`,
  `BLOCKED: ${blockedCount}`,
  '',
  '## Matrix',
  '',
  ...matrixRows,
  '',
  '## Findings',
  '',
  '| URL | Viewport | Status | Finding | Screenshot |',
  '|---|---|---|---|---|',
  ...(issueRows.length ? issueRows : ['| — | — | PASS | No findings | — |']),
  '',
].join('\n');

await fs.writeFile(path.join(outputDir, 'block-c-results.json'), `${JSON.stringify(results, null, 2)}\n`);
await fs.writeFile(path.join(outputDir, 'block-c-matrix.md'), `${matrixRows.join('\n')}\n`);
await fs.writeFile(path.join(outputDir, 'block-c-summary.md'), `${summary}\n`);

const csvHeader = ['route', 'viewport', 'width', 'height', 'status', 'http_status', 'final_url', 'meta_sha', 'horizontal_overflow_px', 'h1', 'issues', 'screenshot'];
const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""').replaceAll('\n', ' ')}"`;
const csv = [csvHeader.map(csvEscape).join(',')];
for (const item of results) {
  csv.push(
    [
      item.route,
      item.viewport.label,
      item.viewport.width,
      item.viewport.height,
      item.status,
      item.httpStatus,
      item.finalUrl,
      item.metaSha,
      item.geometry?.horizontalOverflowPx ?? '',
      item.geometry?.h1Text ?? '',
      [...item.blockers, ...item.issues].join('; '),
      item.screenshot,
    ].map(csvEscape).join(',')
  );
}
await fs.writeFile(path.join(outputDir, 'block-c-results.csv'), `${csv.join('\n')}\n`);

console.log(`BLOCK_C_TOTAL=${totalCases}`);
console.log(`BLOCK_C_PASS=${passCount}`);
console.log(`BLOCK_C_FIX=${fixCount}`);
console.log(`BLOCK_C_BLOCKED=${blockedCount}`);

if (blockedCount > 0 || fixCount > 0) {
  process.exit(1);
}
