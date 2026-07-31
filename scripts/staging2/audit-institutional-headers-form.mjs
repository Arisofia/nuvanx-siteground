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
const nearlyEqual = (left, right, tolerance = 2) => (
  Number.isFinite(left) && Number.isFinite(right) && Math.abs(left - right) <= tolerance
);

async function readHeroState(session) {
  return session.evaluate(String.raw`(() => {
    const candidates = Array.from(document.querySelectorAll(
      '.nvx-canonical-page-hero, .nvx-page--contact > .nvx-brand-hero'
    ));
    const hero = candidates.find((node) => node.querySelector('h1')) || null;
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
      heroClass: hero.className,
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
    ['heroHeight', 2],
    ['heroWidth', 2],
    ['heroLeft', 2],
    ['copyHeight', 2],
    ['copyWidth', 2],
    ['copyLeft', 2],
    ['h1FontSize', 0.5],
    ['h1LineHeight', 0.5],
  ]) {
    if (!nearlyEqual(state[key], reference[key], tolerance)) {
      fail(scope, `${key} ${state[key]} differs from ${reference[key]}`);
    }
  }
  for (const key of [
    'heroBackground',
    'heroOverflow',
    'copyDisplay',
    'copyJustify',
    'copyPaddingTop',
    'copyPaddingRight',
    'copyPaddingBottom',
    'copyPaddingLeft',
    'h1Color',
    'eyebrowColor',
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
        session = await loadPage(port, `${config.baseUrl}${pagePath}`, viewport, expectedH1);
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
        if (state.copyDisplay !== 'flex' || state.copyJustify !== 'flex-end') {
          fail(scope, `copy layout is ${state.copyDisplay}/${state.copyJustify}`);
        }
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

async function readFormState(session) {
  return session.evaluate(String.raw`(() => {
    const section = document.getElementById('nvx-hubspot-form');
    const mount = document.getElementById('nvx-hubspot-native-form');
    const target = document.getElementById('nvx-hubspot-v2-target');
    const inlineForm = target?.querySelector('form, .hs-form, .hbspt-form form');
    const iframe = target?.querySelector('iframe') || mount?.querySelector('iframe');
    const rendered = inlineForm || iframe;
    const submit = target?.querySelector('input[type="submit"], button[type="submit"], .hs-button');
    const fields = Array.from(target?.querySelectorAll('input:not([type="hidden"]), select, textarea') || [])
      .filter((node) => {
        const style = getComputedStyle(node);
        const rect = node.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
      });
    const sectionRect = section?.getBoundingClientRect();
    const mountRect = mount?.getBoundingClientRect();
    const targetRect = target?.getBoundingClientRect();
    const renderedRect = rendered?.getBoundingClientRect();
    const targetStyle = target ? getComputedStyle(target) : null;
    const mountStyle = mount ? getComputedStyle(mount) : null;
    const loader = document.querySelector('script[data-nvx-hubspot-loader="valoracion"]');
    return {
      mountCount: document.querySelectorAll('#nvx-hubspot-native-form').length,
      targetCount: document.querySelectorAll('#nvx-hubspot-v2-target').length,
      loaderCount: document.querySelectorAll('script[data-nvx-hubspot-loader="valoracion"]').length,
      loaderSrc: loader?.src || '',
      loaderType: loader?.type || '',
      formState: target?.dataset.nvxHubspotState || '',
      inlineFormCount: target?.querySelectorAll('form').length || 0,
      iframeCount: document.querySelectorAll('#nvx-hubspot-native-form iframe').length,
      renderedVisible: !!rendered && getComputedStyle(rendered).display !== 'none' && renderedRect.width > 0 && renderedRect.height > 0,
      renderedWidth: renderedRect?.width || 0,
      renderedHeight: renderedRect?.height || 0,
      visibleFieldCount: fields.length,
      submitVisible: !!submit && getComputedStyle(submit).display !== 'none' && submit.getBoundingClientRect().width > 0,
      targetHeight: targetRect?.height || 0,
      mountHeight: mountRect?.height || 0,
      sectionHeight: sectionRect?.height || 0,
      targetBottom: targetRect?.bottom || 0,
      mountBottom: mountRect?.bottom || 0,
      sectionBottom: sectionRect?.bottom || 0,
      targetOverflow: targetStyle?.overflow || '',
      mountOverflow: mountStyle?.overflow || '',
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

async function waitForRenderedForm(session) {
  let state = null;
  for (let attempt = 1; attempt <= 80; attempt += 1) {
    state = await readFormState(session);
    if (state.renderedVisible && state.visibleFieldCount >= 3 && state.submitVisible) return state;
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
      const state = await waitForRenderedForm(session);
      Object.assign(state, { viewport: viewport.name });
      report.valoracion_form.push(state);

      if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
      if (state.mountCount !== 1) fail(scope, `expected one canonical mount, found ${state.mountCount}`);
      if (state.targetCount !== 1) fail(scope, `expected one v2 target, found ${state.targetCount}`);
      if (state.loaderCount !== 1) fail(scope, `expected one HubSpot loader, found ${state.loaderCount}`);
      if (!/js\.hsforms\.net\/forms\/embed\/v2\.js/i.test(state.loaderSrc)) {
        fail(scope, `unexpected loader URL: ${state.loaderSrc || 'absent'}`);
      }
      if (!state.renderedVisible) fail(scope, `form did not render; state=${state.formState || 'absent'} type=${state.loaderType || 'default'}`);
      if (state.visibleFieldCount < 3) fail(scope, `only ${state.visibleFieldCount} visible fields rendered`);
      if (!state.submitVisible) fail(scope, 'submit control is not visible');
      if (state.renderedWidth < viewport.width * 0.72) fail(scope, `rendered form width ${state.renderedWidth}px is too narrow`);
      if (state.targetOverflow === 'hidden' || state.mountOverflow === 'hidden') {
        fail(scope, `form clipping detected target=${state.targetOverflow} mount=${state.mountOverflow}`);
      }
      if (state.targetBottom > state.mountBottom + 2 || state.mountBottom > state.sectionBottom + 2) {
        fail(scope, 'rendered form extends beyond its governed container');
      }

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
  await auditInstitutionalHeaders(port);
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
