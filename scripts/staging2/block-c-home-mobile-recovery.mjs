import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { EX_TEMPFAIL, isSiteGroundTransientResponse } from './siteground-transient-classifier.mjs';
import { isIgnorableExternalConsoleError } from './console-error-classifier.mjs';

const NOT_APPLICABLE = 64;
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || new URL(baseUrl).hostname;
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const resultsPath = path.resolve('scripts/staging2/block-c-artifacts/block-c-results.json');
const recoveryPath = path.resolve('scripts/staging2/block-c-artifacts/block-c-home-mobile-recovery.json');
const screenshotPath = path.resolve('scripts/staging2/block-c-artifacts/screenshots/home--mobile-390x844--public-recovery.jpg');
const realisticBrowserUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';
const targetViewport = { key: 'mobile-390x844', label: 'Mobile 390×844', width: 390, height: 844 };

function fail(message) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_REAL reason=${message.replace(/\s+/g, '_')}`);
  process.exit(1);
}

if (expectedHost !== 'staging2.nuvanx.com' || !/^[0-9a-f]{40}$/.test(expectedSha)) {
  fail(`invalid_recovery_identity host=${expectedHost} sha=${expectedSha || 'missing'}`);
}

let results;
try {
  results = JSON.parse(await fs.readFile(resultsPath, 'utf8'));
} catch (error) {
  fail(`results_unreadable ${error instanceof Error ? error.message : String(error)}`);
}

if (!Array.isArray(results) || results.length === 0) {
  fail('results_empty_or_invalid');
}

const nonPass = results.filter((result) => result?.status !== 'PASS');
const inconclusive = results.filter((result) => result?.externalInconclusive === true);
if (nonPass.length > 0 || inconclusive.length !== 1) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=NOT_APPLICABLE non_pass=${nonPass.length} inconclusive=${inconclusive.length}`);
  process.exit(NOT_APPLICABLE);
}

const target = inconclusive[0];
const targetIsExact = target?.route === '/'
  && target?.viewport?.key === targetViewport.key
  && target?.visualValidation === 'inconclusive-siteground-antibot'
  && target?.originVerified === true
  && Number(target?.originStatus || 0) === 200
  && String(target?.originDeploySha || '') === expectedSha;

