import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}

const viewports = [
  { name: 'Desktop', width: 1440, height: 1100, maxTeamWidth: 520 },
  { name: 'Tablet', width: 1024, height: 768, maxTeamWidth: 440 },
  { name: 'Mobile', width: 390, height: 844, maxTeamWidth: 360 },
];

const transientStatuses = new Set([202, 429, 503]);
const failures = [];
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });

async function gotoWithRetry(page, path) {
  let response;
  for (let attempt = 1; attempt <= 6; attempt += 1) {
    response = await page.goto(`${baseUrl}${path}`, {
      waitUntil: 'domcontentloaded',
      timeout: 45000,
    });
    const status = response?.status() || 0;
    const captcha = response?.headers()?.['sg-captcha'] || '';
    if (!transientStatuses.has(status) && !captcha) {
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
      return response;
    }
    if (attempt < 6) await page.waitForTimeout(2500 * attempt);
  }
  return response;
}

for (const vp of viewports) {
  const context = await browser.newContext({
    viewport: { width: vp.width, height: vp.height },
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();

  let response = await gotoWithRetry(page, '/equipo-medico/');
  if (response?.status() !== 200) {
    failures.push(`${vp.name} equipo HTTP ${response?.status()}`);
    await context.close();
    continue;
  }

  const equipoSha = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => null);
  if (equipoSha !== expectedSha) failures.push(`${vp.name} equipo SHA ${equipoSha || '(missing)'}`);

  const targetImages = page.locator('img[alt="Francisco Geraldo"], img[alt="Yolanda Piñero"]');
  const imageCount = await targetImages.count();
  if (imageCount !== 2) failures.push(`${vp.name} equipo expected 2 target images, got ${imageCount}`);
  for (let i = 0; i < imageCount; i += 1) {
    await targetImages.nth(i).scrollIntoViewIfNeeded();
    await page.waitForTimeout(200);
  }

  const team = await page.evaluate(() => {
    const grid = document.querySelector('.nvx-equipo-staff-grid');
    const gridStyle = grid ? getComputedStyle(grid) : null;
    return {
      gridDisplay: gridStyle?.display || '',
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      images: [...document.images]
        .filter((img) => /Francisco Geraldo|Yolanda Piñero/i.test(img.alt || ''))
        .map((img) => {
          const rect = img.getBoundingClientRect();
          const card = img.closest('.nvx-brand-card--team');
          const cardRect = card?.getBoundingClientRect();
          return {
            alt: img.alt,
            width: Math.round(rect.width),
            height: Math.round(rect.height),
            cardWidth: Math.round(cardRect?.width || 0),
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            inGrid: Boolean(grid && card && grid.contains(card)),
          };
        }),
    };
  });

  if (team.gridDisplay !== 'grid') failures.push(`${vp.name} equipo grid display=${team.gridDisplay}`);
  if (team.overflow > 2) failures.push(`${vp.name} equipo horizontal overflow=${team.overflow}`);
  for (const img of team.images) {
    if (!img.inGrid) failures.push(`${vp.name} ${img.alt} is not inside team grid`);
    if (img.width > vp.maxTeamWidth) failures.push(`${vp.name} ${img.alt} width ${img.width} > ${vp.maxTeamWidth}`);
    if (img.naturalWidth < 1 || img.naturalHeight < 1) failures.push(`${vp.name} ${img.alt} image not loaded`);
  }
  console.log(`TEAM_${vp.name.toUpperCase()}=${JSON.stringify(team)}`);

  response = await gotoWithRetry(page, '/madrid/valoracion/');
  if (response?.status() !== 200) {
    failures.push(`${vp.name} valoracion HTTP ${response?.status()}`);
    await context.close();
    continue;
  }

  const valoracionSha = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => null);
  if (valoracionSha !== expectedSha) failures.push(`${vp.name} valoracion SHA ${valoracionSha || '(missing)'}`);

  const formLocator = page.locator('#nvx-hubspot-form');
  await formLocator.scrollIntoViewIfNeeded();
  await page.waitForTimeout(1200);

  const valoracion = await page.evaluate(() => {
    const root = document.querySelector('#nvx-valoracion-main') || document.querySelector('article');
    const hero = root?.querySelector(':scope > .nvx-brand-hero, :scope > .nvx-valoracion-hero');
    const form = document.querySelector('#nvx-hubspot-form');
    const formRect = form?.getBoundingClientRect();
    const heroRect = hero?.getBoundingClientRect();
    const style = form ? getComputedStyle(form) : null;
    const embedded = form?.querySelector('iframe[data-test-id^="embedded-form-"]');
    const rogueEmbedded = [...document.querySelectorAll('iframe[data-test-id^="embedded-form-"]')]
      .some((iframe) => form && !form.contains(iframe));
    return {
      adjacent: Boolean(hero && form && hero.nextElementSibling === form),
      heroBottom: Math.round(heroRect?.bottom || 0),
      formTop: Math.round(formRect?.top || 0),
      formHeight: Math.round(formRect?.height || 0),
      display: style?.display || '',
      visibility: style?.visibility || '',
      opacity: style?.opacity || '',
      iframe: Boolean(embedded),
      rogueEmbedded,
    };
  });

  const hubspotFrame = page.frames().find((frame) => frame.url().includes('ui-forms-embed-components-app/frame.html'));
  const inputs = hubspotFrame ? await hubspotFrame.locator('input,textarea,select').count().catch(() => 0) : 0;
  const buttons = hubspotFrame ? await hubspotFrame.locator('button,input[type="submit"]').count().catch(() => 0) : 0;

  if (!valoracion.adjacent) failures.push(`${vp.name} valoracion form is not directly after hero`);
  if (valoracion.display === 'none' || valoracion.visibility === 'hidden' || valoracion.opacity === '0' || valoracion.formHeight < 200) {
    failures.push(`${vp.name} valoracion form not visibly rendered`);
  }
  if (!valoracion.iframe || !hubspotFrame || inputs < 4 || buttons < 1) {
    failures.push(`${vp.name} valoracion HubSpot not mounted inputs=${inputs} buttons=${buttons}`);
  }
  if (valoracion.rogueEmbedded) failures.push(`${vp.name} valoracion has HubSpot embed outside #nvx-hubspot-form`);

  console.log(`VALORACION_${vp.name.toUpperCase()}=${JSON.stringify({ ...valoracion, inputs, buttons })}`);
  await context.close();
}

await browser.close();

if (failures.length) {
  console.error('TARGETED_PRODUCTION_VALIDATION=FAIL');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('TARGETED_PRODUCTION_VALIDATION=PASS');
