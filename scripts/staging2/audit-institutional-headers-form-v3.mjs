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

class BrowserCDP {
  constructor(url) {
    this.url = url;
    this.socket = null;
    this.nextId = 0;
    this.pending = new Map();
  }

  async connect() {
    this.socket = new WebSocket(this.url);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Timed out connecting to browser CDP.')), 10000);
      this.socket.addEventListener('open', () => { clearTimeout(timer); resolve(); }, { once: true });
      this.socket.addEventListener('error', () => { clearTimeout(timer); reject(new Error('Browser CDP connection failed.')); }, { once: true });
    });
    this.socket.addEventListener('message', (event) => {
      let message;
      try { message = JSON.parse(String(event.data)); } catch { return; }
      if (!message.id || !this.pending.has(message.id)) return;
      const entry = this.pending.get(message.id);
      clearTimeout(entry.timer);
      this.pending.delete(message.id);
      if (message.error) entry.reject(new Error(message.error.message || 'Browser CDP error'));
      else entry.resolve(message.result || {});
    });
  }

  send(method, params = {}, sessionId = null, timeout = 30000) {
    const id = ++this.nextId;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(new Error(`Browser CDP command timed out: ${method}`));
      }, timeout);
      this.pending.set(id, { resolve, reject, timer });
      const message = { id, method, params };
      if (sessionId) message.sessionId = sessionId;
      this.socket.send(JSON.stringify(message));
    });
  }

  close() {
    for (const entry of this.pending.values()) {
      clearTimeout(entry.timer);
      entry.reject(new Error('Browser CDP closed.'));
    }
    this.pending.clear();
    if (this.socket && this.socket.readyState <= 1) this.socket.close();
  }
}

async function connectBrowser(port) {
  const response = await fetch(`http://127.0.0.1:${port}/json/version`);
  if (!response.ok) throw new Error(`Unable to read browser CDP endpoint: HTTP ${response.status}`);
  const version = await response.json();
  const browser = new BrowserCDP(version.webSocketDebuggerUrl);
  await browser.connect();
  return browser;
}

