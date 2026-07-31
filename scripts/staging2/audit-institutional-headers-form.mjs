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

const pages = [
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

function nearlyEqual(a, b, tolerance = 2) {
  return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a - b) <= tolerance;
}

async function heroState(session) {
  return session.evaluate(String.raw`(() => {
    const hero = document.querySelector(
      '.nvx-brand-page--clinicas > .nvx-canonical-page-hero, '
      + '.nvx-brand-page.nvx-valoracion-page > .nvx-canonical-page-hero, '
      + '.nvx-equipo-editorial .nvx-canonical-page-hero, '
      + '.nvx-blog-archive__hero, '
      + '.nvx-page--contact > .nvx-brand-hero'
    );
    const copy = hero?.querySelector('.nvx-editorial-hero__copy');
    const h1 = hero?.querySelector('h1');
    const eyebrow = hero?.querySelector('.nvx-eyebrow');
    if (!hero || !copy || !h1 || !eyebrow) {
      return { found: false };
    }
    const heroRect = hero.getBoundingClientRect();
    const copyRect = copy.getBoundingClientRect();
    const h1Style = getComputedStyle(h1);
    const heroStyle = getComputedStyle(hero);
    const copyStyle = getComputedStyle(copy);
    const eyebrowStyle = getComputedStyle(eyebrow);
    return {
      found: true,
      heroClass: hero.className,
      heroHeight: heroRect.height,
      heroWidth: heroRect.width,
      heroTop: heroRect.top,
      heroBackground: heroStyle.backgroundColor,
      heroMinHeight: heroStyle.minHeight,
      heroOverflow: heroStyle.overflow,
      copyHeight: copyRect.height,
      copyWidth: copyRect.width,
      copyLeft: copyRect.left,
      copyBottom: copyRect.bottom,
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
      h1MaxWidth: h1Style.maxWidth,
      eyebrowColor: eyebrowStyle.color,
      overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

async function auditHeaders(port) {
  for (const viewport of viewports) {
    const states = [];
    for (const [pagePath, expectedH1] of pages) {
      const scope = `institutional header ${pagePath} ${viewport.name}`;
      let session;
      try {
        session = await loadPage(port, `${config.baseUrl}${pagePath}`, viewport, expectedH1);
        const state = await heroState(session);
        state.path = pagePath;
        state.viewport = viewport.name;
        states.push(state);
        report.institutional_headers.push(state);

        if (!state.found) fail(scope, 'canonical hero, copy, H1 or eyebrow was not found');
        if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
        if (state.h1Text !== expectedH1) fail(scope, `unexpected H1: ${state.h1Text || 'absent'}`);
        if (state.heroWidth < viewport.width - 2) fail(scope, `hero width ${state.heroWidth}px does not fill ${viewport.width}px viewport`);
        if (state.heroOverflow !== 'hidden') fail(scope, `hero overflow is ${state.heroOverflow}`);
        if (state.copyJustify !== 'flex-end') fail(scope, `copy justify-content is ${state.copyJustify}`);
        if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);

        const screenshot = path.join(config.evidenceDir, `institutional-${pagePath.split('/').filter(Boolean).join('-')}-${viewport.name}.png`);
        await captureViewport(session, screenshot);
      } catch (error) {
        fail(scope, error instanceof Error ? error.message : String(error));
      } finally {
        await closeSession(session);
      }
    }

    const reference = states.find((state) => state.path === '/clinicas-de-medicina-estetica-nuvanx/' && state.found);
    if (!reference) {
      fail(`institutional header parity ${viewport.name}`, 'reference Clinics hero was not available');
      continue;
    }
    for (const state of states.filter((candidate) => candidate.found)) {
      const scope = `institutional header parity ${state.path} ${viewport.name}`;
      if (!nearlyEqual(state.heroHeight, reference.heroHeight, 2)) {
        fail(scope, `hero height ${state.heroHeight}px differs from ${reference.heroHeight}px`);
      }
      if (!nearlyEqual(state.copyHeight, reference.copyHeight, 2)) {
        fail(scope, `copy height ${state.copyHeight}px differs from ${reference.copyHeight}px`);
      }
      if (!nearlyEqual(state.copyLeft, reference.copyLeft, 2)) {
        fail(scope, `copy left ${state.copyLeft}px differs from ${reference.copyLeft}px`);
      }
      if (!nearlyEqual(state.h1FontSize, reference.h1FontSize, 0.5)) {
        fail(scope, `H1 size ${state.h1FontSize}px differs from ${reference.h1FontSize}px`);
      }
      if (state.heroBackground !== reference.heroBackground) {
        fail(scope, `background ${state.heroBackground} differs from ${reference.heroBackground}`);
      }
      if (state.h1Color !== reference.h1Color) {
        fail(scope, `H1 color ${state.h1Color} differs from ${reference.h1Color}`);
      }
      if (state.eyebrowColor !== reference.eyebrowColor) {
        fail(scope, `eyebrow color ${state.eyebrowColor} differs from ${reference.eyebrowColor}`);
      }
      for (const property of ['copyPaddingTop', 'copyPaddingRight', 'copyPaddingBottom', 'copyPaddingLeft']) {
        if (state[property] !== reference[property]) {
          fail(scope, `${property} ${state[property]} differs from ${reference[property]}`);
        }
      }
    }
  }
}

async function waitForHubSpotFrame(session) {
  let state = null;
  for (let attempt = 1; attempt <= 40; attempt += 1) {
    state = await session.evaluate(String.raw`(() => {
      const section = document.getElementById('nvx-hubspot-form');
      const mount = document.getElementById('nvx-hubspot-native-form');
      const frameHost = mount?.querySelector('.hs-form-frame');
      const iframe = frameHost?.querySelector('iframe');
      const sectionRect = section?.getBoundingClientRect();
      const mountRect = mount?.getBoundingClientRect();
      const hostRect = frameHost?.getBoundingClientRect();
      const frameRect = iframe?.getBoundingClientRect();
      const hostStyle = frameHost ? getComputedStyle(frameHost) : null;
      const mountStyle = mount ? getComputedStyle(mount) : null;
      const iframeStyle = iframe ? getComputedStyle(iframe) : null;
      return {
        mountCount: document.querySelectorAll('#nvx-hubspot-native-form').length,
        frameHostCount: document.querySelectorAll('#nvx-hubspot-native-form .hs-form-frame').length,
        iframeCount: document.querySelectorAll('#nvx-hubspot-native-form .hs-form-frame iframe').length,
        scriptCount: document.querySelectorAll('script[src*="hsforms.net/forms/embed/"]').length,
        iframeSrc: iframe?.src || '',
        iframeTitle: iframe?.title || '',
        iframeVisible: !!iframe && iframeStyle.display !== 'none' && iframeStyle.visibility !== 'hidden' && frameRect.width > 0 && frameRect.height > 0,
        iframeWidth: frameRect?.width || 0,
        iframeHeight: frameRect?.height || 0,
        frameHostHeight: hostRect?.height || 0,
        mountHeight: mountRect?.height || 0,
        sectionHeight: sectionRect?.height || 0,
        frameBottom: frameRect?.bottom || 0,
        hostBottom: hostRect?.bottom || 0,
        mountBottom: mountRect?.bottom || 0,
        sectionBottom: sectionRect?.bottom || 0,
        hostOverflow: hostStyle?.overflow || '',
        mountOverflow: mountStyle?.overflow || '',
        iframeOverflow: iframeStyle?.overflow || '',
        deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      };
    })()`);
    if (state.iframeCount === 1 && state.iframeVisible && state.iframeSrc) return state;
    await sleep(500);
  }
  return state;
}

async function auditValoracionForm(port) {
  for (const viewport of viewports) {
    const scope = `valoración HubSpot ${viewport.name}`;
    let session;
    try {
      session = await loadPage(
        port,
        `${config.baseUrl}/madrid/valoracion/`,
        viewport,
        'Valoración médica estética en Madrid',
      );
      const state = await waitForHubSpotFrame(session);
      state.viewport = viewport.name;
      report.valoracion_form.push(state);

      if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
      if (state.mountCount !== 1) fail(scope, `expected one canonical mount, found ${state.mountCount}`);
      if (state.frameHostCount !== 1) fail(scope, `expected one frame host, found ${state.frameHostCount}`);
      if (state.iframeCount !== 1) fail(scope, `expected one iframe, found ${state.iframeCount}`);
      if (state.scriptCount !== 1) fail(scope, `expected one HubSpot embed script, found ${state.scriptCount}`);
      if (!state.iframeVisible) fail(scope, 'iframe is not visibly rendered');
      if (!/hubspot|hsforms/i.test(state.iframeSrc)) fail(scope, `unexpected iframe URL: ${state.iframeSrc || 'absent'}`);
      const minimumHeight = viewport.mobile ? 1100 : 900;
      if (state.iframeHeight < minimumHeight) fail(scope, `iframe height ${state.iframeHeight}px is below ${minimumHeight}px`);
      if (state.iframeWidth < viewport.width * 0.75) fail(scope, `iframe width ${state.iframeWidth}px is too narrow`);
      if (state.hostOverflow === 'hidden' || state.mountOverflow === 'hidden') {
        fail(scope, `form is clipped by overflow host=${state.hostOverflow} mount=${state.mountOverflow}`);
      }
      if (state.frameBottom > state.hostBottom + 2 || state.frameBottom > state.mountBottom + 2) {
        fail(scope, 'iframe extends beyond its form host or canonical mount');
      }
      if (state.mountBottom > state.sectionBottom + 2) fail(scope, 'canonical mount extends beyond form section');

      await session.evaluate(`(() => {
        const section = document.getElementById('nvx-hubspot-form');
        section?.scrollIntoView({ block: 'start' });
      })()`);
      await sleep(300);
      const screenshot = path.join(config.evidenceDir, `valoracion-form-${viewport.name}.png`);
      await captureViewport(session, screenshot);
    } catch (error) {
      fail(scope, error instanceof Error ? error.message : String(error));
    } finally {
      await closeSession(session);
    }
  }
}

const runtime = await withHeadlessChrome(async (port) => {
  await auditHeaders(port);
  await auditValoracionForm(port);
});
report.chrome = runtime.chromePath;
report.runtime_error = runtime.runtimeError;
if (runtime.runtimeError) fail('institutional QA runtime', runtime.runtimeError);

writeVisualQaReport(config.evidenceDir, report);
if (findings.length) {
  console.error(`INSTITUTIONAL_HEADERS_FORM_QA_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`INSTITUTIONAL_HEADERS_FORM_QA_OK headers=${report.institutional_headers.length} form=${report.valoracion_form.length} sha=${config.expectedSha}`);