if (!targetIsExact) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=NOT_APPLICABLE route=${target?.route || 'unknown'} viewport=${target?.viewport?.key || 'unknown'}`);
  process.exit(NOT_APPLICABLE);
}

function isVisible(el) {
  if (!el) return false;
  const style = getComputedStyle(el);
  const rect = el.getBoundingClientRect();
  return style.display !== 'none'
    && style.visibility !== 'hidden'
    && Number.parseFloat(style.opacity || '1') > 0.01
    && rect.width > 1
    && rect.height > 1;
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
      // Continue to the next consent selector.
    }
  }
}

async function activateLazyImages(page) {
  await page.evaluate(async () => {
    const root = document.documentElement;
    const body = document.body;
    const rootStyle = root.getAttribute('style');
    const bodyStyle = body?.getAttribute('style') ?? null;
    root.style.setProperty('scroll-behavior', 'auto', 'important');
    if (body) body.style.setProperty('scroll-behavior', 'auto', 'important');
    try {
      const scrollingElement = document.scrollingElement || root;
      const maxY = Math.max(0, scrollingElement.scrollHeight - window.innerHeight);
      const step = Math.max(360, Math.floor(window.innerHeight * 0.8));
      for (let y = 0; y <= maxY; y += step) {
        scrollingElement.scrollTop = y;
        await new Promise((resolve) => setTimeout(resolve, 45));
      }
      scrollingElement.scrollTop = maxY;
      await new Promise((resolve) => setTimeout(resolve, 120));
      scrollingElement.scrollTop = 0;
      root.scrollTop = 0;
      if (body) body.scrollTop = 0;
      window.scrollTo(0, 0);
      await new Promise((resolve) => requestAnimationFrame(() => resolve()));
      await new Promise((resolve) => requestAnimationFrame(() => resolve()));
    } finally {
      if (rootStyle === null) root.removeAttribute('style');
      else root.setAttribute('style', rootStyle);
      if (body) {
        if (bodyStyle === null) body.removeAttribute('style');
        else body.setAttribute('style', bodyStyle);
      }
    }
  });
  await page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
  await page.waitForTimeout(120);
}

async function collectGeometry(page) {
  return page.evaluate(() => {
    const visible = (el) => {
      if (!el) return false;
      const style = getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number.parseFloat(style.opacity || '1') > 0.01 && rect.width > 1 && rect.height > 1;
    };
    const rectData = (el) => {
      const rect = el?.getBoundingClientRect();
      return rect ? {
        width: Math.round(rect.width),
        height: Math.round(rect.height),
        left: Math.round(rect.left),
        right: Math.round(rect.right),
        top: Math.round(rect.top),
        bottom: Math.round(rect.bottom),
      } : null;
    };
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
    const h1Style = h1 ? getComputedStyle(h1) : null;
    const h1Clipped = Boolean(h1 && ((h1.scrollWidth > h1.clientWidth + 2 && ['hidden', 'clip'].includes(h1Style.overflowX)) || (h1.scrollHeight > h1.clientHeight + 2 && ['hidden', 'clip'].includes(h1Style.overflowY))));
    const doc = document.documentElement;
    const body = document.body;
    const vw = window.innerWidth;
    const overflowAmount = Math.max(doc.scrollWidth, body?.scrollWidth || 0) - vw;
    const visibleCtas = Array.from(document.querySelectorAll('a.nvx-btn, a.nvx-button, a.nvx-brand-btn, button.nvx-btn, button.nvx-button, button.nvx-brand-btn, .nvx-brand-actions a, .nvx-actions a, a[href*="valoracion"], a[href*="wa.me"], a[href*="whatsapp"]')).filter(visible);
    const invalidCtas = visibleCtas.filter((el) => {
      if (el.tagName === 'BUTTON') return false;
      const href = (el.getAttribute('href') || '').trim();
      return !href || href === '#';
    }).map((el) => (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120)).slice(0, 10);
    const brokenImages = Array.from(document.images).filter((img) => visible(img) && img.complete && img.naturalWidth === 0 && Boolean(img.currentSrc || img.getAttribute('src'))).map((img) => img.currentSrc || img.src || img.alt || '(unknown image)').slice(0, 12);
    const unresolvedLazyImages = Array.from(document.images).filter((img) => visible(img) && img.naturalWidth === 0 && !img.currentSrc && Boolean(img.dataset.src || img.dataset.lazySrc || img.dataset.original || img.dataset.srcset)).map((img) => img.dataset.src || img.dataset.lazySrc || img.dataset.original || img.dataset.srcset || img.alt || '(unknown lazy image)').slice(0, 12);
    const mainText = (main?.innerText || '').replace(/\s+/g, ' ').trim();
    const runtimeDiagnostics = Array.from(new Set((document.body?.innerText || '').match(/(?:Warning|Deprecated|Fatal error|Notice):[^\n]*/g) || [])).slice(0, 12);
    const visibleSections = main ? Array.from(main.querySelectorAll('section, article')).filter(visible) : [];
    return {
      viewportWidth: vw,
      scrollY: Math.round(window.scrollY || doc.scrollTop || 0),
      horizontalOverflowPx: Math.max(0, Math.round(overflowAmount)),
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
      bodyFontFamily: getComputedStyle(document.body).fontFamily || '',
      runtimeDiagnostics,
      visibleSectionCount: visibleSections.length,
      navVisible: visible(nav),
      navToggleVisible,
      videoVisible: visible(video),
      videoRect: rectData(video),
    };
  });
}

async function testResponsiveMenu(page, issues) {
  const selector = 'button[aria-label*="menu" i], button[data-nvx-menu-toggle], .nvx-menu-toggle, .nav-toggle, button[aria-expanded]';
  const candidates = page.locator(selector);
  let toggle = null;
  for (let index = 0; index < await candidates.count(); index += 1) {
    const candidate = candidates.nth(index);
    if (await candidate.isVisible().catch(() => false)) {
      toggle = candidate;
      break;
    }
  }
  if (!toggle) {
    issues.push('Mobile: no visible menu toggle found');
    return;
  }
  try {
    const beforeExpanded = await toggle.getAttribute('aria-expanded');
    const beforeLinks = await page.locator('header nav a:visible, .nvx-mobile-nav a:visible, .nvx-mobile-menu a:visible, [data-nvx-mobile-menu] a:visible').count();
    await toggle.click({ timeout: 2500 });
    await page.waitForTimeout(220);
    const afterExpanded = await toggle.getAttribute('aria-expanded');
    const afterLinks = await page.locator('header nav a:visible, .nvx-mobile-nav a:visible, .nvx-mobile-menu a:visible, [data-nvx-mobile-menu] a:visible').count();
    const openedByAria = beforeExpanded !== 'true' && afterExpanded === 'true';
    const linksExposed = afterLinks > beforeLinks && afterLinks > 0;
    if (!openedByAria && !linksExposed) issues.push('Mobile: menu toggle did not expose navigation');
    if (afterLinks === 0) issues.push('Mobile: compact navigation opened without visible links');
    await page.keyboard.press('Escape').catch(() => {});
  } catch (error) {
    issues.push(`Mobile: menu toggle interaction failed: ${error instanceof Error ? error.message : String(error)}`);
  }
}

await fs.mkdir(path.dirname(screenshotPath), { recursive: true });
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
const attempts = [];
let recovered = null;

try {
  for (let attempt = 1; attempt <= 5; attempt += 1) {
    const context = await browser.newContext({
      viewport: { width: targetViewport.width, height: targetViewport.height },
      screen: { width: targetViewport.width, height: targetViewport.height },
      deviceScaleFactor: 1,
      ignoreHTTPSErrors: true,
      userAgent: realisticBrowserUa,
      locale: 'es-ES',
      extraHTTPHeaders: {
        'Cache-Control': 'no-cache',
        Pragma: 'no-cache',
        'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
      },
    });
    await context.addCookies([{ name: 'wpSGCacheBypass', value: '1', url: `${baseUrl}/` }]);
    const page = await context.newPage();
    const consoleErrors = [];
    const networkErrors = [];
    const imageHttpErrors = [];
    page.on('console', (msg) => {
      if (msg.type() !== 'error') return;
      const text = msg.text();
      if (!isIgnorableExternalConsoleError(text) && !/Failed to load resource/i.test(text)) consoleErrors.push(text);
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));
    page.on('requestfailed', (request) => {
      const requestUrl = request.url();
      const failureText = request.failure()?.errorText || 'request failed';
      const resourceType = request.resourceType();
      const expectedMediaAbort = resourceType === 'media' && /ERR_ABORTED/i.test(failureText);
      if (requestUrl.startsWith(baseUrl) && !expectedMediaAbort) networkErrors.push(`${requestUrl}: ${failureText}`);
      if (resourceType === 'image' && requestUrl.startsWith(baseUrl)) imageHttpErrors.push(`${requestUrl}: ${failureText}`);
    });
    page.on('response', (response) => {
      if (response.request().resourceType() === 'image' && response.url().startsWith(baseUrl) && response.status() >= 400) {
        imageHttpErrors.push(`${response.url()}: HTTP ${response.status()}`);
      }
    });

    let response = null;
    let finalUrl = `${baseUrl}/`;
    try {
      response = await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 40000 });
      finalUrl = page.url();
    } catch (error) {
      attempts.push({ attempt, outcome: 'navigation-error', error: error instanceof Error ? error.message : String(error) });
      await context.close();
      if (attempt < 5) await new Promise((resolve) => setTimeout(resolve, 2500 * attempt));
      continue;
    }

    const edgeStatus = response?.status() || 0;
    const responseHeaders = response ? response.headers() : {};
    if (!response || isSiteGroundTransientResponse(edgeStatus, responseHeaders, finalUrl)) {
      attempts.push({ attempt, outcome: 'siteground-transient', edgeHttpStatus: edgeStatus, finalUrl });
      await page.screenshot({ path: screenshotPath.replace('.jpg', `--challenge-${attempt}.jpg`), type: 'jpeg', quality: 72, fullPage: true }).catch(() => {});
      await context.close();
      if (attempt < 5) await new Promise((resolve) => setTimeout(resolve, 3000 * attempt));
      continue;
    }

    const blockers = [];
    const issues = [];
    if (edgeStatus !== 200) blockers.push(`Expected public HTTP 200, got ${edgeStatus}`);
    if (new URL(finalUrl).hostname !== expectedHost) blockers.push(`Final hostname ${new URL(finalUrl).hostname} != ${expectedHost}`);

    await handleCookieConsent(page);
    await page.evaluate(async () => { if (document.fonts) await document.fonts.ready; }).catch(() => {});
    await page.waitForTimeout(400);
    await activateLazyImages(page);

    const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
    if (metaSha !== expectedSha) blockers.push(`Deployment SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`);
    const robots = (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
    const xRobots = responseHeaders['x-robots-tag'] || '';
    if (!robots.toLowerCase().includes('noindex') && !xRobots.toLowerCase().includes('noindex')) blockers.push('Staging noindex protection missing');

    const geometry = await collectGeometry(page);
    if (!geometry.headerVisible) issues.push('Header is not visibly rendered');
    if (!geometry.footerVisible) issues.push('Footer is not visibly rendered');
    if (!geometry.mainVisible) issues.push('Main content is not visibly rendered');
    if (geometry.visibleH1Count !== 1) issues.push(`Expected 1 visible H1, found ${geometry.visibleH1Count}`);
    if (!geometry.h1Text) issues.push('H1 is empty or unreadable');
    if (geometry.h1Clipped) issues.push('H1 is clipped/truncated by its container');
    if (geometry.h1Rect && (geometry.h1Rect.left < -2 || geometry.h1Rect.right > targetViewport.width + 2)) issues.push('H1 extends outside viewport');
    if (geometry.h1Rect && geometry.h1Rect.top < -2) issues.push(`H1 starts above viewport (${geometry.h1Rect.top}px)`);
    if (geometry.horizontalOverflowPx > 2) issues.push(`Horizontal viewport overflow: ${geometry.horizontalOverflowPx}px`);
    if (geometry.headerRect && (geometry.headerRect.left < -2 || geometry.headerRect.right > targetViewport.width + 2)) issues.push('Header extends outside viewport bounds');
    if (geometry.footerRect && (geometry.footerRect.left < -2 || geometry.footerRect.right > targetViewport.width + 2)) issues.push('Footer extends outside viewport bounds');
    if (!geometry.heroVisible) issues.push('Hero/intro is not visibly rendered');
    if (geometry.visibleCtaCount === 0) issues.push('No visible CTA found');
    if (geometry.invalidCtas.length > 0) issues.push(`Invalid visible CTA href (#/empty): ${geometry.invalidCtas.join(' | ')}`);
    if (geometry.brokenImages.length > 0) issues.push(`Broken visible images: ${geometry.brokenImages.join(' | ')}`);
    if (geometry.unresolvedLazyImages.length > 0) issues.push(`Lazy images unresolved after full-page activation: ${geometry.unresolvedLazyImages.join(' | ')}`);
    if (imageHttpErrors.length > 0) issues.push(`Image request errors: ${[...new Set(imageHttpErrors)].slice(0, 8).join(' | ')}`);
    if (geometry.fontsStatus !== 'loaded') issues.push(`Fonts did not reach loaded state (${geometry.fontsStatus})`);
    if (!geometry.bodyFontFamily) issues.push('Body computed font-family is empty');
    if (geometry.runtimeDiagnostics.length > 0) issues.push(`Visible PHP/runtime diagnostics: ${geometry.runtimeDiagnostics.join(' | ')}`);
    if (geometry.mainTextLength < 80) issues.push(`Main readable text unexpectedly short (${geometry.mainTextLength} chars)`);
    if (geometry.visibleSectionCount < 2 && geometry.mainTextLength < 400) issues.push(`Later sections may be missing; only ${geometry.visibleSectionCount} visible semantic sections and ${geometry.mainTextLength} chars`);
    if (!geometry.videoVisible) issues.push('Home hero video is not visible');
    if (geometry.videoRect && (geometry.videoRect.width < 100 || geometry.videoRect.height < 100)) issues.push(`Home hero video renders too small (${geometry.videoRect.width}×${geometry.videoRect.height})`);
    await testResponsiveMenu(page, issues);
    if (consoleErrors.length > 0) issues.push(`${consoleErrors.length} browser console error(s)`);
    if (networkErrors.length > 0) issues.push(`${networkErrors.length} same-origin network error(s)`);

    await page.screenshot({ path: screenshotPath, type: 'jpeg', quality: 72, fullPage: true });
    attempts.push({ attempt, outcome: blockers.length || issues.length ? 'visual-failure' : 'pass', edgeHttpStatus: edgeStatus, finalUrl, metaSha, blockers, issues });

    if (blockers.length > 0 || issues.length > 0) {
      await fs.writeFile(recoveryPath, `${JSON.stringify({ schema: 1, expectedSha, target: { route: '/', viewport: targetViewport }, attempts, blockers, issues, geometry, screenshot: path.relative(path.dirname(resultsPath), screenshotPath) }, null, 2)}\n`);
      await context.close();
      fail([...blockers, ...issues].join('; '));
    }

    recovered = { attempt, edgeHttpStatus: edgeStatus, finalUrl, metaSha, geometry, screenshot: path.relative(path.dirname(resultsPath), screenshotPath) };
    await context.close();
    break;
  }
} finally {
  await browser.close();
}

