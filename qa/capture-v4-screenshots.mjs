import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const USER = process.env.STAGING_BASIC_USER || '';
const PASS = process.env.STAGING_BASIC_PASSWORD || '';
const URL = `${BASE}/?nvxqa=editorial`;
const OUT = path.join(__dirname, 'screenshots');
const VIDEO_TIME = Number(process.env.NVX_HERO_VIDEO_TIME || '2.4');

if (!USER || !PASS) {
  throw new Error('STAGING_BASIC_USER and STAGING_BASIC_PASSWORD are required.');
}

const VIEWPORTS = [
  { width: 1440, height: 900, tag: '1440' },
  { width: 1024, height: 768, tag: '1024' },
  { width: 390, height: 844, tag: '390' },
];

const COOKIE_SELECTORS = [
  '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
  '#cookie_action_close_header',
  'button:has-text("Aceptar")',
  'button:has-text("Accept")',
  '.cmplz-btn.cmplz-accept',
];

fs.mkdirSync(OUT, { recursive: true });

async function acceptCookies(page) {
  for (const selector of COOKIE_SELECTORS) {
    const button = page.locator(selector).first();
    if (await button.isVisible().catch(() => false)) {
      await button.click({ timeout: 3000 }).catch(() => {});
      await page.waitForTimeout(300);
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

async function waitForFonts(page) {
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
  });
}

async function loadAllImages(page) {
  await page.evaluate(async () => {
    const images = Array.from(document.querySelectorAll('#nvx-home-main img'));

    for (const image of images) {
      image.scrollIntoView({ block: 'center' });
      await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    }

    await Promise.all(
      images.map(
        (image) =>
          new Promise((resolve, reject) => {
            if (image.complete && image.naturalWidth > 0) return resolve();
            const timer = setTimeout(
              () => reject(new Error(`Timed out loading image: ${image.currentSrc || image.src}`)),
              12000
            );
            image.addEventListener(
              'load',
              () => {
                clearTimeout(timer);
                resolve();
              },
              { once: true }
            );
            image.addEventListener(
              'error',
              () => {
                clearTimeout(timer);
                reject(new Error(`Image failed: ${image.currentSrc || image.src}`));
              },
              { once: true }
            );
          })
      )
    );
  });
}

async function stabilizeVideo(page) {
  await page.evaluate(async (targetTime) => {
    const video = document.querySelector('#nvx-home-hero-video');
    if (!video) throw new Error('Missing #nvx-home-hero-video');

    video.muted = true;
    video.pause();

    if (video.readyState < 2) {
      await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Hero video did not become ready')), 12000);
        video.addEventListener(
          'loadeddata',
          () => {
            clearTimeout(timer);
            resolve();
          },
          { once: true }
        );
        video.addEventListener(
          'error',
          () => {
            clearTimeout(timer);
            reject(new Error('Hero video failed to load'));
          },
          { once: true }
        );
        video.load();
      });
    }

    const safeTime = Math.min(Math.max(targetTime, 0), Math.max((video.duration || targetTime) - 0.1, 0));
    if (Number.isFinite(safeTime)) {
      video.currentTime = safeTime;
      await new Promise((resolve) => {
        const timer = setTimeout(resolve, 1200);
        video.addEventListener(
          'seeked',
          () => {
            clearTimeout(timer);
            resolve();
          },
          { once: true }
        );
      });
    }
    video.pause();
  }, VIDEO_TIME);
}

async function preparePage(page) {
  const failedResponses = [];
  page.on('response', (response) => {
    if (response.status() >= 400) {
      failedResponses.push({ status: response.status(), url: response.url() });
    }
  });

  const response = await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 90000 });
  if (!response || response.status() >= 400) {
    throw new Error(`Page load failed: ${response?.status() ?? 'no response'}`);
  }

  await page.waitForSelector('#nvx-home-main.nvx-editorial-home', { timeout: 30000 });
  await page.waitForSelector('.nvx-home-hero-stage', { timeout: 30000 });
  await acceptCookies(page);
  await waitForFonts(page);
  await loadAllImages(page);
  await stabilizeVideo(page);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForFunction(() => window.scrollY === 0);
  await page.waitForTimeout(500);

  const imageFailures = await page.evaluate(() =>
    Array.from(document.querySelectorAll('#nvx-home-main img'))
      .filter((image) => !image.complete || image.naturalWidth === 0)
      .map((image) => image.currentSrc || image.src)
  );

  if (imageFailures.length) {
    throw new Error(`Failed images: ${imageFailures.join(', ')}`);
  }

  const relevantFailures = failedResponses.filter(
    ({ url }) => !/google|facebook|doubleclick|googletagmanager|connect\.facebook/i.test(url)
  );
  if (relevantFailures.length) {
    throw new Error(`HTTP failures: ${JSON.stringify(relevantFailures)}`);
  }
}

async function captureViewport(browser, viewport) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    deviceScaleFactor: 1,
    httpCredentials: { username: USER, password: PASS },
  });
  const page = await context.newPage();
  const viewportPath = path.join(
    OUT,
    `chromium_home_${viewport.width}x${viewport.height}_viewport.png`
  );
  const heroPath = path.join(OUT, `hero_${viewport.tag}.png`);
  const introPath = path.join(OUT, `intro_${viewport.tag}.png`);
  const diagnosticPath = path.join(OUT, `diagnostic_${viewport.tag}.png`);

  try {
    await preparePage(page);
    await page.screenshot({ path: viewportPath, fullPage: false });

    const hero = page.locator('.nvx-home-hero-stage').first();
    const intro = page.locator('.nvx-home-intro').first();
    if (!(await hero.isVisible())) throw new Error('Hero is not visible');
    if (!(await intro.isVisible())) throw new Error('Intro is not visible');

    await hero.screenshot({ path: heroPath });
    await intro.screenshot({ path: introPath });

    for (const file of [viewportPath, heroPath, introPath]) {
      if (!fs.existsSync(file) || fs.statSync(file).size === 0) {
        throw new Error(`Missing or empty screenshot: ${file}`);
      }
    }
  } catch (error) {
    await page.screenshot({ path: diagnosticPath, fullPage: true }).catch(() => {});
    throw error;
  } finally {
    await context.close();
  }
}

async function captureFullPage(browser, viewport) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    deviceScaleFactor: 1,
    httpCredentials: { username: USER, password: PASS },
  });
  const page = await context.newPage();
  const output = path.join(
    OUT,
    `chromium_home_${viewport.width}x${viewport.height}_fullpage.png`
  );

  try {
    await preparePage(page);
    await page.screenshot({ path: output, fullPage: true });
    if (!fs.existsSync(output) || fs.statSync(output).size === 0) {
      throw new Error(`Missing or empty screenshot: ${output}`);
    }
  } finally {
    await context.close();
  }
}

const browser = await chromium.launch({
  headless: true,
  args: ['--disable-blink-features=AutomationControlled', '--autoplay-policy=no-user-gesture-required'],
});

try {
  for (const viewport of VIEWPORTS) {
    await captureViewport(browser, viewport);
  }
  await captureFullPage(browser, VIEWPORTS[0]);
  await captureFullPage(browser, VIEWPORTS[2]);
} finally {
  await browser.close();
}

console.log('Ticket 43 editorial screenshots captured successfully.');
