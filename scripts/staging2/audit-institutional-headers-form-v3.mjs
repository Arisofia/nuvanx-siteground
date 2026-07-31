import fs from 'node:fs';
import path from 'node:path';

import {
  assertVisualQaRuntime,
  captureViewport,
  closeSession,
  createGovernedLoadPage,
  createVisualQaReport,
  resolveVisualQaConfig,
  sleep,
  withHeadlessChrome,
  writeVisualQaReport,
} from './visual-qa-common.mjs';

const config = resolveVisualQaConfig();
assertVisualQaRuntime(config);
fs.mkdirSync(config.evidenceDir, { recursive: true });

const { report, findings, fail } = createVisualQaReport(config);
report.institutional_headers = [];
report.valoracion_form = [];

const institutionalPages = [
  ['/clinicas-de-medicina-estetica-nuvanx/', 'Clínicas de medicina estética láser en Madrid'],
  ['/blog/', 'Medicina estética con criterio'],
  ['/equipo-medico/', 'Equipo médico NUVANX: quién te valora y quién trata'],
  ['/contacto/', 'Contacto NUVANX en Madrid'],
  ['/madrid/valoracion/', 'Valoración médica estética en Madrid'],
];
const viewports = [
  { name: 'desktop', width: 1440, height: 1000, mobile: false },
  { name: 'mobile', width: 390, height: 844, mobile: true },
];
const loadPage = createGovernedLoadPage({ expectedSha: config.expectedSha });
const nearlyEqual = (a, b, tolerance = 2) => Number.isFinite(a) && Number.isFinite(b) && Math.abs(a - b) <= tolerance;

function retryUrl(url, attempt) {
  if (attempt === 0) return url;
  const candidate = new URL(url);
  candidate.searchParams.set('nvx_qa_retry', `${Date.now()}-${attempt}`);
  return candidate.toString();
}

async function loadPageWithRetry(port, url, viewport, expectedH1) {
  let lastError = null;
  for (let attempt = 0; attempt < 2; attempt += 1) {
    try {
      return await loadPage(port, retryUrl(url, attempt), viewport, expectedH1);
    } catch (error) {
      lastError = error;
      const message = error instanceof Error ? error.message : String(error);
      if (attempt === 1 || !message.includes('governed page did not become ready')) throw error;
      await sleep(750);
    }
  }
  throw lastError || new Error('Governed page did not become ready.');
}