if (!recovered) {
  await fs.writeFile(recoveryPath, `${JSON.stringify({ schema: 1, expectedSha, target: { route: '/', viewport: targetViewport }, status: 'transient-exhausted', attempts }, null, 2)}\n`);
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT_EXHAUSTED attempts=${attempts.length}`);
  process.exit(EX_TEMPFAIL);
}

const recoveredResults = results.map((result) => {
  if (result !== target) return result;
  return {
    ...result,
    httpStatus: 200,
    edgeHttpStatus: 200,
    finalUrl: recovered.finalUrl,
    metaSha: recovered.metaSha,
    externalInconclusive: false,
    visualValidation: 'complete-public-browser-recovery',
    validationTransport: 'public-browser-recovery-after-siteground-antibot',
    recoveredFromExternalInconclusive: true,
    recoveryAttempt: recovered.attempt,
    recoveryScreenshot: recovered.screenshot,
    geometry: recovered.geometry,
    notes: [...(Array.isArray(result.notes) ? result.notes : []), `Public browser recovery completed the missing ${targetViewport.label} visual contract after SiteGround Antibot.`],
  };
});

await fs.writeFile(resultsPath, `${JSON.stringify(recoveredResults, null, 2)}\n`);
await fs.writeFile(recoveryPath, `${JSON.stringify({ schema: 1, expectedSha, status: 'pass', target: { route: '/', viewport: targetViewport }, attempts, recovered }, null, 2)}\n`);
console.log(`BLOCK_C_HOME_MOBILE_RECOVERY=PASS attempt=${recovered.attempt} sha=${expectedSha} visual_contract=complete edge_http=200`);
process.exit(0);
