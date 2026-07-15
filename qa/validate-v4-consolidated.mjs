import { chromium } from '@playwright/test';
import { spawnSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const USER = process.env.STAGING_BASIC_USER || '';
const PASS = process.env.STAGING_BASIC_PASSWORD || '';
const URL = `${BASE}/?nvxqa=editorial`;
const RESULTS_DIR = path.join(__dirname, 'results');
const RESULT_FILE = path.join(RESULTS_DIR, 'v4-validation.json');

if (!USER || !PASS) {
  throw new Error('STAGING_BASIC_USER and STAGING_BASIC_PASSWORD are required.');
}

const VIEWPORTS = [
  { width: 1440, height: 900, tag: '1440' },
  { width: 1024, height: 768, tag: '1024' },
  { width: 390, height: 844, tag: '390' },
];

const controls = [];

function record(id, name, pass, measured, expected = null) {
  controls.push({ id, name, status: pass ? 'PASS' : 'FAIL', measured, expected });
  return pass;
}

function parseRgb(value) {
  const match = /rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(value || '');
  return match ? [Number(match[1]), Number(match[2]), Number(match[3])] : null;
}

function luminance([r, g, b]) {
  const channel = (value) => {
    const normalized = value / 255;
    return normalized <= 0.03928
      ? normalized / 12.92
      : ((normalized + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

function contrastRatio(foreground, background) {
  const a = luminance(foreground);
  const b = luminance(background);
  return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}

function runCopyLock() {
  const canonical = path.join(ROOT, 'qa/fixtures/ticket-43/home-production-post-content.html');
  const candidate = path.join(ROOT, 'deploy/ticket-43/post_content_v3-production-copy.html');
  const result = spawnSync(
    'php',
    [path.join(ROOT, 'scripts/ticket-43/verify-copy-integrity.php'), canonical, candidate],
    { encoding: 'utf8' }
  );

  let manifest = null;
  const stdout = result.stdout || '';
  try {
    const start = stdout.indexOf('{');
    const end = stdout.lastIndexOf('}');
    if (start >= 0 && end >= start) manifest = JSON.parse(stdout.slice(start, end + 1));
  } catch (_) {}

  record(
    'copy_lock',
    'Production copy lock',
    result.status === 0,
    manifest || { stdout: stdout.trim(), stderr: (result.stderr || '').trim() },
    'COPY INTEGRITY OK'
  );
}

async function acceptCookies(page) {
  for (const selector of [
    '.cmplz-btn.cmplz-accept',
    '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
    'button:has-text("Aceptar")',
    'button:has-text("Accept")',
  ]) {
    const button = page.locator(selector).first();
    if (await button.isVisible().catch(() => false)) {
      await button.click({ timeout: 3000 }).catch(() => {});
      break;
    }
  }
  await page.evaluate(() => {
    document
      .querySelectorAll(
        '.cmplz-cookiebanner, #cmplz-cookiebanner-container, #cookie-law-info-bar, #CybotCookiebotDialog'
      )
      .forEach((element) => element.remove());
  });
}

async function loadAssets(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
    const images = Array.from(document.querySelectorAll('#nvx-home-main img'));
    for (const image of images) {
      image.scrollIntoView({ block: 'center' });
      await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    }
    await Promise.all(
      images.map(
        (image) =>
          new Promise((resolve) => {
            if (image.complete) return resolve();
            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', resolve, { once: true });
            setTimeout(resolve, 12000);
          })
      )
    );
  });

  await page.evaluate(async () => {
    const video = document.querySelector('#nvx-home-hero-video');
    if (!video) return;
    video.muted = true;
    if (video.readyState < 2) {
      await new Promise((resolve) => {
        video.addEventListener('loadeddata', resolve, { once: true });
        video.addEventListener('error', resolve, { once: true });
        setTimeout(resolve, 12000);
        video.load();
      });
    }
    video.pause();
  });

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForFunction(() => window.scrollY === 0);
}

async function inspectViewport(browser, viewport) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    deviceScaleFactor: 1,
    httpCredentials: { username: USER, password: PASS },
  });
  const page = await context.newPage();
  const responses = [];
  const consoleErrors = [];

  page.on('response', (response) => {
    if (response.status() >= 400) responses.push({ status: response.status(), url: response.url() });
  });
  page.on('console', (message) => {
    if (message.type() === 'error') consoleErrors.push(message.text());
  });
  page.on('pageerror', (error) => consoleErrors.push(error.message));

  const response = await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.waitForSelector('#nvx-home-main.nvx-editorial-home', { timeout: 30000 });
  await acceptCookies(page);
  await loadAssets(page);
  await page.waitForTimeout(500);

  const metrics = await page.evaluate(() => {
    const rect = (element) => (element ? element.getBoundingClientRect() : null);
    const visible = (element) => {
      if (!element) return false;
      const style = getComputedStyle(element);
      const box = rect(element);
      return style.display !== 'none' && style.visibility !== 'hidden' && box.width > 0 && box.height > 0;
    };
    const intersects = (a, b) => {
      if (!a || !b) return false;
      return !(a.bottom <= b.top || a.top >= b.bottom || a.right <= b.left || a.left >= b.right);
    };

    const video = document.querySelector('#nvx-home-hero-video');
    const hero = document.querySelector('.nvx-home-hero-stage');
    const wave = document.querySelector('.nvx-home-organic-wave');
    const primary = document.querySelector('.nvx-home-hero-ctas .nvx-button--primary');
    const secondary = document.querySelector('.nvx-home-hero-ctas .nvx-button--secondary');
    const introLead = document.querySelector('.nvx-home-intro .nvx-home-editorial__lead');
    const introImage = document.querySelector('.nvx-home-intro img');
    const indexNumber = document.querySelector('.nvx-index-item__number');
    const indexBody = document.querySelector('.nvx-index-item__body');
    const h1 = document.querySelector('#nvx-home-h1');
    const headerCta = document.querySelector('#nvx-header-cta');
    const logo = document.querySelector('#nvx-header .nvx-logo');
    const logoImage = document.querySelector('#nvx-header .nvx-logo__img');
    const logoWordmark = document.querySelector('#nvx-header .nvx-logo__wordmark');
    const logoTagline = document.querySelector('#nvx-header .nvx-logo__tagline');

    const videoRect = rect(video);
    const heroRect = rect(hero);
    const waveRect = rect(wave);
    const primaryRect = rect(primary);
    const secondaryRect = rect(secondary);
    const videoStyle = video ? getComputedStyle(video) : null;
    const h1Style = h1 ? getComputedStyle(h1) : null;
    const numberStyle = indexNumber ? getComputedStyle(indexNumber) : null;
    const bodyStyle = indexBody ? getComputedStyle(indexBody) : null;
    const headerCtaStyle = headerCta ? getComputedStyle(headerCta) : null;

    const failedImages = Array.from(document.querySelectorAll('#nvx-home-main img'))
      .filter((image) => !image.complete || image.naturalWidth === 0)
      .map((image) => image.currentSrc || image.src);

    const emptyBlocks = Array.from(document.querySelectorAll('#nvx-home-main section'))
      .map((section) => ({
        className: section.className,
        height: rect(section).height,
        text: (section.textContent || '').replace(/\s+/g, '').length,
        hasMedia: Boolean(section.querySelector('img, video, svg, iframe')),
      }))
      .filter((section) => section.height > 120 && section.text === 0 && !section.hasMedia);

    return {
      scrollY: window.scrollY,
      overflow: document.documentElement.scrollWidth - window.innerWidth,
      mainCount: document.querySelectorAll('main').length,
      homeMainCount: document.querySelectorAll('#nvx-home-main').length,
      semanticWrapper: Boolean(document.querySelector('#nvx-home-main.nvx-editorial-home')),
      versionedWrapperCount: document.querySelectorAll(
        '#nvx-home-main.nvx-editorial-home-v4, #nvx-home-main [class*="nvx-v3-"]'
      ).length,
      h1Count: document.querySelectorAll('#nvx-home-main h1').length,
      h2Count: document.querySelectorAll('#nvx-home-main h2').length,
      treatmentCount: document.querySelectorAll('.nvx-home-tratamientos-editorial > .nvx-index-item').length,
      faqCount: document.querySelectorAll('.nvx-home-faq details').length,
      failedImages,
      emptyBlocks,
      introImageLoaded: Boolean(introImage?.complete && introImage?.naturalWidth > 0),
      introImageNatural: introImage
        ? { width: introImage.naturalWidth, height: introImage.naturalHeight, src: introImage.currentSrc || introImage.src }
        : null,
      video: {
        exists: Boolean(video),
        objectFit: videoStyle?.objectFit || null,
        clipPath: videoStyle?.clipPath || null,
        maskImage: videoStyle?.maskImage || null,
        widthPercent: heroRect && videoRect ? (videoRect.width / heroRect.width) * 100 : null,
        rightDelta: heroRect && videoRect ? Math.abs(videoRect.right - heroRect.right) : null,
        renderedRatio: videoRect?.height ? videoRect.width / videoRect.height : null,
        nativeRatio: video?.videoHeight ? video.videoWidth / video.videoHeight : null,
      },
      introLeadSize: introLead ? parseFloat(getComputedStyle(introLead).fontSize) : null,
      h1Font: h1Style?.fontFamily || null,
      numberFont: numberStyle?.fontFamily || null,
      numberSize: numberStyle ? parseFloat(numberStyle.fontSize) : null,
      numberVariant: numberStyle?.fontVariantNumeric || null,
      bodyFont: bodyStyle?.fontFamily || null,
      primaryIntersectsWave: intersects(primaryRect, waveRect),
      secondaryIntersectsWave: intersects(secondaryRect, waveRect),
      headerCta: headerCtaStyle
        ? {
            background: headerCtaStyle.backgroundColor,
            color: headerCtaStyle.color,
            boxShadow: headerCtaStyle.boxShadow,
            visible: visible(headerCta),
          }
        : null,
      logoVisible: visible(logo),
      fullLogoVisible:
        visible(logoImage) || (visible(logoWordmark) && visible(logoTagline)),
      darkCtaCount: Array.from(document.querySelectorAll('.nvx-home-cta-final-band')).filter(visible).length,
    };
  });

  await context.close();
  return {
    tag: viewport.tag,
    status: response?.status() ?? null,
    metrics,
    responses: responses.filter(
      ({ url }) => !/google|facebook|doubleclick|googletagmanager|connect\.facebook/i.test(url)
    ),
    consoleErrors,
  };
}

