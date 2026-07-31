import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { randomInt } from 'node:crypto';

export const STAGING2_BASE_URL = 'https://staging2.nuvanx.com';
export const DEFAULT_VISUAL_QA_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36';
export const MIN_SCREENSHOT_BYTES = 15000;

export const pages = [
  ['/', 'Medicina estética con criterio médico y resultados naturales.'],
  ['/casos-de-pacientes/', 'Casos de pacientes y tratamientos realizados en NUVANX'],
  ['/soluciones-medicas/', 'Soluciones médicas para rostro, piel y contorno corporal.'],
  ['/protocolos-signature/', 'Protocolos Signature: Medicina estética de diagnóstico.'],
  ['/remodelacion-corporal-laser-madrid/', 'Remodelación corporal láser diseñada según tu anatomía.'],
  ['/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid'],
  ['/papada-definicion-mandibular-madrid/', 'Tratamiento médico de papada y definición mandibular en Madrid.'],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Tratamiento médico para firmeza, densidad y calidad cutánea.'],
  ['/cicatrices-acne-poros-textura-madrid/', 'Tratamiento médico de cicatrices, poros dilatados y textura cutánea.'],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Tratamiento médico de manchas, rojeces y daño solar.'],
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Esa grasa del abdomen que no se va ni a dieta ni a gimnasio.'],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Para que la manga caiga bien — sin que la piel quede colgando después.'],
  ['/grasa-espalda-zona-sujetador-madrid/', 'El pliegue que marca la ropa, aunque tu peso esté bien.'],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'La piel más delicada del cuerpo merece el abordaje más cuidadoso.'],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Una zona pequeña que cambia toda la línea de la pierna.'],
  ['/contorno-corporal-masculino-madrid/', 'Pensado para el cuerpo de un hombre, no adaptado del de una mujer.'],
  ['/por-que-nuvanx/', 'Por qué NUVANX. Sin retórica de marketing.'],
  ['/inversion-medicina-estetica/', 'El presupuesto forma parte de una decisión informada.'],
  ['/equipo-medico/', 'Equipo médico NUVANX: quién te valora y quién trata'],
];

export const viewports = [
  { name: 'desktop', width: 1440, height: 1000, mobile: false },
  { name: 'mobile', width: 390, height: 844, mobile: true },
];

export const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
export const safeName = (value) => String(value || '').split('/').filter(Boolean).join('__') || 'home';

export function locateChrome() {
  const candidates = [process.env.CHROME_BIN, 'google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser'].filter(Boolean);
  const searchPaths = (process.env.PATH || '').split(path.delimiter);
  for (const candidate of candidates) {
    if (candidate.includes(path.sep) && fs.existsSync(candidate)) return candidate;
    for (const searchPath of searchPaths) {
      const executable = path.join(searchPath, candidate);
      if (fs.existsSync(executable)) return executable;
    }
  }
  throw new Error('Google Chrome or Chromium is not installed on the runner.');
}

export class CDPSession {
  constructor(webSocketUrl) {
    this.webSocketUrl = webSocketUrl;
    this.webSocket = null;
    this.nextId = 0;
    this.pending = new Map();
    this.eventHandlers = new Map();
    this.pendingWaiters = new Map();
  }

