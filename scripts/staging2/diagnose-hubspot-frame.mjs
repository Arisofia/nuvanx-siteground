import { chromium } from 'playwright';

const base = 'https://staging2.nuvanx.com';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const target = `${base}/madrid/valoracion/?gclid=NVXDIAG-${Date.now()}`;
const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  const failures = [];
  page.on('requestfailed', (request) => {
    const url = request.url();
    if (/hubspot|hsforms|js-eu1|forms-eu1/i.test(url)) failures.push({ url, error: request.failure()?.errorText || '' });
  });
  let response = null;
  for (let attempt = 1; attempt <= 8; attempt += 1) {
    response = null;
    try {
      response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      console.log(`NAV attempt=${attempt} status=${response?.status() || 0} url=${page.url()}`);
      if (response?.status() === 200 && new URL(page.url()).pathname === '/madrid/valoracion/') break;
    } catch (error) {
      console.log(`NAV attempt=${attempt} error=${error.message}`);
    }
    await page.waitForTimeout(2500);
  }

  const finalStatus = response?.status() || 0;
  const finalUrl = page.url();
  const finalPathname = (() => {
    try {
      return new URL(finalUrl).pathname;
    } catch {
      return '';
    }
  })();

  if (!response || finalStatus !== 200 || finalPathname !== '/madrid/valoracion/') {
    console.log(
      `NAV failed: status=${finalStatus} pathname="${finalPathname}" url=${finalUrl}`,
    );
    throw new Error(
      `Navigation to canonical route failed: expected status=200 and pathname="/madrid/valoracion/" ` +
      `but got status=${finalStatus} pathname="${finalPathname}" url=${finalUrl}`,
    );
  }

  await page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first().waitFor({ state: 'attached', timeout: 30000 });
  await page.waitForTimeout(12000);

  const iframeMeta = await page.locator('#nvx-hubspot-form iframe').evaluateAll((nodes) => nodes.map((node) => ({
    src: node.getAttribute('src'),
    title: node.getAttribute('title'),
    dataTestId: node.getAttribute('data-test-id'),
    name: node.getAttribute('name'),
    id: node.id,
  })));
  console.log(`IFRAMES=${JSON.stringify(iframeMeta)}`);
  // Only log structural attributes: raw innerHTML / innerText can carry HubSpot tracking
  // tokens (hutk, session ids) or cookie-derived values into public CI logs.
  console.log(`FRAME_COUNT=${page.frames().length}`);

  const frames = page.frames();
  for (let i = 0; i < frames.length; i += 1) {
    const frame = frames[i];
    let fields = [];
    let forms = 0;
    let inspectError = '';
    try {
      forms = await frame.locator('form').count();
      fields = await frame.locator('input,textarea,select,button').evaluateAll((nodes) => nodes.map((node) => ({
        tag: node.tagName.toLowerCase(), name: node.getAttribute('name') || '', type: node.getAttribute('type') || '', id: node.id || ''
      })).slice(0, 100));
    } catch (error) {
      inspectError = `FRAME_INSPECT_ERROR:${error.message}`;
    }
    console.log(`FRAME_${i}=${JSON.stringify({ url: frame.url(), forms, fields, inspectError })}`);
  }
  console.log(`HUBSPOT_REQUEST_FAILURES=${JSON.stringify(failures)}`);
} finally {
  await browser.close();
}