fs.mkdirSync(RESULTS_DIR, { recursive: true });
runCopyLock();

const browser = await chromium.launch({
  headless: true,
  args: ['--disable-blink-features=AutomationControlled', '--autoplay-policy=no-user-gesture-required'],
});

let results;
try {
  results = [];
  for (const viewport of VIEWPORTS) results.push(await inspectViewport(browser, viewport));
} finally {
  await browser.close();
}

for (const result of results) {
  const { tag, status, metrics, responses, consoleErrors } = result;
  record(`page_${tag}`, `Authenticated page load @${tag}`, status === 200, status, 200);
  record(`scroll_${tag}`, `scrollY = 0 @${tag}`, metrics.scrollY === 0, metrics.scrollY, 0);
  record(`overflow_${tag}`, `No horizontal overflow @${tag}`, metrics.overflow === 0, metrics.overflow, 0);
  record(`images_${tag}`, `All home images loaded @${tag}`, metrics.failedImages.length === 0, metrics.failedImages, []);
  record(`network_${tag}`, `No relevant 4xx/5xx @${tag}`, responses.length === 0, responses, []);
  record(`console_${tag}`, `No console/page errors @${tag}`, consoleErrors.length === 0, consoleErrors, []);
  record(`intro_image_${tag}`, `Intro image loaded @${tag}`, metrics.introImageLoaded, metrics.introImageNatural, 'naturalWidth > 0');
}

