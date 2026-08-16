export const BROWSER_RECOVERY_CONFIG = Object.freeze({
  maxAttempts: 5,
  navigationTimeoutMs: 40000,
  navigationErrorBackoffBaseMs: 2500,
  transientBackoffBaseMs: 3000,
  visualRetryBackoffBaseMs: 1800,
  consentVisibleTimeoutMs: 350,
  consentClickTimeoutMs: 1500,
  consentPostClickMs: 150,
  fontSettleMs: 400,
  networkIdleTimeoutMs: 3000,
  lazyScrollMinStepPx: 360,
  lazyScrollViewportFactor: 0.8,
  lazyScrollStepDelayMs: 45,
  lazyBottomSettleMs: 120,
  lazyPostSettleMs: 120,
  menuClickTimeoutMs: 2500,
  menuSettleMs: 220,
  screenshotQuality: 72,
});

export async function handleCookieConsent(page, config = BROWSER_RECOVERY_CONFIG) {
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
      if (await button.isVisible({ timeout: config.consentVisibleTimeoutMs })) {
        await button.click({ timeout: config.consentClickTimeoutMs });
        await page.waitForTimeout(config.consentPostClickMs);
        return;
      }
    } catch {
      // Continue to the next consent selector.
    }
  }
}

export async function waitForVisualStability(page, config = BROWSER_RECOVERY_CONFIG) {
  await page.evaluate(async () => {
    if (document.fonts) await document.fonts.ready;
  }).catch(() => {});
  await page.waitForTimeout(config.fontSettleMs);
}

export async function activateLazyImages(page, config = BROWSER_RECOVERY_CONFIG) {
  await page.evaluate(async (cfg) => {
    const root = document.documentElement;
    const body = document.body;
    const rootStyle = root.getAttribute('style');
    const bodyStyle = body?.getAttribute('style') ?? null;
    root.style.setProperty('scroll-behavior', 'auto', 'important');
    if (body) body.style.setProperty('scroll-behavior', 'auto', 'important');

    try {
      const scrollingElement = document.scrollingElement || root;
      const maxY = Math.max(0, scrollingElement.scrollHeight - window.innerHeight);
      const step = Math.max(cfg.lazyScrollMinStepPx, Math.floor(window.innerHeight * cfg.lazyScrollViewportFactor));
      for (let y = 0; y <= maxY; y += step) {
        scrollingElement.scrollTop = y;
        await new Promise((resolve) => setTimeout(resolve, cfg.lazyScrollStepDelayMs));
      }
      scrollingElement.scrollTop = maxY;
      await new Promise((resolve) => setTimeout(resolve, cfg.lazyBottomSettleMs));
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
  }, config);

  await page.waitForLoadState('networkidle', { timeout: config.networkIdleTimeoutMs }).catch(() => {});
  await page.waitForTimeout(config.lazyPostSettleMs);
}

export async function collectHomeGeometry(page) {
  return page.evaluate(() => {
    const visible = (el) => {
      if (!el) return false;
      const style = getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return style.display !== 'none'
        && style.visibility !== 'hidden'
        && Number.parseFloat(style.opacity || '1') > 0.01
        && rect.width > 1
        && rect.height > 1;
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
    const h1Clipped = Boolean(h1 && (
      (h1.scrollWidth > h1.clientWidth + 2 && ['hidden', 'clip'].includes(h1Style.overflowX))
      || (h1.scrollHeight > h1.clientHeight + 2 && ['hidden', 'clip'].includes(h1Style.overflowY))
    ));
    const doc = document.documentElement;
    const body = document.body;
    const viewportWidth = window.innerWidth;
    const overflowAmount = Math.max(doc.scrollWidth, body?.scrollWidth || 0) - viewportWidth;
    const visibleCtas = Array.from(document.querySelectorAll(
      'a.nvx-btn, a.nvx-button, a.nvx-brand-btn, button.nvx-btn, button.nvx-button, button.nvx-brand-btn, .nvx-brand-actions a, .nvx-actions a, a[href*="valoracion"], a[href*="wa.me"], a[href*="whatsapp"]'
    )).filter(visible);
    const invalidCtas = visibleCtas
      .filter((el) => {
        if (el.tagName === 'BUTTON') return false;
        const href = (el.getAttribute('href') || '').trim();
        return !href || href === '#';
      })
      .map((el) => (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120))
      .slice(0, 10);
    const brokenImages = Array.from(document.images)
      .filter((img) => visible(img) && img.complete && img.naturalWidth === 0 && Boolean(img.currentSrc || img.getAttribute('src')))
      .map((img) => img.currentSrc || img.src || img.alt || '(unknown image)')
      .slice(0, 12);
    const unresolvedLazyImages = Array.from(document.images)
      .filter((img) => visible(img) && img.naturalWidth === 0 && !img.currentSrc && Boolean(img.dataset.src || img.dataset.lazySrc || img.dataset.original || img.dataset.srcset))
      .map((img) => img.dataset.src || img.dataset.lazySrc || img.dataset.original || img.dataset.srcset || img.alt || '(unknown lazy image)')
      .slice(0, 12);
    const mainText = (main?.innerText || '').replace(/\s+/g, ' ').trim();
    const runtimeDiagnostics = Array.from(new Set(
      (document.body?.innerText || '').match(/(?:Warning|Deprecated|Fatal error|Notice):[^\n]*/g) || []
    )).slice(0, 12);
    const visibleSections = main ? Array.from(main.querySelectorAll('section, article')).filter(visible) : [];

    return {
      viewportWidth,
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

export async function testResponsiveMenu(page, issues, config = BROWSER_RECOVERY_CONFIG) {
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
    await toggle.click({ timeout: config.menuClickTimeoutMs });
    await page.waitForTimeout(config.menuSettleMs);
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

export async function evaluateHomeVisualContract({
  page,
  geometry,
  viewport,
  consoleErrors,
  networkErrors,
  imageHttpErrors,
  config = BROWSER_RECOVERY_CONFIG,
}) {
  const issues = [];

  if (!geometry.headerVisible) issues.push('Header is not visibly rendered');
  if (!geometry.footerVisible) issues.push('Footer is not visibly rendered');
  if (!geometry.mainVisible) issues.push('Main content is not visibly rendered');
  if (geometry.visibleH1Count !== 1) issues.push(`Expected 1 visible H1, found ${geometry.visibleH1Count}`);
  if (!geometry.h1Text) issues.push('H1 is empty or unreadable');
  if (geometry.h1Clipped) issues.push('H1 is clipped/truncated by its container');
  if (geometry.h1Rect && (geometry.h1Rect.left < -2 || geometry.h1Rect.right > viewport.width + 2)) issues.push('H1 extends outside viewport');
  if (geometry.h1Rect && geometry.h1Rect.top < -2) issues.push(`H1 starts above viewport (${geometry.h1Rect.top}px)`);
  if (geometry.horizontalOverflowPx > 2) issues.push(`Horizontal viewport overflow: ${geometry.horizontalOverflowPx}px`);
  if (geometry.headerRect && (geometry.headerRect.left < -2 || geometry.headerRect.right > viewport.width + 2)) issues.push('Header extends outside viewport bounds');
  if (geometry.footerRect && (geometry.footerRect.left < -2 || geometry.footerRect.right > viewport.width + 2)) issues.push('Footer extends outside viewport bounds');
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

  await testResponsiveMenu(page, issues, config);
  if (consoleErrors.length > 0) issues.push(`${consoleErrors.length} browser console error(s)`);
  if (networkErrors.length > 0) issues.push(`${networkErrors.length} same-origin network error(s)`);

  return issues;
}