async function readHero(session) {
  return session.evaluate(`(() => {
    const hero = Array.from(document.querySelectorAll('.nvx-canonical-page-hero, .nvx-page--contact > .nvx-brand-hero')).find((node) => node.querySelector('h1')) || null;
    const copy = hero?.querySelector('.nvx-editorial-hero__copy');
    const h1 = hero?.querySelector('h1');
    const eyebrow = hero?.querySelector('.nvx-eyebrow');
    if (!hero || !copy || !h1 || !eyebrow) return { found: false };
    const heroRect = hero.getBoundingClientRect();
    const copyRect = copy.getBoundingClientRect();
    const heroStyle = getComputedStyle(hero);
    const copyStyle = getComputedStyle(copy);
    const h1Style = getComputedStyle(h1);
    const eyebrowStyle = getComputedStyle(eyebrow);
    return {
      found: true,
      heroHeight: heroRect.height,
      heroWidth: heroRect.width,
      heroLeft: heroRect.left,
      heroBackground: heroStyle.backgroundColor,
      heroOverflow: heroStyle.overflow,
      copyHeight: copyRect.height,
      copyWidth: copyRect.width,
      copyLeft: copyRect.left,
      copyDisplay: copyStyle.display,
      copyJustify: copyStyle.justifyContent,
      copyPaddingTop: copyStyle.paddingTop,
      copyPaddingRight: copyStyle.paddingRight,
      copyPaddingBottom: copyStyle.paddingBottom,
      copyPaddingLeft: copyStyle.paddingLeft,
      h1Text: h1.textContent.trim(),
      h1FontSize: Number.parseFloat(h1Style.fontSize),
      h1LineHeight: Number.parseFloat(h1Style.lineHeight),
      h1Color: h1Style.color,
      eyebrowColor: eyebrowStyle.color,
      overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

function validateHero(state, scope, expectedH1, viewport) {
  if (!state.found) { fail(scope, 'canonical hero structure was not found'); return; }
  if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
  if (state.h1Text !== expectedH1) fail(scope, `unexpected H1: ${state.h1Text || 'absent'}`);
  if (state.heroWidth < viewport.width - 2) fail(scope, `hero width ${state.heroWidth}px does not fill viewport`);
  if (state.heroOverflow !== 'hidden') fail(scope, `hero overflow is ${state.heroOverflow}`);
  if (state.copyDisplay !== 'flex' || state.copyJustify !== 'flex-end') fail(scope, `copy layout is ${state.copyDisplay}/${state.copyJustify}`);
  if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
}

function validateParity(states, viewport) {
  const reference = states.find((state) => state.path === institutionalPages[0][0] && state.found);
  if (!reference) { fail(`institutional parity ${viewport.name}`, 'Clinics reference hero was not available'); return; }
  for (const state of states.filter((candidate) => candidate.found)) {
    const scope = `institutional parity ${state.path} ${viewport.name}`;
    for (const [key, tolerance] of [
      ['heroHeight', 2], ['heroWidth', 2], ['heroLeft', 2], ['copyHeight', 2],
      ['copyWidth', 2], ['copyLeft', 2], ['h1FontSize', 0.5], ['h1LineHeight', 0.5],
    ]) if (!nearlyEqual(state[key], reference[key], tolerance)) fail(scope, `${key} ${state[key]} differs from ${reference[key]}`);
    for (const key of [
      'heroBackground', 'heroOverflow', 'copyDisplay', 'copyJustify',
      'copyPaddingTop', 'copyPaddingRight', 'copyPaddingBottom', 'copyPaddingLeft',
      'h1Color', 'eyebrowColor',
    ]) if (state[key] !== reference[key]) fail(scope, `${key} ${state[key]} differs from ${reference[key]}`);
  }
}

async function auditHeaders(port) {
  for (const viewport of viewports) {
    const states = [];
    for (const [pagePath, expectedH1] of institutionalPages) {
      const scope = `institutional header ${pagePath} ${viewport.name}`;
      let session;
      try {
        session = await loadPageWithRetry(port, `${config.baseUrl}${pagePath}`, viewport, expectedH1);
        const state = await readHero(session);
        Object.assign(state, { path: pagePath, viewport: viewport.name });
        states.push(state);
        report.institutional_headers.push(state);
        validateHero(state, scope, expectedH1, viewport);
      } catch (error) {
        fail(scope, error instanceof Error ? error.message : String(error));
      } finally {
        await closeSession(session);
      }
    }
    validateParity(states, viewport);
  }
}

async function readForm(session) {
  return session.evaluate(`(() => {
    const section = document.getElementById('nvx-hubspot-form');
    const mount = document.getElementById('nvx-hubspot-native-form');
    const target = document.getElementById('nvx-hubspot-v2-target');
    const iframe = target?.querySelector('iframe');
    const inlineForm = target?.querySelector('form');
    const visible = (node) => {
      if (!node) return false;
      const style = getComputedStyle(node);
      const rect = node.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };
    const sectionRect = section?.getBoundingClientRect();
    const mountRect = mount?.getBoundingClientRect();
    const targetRect = target?.getBoundingClientRect();
    const iframeRect = iframe?.getBoundingClientRect();
    const inlineFields = inlineForm ? Array.from(inlineForm.querySelectorAll('input:not([type="hidden"]), select, textarea')).filter(visible) : [];
    const inlineSubmits = inlineForm ? Array.from(inlineForm.querySelectorAll('input[type="submit"], button[type="submit"], .hs-button')).filter(visible) : [];
    return {
      mountCount: document.querySelectorAll('#nvx-hubspot-native-form').length,
      targetCount: document.querySelectorAll('#nvx-hubspot-v2-target').length,
      loaderCount: document.querySelectorAll('script[data-nvx-hubspot-loader="valoracion"]').length,
      loaderSrc: document.querySelector('script[data-nvx-hubspot-loader="valoracion"]')?.src || '',
      formState: target?.dataset.nvxHubspotState || '',
      iframeCount: target?.querySelectorAll('iframe').length || 0,
      inlineFormCount: target?.querySelectorAll('form').length || 0,
      inlineFieldCount: inlineFields.length,
      inlineSubmitVisible: inlineSubmits.length > 0,
      iframeSrc: iframe?.src || '',
      iframeTitle: iframe?.title || '',
      iframeReadyMarker: iframe?.dataset.nvxValoracionForm || '',
      iframeVisible: visible(iframe),
      iframeWidth: iframeRect?.width || 0,
      iframeHeight: iframeRect?.height || 0,
      renderedWidth: iframe ? iframeRect?.width || 0 : targetRect?.width || 0,
      targetBottom: targetRect?.bottom || 0,
      mountBottom: mountRect?.bottom || 0,
      sectionBottom: sectionRect?.bottom || 0,
      targetOverflow: target ? getComputedStyle(target).overflow : '',
      mountOverflow: mount ? getComputedStyle(mount).overflow : '',
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

async function waitForForm(session) {
  let state = null;
  for (let attempt = 0; attempt < 80; attempt += 1) {
    state = await readForm(session);
    const iframeReady = state.iframeCount === 1 && state.iframeVisible && state.iframeReadyMarker === 'ready';
    const inlineReady = state.inlineFormCount === 1 && state.inlineFieldCount >= 3 && state.inlineSubmitVisible;
    if (state.formState === 'ready' && (iframeReady || inlineReady)) return state;
    await sleep(500);
  }
  return state;
}

function parseUrl(value, scope, label) {
  try {
    return new URL(value);
  } catch {
    fail(scope, `invalid ${label} URL: ${value || 'absent'}`);
    return null;
  }
}

function validateTrustedOrigin(source, scope, expectedOrigin, label) {
  const hasCredentials = Boolean(source.username || source.password);
  if (source.protocol !== 'https:' || source.origin !== expectedOrigin || source.port || hasCredentials) {
    fail(scope, `unexpected ${label} origin: ${source.origin}`);
  }
}

function validateLoaderSource(state, scope) {
  const source = parseUrl(state.loaderSrc, scope, 'loader');
  if (!source) return;
  validateTrustedOrigin(source, scope, 'https://js.hsforms.net', 'loader');
  if (source.pathname !== '/forms/embed/v2.js') fail(scope, `unexpected loader path: ${source.pathname}`);
}

function validateIframeSource(state, scope) {
  const source = parseUrl(state.iframeSrc, scope, 'iframe');
  if (!source) return;
  validateTrustedOrigin(source, scope, 'https://js-eu1.hsforms.net', 'iframe');
  if (source.pathname !== '/ui-forms-embed-components-app/frame.html') fail(scope, `unexpected iframe path: ${source.pathname}`);
  if (source.searchParams.get('_hsPortalId') !== '147416356') fail(scope, 'HubSpot portal ID does not match');
  if (source.searchParams.get('_hsFormId') !== '5042522a-0bc5-4381-ac3e-5aee8649b69c') fail(scope, 'HubSpot form ID does not match');
}

function validateFormContract(state, scope) {
  if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
  if (state.mountCount !== 1 || state.targetCount !== 1 || state.loaderCount !== 1) {
    fail(scope, `mount/target/loader counts are ${state.mountCount}/${state.targetCount}/${state.loaderCount}`);
  }
  if (state.formState !== 'ready') fail(scope, `form state is ${state.formState || 'absent'}`);
  if (state.iframeCount + state.inlineFormCount !== 1) {
    fail(scope, `expected one render mode, found iframe=${state.iframeCount} inline=${state.inlineFormCount}`);
  }
  validateLoaderSource(state, scope);
}

function validateIframeMode(state, scope) {
  if (state.iframeCount !== 1) return;
  if (!state.iframeVisible) fail(scope, 'HubSpot iframe is not visible');
  if (state.iframeReadyMarker !== 'ready') fail(scope, `iframe ready marker is ${state.iframeReadyMarker || 'absent'}`);
  if (state.iframeHeight < 700) fail(scope, `iframe height ${state.iframeHeight}px is below 700px`);
  validateIframeSource(state, scope);
}

function validateInlineMode(state, scope) {
  if (state.inlineFormCount !== 1) return;
  if (state.inlineFieldCount < 3 || !state.inlineSubmitVisible) {
    fail(scope, `inline form is incomplete; fields=${state.inlineFieldCount} submit=${state.inlineSubmitVisible}`);
  }
}

function validateFormGeometry(state, scope, viewport) {
  const minimumWidth = viewport.mobile ? 270 : 700;
  if (state.renderedWidth < minimumWidth) fail(scope, `rendered form width ${state.renderedWidth}px is below ${minimumWidth}px`);
  if (state.targetOverflow === 'hidden' || state.mountOverflow === 'hidden') {
    fail(scope, `clipping detected target=${state.targetOverflow} mount=${state.mountOverflow}`);
  }
  if (state.targetBottom > state.mountBottom + 2 || state.mountBottom > state.sectionBottom + 2) {
    fail(scope, 'form extends beyond its governed container');
  }
}

function validateForm(state, scope, viewport) {
  validateFormContract(state, scope);
  validateIframeMode(state, scope);
  validateInlineMode(state, scope);
  validateFormGeometry(state, scope, viewport);
}

async function auditForm(port) {
  for (const viewport of viewports) {
    const scope = `valoración HubSpot ${viewport.name}`;
    let session;
    try {
      session = await loadPageWithRetry(port, `${config.baseUrl}/madrid/valoracion/`, viewport, 'Valoración médica estética en Madrid');
      const state = await waitForForm(session);
      report.valoracion_form.push({ viewport: viewport.name, state });
      validateForm(state, scope, viewport);
      await session.evaluate(`document.getElementById('nvx-hubspot-form')?.scrollIntoView({ block: 'start' })`);
      await sleep(300);
      await captureViewport(session, path.join(config.evidenceDir, `valoracion-form-${viewport.name}.png`));
    } catch (error) {
      fail(scope, error instanceof Error ? error.message : String(error));
    } finally {
      await closeSession(session);
    }
  }
}

const runtime = await withHeadlessChrome(async (port) => {
  await auditHeaders(port);
  await auditForm(port);
});
report.chrome = runtime.chromePath;
report.runtime_error = runtime.runtimeError;
if (runtime.runtimeError) fail('institutional QA runtime', runtime.runtimeError);
writeVisualQaReport(config.evidenceDir, report);

if (findings.length) {
  console.error(`INSTITUTIONAL_HEADERS_FORM_QA_V3_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`INSTITUTIONAL_HEADERS_FORM_QA_V3_OK headers=${report.institutional_headers.length} form=${report.valoracion_form.length} sha=${config.expectedSha}`);
