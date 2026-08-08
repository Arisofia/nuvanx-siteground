import { chromium } from 'playwright';

const base = 'https://staging2.nuvanx.com';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const target = `${base}/madrid/valoracion/?gclid=NVXDIAG-${Date.now()}`;

// HubSpot v4 embed/frame URLs commonly carry tracking parameters (hutk, portal/page ids)
/**
 * Removes query parameters from a URL while preserving its origin and pathname.
 * @param {string} value - The URL to sanitize.
 * @return {string} The URL origin and pathname, or an empty string when the value is invalid.
 */
function sanitizeUrl(value) {
  try {
    const url = new URL(value);
    // Opaque frame URLs (about:blank, data:, etc.) have origin "null" and would log as
    // confusing values like "nullblank"; only origin+pathname for http(s) is meaningful.
    if (!/^https?:$/.test(url.protocol)) return url.protocol === 'about:' ? value : '';
    return `${url.origin}${url.pathname}`;
  } catch {
    return '';
  }
}

const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage();
  const failures = [];
  page.on('requestfailed', (request) => {
    const url = request.url();
    if (/hubspot|hsforms|js-eu1|forms-eu1/i.test(url)) failures.push({ url: sanitizeUrl(url), error: request.failure()?.errorText || '' });
  });
  let response = null;
  // Remember the best observed response across attempts so a later throw does not
  // report status=0 when an earlier attempt actually returned an HTTP response.
  let observedStatus = 0;
  let observedUrl = '';
  for (let attempt = 1; attempt <= 8; attempt += 1) {
    response = null;
    try {
      response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      const status = response?.status() || 0;
      if (status) {
        observedStatus = status;
        observedUrl = page.url();
      }
      console.log(`NAV attempt=${attempt} status=${status} url=${page.url()}`);
      if (status === 200 && new URL(page.url()).pathname === '/madrid/valoracion/') break;
    } catch (error) {
      console.log(`NAV attempt=${attempt} error=${error.message}`);
    }
    await page.waitForTimeout(2500);
  }

  const finalStatus = response?.status() || observedStatus || 0;
  const finalUrl = response ? page.url() : (observedUrl || page.url());
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

  /**
   * Collects and logs structural diagnostics for the HubSpot form, its frames, and related request failures.
   */
  async function collectDiagnostics() {
    const iframeMeta = await page.locator('#nvx-hubspot-form iframe').evaluateAll((nodes) => nodes.map((node) => ({
      src: node.getAttribute('src'),
      title: node.getAttribute('title'),
      dataTestId: node.dataset.testId,
      name: node.getAttribute('name'),
      id: node.id,
    })));
    // Strip query strings from iframe src before logging; HubSpot embeds carry tracking tokens there.
    console.log(`IFRAMES=${JSON.stringify(iframeMeta.map((meta) => ({ ...meta, src: sanitizeUrl(meta.src) })))}`);
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
      console.log(`FRAME_${i}=${JSON.stringify({ url: sanitizeUrl(frame.url()), forms, fields, inspectError })}`);
    }
    console.log(`HUBSPOT_REQUEST_FAILURES=${JSON.stringify(failures)}`);
  }

  try {
    await page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first().waitFor({ state: 'attached', timeout: 30000 });
    await page.waitForTimeout(12000);
    await collectDiagnostics();
  } catch (error) {
    await collectDiagnostics().catch((diagnosticError) => {
      console.log(`DIAGNOSTICS_COLLECTION_FAILED=${diagnosticError.message}`);
    });
    throw error;
  }
} finally {
  await browser.close();
}
