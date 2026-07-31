import fs from 'node:fs';
import path from 'node:path';

import {
  assertVisualQaRuntime,
  captureViewport,
  CDPSession,
  closeSession,
  createGovernedLoadPage,
  createTarget,
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

const loadGovernedPage = createGovernedLoadPage({ expectedSha: config.expectedSha });
const nearlyEqual = (left, right, tolerance = 2) => (
  Number.isFinite(left) && Number.isFinite(right) && Math.abs(left - right) <= tolerance
);

async function loadDirectPage(port, url, viewport) {
  const target = await createTarget(port);
  const session = new CDPSession(target.webSocketDebuggerUrl);
  await session.connect();
  await session.send('Page.enable');
  await session.send('Runtime.enable');
  await session.send('Network.enable');
  await session.send('Network.setUserAgentOverride', {
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
    platform: viewport.mobile ? 'Android' : 'Windows',
  });
  await session.send('Emulation.setDeviceMetricsOverride', {
    width: viewport.width,
    height: viewport.height,
    deviceScaleFactor: 1,
    mobile: viewport.mobile,
    screenWidth: viewport.width,
    screenHeight: viewport.height,
  });

  let loaded = false;
  const unsubscribe = session.on('Page.loadEventFired', () => { loaded = true; });
  const navigation = await session.send('Page.navigate', { url });
  if (navigation.errorText) throw new Error(`Direct frame navigation failed: ${navigation.errorText}`);
  for (let attempt = 0; attempt < 120 && !loaded; attempt += 1) await sleep(100);
  unsubscribe();
  await session.evaluate(`new Promise((resolve) => {
    const finish = () => setTimeout(resolve, 600);
    if (document.fonts?.ready) document.fonts.ready.then(finish, finish); else finish();
  })`);
  return session;
}

async function readHeroState(session) {
  return session.evaluate(String.raw`(() => {
    const hero = Array.from(document.querySelectorAll('.nvx-canonical-page-hero, .nvx-page--contact > .nvx-brand-hero'))
      .find((node) => node.querySelector('h1')) || null;
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

function compareHero(reference, state, scope) {
  for (const [key, tolerance] of [
    ['heroHeight', 2], ['heroWidth', 2], ['heroLeft', 2], ['copyHeight', 2],
    ['copyWidth', 2], ['copyLeft', 2], ['h1FontSize', 0.5], ['h1LineHeight', 0.5],
  ]) {
    if (!nearlyEqual(state[key], reference[key], tolerance)) {
      fail(scope, `${key} ${state[key]} differs from ${reference[key]}`);
    }
  }
  for (const key of [
    'heroBackground', 'heroOverflow', 'copyDisplay', 'copyJustify',
    'copyPaddingTop', 'copyPaddingRight', 'copyPaddingBottom', 'copyPaddingLeft',
    'h1Color', 'eyebrowColor',
  ]) {
    if (state[key] !== reference[key]) fail(scope, `${key} ${state[key]} differs from ${reference[key]}`);
  }
}

async function auditInstitutionalHeaders(port) {
  for (const viewport of viewports) {
    const states = [];
    for (const [pagePath, expectedH1] of institutionalPages) {
      const scope = `institutional header ${pagePath} ${viewport.name}`;
      let session;
      try {
        session = await loadGovernedPage(port, `${config.baseUrl}${pagePath}`, viewport, expectedH1);
        const state = await readHeroState(session);
        Object.assign(state, { path: pagePath, viewport: viewport.name });
        report.institutional_headers.push(state);
        states.push(state);
        if (!state.found) {
          fail(scope, 'canonical hero, copy, H1 or eyebrow was not found');
          continue;
        }
        if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
        if (state.h1Text !== expectedH1) fail(scope, `unexpected H1: ${state.h1Text || 'absent'}`);
        if (state.heroWidth < viewport.width - 2) fail(scope, `hero width ${state.heroWidth}px does not fill viewport`);
        if (state.heroOverflow !== 'hidden') fail(scope, `hero overflow is ${state.heroOverflow}`);
        if (state.copyDisplay !== 'flex' || state.copyJustify !== 'flex-end') fail(scope, `copy layout is ${state.copyDisplay}/${state.copyJustify}`);
        if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
        const slug = pagePath.split('/').filter(Boolean).join('-');
        await captureViewport(session, path.join(config.evidenceDir, `institutional-${slug}-${viewport.name}.png`));
      } catch (error) {
        fail(scope, error instanceof Error ? error.message : String(error));
      } finally {
        await closeSession(session);
      }
    }
    const reference = states.find((state) => state.path === institutionalPages[0][0] && state.found);
    if (!reference) {
      fail(`institutional parity ${viewport.name}`, 'Clinics reference hero was not available');
      continue;
    }
    for (const state of states.filter((candidate) => candidate.found)) {
      compareHero(reference, state, `institutional parity ${state.path} ${viewport.name}`);
    }
  }
}

function flattenFrameTree(node, frames = []) {
  if (!node?.frame) return frames;
  frames.push({ id: node.frame.id, url: node.frame.url, name: node.frame.name || '' });
  for (const child of node.childFrames || []) flattenFrameTree(child, frames);
  return frames;
}

async function readParentFormState(session) {
  const dom = await session.evaluate(String.raw`(() => {
    const section = document.getElementById('nvx-hubspot-form');
    const mount = document.getElementById('nvx-hubspot-native-form');
    const target = document.getElementById('nvx-hubspot-v2-target');
    const iframe = target?.querySelector('iframe') || mount?.querySelector('iframe');
    const sectionRect = section?.getBoundingClientRect();
    const mountRect = mount?.getBoundingClientRect();
    const targetRect = target?.getBoundingClientRect();
    const frameRect = iframe?.getBoundingClientRect();
    const attributes = iframe ? Object.fromEntries(Array.from(iframe.attributes).map((attribute) => [attribute.name, attribute.value])) : {};
    return {
      mountCount: document.querySelectorAll('#nvx-hubspot-native-form').length,
      targetCount: document.querySelectorAll('#nvx-hubspot-v2-target').length,
      loaderCount: document.querySelectorAll('script[data-nvx-hubspot-loader="valoracion"]').length,
      loaderSrc: document.querySelector('script[data-nvx-hubspot-loader="valoracion"]')?.src || '',
      formState: target?.dataset.nvxHubspotState || '',
      iframeCount: document.querySelectorAll('#nvx-hubspot-native-form iframe').length,
      iframeSrc: iframe?.src || '',
      iframeSrcAttribute: iframe?.getAttribute('src') || '',
      iframeSrcdocLength: (iframe?.getAttribute('srcdoc') || '').length,
      iframeAttributes: attributes,
      iframeVisible: !!iframe && getComputedStyle(iframe).display !== 'none' && frameRect.width > 0 && frameRect.height > 0,
      iframeWidth: frameRect?.width || 0,
      iframeHeight: frameRect?.height || 0,
      targetWidth: targetRect?.width || 0,
      targetHeight: targetRect?.height || 0,
      mountWidth: mountRect?.width || 0,
      mountHeight: mountRect?.height || 0,
      sectionWidth: sectionRect?.width || 0,
      sectionHeight: sectionRect?.height || 0,
      targetBottom: targetRect?.bottom || 0,
      mountBottom: mountRect?.bottom || 0,
      sectionBottom: sectionRect?.bottom || 0,
      targetOverflow: target ? getComputedStyle(target).overflow : '',
      mountOverflow: mount ? getComputedStyle(mount).overflow : '',
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
  const tree = await session.send('Page.getFrameTree');
  dom.frames = flattenFrameTree(tree.frameTree);
  dom.externalFrameUrl = dom.frames.map((frame) => frame.url).find((url) => (
    /^https:\/\//i.test(url) && !url.startsWith(config.baseUrl)
  )) || dom.iframeSrc || dom.iframeSrcAttribute;
  return dom;
}

async function waitForParentFrame(session) {
  let state = null;
  for (let attempt = 0; attempt < 80; attempt += 1) {
    state = await readParentFormState(session);
    if (state.iframeCount === 1 && state.iframeVisible && state.externalFrameUrl) return state;
    await sleep(500);
  }
  return state;
}

async function inspectDirectFrame(port, url, viewport, suffix = '') {
  let session;
  try {
    session = await loadDirectPage(port, url, viewport);
    let state = null;
    for (let attempt = 0; attempt < 60; attempt += 1) {
      state = await session.evaluate(String.raw`(() => {
        const visible = (node) => {
          const style = getComputedStyle(node);
          const rect = node.getBoundingClientRect();
          return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        };
        const fields = Array.from(document.querySelectorAll('input:not([type="hidden"]), select, textarea')).filter(visible);
        const submits = Array.from(document.querySelectorAll('input[type="submit"], button[type="submit"], .hs-button')).filter(visible);
        const nested = Array.from(document.querySelectorAll('iframe')).find(visible);
        return {
          url: location.href,
          title: document.title,
          bodyTextLength: (document.body?.innerText || '').trim().length,
          visibleFieldCount: fields.length,
          submitVisible: submits.length > 0,
          submitCount: submits.length,
          nestedIframeUrl: nested?.src || '',
          documentWidth: document.documentElement.scrollWidth,
          viewportWidth: document.documentElement.clientWidth,
          documentHeight: Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0),
        };
      })()`);
      if (state.visibleFieldCount >= 3 && state.submitVisible) break;
      await sleep(500);
    }
    await captureViewport(session, path.join(config.evidenceDir, `hubspot-frame-${viewport.name}${suffix}.png`));
    if (state.visibleFieldCount < 3 && state.nestedIframeUrl && state.nestedIframeUrl !== url) {
      const nested = await inspectDirectFrame(port, state.nestedIframeUrl, viewport, `${suffix}-nested`);
      state.nested = nested;
      state.visibleFieldCount = Math.max(state.visibleFieldCount, nested.visibleFieldCount || 0);
      state.submitVisible = state.submitVisible || nested.submitVisible;
    }
    return state;
  } finally {
    await closeSession(session);
  }
}

async function auditValoracionForm(port) {
  for (const viewport of viewports) {
    const scope = `valoración HubSpot ${viewport.name}`;
    let session;
    try {
      session = await loadGovernedPage(
        port,
        `${config.baseUrl}/madrid/valoracion/`,
        viewport,
        'Valoración médica estética en Madrid',
      );
      const parent = await waitForParentFrame(session);
      const state = { viewport: viewport.name, parent, direct: null };
      report.valoracion_form.push(state);

      if (parent.deploySha !== config.expectedSha) fail(scope, `served SHA ${parent.deploySha || 'absent'}`);
      if (parent.mountCount !== 1) fail(scope, `expected one canonical mount, found ${parent.mountCount}`);
      if (parent.targetCount !== 1) fail(scope, `expected one v2 target, found ${parent.targetCount}`);
      if (parent.loaderCount !== 1) fail(scope, `expected one HubSpot loader, found ${parent.loaderCount}`);
      if (!/js\.hsforms\.net\/forms\/embed\/v2\.js/i.test(parent.loaderSrc)) fail(scope, `unexpected loader URL: ${parent.loaderSrc || 'absent'}`);
      if (parent.iframeCount !== 1 || !parent.iframeVisible) fail(scope, 'one visible HubSpot iframe was not rendered');
      if (!/^https:\/\//i.test(parent.externalFrameUrl || '')) fail(scope, `missing external frame URL: ${parent.externalFrameUrl || 'absent'}`);
      const minimumWidth = viewport.mobile ? 300 : 700;
      if (parent.iframeWidth < minimumWidth) fail(scope, `iframe width ${parent.iframeWidth}px is below ${minimumWidth}px`);
      if (parent.iframeHeight < 700) fail(scope, `iframe height ${parent.iframeHeight}px is below 700px`);
      if (parent.targetOverflow === 'hidden' || parent.mountOverflow === 'hidden') fail(scope, `form clipping detected target=${parent.targetOverflow} mount=${parent.mountOverflow}`);
      if (parent.targetBottom > parent.mountBottom + 2 || parent.mountBottom > parent.sectionBottom + 2) fail(scope, 'form extends beyond its governed container');

      await session.evaluate(`document.getElementById('nvx-hubspot-form')?.scrollIntoView({ block: 'start' })`);
      await sleep(300);
      await captureViewport(session, path.join(config.evidenceDir, `valoracion-form-${viewport.name}.png`));

      if (/^https:\/\//i.test(parent.externalFrameUrl || '')) {
        state.direct = await inspectDirectFrame(port, parent.externalFrameUrl, viewport);
        if (state.direct.visibleFieldCount < 3) fail(scope, `child frame exposes only ${state.direct.visibleFieldCount} visible fields`);
        if (!state.direct.submitVisible) fail(scope, 'child frame submit control is not visible');
      }
    } catch (error) {
      fail(scope, error instanceof Error ? error.message : String(error));
    } finally {
      await closeSession(session);
    }
  }
}

const runtime = await withHeadlessChrome(async (port) => {
  await auditInstitutionalHeaders(port);
  await auditValoracionForm(port);
});
report.chrome = runtime.chromePath;
report.runtime_error = runtime.runtimeError;
if (runtime.runtimeError) fail('institutional QA runtime', runtime.runtimeError);

writeVisualQaReport(config.evidenceDir, report);
if (findings.length) {
  console.error(`INSTITUTIONAL_HEADERS_FORM_QA_V2_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`INSTITUTIONAL_HEADERS_FORM_QA_V2_OK headers=${report.institutional_headers.length} form=${report.valoracion_form.length} sha=${config.expectedSha}`);
