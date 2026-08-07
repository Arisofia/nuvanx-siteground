import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const url = `${baseUrl}/madrid/valoracion/`;

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA');
}

const viewports = [
  { key: 'desktop', width: 1440, height: 1100 },
  { key: 'tablet', width: 1024, height: 768 },
  { key: 'mobile', width: 390, height: 844 },
];

const outDir = path.resolve('scripts/staging2/valoracion-artifacts');
await fs.rm(outDir, { recursive: true, force: true });
await fs.mkdir(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];

async function navigate(page) {
  let last;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 40000 });
    last = response;
    if (response && response.status() !== 202 && !(await response.allHeaders())['sg-captcha']) return response;
    await page.waitForTimeout(2500 * attempt);
  }
  return last;
}

for (const viewport of viewports) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130 Safari/537.36 NUVANX-Valoracion-QA/1.0',
  });
  const page = await context.newPage();
  const issues = [];

  const response = await navigate(page);
  if (!response || response.status() !== 200) {
    issues.push(`Expected HTTP 200, got ${response?.status() || 0}`);
  }

  const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
  if (metaSha !== expectedSha) issues.push(`SHA mismatch ${metaSha || 'missing'} != ${expectedSha}`);

  await page.evaluate(async () => { if (document.fonts?.ready) await document.fonts.ready; }).catch(() => {});
  await page.waitForTimeout(350);

  const placement = await page.evaluate(() => {
    const visible = (el) => {
      if (!el) return false;
      const s = getComputedStyle(el);
      const r = el.getBoundingClientRect();
      return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 1 && r.height > 1;
    };
    const header = document.querySelector('header, .nvx-header, .nvx-site-header');
    const root = document.getElementById('nvx-valoracion-main');
    const hero = root?.querySelector(':scope > .nvx-valoracion-hero, :scope > .nvx-brand-hero');
    const formSection = document.getElementById('nvx-hubspot-form');
    const frame = formSection?.querySelector('.hs-form-frame[data-nvx-hubspot-lazy="1"]');
    const hr = hero?.getBoundingClientRect();
    const fr = formSection?.getBoundingClientRect();
    const hdr = header?.getBoundingClientRect();
    return {
      headerVisible: visible(header),
      heroVisible: visible(hero),
      formVisible: visible(formSection),
      frameExists: Boolean(frame),
      adjacent: Boolean(hero && formSection && hero.nextElementSibling === formSection),
      heroHeight: hr ? Math.round(hr.height) : 0,
      formTop: fr ? Math.round(fr.top) : null,
      heroBottom: hr ? Math.round(hr.bottom) : null,
      headerBottom: hdr ? Math.round(hdr.bottom) : null,
    };
  });

  if (!placement.headerVisible) issues.push('Header/menu is not visible');
  if (!placement.heroVisible) issues.push('Valuation heading block is not visible');
  if (!placement.formVisible) issues.push('Form section is not visible');
  if (!placement.frameExists) issues.push('HubSpot page frame is missing');
  if (!placement.adjacent) issues.push('Form section is not the immediate sibling after the page heading');
  if (placement.formTop !== null && placement.heroBottom !== null && placement.formTop < placement.heroBottom - 2) {
    issues.push('Form overlaps the page heading');
  }

  // Dedicated valuation routes must not wait for scroll. The page renderer emits
  // an eager activation event on window.load; verify HubSpot materializes inside
  // the intended section. Current HubSpot Forms v2 may render either legacy
  // `.hbspt-form` markup or the newer cross-origin iframe embed.
  await page.waitForLoadState('load').catch(() => {});
  await page.waitForTimeout(250);
  const host = page.locator('#nvx-hubspot-form');
  await host.dispatchEvent('focusin').catch(() => {});

  const mountedSelector = [
    '#nvx-hubspot-form .hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]',
    '#nvx-hubspot-form .hbspt-form',
    '#nvx-hubspot-form form.hs-form',
  ].join(', ');

  let mounted = false;
  try {
    await page.locator(mountedSelector).first().waitFor({ state: 'attached', timeout: 12000 });
    mounted = true;
  } catch {
    mounted = false;
  }
  if (!mounted) issues.push('HubSpot form did not mount inside #nvx-hubspot-form within 12s');

  const mountState = await page.evaluate(() => {
    const section = document.getElementById('nvx-hubspot-form');
    const expectedFormId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
    const expectedPortalId = '147416356';
    const embedded = section?.querySelector('.hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]') || null;
    const embeddedSrc = embedded?.getAttribute('src') || '';
    const embeddedTestId = embedded?.getAttribute('data-test-id') || '';
    const rogueLegacy = Array.from(document.querySelectorAll('.hbspt-form, form.hs-form')).filter((el) => !section?.contains(el)).length;
    const rogueIframes = Array.from(document.querySelectorAll('iframe[data-test-id^="embedded-form-"]')).filter((el) => !section?.contains(el)).length;
    return {
      embedded: Boolean(embedded),
      embeddedSrc,
      embeddedTestId,
      expectedIdentity: Boolean(
        embedded &&
        embeddedSrc.includes(`_hsPortalId=${expectedPortalId}`) &&
        embeddedSrc.includes(`_hsFormId=${expectedFormId}`) &&
        embeddedTestId.includes(expectedFormId)
      ),
      rogueMounts: rogueLegacy + rogueIframes,
    };
  });

  if (mountState.embedded && !mountState.expectedIdentity) {
    issues.push('HubSpot iframe mounted with an unexpected portal/form identity');
  }
  if (mountState.rogueMounts > 0) {
    issues.push(`Found ${mountState.rogueMounts} HubSpot form mount(s) outside #nvx-hubspot-form`);
  }

  await page.screenshot({ path: path.join(outDir, `valoracion-${viewport.key}.jpg`), type: 'jpeg', quality: 78, fullPage: true });
  results.push({ viewport, placement, mounted, mountState, issues });
  console.log(`${issues.length ? 'FIX' : 'PASS'} /madrid/valoracion/ ${viewport.width}x${viewport.height}`);
  issues.forEach((issue) => console.error(`  ${issue}`));
  await context.close();
}

await browser.close();
await fs.writeFile(path.join(outDir, 'results.json'), `${JSON.stringify(results, null, 2)}\n`);

if (results.some((result) => result.issues.length > 0)) process.exit(1);
console.log('VALORACION_PLACEMENT=PASS');
