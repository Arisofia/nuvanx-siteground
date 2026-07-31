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

// Binds action-failure regression coverage to the immutable PR head under test.
const config = resolveVisualQaConfig();
assertVisualQaRuntime(config);
fs.mkdirSync(config.evidenceDir, { recursive: true });

const { report, findings, fail } = createVisualQaReport(config);
report.institutional_headers = [];
report.valoracion_form = [];

const institutionalPages = [
  ['/clinicas/', 'Clínicas NUVANX'],
  ['/madrid/valoracion/', 'Valoración médica estética en Madrid'],
  ['/equipo-medico/', 'Equipo médico'],
  ['/blog/', 'Journal NUVANX'],
  ['/contacto/', 'Contacto'],
];

const viewports = [
  { name: 'desktop', width: 1440, height: 1000, mobile: false },
  { name: 'mobile', width: 390, height: 844, mobile: true },
];

const loadPageWithRetry = createGovernedLoadPage({
  config,
  attempts: 3,
  retryDelayMs: 1500,
  settleMs: 1600,
});

function nearlyEqual(left, right, tolerance = 1) {
  return Math.abs(left - right) <= tolerance;
}

async function readHero(session) {
  return session.evaluate(`(() => {
    const hero = document.querySelector('.nvx-canonical-page-hero, .nvx-page--contact > .nvx-brand-hero');
    const copy = hero?.querySelector('.nvx-editorial-hero__copy');
    const h1 = hero?.querySelector('h1');
    const eyebrow = hero?.querySelector('.nvx-eyebrow, .nvx-brand-kicker');
    const meta = hero?.querySelector('.nvx-brand-meta, .nvx-lead');
    const actions = hero?.querySelector('.nvx-cta-cluster, .nvx-brand-actions, .nvx-cta-pair-ctas, .nvx-cta-group');
    if (!hero || !copy || !h1 || !eyebrow) return { found: false };
    const heroStyle = getComputedStyle(hero);
    const copyStyle = getComputedStyle(copy);
    const h1Style = getComputedStyle(h1);
    const eyebrowStyle = getComputedStyle(eyebrow);
    const metaStyle = meta ? getComputedStyle(meta) : null;
    const actionsStyle = actions ? getComputedStyle(actions) : null;
    const rect = hero.getBoundingClientRect();
    const copyRect = copy.getBoundingClientRect();
    return {
      found: true,
      heroWidth: rect.width,
      heroHeight: rect.height,
      heroLeft: rect.left,
      heroTop: rect.top,
      heroBackground: heroStyle.backgroundColor,
      heroOverflow: heroStyle.overflow,
      heroMinHeight: heroStyle.minHeight,
      heroMaxHeight: heroStyle.maxHeight,
      copyDisplay: copyStyle.display,
      copyWidth: copyRect.width,
      copyHeight: copyRect.height,
      copyLeft: copyRect.left,
      copyJustify: copyStyle.justifyContent,
      copyPaddingTop: copyStyle.paddingTop,
      copyPaddingRight: copyStyle.paddingRight,
      copyPaddingBottom: copyStyle.paddingBottom,
      copyPaddingLeft: copyStyle.paddingLeft,
      h1Text: h1.textContent.trim(),
      h1FontSize: h1Style.fontSize,
      h1LineHeight: h1Style.lineHeight,
      h1Color: h1Style.color,
      eyebrowColor: eyebrowStyle.color,
      metaDisplay: metaStyle?.display || '',
      metaWidth: meta?.getBoundingClientRect().width || 0,
      actionsDisplay: actionsStyle?.display || '',
      actionsWidth: actions?.getBoundingClientRect().width || 0,
      overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

function validateHero(state, scope, expectedH1, viewport) {
  if (!state.found) {
    fail(scope, 'canonical hero, copy, H1 or eyebrow was not found');
    return;
  }
  if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
  if (state.h1Text !== expectedH1) fail(scope, `unexpected H1: ${state.h1Text || 'absent'}`);
  if (state.heroWidth < viewport.width - 2) fail(scope, `hero width ${state.heroWidth}px does not fill ${viewport.width}px viewport`);
  if (state.heroOverflow !== 'hidden') fail(scope, `hero overflow is ${state.heroOverflow}`);
  if (state.copyJustify !== 'flex-end') fail(scope, `copy justify-content is ${state.copyJustify}`);
  if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
}

function validateParity(states, viewport) {
  const usable = states.filter((state) => state.found);
  if (!usable.length) return;
  const reference = usable[0];
  for (const state of usable.slice(1)) {
    const scope = `institutional parity ${state.path} ${viewport.name}`;
    if (!nearlyEqual(state.heroHeight, reference.heroHeight, 2)) fail(scope, `hero height ${state.heroHeight}px differs from ${reference.heroHeight}px`);
    if (!nearlyEqual(state.copyWidth, reference.copyWidth, 2)) fail(scope, `copy width ${state.copyWidth}px differs from ${reference.copyWidth}px`);
    if (!nearlyEqual(state.copyLeft, reference.copyLeft, 2)) fail(scope, `copy left ${state.copyLeft}px differs from ${reference.copyLeft}px`);
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

function validateIframeSource(state, scope) {
  let source;
  try { source = new URL(state.iframeSrc); } catch { fail(scope, `invalid iframe URL: ${state.iframeSrc || 'absent'}`); return; }
  if (!/^js-eu1\.hsforms\.net$/i.test(source.hostname)) fail(scope, `unexpected iframe host: ${source.hostname}`);
  if (source.pathname !== '/ui-forms-embed-components-app/frame.html') fail(scope, `unexpected iframe path: ${source.pathname}`);
  if (source.searchParams.get('_hsPortalId') !== '147416356') fail(scope, 'HubSpot portal ID does not match');
  if (source.searchParams.get('_hsFormId') !== '5042522a-0bc5-4381-ac3e-5aee8649b69c') fail(scope, 'HubSpot form ID does not match');
}

function validateForm(state, scope, viewport) {
  if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
  if (state.mountCount !== 1 || state.targetCount !== 1 || state.loaderCount !== 1) fail(scope, `mount/target/loader counts are ${state.mountCount}/${state.targetCount}/${state.loaderCount}`);
  if (!/js\.hsforms\.net\/forms\/embed\/v2\.js/i.test(state.loaderSrc)) fail(scope, `unexpected loader URL: ${state.loaderSrc || 'absent'}`);
  if (state.formState !== 'ready') fail(scope, `form state is ${state.formState || 'absent'}`);
  if (state.iframeCount + state.inlineFormCount !== 1) fail(scope, `expected one render mode, found iframe=${state.iframeCount} inline=${state.inlineFormCount}`);
  if (state.iframeCount === 1) {
    if (!state.iframeVisible) fail(scope, 'HubSpot iframe is not visible');
    if (state.iframeReadyMarker !== 'ready') fail(scope, `iframe ready marker is ${state.iframeReadyMarker || 'absent'}`);
    if (state.iframeHeight < 700) fail(scope, `iframe height ${state.iframeHeight}px is below 700px`);
    validateIframeSource(state, scope);
  }
  if (state.inlineFormCount === 1 && (state.inlineFieldCount < 3 || !state.inlineSubmitVisible)) fail(scope, `inline form is incomplete; fields=${state.inlineFieldCount} submit=${state.inlineSubmitVisible}`);
  const minimumWidth = viewport.mobile ? 270 : 700;
  if (state.renderedWidth < minimumWidth) fail(scope, `rendered form width ${state.renderedWidth}px is below ${minimumWidth}px`);
  if (state.targetOverflow === 'hidden' || state.mountOverflow === 'hidden') fail(scope, `clipping detected target=${state.targetOverflow} mount=${state.mountOverflow}`);
  if (state.targetBottom > state.mountBottom + 2 || state.mountBottom > state.sectionBottom + 2) fail(scope, 'form extends beyond its governed container');
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