  async connect() {
    this.webSocket = new WebSocket(this.webSocketUrl);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Timed out opening Chrome DevTools WebSocket.')), 10000);
      this.webSocket.addEventListener('open', () => { clearTimeout(timer); resolve(); }, { once: true });
      this.webSocket.addEventListener('error', () => { clearTimeout(timer); reject(new Error('Unable to open Chrome DevTools WebSocket.')); }, { once: true });
    });
    this.webSocket.addEventListener('message', (event) => this.handleMessage(event));
  }

  /**
   * Subscribe to CDP events (messages without id). Returns an unsubscribe function.
   */
  on(method, handler) {
    if (!this.eventHandlers.has(method)) this.eventHandlers.set(method, new Set());
    this.eventHandlers.get(method).add(handler);
    return () => this.off(method, handler);
  }

  /** Remove a previously registered CDP event handler. */
  off(method, handler) {
    this.eventHandlers.get(method)?.delete(handler);
  }

  /** Drop every event handler (used when closing a page session). */
  removeAllListeners() {
    this.eventHandlers.clear();
  }

  dispatchEvent(method, params) {
    const handlers = this.eventHandlers.get(method);
    if (!handlers) return;
    for (const handler of handlers) {
      try {
        Promise.resolve(handler(params || {})).catch((error) => {
          if (process.env.NVX_CDP_DEBUG) {
            console.error(`CDP async listener error for ${method}:`, error);
          }
        });
      } catch (error) {
        // Synchronous listener failures must not tear down the CDP session.
        if (process.env.NVX_CDP_DEBUG) {
          console.error(`CDP listener error for ${method}:`, error);
        }
      }
    }
  }

  resolvePending(message) {
    if (!message?.id || !this.pending.has(message.id)) return;
    const pending = this.pending.get(message.id);
    clearTimeout(pending.timer);
    this.pending.delete(message.id);
    if (message.error) {
      pending.reject(new Error(`${message.error?.message || 'CDP error'} (${message.error?.code || 'unknown'})`));
      return;
    }
    pending.resolve(message.result || {});
  }

  handleMessage(event) {
    let message;
    try {
      message = JSON.parse(String(event.data));
    } catch {
      // Non-JSON frames must not break the CDP session or leave sends pending.
      // Optionally log malformed frames when debugging CDP traffic.
      try {
        if (typeof process !== 'undefined' && process.env?.NVX_CDP_DEBUG) {
          const raw = String(event.data ?? '');
          const maxLength = 512;
          const truncated = raw.length > maxLength ? `${raw.slice(0, maxLength)}…` : raw;
          console.warn('[CDP] Ignoring non-JSON frame', {
            length: raw.length,
            truncatedPayload: truncated,
          });
        }
      } catch {
        // Logging must never interfere with normal operation.
      }
      return;
    }
    if (message?.method) this.dispatchEvent(message.method, message.params);
    this.resolvePending(message);
  }

  send(method, params = {}, timeoutMilliseconds = 30000) {
    const id = ++this.nextId;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(new Error(`CDP command timed out: ${method}`));
      }, timeoutMilliseconds);
      this.pending.set(id, { resolve, reject, timer });
      this.webSocket.send(JSON.stringify({ id, method, params }));
    });
  }

  waitFor(method, timeoutMilliseconds = 30000) {
    return new Promise((resolve, reject) => {
      let settled = false;
      let unsubscribe = () => {};
      const timer = setTimeout(() => {
        if (settled) return;
        settled = true;
        this.pendingWaiters.delete(timer);
        unsubscribe();
        reject(new Error(`CDP event timed out: ${method}`));
      }, timeoutMilliseconds);
      unsubscribe = this.on(method, (params) => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        this.pendingWaiters.delete(timer);
        unsubscribe();
        resolve(params || {});
      });
      this.pendingWaiters.set(timer, { reject, timer });
    });
  }

  async evaluate(expression, awaitPromise = true) {
    const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text || 'Browser evaluation failed.');
    return result.result?.value;
  }

  call(functionSource, ...args) {
    return this.evaluate(`(${functionSource})(${args.map((value) => JSON.stringify(value)).join(',')})`);
  }

  closeSocket() {
    const closeError = new Error('CDP session closed before command completed.');
    for (const pending of this.pending.values()) {
      clearTimeout(pending.timer);
      pending.reject(closeError);
    }
    this.pending.clear();
    for (const waiter of this.pendingWaiters.values()) {
      clearTimeout(waiter.timer);
      waiter.reject(closeError);
    }
    this.pendingWaiters.clear();
    this.removeAllListeners();
    if (this.webSocket && this.webSocket.readyState <= 1) this.webSocket.close();
  }
}