async function readHero(session) {
  return session.evaluate(`(() => {
    const hero = Array.from(document.querySelectorAll('.nvx-canonical-page-hero, .nvx-page--contact > .nvx-brand-hero')).find((node) => node.querySelector('h1')) || null;
    const copy = hero?.querySelector('.nvx-editorial-hero__copy');
    const h1 = hero?.querySelector('h1');
    const eyebrow = hero?.querySelector('.nvx-eyebrow');
    if (!hero || !copy || !h1 || !eyebrow) return { found: false };
    const hr = hero.getBoundingClientRect();
    const cr = copy.getBoundingClientRect();
    const hs = getComputedStyle(hero);
    const cs = getComputedStyle(copy);
    const h1s = getComputedStyle(h1);
    const es = getComputedStyle(eyebrow);
    return {
      found: true,
      heroHeight: hr.height,
      heroWidth: hr.width,
      heroLeft: hr.left,
      heroBackground: hs.backgroundColor,
      heroOverflow: hs.overflow,
      copyHeight: cr.height,
      copyWidth: cr.width,
      copyLeft: cr.left,
      copyDisplay: cs.display,
      copyJustify: cs.justifyContent,
      copyPaddingTop: cs.paddingTop,
      copyPaddingRight: cs.paddingRight,
      copyPaddingBottom: cs.paddingBottom,
      copyPaddingLeft: cs.paddingLeft,
      h1Text: h1.textContent.trim(),
      h1FontSize: Number.parseFloat(h1s.fontSize),
      h1LineHeight: Number.parseFloat(h1s.lineHeight),
      h1Color: h1s.color,
      eyebrowColor: es.color,
      overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

function validateHeroState(state, scope, expectedH1, viewport) {
  if (!state.found) { fail(scope, 'canonical hero structure was not found'); return; }
  if (state.deploySha !== config.expectedSha) fail(scope, `served SHA ${state.deploySha || 'absent'}`);
  if (state.h1Text !== expectedH1) fail(scope, `unexpected H1: ${state.h1Text || 'absent'}`);
  if (state.heroWidth < viewport.width - 2) fail(scope, `hero width ${state.heroWidth}px does not fill viewport`);
  if (state.heroOverflow !== 'hidden') fail(scope, `hero overflow is ${state.heroOverflow}`);
  if (state.copyDisplay !== 'flex' || state.copyJustify !== 'flex-end') fail(scope, `copy layout is ${state.copyDisplay}/${state.copyJustify}`);
  if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
}

function auditParity(states, viewport) {
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
        session = await loadPage(port, `${config.baseUrl}${pagePath}`, viewport, expectedH1);
        const state = await readHero(session);
        Object.assign(state, { path: pagePath, viewport: viewport.name });
        states.push(state);
        report.institutional_headers.push(state);
        validateHeroState(state, scope, expectedH1, viewport);
      } catch (error) {
        fail(scope, error instanceof Error ? error.message : String(error));
      } finally {
        await closeSession(session);
      }
    }
    auditParity(states, viewport);
  }
}

async function readParentForm(session) {
  return session.evaluate(`(() => {
    const section = document.getElementById('nvx-hubspot-form');
    const mount = document.getElementById('nvx-hubspot-native-form');
    const target = document.getElementById('nvx-hubspot-v2-target');
    const iframe = target?.querySelector('iframe');
    const inlineForm = target?.querySelector('form');
    const sr = section?.getBoundingClientRect();
    const mr = mount?.getBoundingClientRect();
    const tr = target?.getBoundingClientRect();
    const fr = iframe?.getBoundingClientRect();
    return {
      mountCount: document.querySelectorAll('#nvx-hubspot-native-form').length,
      targetCount: document.querySelectorAll('#nvx-hubspot-v2-target').length,
      loaderCount: document.querySelectorAll('script[data-nvx-hubspot-loader="valoracion"]').length,
      loaderSrc: document.querySelector('script[data-nvx-hubspot-loader="valoracion"]')?.src || '',
      formState: target?.dataset.nvxHubspotState || '',
      iframeCount: target?.querySelectorAll('iframe').length || 0,
      inlineFormCount: target?.querySelectorAll('form').length || 0,
      iframeSrc: iframe?.src || '',
      iframeVisible: !!iframe && getComputedStyle(iframe).display !== 'none' && fr.width > 0 && fr.height > 0,
      iframeWidth: fr?.width || 0,
      iframeHeight: fr?.height || 0,
      targetWidth: tr?.width || 0,
      mountWidth: mr?.width || 0,
      targetBottom: tr?.bottom || 0,
      mountBottom: mr?.bottom || 0,
      sectionBottom: sr?.bottom || 0,
      targetOverflow: target ? getComputedStyle(target).overflow : '',
      mountOverflow: mount ? getComputedStyle(mount).overflow : '',
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
    };
  })()`);
}

async function waitForParentForm(session) {
  let state = null;
  for (let attempt = 0; attempt < 80; attempt += 1) {
    state = await readParentForm(session);
    if (state.iframeCount === 1 && state.iframeVisible && state.formState === 'ready') return state;
    await sleep(500);
  }
  return state;
}

async function inspectHubSpotTarget(browser, iframeSrc) {
  let targetInfo = null;
  for (let attempt = 0; attempt < 80; attempt += 1) {
    const targets = await browser.send('Target.getTargets');
    targetInfo = (targets.targetInfos || []).find((target) => (
      target.type === 'iframe' && target.url && (
        target.url === iframeSrc || target.url.includes('ui-forms-embed-components-app/frame.html')
      )
    ));
    if (targetInfo) break;
    await sleep(250);
  }
  if (!targetInfo) {
    const targets = await browser.send('Target.getTargets');
    return { found: false, targets: (targets.targetInfos || []).map(({ targetId, type, url, title }) => ({ targetId, type, url, title })) };
  }

  const attachment = await browser.send('Target.attachToTarget', { targetId: targetInfo.targetId, flatten: true });
  const sessionId = attachment.sessionId;
  await browser.send('Runtime.enable', {}, sessionId);
  let state = null;
  for (let attempt = 0; attempt < 80; attempt += 1) {
    const evaluation = await browser.send('Runtime.evaluate', {
      expression: `(() => {
        const visible = (node) => {
          const style = getComputedStyle(node);
          const rect = node.getBoundingClientRect();
          return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        };
        const fields = Array.from(document.querySelectorAll('input:not([type="hidden"]), select, textarea')).filter(visible);
        const submits = Array.from(document.querySelectorAll('input[type="submit"], button[type="submit"], .hs-button')).filter(visible);
        return {
          url: location.href,
          title: document.title,
          bodyTextLength: (document.body?.innerText || '').trim().length,
          bodyTextSample: (document.body?.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 300),
          visibleFieldCount: fields.length,
          submitVisible: submits.length > 0,
          submitCount: submits.length,
          documentWidth: document.documentElement.scrollWidth,
          viewportWidth: document.documentElement.clientWidth,
          documentHeight: Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0),
        };
      })()`,
      returnByValue: true,
      awaitPromise: true,
    }, sessionId);
    state = evaluation.result?.value || null;
    if (state?.visibleFieldCount >= 3 && state?.submitVisible) break;
    await sleep(500);
  }
  try { await browser.send('Target.detachFromTarget', { sessionId }); } catch { /* already detached */ }
  return { found: true, target: targetInfo, state };
}

async function auditForm(port, browser) {
  for (const viewport of viewports) {
    const scope = `valoración HubSpot ${viewport.name}`;
    let session;
    try {
      session = await loadPage(port, `${config.baseUrl}/madrid/valoracion/`, viewport, 'Valoración médica estética en Madrid');
      const parent = await waitForParentForm(session);
      const child = await inspectHubSpotTarget(browser, parent.iframeSrc || '');
      report.valoracion_form.push({ viewport: viewport.name, parent, child });

      if (parent.deploySha !== config.expectedSha) fail(scope, `served SHA ${parent.deploySha || 'absent'}`);
      if (parent.mountCount !== 1 || parent.targetCount !== 1 || parent.loaderCount !== 1) fail(scope, `mount/target/loader counts are ${parent.mountCount}/${parent.targetCount}/${parent.loaderCount}`);
      if (!/js\.hsforms\.net\/forms\/embed\/v2\.js/i.test(parent.loaderSrc)) fail(scope, `unexpected loader URL: ${parent.loaderSrc || 'absent'}`);
      if (parent.formState !== 'ready') fail(scope, `form state is ${parent.formState || 'absent'}`);
      if (parent.iframeCount !== 1) fail(scope, `expected exactly 1 iframe, found ${parent.iframeCount}`);
      if (parent.inlineFormCount !== 1) fail(scope, `expected exactly 1 inline form mount, found ${parent.inlineFormCount}`);
      if (!parent.iframeVisible) fail(scope, 'HubSpot iframe is not visible');
      const minimumWidth = viewport.mobile ? 270 : 700;
      if (parent.iframeWidth < minimumWidth) fail(scope, `iframe width ${parent.iframeWidth}px is below ${minimumWidth}px`);
      if (parent.iframeHeight < 700) fail(scope, `iframe height ${parent.iframeHeight}px is below 700px`);
      if (parent.targetOverflow === 'hidden' || parent.mountOverflow === 'hidden') fail(scope, `clipping detected target=${parent.targetOverflow} mount=${parent.mountOverflow}`);
      if (parent.targetBottom > parent.mountBottom + 2 || parent.mountBottom > parent.sectionBottom + 2) fail(scope, 'form extends beyond its governed container');
      if (!child.found) fail(scope, 'HubSpot iframe process was not discoverable through browser CDP');
      if ((child.state?.visibleFieldCount || 0) < 3) fail(scope, `iframe exposes only ${child.state?.visibleFieldCount || 0} visible fields; text=${child.state?.bodyTextSample || 'absent'}`);
      if (!child.state?.submitVisible) fail(scope, 'iframe submit control is not visible');

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
  const browser = await connectBrowser(port);
  try {
    await auditHeaders(port);
    await auditForm(port, browser);
  } finally {
    browser.close();
  }
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
