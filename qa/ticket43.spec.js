const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

if (!process.env.STAGING_BASE_URL || !process.env.STAGING_BASIC_USER || !process.env.STAGING_BASIC_PASSWORD) {
  console.error("CRITICAL ERROR: Missing STAGING environment variables. Aborting test execution.");
  process.exit(1);
}

const BASE_URL = process.env.STAGING_BASE_URL.replace(/\/$/, "");

const VIEWPORTS = [
  { width: 1440, height: 900, name: '1440x900' },
  { width: 1280, height: 800, name: '1280x800' },
  { width: 1024, height: 768, name: '1024x768' },
  { width: 768, height: 1024, name: '768x1024' },
  { width: 390, height: 844, name: '390x844' },
  { width: 360, height: 800, name: '360x800' }
];

const ROUTES = [
  { path: '/', name: 'home' },
  { path: '/medicina-estetica-laser/', name: 'medicina-estetica' },
  { path: '/madrid/valoracion/', name: 'valoracion' },
  { path: '/equipo-medico/', name: 'equipo' },
  { path: '/contacto/', name: 'contacto' },
  { path: '/gracias/', name: 'gracias' }
];

test.describe('Ticket 43 Visual Reconciliation', () => {

  let browserResults = {};

  test.afterAll(async ({ browserName }) => {
    const resultsDir = path.join(__dirname, 'results');
    if (!fs.existsSync(resultsDir)) {
      fs.mkdirSync(resultsDir, { recursive: true });
    }
    fs.writeFileSync(
      path.join(resultsDir, `${browserName}-visual-metrics.json`), 
      JSON.stringify(browserResults, null, 2)
    );
  });

  for (const route of ROUTES) {
    for (const vp of VIEWPORTS) {
      test(`Visual Validation - ${route.name} - ${vp.name}`, async ({ browser, browserName }) => {
        const context = await browser.newContext({
          baseURL: BASE_URL,
          httpCredentials: {
            username: process.env.STAGING_BASIC_USER,
            password: process.env.STAGING_BASIC_PASSWORD
          },
          viewport: { width: vp.width, height: vp.height }
        });
        const page = await context.newPage();

        const consoleErrors = [];
        const failedRequests = [];
        const httpErrors = [];

        page.on('console', msg => {
          if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        
        page.on('requestfailed', request => {
          failedRequests.push(request.url());
        });
        
        page.on('response', response => {
          if (response.status() >= 400) {
            httpErrors.push(`${response.status()} ${response.url()}`);
          }
        });

        const targetUrl = route.path === '/' ? `${route.path}?nvxqa=ticket43-a218537` : route.path;
        const response = await page.goto(targetUrl, { waitUntil: 'networkidle' });

        // Assertions: Respuesta e Invariantes
        expect(response).not.toBeNull();
        expect(response.status()).toBe(200);
        
        const currentUrl = new URL(page.url());
        expect(currentUrl.pathname).toBe(route.path);
        
        const mainCount = await page.locator('main').count();
        expect(mainCount).toBe(1);

        const nvxMainCount = await page.locator('#nvx-home-main').count();
        if (route.path === '/') {
            expect(nvxMainCount).toBe(1);
        }

        await page.waitForFunction(() => document.fonts.status === 'loaded');
        
        const fontsLoaded = await page.evaluate(() => {
          let hasBodoni = false;
          let hasManrope = false;
          document.fonts.forEach(font => {
            if (font.family.includes('Bodoni Moda')) hasBodoni = true;
            if (font.family.includes('Manrope')) hasManrope = true;
          });
          const bodoniCheck = document.fonts.check('44px "Bodoni Moda"');
          const manropeCheck = document.fonts.check('16px "Manrope"');
          return { hasBodoni, hasManrope, bodoniCheck, manropeCheck };
        });

        expect(failedRequests.length, `Failed requests found: ${failedRequests.join(', ')}`).toBe(0);
        expect(httpErrors.length, `HTTP errors found: ${httpErrors.join(', ')}`).toBe(0);
        expect(consoleErrors.length, `Console errors found: ${consoleErrors.join(', ')}`).toBe(0);

        if (route.path === '/') {
            const isCssLoadedAndCorrect = await page.evaluate(async () => {
                for (const sheet of Array.from(document.styleSheets)) {
                    try {
                        if (sheet.href && sheet.href.includes('nvx-brand-home')) {
                            const res = await fetch(sheet.href);
                            const text = await res.text();
                            if (text.includes('TICKET #43: RECONCILIACIÓN VISUAL')) {
                                return true;
                            }
                        }
                    } catch(e) { }
                }
                return false;
            });
            expect(isCssLoadedAndCorrect, "CSS TICKET #43 no cargó o no contiene la firma.").toBeTruthy();
            
            expect(fontsLoaded.hasBodoni).toBeTruthy();
            expect(fontsLoaded.hasManrope).toBeTruthy();
            expect(fontsLoaded.bodoniCheck).toBeTruthy();
            expect(fontsLoaded.manropeCheck).toBeTruthy();
        }

        const screenshotDir = path.join(__dirname, 'screenshots');
        if (!fs.existsSync(screenshotDir)) {
          fs.mkdirSync(screenshotDir, { recursive: true });
        }
        const screenshotPath = path.join(screenshotDir, `${browserName}_${route.name}_${vp.name}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: true });

        const metrics = await page.evaluate(() => {
          const hero = document.querySelector('.nvx-brand-hero');
          const videoFeature = document.querySelector('.nvx-home-video-feature');
          const h1 = document.querySelector('h1');
          
          const getBox = (el) => el ? el.getBoundingClientRect() : null;
          const getStyle = (el, prop) => el ? window.getComputedStyle(el)[prop] : null;

          return {
            scrollWidth: document.documentElement.scrollWidth,
            viewportWidth: window.innerWidth,
            heroBox: getBox(hero),
            videoBox: getBox(videoFeature),
            videoPos: getStyle(videoFeature, 'position'),
            h1FontSize: getStyle(h1, 'font-size')
          };
        });

        const key = `${route.name}_${vp.name}`;
        browserResults[key] = {
          ...metrics,
          fonts: fontsLoaded
        };

        expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.viewportWidth);
        
        if (route.path === '/') {
            expect(metrics.heroBox).not.toBeNull();
            expect(metrics.videoBox).not.toBeNull();
            expect(metrics.videoPos).not.toBe('absolute');
        }

        if (vp.width <= 680 && metrics.h1FontSize) {
            const size = parseFloat(metrics.h1FontSize);
            expect(size).toBeLessThanOrEqual(44);
        }
        
        await context.close();
      });
    }
  }
});