const desktop = results[0].metrics;
const tablet = results[1].metrics;
const mobile = results[2].metrics;

record('single_main', 'Exactly one main landmark', desktop.mainCount === 1, desktop.mainCount, 1);
record('single_home_main', 'Exactly one #nvx-home-main', desktop.homeMainCount === 1, desktop.homeMainCount, 1);
record('semantic_wrapper', 'Semantic editorial wrapper active', desktop.semanticWrapper, desktop.semanticWrapper, true);
record('no_versioned_runtime', 'No V3/V4 runtime class markers', desktop.versionedWrapperCount === 0, desktop.versionedWrapperCount, 0);
record('heading_contract', 'Heading structure preserved', desktop.h1Count === 1 && desktop.h2Count === 6, { h1: desktop.h1Count, h2: desktop.h2Count }, { h1: 1, h2: 6 });
record('content_counts', 'Treatments and FAQ counts preserved', desktop.treatmentCount === 7 && desktop.faqCount === 13, { treatments: desktop.treatmentCount, faq: desktop.faqCount }, { treatments: 7, faq: 13 });
record('zero_empty_sections', 'No empty visible sections over 120px', desktop.emptyBlocks.length === 0, desktop.emptyBlocks, []);
record('video_exists', 'Hero video exists', desktop.video.exists, desktop.video.exists, true);
record('video_contain', 'Hero video object-fit contain', desktop.video.objectFit === 'contain', desktop.video.objectFit, 'contain');
record('video_no_clip', 'Hero video has no clip-path', desktop.video.clipPath === 'none', desktop.video.clipPath, 'none');
record('video_no_mask', 'Hero video has no mask', desktop.video.maskImage === 'none', desktop.video.maskImage, 'none');
record('video_full_width', 'Hero video occupies full hero width', (desktop.video.widthPercent || 0) >= 99, desktop.video.widthPercent, '>= 99%');
record('video_right_edge', 'Hero video reaches hero right edge', (desktop.video.rightDelta ?? 999) <= 2, desktop.video.rightDelta, '<= 2px');
record(
  'video_ratio',
  'Rendered video ratio matches native ratio',
  desktop.video.renderedRatio !== null &&
    desktop.video.nativeRatio !== null &&
    Math.abs(desktop.video.renderedRatio - desktop.video.nativeRatio) <= 0.02,
  { rendered: desktop.video.renderedRatio, native: desktop.video.nativeRatio },
  'difference <= 0.02'
);
record('intro_lead_1440', 'Intro lead <= 22px @1440', (desktop.introLeadSize || 999) <= 22, desktop.introLeadSize, '<= 22');
record('intro_lead_1024', 'Intro lead <= 22px @1024', (tablet.introLeadSize || 999) <= 22, tablet.introLeadSize, '<= 22');
record('intro_lead_390', 'Intro lead <= 20px @390', (mobile.introLeadSize || 999) <= 20, mobile.introLeadSize, '<= 20');
record('display_font', 'H1 uses Bodoni Moda', /Bodoni Moda/i.test(desktop.h1Font || ''), desktop.h1Font, 'Bodoni Moda');
record('number_font', 'Index numbers use Bodoni Moda', /Bodoni Moda/i.test(desktop.numberFont || ''), desktop.numberFont, 'Bodoni Moda');
record('number_scale', 'Index number uses editorial scale', (desktop.numberSize || 0) >= 28, desktop.numberSize, '>= 28px');
record('number_variant', 'Index number uses tabular figures', /tabular-nums/i.test(desktop.numberVariant || ''), desktop.numberVariant, 'tabular-nums');
record('body_font', 'Index body uses Manrope', /Manrope/i.test(desktop.bodyFont || ''), desktop.bodyFont, 'Manrope');
record('primary_wave', 'Primary hero CTA does not intersect wave', !desktop.primaryIntersectsWave, desktop.primaryIntersectsWave, false);
record('secondary_wave', 'Secondary hero CTA does not intersect wave', !desktop.secondaryIntersectsWave, desktop.secondaryIntersectsWave, false);
record('logo_visible', 'Full NUVANX logo is visible', desktop.logoVisible && desktop.fullLogoVisible, { logo: desktop.logoVisible, full: desktop.fullLogoVisible }, true);

