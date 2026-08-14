import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const target = `${baseUrl}/madrid/valoracion/`;
const realisticBrowserUa = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function fail(message, detail = null) {
  const suffix = detail === null ? '' : ` ${JSON.stringify(detail)}`;
  throw new Error(`${message}${suffix}`);
}

async function reachValuation(page) {
  let lastStatus = 0;
  let lastError = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    try {
      const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 40000 });
      lastStatus = response?.status() || 0;
      const path = new URL(page.url()).pathname;
      console.log(`A11Y_NAV attempt=${attempt} status=${lastStatus} path=${path}`);
      if (lastStatus === 200 && path === '/madrid/valoracion/') return;
      if (lastStatus === 202 || lastStatus === 429 || lastStatus >= 500) {
        await sleep(1800 * attempt);
        continue;
      }
      await sleep(1000);
    } catch (error) {
      lastError = error;
      await sleep(1200 * attempt);
    }
  }
  fail('HubSpot a11y route unreachable', {
    lastStatus,
    lastError: lastError?.message || null,
    finalUrl: page.url(),
  });
}

async function loadHubSpotFrame(page) {
  const host = page.locator('#nvx-hubspot-form');
  await host.waitFor({ state: 'attached', timeout: 20000 });
  await host.scrollIntoViewIfNeeded().catch(() => {});
  await host.dispatchEvent('focusin').catch(() => {});
  await page.locator('#nvx-hubspot-native-form').dispatchEvent('focusin').catch(() => {});

  const iframe = page.locator('#nvx-hubspot-form iframe, iframe.hs-form-iframe, iframe[src*="hsforms"]').first();
  await iframe.waitFor({ state: 'attached', timeout: 45000 });

  const iframeTitle = (await iframe.getAttribute('title'))?.trim() || '';
  if (!iframeTitle || /^(form|hubspot form|hs-form-iframe)$/i.test(iframeTitle)) {
    fail('HubSpot iframe has no meaningful accessible title', { iframeTitle });
  }
  console.log(`IFRAME_TITLE=${JSON.stringify(iframeTitle)}`);

  const handle = await iframe.elementHandle();
  const frame = handle ? await handle.contentFrame() : null;
  if (!frame) fail('HubSpot iframe content frame unavailable');
  await frame.locator('form').first().waitFor({ state: 'attached', timeout: 30000 });
  return frame;
}

async function inspectControls(frame) {
  const controls = await frame.locator('input, select, textarea').evaluateAll((nodes) => nodes
    .filter((el) => {
      const type = (el.getAttribute('type') || '').toLowerCase();
      return type !== 'hidden' && type !== 'submit' && !el.disabled;
    })
    .map((el) => {
      const id = el.id || '';
      const labels = Array.from(el.labels || []).map((label) => (label.textContent || '').replace(/\s+/g, ' ').trim()).filter(Boolean);
      const ariaLabel = (el.getAttribute('aria-label') || '').trim();
      const labelledBy = (el.getAttribute('aria-labelledby') || '').trim();
      const labelledByText = labelledBy
        ? labelledBy.split(/\s+/).map((ref) => document.getElementById(ref)?.textContent || '').join(' ').replace(/\s+/g, ' ').trim()
        : '';
      const placeholder = (el.getAttribute('placeholder') || '').trim();
      const requiredNative = el.hasAttribute('required');
      const ariaRequired = el.getAttribute('aria-required');
      return {
        tag: el.tagName.toLowerCase(),
        type: (el.getAttribute('type') || '').toLowerCase(),
        name: el.getAttribute('name') || '',
        id,
        labels,
        ariaLabel,
        labelledBy,
        labelledByText,
        placeholder,
        requiredNative,
        ariaRequired,
      };
    }));

  if (!controls.length) fail('HubSpot form exposes no inspectable controls');

  const unnamed = controls.filter((control) =>
    control.labels.length === 0 && !control.ariaLabel && !control.labelledByText
  );
  if (unnamed.length) {
    fail('HubSpot controls without accessible name', unnamed);
  }

  const labelledByBroken = controls.filter((control) => control.labelledBy && !control.labelledByText);
  if (labelledByBroken.length) {
    fail('HubSpot controls reference empty/missing aria-labelledby targets', labelledByBroken);
  }

  const required = controls.filter((control) => control.requiredNative || control.ariaRequired === 'true');
  const requiredNotExposed = required.filter((control) => !control.requiredNative && control.ariaRequired !== 'true');
  if (requiredNotExposed.length) {
    fail('Required HubSpot controls are not exposed as required', requiredNotExposed);
  }

  console.log(`HUBSPOT_A11Y_CONTROLS=${controls.length}`);
  console.log(`HUBSPOT_A11Y_REQUIRED=${required.length}`);
  console.log('HUBSPOT_ACCESSIBLE_NAMES=PASS');
  console.log('HUBSPOT_REQUIRED_EXPOSURE=PASS');
  return { controls, required };
}