export async function waitForChrome(port) {
  for (let attempt = 0; attempt < 80; attempt += 1) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (response.ok) return;
    } catch { /* Chrome may still be starting. */ }
    await sleep(250);
  }
  throw new Error('Chrome DevTools endpoint did not become ready.');
}

export async function createTarget(port) {
  const response = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent('about:blank')}`, { method: 'PUT' });
  if (!response.ok) throw new Error(`Unable to create Chrome target: HTTP ${response.status}`);
  return response.json();
}

export async function closeSession(session) {
  if (!session) return;
  try { await session.send('Page.close', {}, 3000); } catch { /* Target may already be closed. */ }
  session.closeSocket();
}

export async function pageState(session) {
  return session.evaluate(String.raw`(() => {
    const text = (document.body?.innerText || '').replace(/\s+/g, ' ').trim();
    const h1 = Array.from(document.querySelectorAll('h1')).map((node) => node.textContent.trim());
    const header = document.querySelector('#nvx-header');
    const footer = document.querySelector('footer');
    const documentWidth = document.documentElement.scrollWidth;
    const viewportWidth = document.documentElement.clientWidth;
    return {
      url: location.href,
      title: document.title,
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      h1,
      forbidden: /(?:403\s*-\s*Forbidden)|(?:Access to this page is forbidden)/i.test(text),
      overflow: Math.max(0, documentWidth - viewportWidth),
      contentHeight: Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0),
      headerVisible: !!header && getComputedStyle(header).display !== 'none' && header.getBoundingClientRect().height > 0,
      footerVisible: !!footer && getComputedStyle(footer).display !== 'none' && footer.getBoundingClientRect().height > 0,
    };
  })()`);
}

export async function captureFullPage(session, destination, viewport) {
  await session.evaluate(`new Promise(async (resolve) => {
    const max = Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0);
    for (let y = 0; y < max; y += 700) { window.scrollTo(0, y); await new Promise((next) => setTimeout(next, 30)); }
    window.scrollTo(0, 0); setTimeout(resolve, 200);
  })`);
  const metrics = await session.send('Page.getLayoutMetrics');
  const size = metrics.cssContentSize || metrics.contentSize;
  const fullHeight = Math.max(viewport.height, Math.min(Math.ceil(size.height), 20000));
  await session.send('Emulation.setDeviceMetricsOverride', {
    width: viewport.width,
    height: fullHeight,
    deviceScaleFactor: 1,
    mobile: viewport.mobile,
    screenWidth: viewport.width,
    screenHeight: fullHeight,
  });
  const screenshot = await session.send('Page.captureScreenshot', {
    format: 'png',
    fromSurface: true,
    captureBeyondViewport: true,
    clip: { x: 0, y: 0, width: viewport.width, height: fullHeight, scale: 1 },
  });
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

export async function captureViewport(session, destination) {
  const screenshot = await session.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

export function checkMenuLabels(scope, text, required, forbidden, fail) {
  for (const label of required) if (!text.includes(label)) fail(scope, `missing: ${label}`);
  for (const label of forbidden) if (text.includes(label)) fail(scope, `exposes retired label: ${label}`);
}

export async function openMobileAccordion(session, linkPattern, parentSubmenuClass = null) {
  return session.call((pattern, parentClass) => {
    const nav = document.getElementById('nvx-mobile-nav');
    const regex = new RegExp(pattern, 'i');
    let scopeElement = nav?.querySelector('.nvx-mobile-nav__list');
    if (parentClass) {
      const parentItem = Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li') || []).find((node) => /protocolos signature/i.test(Array.from(node.children).find((child) => child.tagName === 'A')?.textContent || ''));
      scopeElement = parentItem ? Array.from(parentItem.children).find((child) => child.classList?.contains(parentClass)) : null;
    }
    const item = Array.from(scopeElement?.querySelectorAll(':scope > li') || []).find((node) => regex.test(Array.from(node.children).find((child) => child.tagName === 'A')?.textContent || ''));
    const toggle = item ? Array.from(item.children).find((child) => child.classList?.contains('nvx-mobile-nav__toggle')) : null;
    toggle?.click();
    return Boolean(toggle);
  }, linkPattern, parentSubmenuClass);
}

export async function verifyMobileDrawer(session, scope, result, fail) {
  const drawer = await session.evaluate(`(() => { const nav = document.getElementById('nvx-mobile-nav'); const button = document.getElementById('nvx-hamburger-btn'); return { open: !!nav && nav.classList.contains('is-open') && nav.getAttribute('aria-hidden') === 'false', expanded: button?.getAttribute('aria-expanded'), activeId: document.activeElement?.id || '' }; })()`);
  Object.assign(result, drawer);
  if (!drawer.open || drawer.expanded !== 'true') fail(scope, 'hamburger did not open the drawer with correct ARIA state');
  if (drawer.activeId !== 'nvx-mobile-close') fail(scope, `focus did not move to close button; active=${drawer.activeId || 'none'}`);
}

export async function verifyMobileAccordions(session, scope, result, fail) {
  if (!await openMobileAccordion(session, 'protocolos signature')) throw new Error('Protocolos Signature mobile accordion toggle was not found.');
  await sleep(250);
  if (!await openMobileAccordion(session, 'contour architecture', 'sub-menu')) throw new Error('Contour Architecture nested mobile toggle was not found.');
  await sleep(300);

  const state = await session.evaluate(String.raw`(() => { const nav = document.getElementById('nvx-mobile-nav'); const text = nav?.textContent.replace(/\s+/g, ' ').trim() || ''; return { text, expanded: Array.from(nav?.querySelectorAll('.nvx-mobile-nav__toggle[aria-expanded="true"]') || []).length, overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth), drawerWidth: nav?.getBoundingClientRect().width || 0, viewportWidth: document.documentElement.clientWidth }; })()`);
  Object.assign(result, state);

  checkMenuLabels(scope, state.text, ['Abdomen y flancos', 'Brazos y axila', 'Espalda y zona del sujetador', 'Muslos y región subglútea', 'Rodillas', 'Contorno masculino'], ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame'], fail);
  if (state.expanded < 2) fail(scope, `expected two expanded accordion levels, found ${state.expanded}`);
  if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
  if (state.drawerWidth > state.viewportWidth + 2) fail(scope, `drawer width ${state.drawerWidth}px exceeds viewport ${state.viewportWidth}px`);
}

export async function verifyMobileDrawerEscape(session, scope, result, fail) {
  await session.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27, nativeVirtualKeyCode: 27 });
  await session.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27, nativeVirtualKeyCode: 27 });
  await sleep(200);

  const closed = await session.evaluate(`(() => { const nav = document.getElementById('nvx-mobile-nav'); const button = document.getElementById('nvx-hamburger-btn'); return { closed: !!nav && !nav.classList.contains('is-open') && nav.getAttribute('aria-hidden') === 'true', expanded: button?.getAttribute('aria-expanded'), activeId: document.activeElement?.id || '' }; })()`);
  result.escape_close = closed;

  if (!closed.closed || closed.expanded !== 'false') fail(scope, 'Escape did not close drawer and reset ARIA state');
  if (closed.activeId !== 'nvx-hamburger-btn') fail(scope, `focus was not restored to hamburger; active=${closed.activeId || 'none'}`);
}

export async function auditDesktopNavigation(port, loadPage, closeSession, report, fail) {
  const scope = 'desktop mega-menu';
  const result = {};
  let session;
  try {
    session = await loadPage(port, `${report.base_url}/`, viewports[0], pages[0][1]);
    const initial = await session.evaluate(`(() => {
      const links = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li > a')).map((node) => node.textContent.trim().toUpperCase());
      const item = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li')).find((node) => /protocolos signature/i.test(Array.from(node.children).find((child) => child.tagName === 'A')?.textContent || ''));
      const link = item ? Array.from(item.children).find((child) => child.tagName === 'A') : null;
      const rect = link?.getBoundingClientRect();
      return { links, x: rect ? rect.left + rect.width / 2 : 0, y: rect ? rect.top + rect.height / 2 : 0 };
    })()`);
    result.top_level_links = initial.links;
    const required = ['INICIO', 'SOLUCIONES MÉDICAS', 'PROTOCOLOS SIGNATURE', 'TECNOLOGÍA', 'CASOS CLÍNICOS', 'EQUIPO MÉDICO', 'CLÍNICAS', 'JOURNAL', 'CONTACTO'];
    for (const label of required) if (!initial.links.includes(label)) fail(scope, `missing top-level item: ${label}`);
    if (!initial.x || !initial.y) throw new Error('Protocolos Signature desktop link was not found.');
    await session.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: initial.x, y: initial.y });
    await sleep(500);
    const opened = await session.evaluate(String.raw`(() => {
      const item = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li')).find((node) => /protocolos signature/i.test(Array.from(node.children).find((child) => child.tagName === 'A')?.textContent || ''));
      const submenu = item ? Array.from(item.children).find((child) => child.classList?.contains('sub-menu')) : null;
      if (!submenu) return { visible: false, text: '', overflow: 999 };
      const style = getComputedStyle(submenu); const rect = submenu.getBoundingClientRect();
      return { visible: style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity || 1) > 0 && rect.width > 0 && rect.height > 0, text: submenu.innerText.replace(/\s+/g, ' ').trim(), overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth) };
    })()`);
    Object.assign(result, opened);
    if (!opened.visible) fail(scope, 'Protocolos Signature submenu did not become visible on hover');
    for (const label of ['NUVANX Contour Architecture™', 'NUVANX Post-Maternity Contour™', 'NUVANX Profile Definition™', 'NUVANX Skin Architecture™', 'NUVANX Surface Renewal™', 'NUVANX Tone Correction™']) if (!opened.text.includes(label)) fail(scope, `submenu missing: ${label}`);
    for (const forbidden of ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame']) if (opened.text.includes(forbidden)) fail(scope, `submenu exposes retired label: ${forbidden}`);
    if (opened.overflow > 2) fail(scope, `horizontal overflow is ${opened.overflow}px`);
    const destination = path.join(process.env.EVIDENCE_DIR, 'navigation-desktop-mega.png');
    await captureViewport(session, destination);
    result.screenshot = path.basename(destination);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.navigation.desktop = result;
}

export async function auditMobileNavigation(port, loadPage, closeSession, report, fail) {
  const scope = 'mobile drawer';
  const result = {};
  let session;
  try {
    session = await loadPage(port, `${report.base_url}/`, viewports[1], pages[0][1]);
    await session.evaluate(`(() => { document.getElementById('nvx-hamburger-btn')?.focus(); document.getElementById('nvx-hamburger-btn')?.click(); })()`);
    await sleep(250);

    await verifyMobileDrawer(session, scope, result, fail);
    await verifyMobileAccordions(session, scope, result, fail);

    const destination = path.join(process.env.EVIDENCE_DIR, 'navigation-mobile-drawer.png');
    await captureViewport(session, destination);
    result.screenshot = path.basename(destination);

    await verifyMobileDrawerEscape(session, scope, result, fail);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.navigation.mobile = result;
}

/**
 * Resolve BASE_URL / EXPECTED_SHA / EVIDENCE_DIR for governed visual QA.
 * @param {NodeJS.ProcessEnv} [env]
 */
export function resolveVisualQaConfig(env = process.env) {
  return {
    baseUrl: (env.BASE_URL || STAGING2_BASE_URL).replace(/\/$/, ''),
    expectedSha: env.EXPECTED_SHA || '',
    evidenceDir: env.EVIDENCE_DIR || 'staging2-deployment-evidence/visual-qa',
  };
}

/**
 * Hard-fail when staging2 visual QA is invoked with unsafe runtime inputs.
 * @param {{ baseUrl: string, expectedSha: string }} config
 */
export function assertVisualQaRuntime({ baseUrl, expectedSha }) {
  if (baseUrl !== STAGING2_BASE_URL) {
    console.error(`ERROR: refusing unexpected BASE_URL: ${baseUrl}`);
    process.exit(1);
  }
  if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
    console.error('ERROR: EXPECTED_SHA must be a full lowercase 40-character SHA.');
    process.exit(1);
  }
  if (typeof WebSocket !== 'function') {
    console.error('ERROR: Node.js WebSocket support is required.');
    process.exit(1);
  }
}

/**
 * @param {{ baseUrl: string, expectedSha: string }} config
 */
export function createVisualQaReport({ baseUrl, expectedSha }) {
  const findings = [];
  const report = {
    base_url: baseUrl,
    expected_sha: expectedSha,
    generated_at: new Date().toISOString(),
    chrome: '',
    pages: [],
    navigation: {},
    findings,
  };
  const fail = (scope, message) => findings.push(`${scope}: ${message}`);
  return { report, findings, fail };
}

/**
 * Wait until the deploy SHA meta tag and sole H1 match the governed contract.
 * @param {CDPSession} session
 * @param {string} expectedSha
 * @param {string} expectedH1
 */
export async function waitForGovernedPage(session, expectedSha, expectedH1) {
  let lastState = null;
  for (let attempt = 1; attempt <= 36; attempt += 1) {
    lastState = await session.evaluate(String.raw`(() => ({
      readyState: document.readyState,
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      h1: Array.from(document.querySelectorAll('h1')).map((node) => node.textContent.trim()),
      text: (document.body?.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 240),
    }))()`);
    if (
      lastState.deploySha === expectedSha
      && lastState.h1.length === 1
      && lastState.h1[0] === expectedH1
    ) {
      return lastState;
    }
    if (attempt === 12 || attempt === 24) {
      await session.send('Page.reload', { ignoreCache: true });
    }
    await sleep(500);
  }
  throw new Error(
    `governed page did not become ready; sha=${lastState?.deploySha || 'absent'} h1=${JSON.stringify(lastState?.h1 || [])} body=${lastState?.text || 'empty'}`,
  );
}

/**
 * Build a loadPage(port, url, viewport, expectedH1) that enforces deploy SHA + H1.
 * @param {{ userAgent?: string, expectedSha: string }} options
 */
export function createGovernedLoadPage({
  userAgent = DEFAULT_VISUAL_QA_USER_AGENT,
  expectedSha,
}) {
  return async function loadPage(port, url, viewport, expectedH1) {
    const target = await createTarget(port);
    const session = new CDPSession(target.webSocketDebuggerUrl);
    await session.connect();
    await session.send('Page.enable');
    await session.send('Runtime.enable');
    await session.send('Network.enable');
    await session.send('Network.setUserAgentOverride', {
      userAgent,
      acceptLanguage: 'es-ES,es;q=0.9,en;q=0.7',
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
    const navigation = await session.send('Page.navigate', { url });
    if (navigation.errorText) {
      throw new Error(`Navigation failed: ${navigation.errorText}`);
    }
    await waitForGovernedPage(session, expectedSha, expectedH1);
    await session.evaluate(`new Promise((resolve) => {
      const finish = () => setTimeout(resolve, 500);
      if (document.fonts?.ready) document.fonts.ready.then(finish, finish); else finish();
    })`);
    return session;
  };
}

/**
 * Record layout/content findings for one governed page × viewport capture.
 * @param {Record<string, any>} result
 * @param {{ scope: string, expectedSha: string, expectedH1: string, fail: Function }} ctx
 */
export function recordGovernedViewportFindings(result, { scope, expectedSha, expectedH1, fail }) {
  if (result.forbidden) fail(scope, 'rendered a 403 Forbidden page');
  if (result.deploySha !== expectedSha) {
    fail(scope, `served SHA ${result.deploySha || 'absent'} instead of ${expectedSha}`);
  }
  if (result.h1.length !== 1 || result.h1[0] !== expectedH1) {
    fail(scope, `H1 mismatch: ${JSON.stringify(result.h1)}`);
  }
  if (result.overflow > 2) fail(scope, `horizontal overflow is ${result.overflow}px`);
  if (!result.headerVisible) fail(scope, 'header is not visible');
  if (!result.footerVisible) fail(scope, 'footer is not visible');
}

/**
 * Audit one page/viewport pair: load, assert, screenshot, push to report.pages.
 */
export async function auditGovernedViewport({
  port,
  pagePath,
  expectedH1,
  viewport,
  loadPage,
  baseUrl,
  expectedSha,
  evidenceDir,
  fail,
  report,
}) {
  const scope = `${pagePath} ${viewport.name}`;
  const result = { path: pagePath, viewport: viewport.name };
  let session;
  try {
    session = await loadPage(port, `${baseUrl}${pagePath}`, viewport, expectedH1);
    Object.assign(result, await pageState(session));
    recordGovernedViewportFindings(result, { scope, expectedSha, expectedH1, fail });
    const destination = path.join(evidenceDir, `${safeName(pagePath)}-${viewport.name}.png`);
    await captureFullPage(session, destination, viewport);
    result.screenshot = path.basename(destination);
    result.screenshot_bytes = fs.statSync(destination).size;
    if (result.screenshot_bytes < MIN_SCREENSHOT_BYTES) {
      fail(scope, `screenshot is unexpectedly small (${result.screenshot_bytes} bytes)`);
    }
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.pages.push(result);
}

/** Walk the full pages × viewports matrix. */
export async function auditGovernedPages({
  port,
  loadPage,
  baseUrl,
  expectedSha,
  evidenceDir,
  fail,
  report,
}) {
  for (const [pagePath, expectedH1] of pages) {
    for (const viewport of viewports) {
      await auditGovernedViewport({
        port,
        pagePath,
        expectedH1,
        viewport,
        loadPage,
        baseUrl,
        expectedSha,
        evidenceDir,
        fail,
        report,
      });
    }
  }
}

/**
 * Launch headless Chrome, run the audit callback, and always clean up.
 * @param {(port: number) => Promise<void>} callback
 * @returns {Promise<{ chromePath: string, runtimeError: string|null }>}
 */
export async function withHeadlessChrome(callback) {
  const chromePath = locateChrome();
  const port = randomInt(9300, 9800);
  const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-chrome-'));
  const chrome = spawn(chromePath, [
    '--headless=new', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu',
    '--no-first-run', '--no-default-browser-check', '--hide-scrollbars',
    `--remote-debugging-port=${port}`, `--user-data-dir=${profileDir}`, 'about:blank',
  ], { stdio: ['ignore', 'ignore', 'ignore'] });

  let runtimeError = null;
  try {
    await waitForChrome(port);
    await callback(port);
  } catch (error) {
    runtimeError = error instanceof Error ? error.message : String(error);
  } finally {
    chrome.kill('SIGTERM');
    await sleep(250);
    fs.rmSync(profileDir, { recursive: true, force: true });
  }
  return { chromePath, runtimeError };
}

export function writeVisualQaReport(evidenceDir, report) {
  fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
}

export function exitVisualQa(report, findings, expectedSha) {
  if (findings.length) {
    console.error(`VISUAL_QA_FAILED findings=${findings.length}`);
    for (const finding of findings) console.error(`- ${finding}`);
    process.exit(1);
  }
  console.log(
    `VISUAL_QA_OK pages=${report.pages.length} screenshots=${report.pages.length + 2} sha=${expectedSha}`,
  );
}