const ctaBackground = parseRgb(desktop.headerCta?.background);
const ctaColor = parseRgb(desktop.headerCta?.color);
const ctaContrast = ctaBackground && ctaColor ? contrastRatio(ctaColor, ctaBackground) : 0;
record('header_cta_visible', 'Header CTA visible', desktop.headerCta?.visible === true, desktop.headerCta, true);
record('header_cta_black', 'Header CTA has dark background', Boolean(ctaBackground && ctaBackground.every((channel) => channel <= 32)), desktop.headerCta?.background, 'RGB channels <= 32');
record('header_cta_contrast', 'Header CTA contrast >= 4.5', ctaContrast >= 4.5, ctaContrast, '>= 4.5');
record('header_cta_flat', 'Header CTA has no shadow', desktop.headerCta?.boxShadow === 'none', desktop.headerCta?.boxShadow, 'none');
record('single_dark_cta', 'One dominant dark CTA band', desktop.darkCtaCount === 1, desktop.darkCtaCount, 1);

const failed = controls.filter((control) => control.status === 'FAIL');
const report = {
  generated_at: new Date().toISOString(),
  staging_url: URL,
  summary: { total: controls.length, passed: controls.length - failed.length, failed: failed.length },
  controls,
  viewports: results,
};

fs.writeFileSync(RESULT_FILE, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify(report.summary));

if (failed.length) {
  console.error(failed.map((control) => `${control.id}: ${JSON.stringify(control.measured)}`).join('\n'));
  process.exit(1);
}