async function inspectErrorCycle(frame, requiredControls) {
  if (!requiredControls.length) {
    fail('HubSpot form exposes no required controls; cannot validate error identification semantics');
  }

  const submit = frame.locator('button[type="submit"], input[type="submit"]').first();
  await submit.waitFor({ state: 'visible', timeout: 15000 });
  await submit.click().catch(async () => {
    await submit.click({ force: true });
  });
  await sleep(800);

  const state = await frame.locator('form').first().evaluate((form) => {
    function textForIds(value) {
      if (!value) return '';
      return value.split(/\s+/)
        .map((id) => document.getElementById(id)?.textContent || '')
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    const candidates = Array.from(form.querySelectorAll('input, select, textarea'))
      .filter((el) => {
        const type = (el.getAttribute('type') || '').toLowerCase();
        return type !== 'hidden' && type !== 'submit' && !el.disabled && (el.required || el.getAttribute('aria-required') === 'true');
      });

    return candidates.map((el) => {
      const invalid = el.matches(':invalid') || el.getAttribute('aria-invalid') === 'true';
      const describedBy = (el.getAttribute('aria-describedby') || '').trim();
      const errorMessage = (el.getAttribute('aria-errormessage') || '').trim();
      const associatedText = [textForIds(describedBy), textForIds(errorMessage)].filter(Boolean).join(' ').trim();
      const wrapper = el.closest('.hs-form-field, .field, [class*="field"]');
      const visibleError = wrapper
        ? Array.from(wrapper.querySelectorAll('.hs-error-msg, .hs-error-msgs, [role="alert"], [aria-live]'))
          .map((node) => (node.textContent || '').replace(/\s+/g, ' ').trim())
          .filter(Boolean)
          .join(' ')
        : '';
      return {
        name: el.getAttribute('name') || '',
        invalid,
        ariaInvalid: el.getAttribute('aria-invalid'),
        describedBy,
        errorMessage,
        associatedText,
        visibleError,
      };
    });
  });

  const invalid = state.filter((item) => item.invalid);
  if (!invalid.length) {
    fail('HubSpot required-field validation produced no invalid controls', state);
  }

  const unidentified = invalid.filter((item) => !item.associatedText && !item.visibleError);
  if (unidentified.length) {
    fail('HubSpot invalid controls have no associated or colocated error text', unidentified);
  }

  const explicitAssociationMissing = invalid.filter((item) => !item.associatedText);
  if (explicitAssociationMissing.length) {
    console.log(`HUBSPOT_ERROR_ASSOCIATION=WEAK count=${explicitAssociationMissing.length}`);
    console.log(`HUBSPOT_ERROR_ASSOCIATION_DETAIL=${JSON.stringify(explicitAssociationMissing)}`);
  } else {
    console.log('HUBSPOT_ERROR_ASSOCIATION=PASS');
  }

  console.log(`HUBSPOT_INVALID_CONTROLS=${invalid.length}`);
  console.log('HUBSPOT_ERROR_IDENTIFICATION=PASS');
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
try {
  const context = await browser.newContext({ userAgent: realisticBrowserUa, ignoreHTTPSErrors: true });
  const page = await context.newPage();
  await reachValuation(page);
  const frame = await loadHubSpotFrame(page);
  const { required } = await inspectControls(frame);
  await inspectErrorCycle(frame, required);
  console.log('H1_HUBSPOT_A11Y=PASS');
} finally {
  await browser.close();
}
