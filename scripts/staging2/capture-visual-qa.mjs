#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-visual-qa';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';

if (baseUrl !== 'https://staging2.nuvanx.com') {
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
fs.mkdirSync(evidenceDir, { recursive: true });

const pages = [
  ['/soluciones-medicas/', 'Soluciones médicas para rostro, piel y contorno corporal.'],
  ['/protocolos-signature/', 'Protocolos Signature: Medicina estética de diagnóstico.'],
  ['/remodelacion-corporal-laser-madrid/', 'Remodelación corporal láser diseñada según tu anatomía.'],
  ['/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid'],
  ['/papada-definicion-mandibular-madrid/', 'Papada y definición mandibular en Madrid'],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Calidad, firmeza y luminosidad de la piel en Madrid'],
  ['/cicatrices-acne-poros-textura-madrid/', 'Cicatrices de acné, poros y textura en Madrid'],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Manchas, rojeces y fotodaño en Madrid'],
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Grasa localizada en abdomen y flancos en Madrid'],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Flacidez y grasa localizada en brazos en Madrid'],
  ['/grasa-espalda-zona-sujetador-madrid/', 'Grasa de espalda y zona del sujetador en Madrid'],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'Flacidez en muslos internos y región subglútea en Madrid'],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Grasa localizada y flacidez en rodillas en Madrid'],
  ['/contorno-corporal-masculino-madrid/', 'Contorno corporal masculino en Madrid'],
  ['/por-que-nuvanx/', 'Por qué NUVANX. Sin retórica de marketing.'],
  ['/inversion-medicina-estetica/', 'El presupuesto forma parte de una decisión informada.'],
];

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
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Locates an installed Google Chrome or Chromium executable.
 * @returns {string} The executable path.
 * @throws {Error} If no supported browser executable is found.
 */
function locateChrome() {
  const candidates = [process.env.CHROME_BIN, 'google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser'].filter(Boolean);
  for (const candidate of candidates) {
    if (candidate.includes(path.sep) && fs.existsSync(candidate)) return candidate;
    const found = spawnSync('which', [candidate], { encoding: 'utf8' });
    if (found.status === 0 && found.stdout.trim()) return found.stdout.trim();
  }
  throw new Error('Google Chrome or Chromium is not installed on the runner.');
}

/**
 * Fetch a page and retry when the response is unsuccessful or forbidden.
 * @param {string} url - The URL to fetch.
 * @returns {Promise<{status: number, body: string}>} The successful response status and body.
 * @throws {Error} If all fetch attempts fail.
 */
async function fetchWithRetry(url) {
  let lastStatus = 0;
  let lastBody = '';
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const response = await fetch(url, { redirect: 'follow', headers: { 'user-agent': userAgent, accept: 'text/html' } });
    lastStatus = response.status;
    lastBody = await response.text();
    if (response.status === 200 && !/403\s*-\s*Forbidden|Access to this page is forbidden/i.test(lastBody)) {
      return { status: response.status, body: lastBody };
    }
    await sleep(attempt * 1500);
  }
  throw new Error(`HTTP preflight failed with status ${lastStatus}; body marker=${lastBody.slice(0, 120).replace(/\s+/g, ' ')}`);
}

class CDPSession {
  constructor(url) {
    this.url = url;
    this.ws = null;
    this.nextId = 0;
    this.pending = new Map();
    this.waiters = new Map();
  }

  async connect() {
    this.ws = new WebSocket(this.url);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Timed out opening Chrome DevTools WebSocket.')), 10000);
      this.ws.addEventListener('open', () => { clearTimeout(timer); resolve(); }, { once: true });
      this.ws.addEventListener('error', () => { clearTimeout(timer); reject(new Error('Unable to open Chrome DevTools WebSocket.')); }, { once: true });
    });
    this.ws.addEventListener('message', (event) => this.onMessage(event));
  }

  onMessage(event) {
    const message = JSON.parse(String(event.data));
    if (message.id && this.pending.has(message.id)) {
      const { resolve, reject, timer } = this.pending.get(message.id);
      clearTimeout(timer);
      this.pending.delete(message.id);
      if (message.error) reject(new Error(`${message.error.message || 'CDP error'} (${message.error.code || 'unknown'})`));
      else resolve(message.result || {});
      return;
    }
    if (message.method && this.waiters.has(message.method)) {
      const queue = this.waiters.get(message.method);
      const waiter = queue.shift();
      if (!queue.length) this.waiters.delete(message.method);
      clearTimeout(waiter.timer);
      waiter.resolve(message.params || {});
    }
  }

  send(method, params = {}, timeoutMs = 15000) {
    const id = ++this.nextId;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pending.delete(id);
        reject(new Error(`CDP command timed out: ${method}`));
      }, timeoutMs);
      this.pending.set(id, { resolve, reject, timer });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  waitFor(method, timeoutMs = 20000) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        const queue = this.waiters.get(method) || [];
        this.waiters.set(method, queue.filter((entry) => entry.timer !== timer));
        reject(new Error(`CDP event timed out: ${method}`));
      }, timeoutMs);
      const queue = this.waiters.get(method) || [];
      queue.push({ resolve, reject, timer });
      this.waiters.set(method, queue);
    });
  }

  async evaluate(expression, awaitPromise = true) {
    const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Browser evaluation failed.');
    return result.result?.value;
  }

  close() {
    if (this.ws && this.ws.readyState <= 1) this.ws.close();
  }
}

/**
 * Wait for the Chrome DevTools endpoint to become available.
 * @param {number} port - The local port hosting the DevTools endpoint.
 * @return {Promise<Object>} The endpoint metadata.
 * @throws {Error} If the endpoint does not become ready.
 */
async function waitForChrome(port) {
  for (let attempt = 0; attempt < 80; attempt += 1) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (response.ok) return await response.json();
    } catch {}
    await sleep(250);
  }
  throw new Error('Chrome DevTools endpoint did not become ready.');
}

/**
 * Creates a Chrome DevTools target for the specified URL.
 * @param {number} port - The local Chrome DevTools debugging port.
 * @param {string} url - The URL to open in the target.
 * @return {Promise<Object>} The target details returned by Chrome.
 * @throws {Error} If Chrome rejects the target creation request.
 */
async function createTarget(port, url) {
  const response = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent(url)}`, { method: 'PUT' });
  if (!response.ok) throw new Error(`Unable to create Chrome target: HTTP ${response.status}`);
  return response.json();
}

/**
 * Loads a page in a configured Chrome DevTools Protocol session.
 * @param {number} port - The local Chrome DevTools port.
 * @param {string} url - The page URL to navigate to.
 * @param {{width: number, height: number, mobile: boolean}} viewport - Viewport dimensions and device mode.
 * @returns {CDPSession} The connected session for the loaded page.
 */
async function loadPage(port, url, viewport) {
  const target = await createTarget(port, 'about:blank');
  const session = new CDPSession(target.webSocketDebuggerUrl);
  await session.connect();
  await session.send('Page.enable');
  await session.send('Runtime.enable');
  await session.send('Network.enable');
  await session.send('Network.setUserAgentOverride', { userAgent, platform: viewport.mobile ? 'Android' : 'Windows' });
  await session.send('Emulation.setDeviceMetricsOverride', {
    width: viewport.width,
    height: viewport.height,
    deviceScaleFactor: 1,
    mobile: viewport.mobile,
    screenWidth: viewport.width,
    screenHeight: viewport.height,
  });
  const loaded = session.waitFor('Page.loadEventFired', 30000);
  const navigation = await session.send('Page.navigate', { url }, 30000);
  if (navigation.errorText) throw new Error(`Navigation failed: ${navigation.errorText}`);
  await loaded;
  await session.evaluate(`new Promise((resolve) => {
    const finish = () => setTimeout(resolve, 600);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(finish, finish); else finish();
  })`);
  return session;
}

/**
 * Extract the current page metadata, content markers, and layout state.
 * @param {CDPSession} session - The active Chrome DevTools Protocol session.
 * @return {Promise<Object>} The page URL, title, H1 texts, forbidden-page status, layout measurements, visibility states, and body class.
 */
async function pageState(session) {
  return session.evaluate(`(() => {
    const text = (document.body?.innerText || '').replace(/\\s+/g, ' ').trim();
    const h1 = Array.from(document.querySelectorAll('h1')).map((node) => node.textContent.trim());
    const header = document.querySelector('#nvx-header');
    const footer = document.querySelector('footer');
    const primaryCta = document.querySelector('#nvx-header-cta, .nvx-header__cta');
    const documentWidth = document.documentElement.scrollWidth;
    const viewportWidth = document.documentElement.clientWidth;
    return {
      url: location.href,
      title: document.title,
      h1,
      forbidden: /403\\s*-\\s*Forbidden|Access to this page is forbidden/i.test(text),
      documentWidth,
      viewportWidth,
      overflow: Math.max(0, documentWidth - viewportWidth),
      contentHeight: Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0),
      headerVisible: !!header && getComputedStyle(header).display !== 'none' && header.getBoundingClientRect().height > 0,
      footerVisible: !!footer && getComputedStyle(footer).display !== 'none' && footer.getBoundingClientRect().height > 0,
      ctaVisible: !!primaryCta && getComputedStyle(primaryCta).display !== 'none',
      bodyClass: document.body?.className || '',
    };
  })()`);
}

/**
 * Captures the page's full rendered content as a PNG file.
 * @param {CDPSession} session - The active Chrome DevTools Protocol session.
 * @param {string} destination - The file path for the PNG screenshot.
 * @param {{width: number, height: number, mobile: boolean}} viewport - The viewport dimensions and device mode.
 */
async function captureFullPage(session, destination, viewport) {
  const metrics = await session.send('Page.getLayoutMetrics');
  const size = metrics.cssContentSize || metrics.contentSize;
  const fullHeight = Math.max(viewport.height, Math.min(Math.ceil(size.height), 20000));
  const fullWidth = Math.max(viewport.width, Math.min(Math.ceil(size.width), viewport.width));
  await session.send('Emulation.setDeviceMetricsOverride', {
    width: viewport.width,
    height: fullHeight,
    deviceScaleFactor: 1,
    mobile: viewport.mobile,
    screenWidth: viewport.width,
    screenHeight: fullHeight,
  });
  await sleep(200);
  const screenshot = await session.send('Page.captureScreenshot', {
    format: 'png',
    fromSurface: true,
    captureBeyondViewport: true,
    clip: { x: 0, y: 0, width: fullWidth, height: fullHeight, scale: 1 },
  }, 30000);
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

/**
 * Captures the current viewport as a PNG image and saves it to a file.
 * @param {CDPSession} session - The active Chrome DevTools Protocol session.
 * @param {string} destination - The file path for the captured image.
 */
async function captureViewport(session, destination) {
  const screenshot = await session.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

/**
 * Converts a URL path into a filesystem-friendly name.
 * @param {string} pagePath - The URL path to convert.
 * @return {string} The path with slashes replaced by double underscores, or `home` for the root path.
 */
function safeName(pagePath) {
  return pagePath.replace(/^\/+|\/+$/g, '').replaceAll('/', '__') || 'home';
}

/**
 * Audit configured pages across desktop and mobile viewports and record visual evidence.
 * @param {number} port - The local Chrome DevTools Protocol port.
 */
async function auditPages(port) {
  const viewports = [
    { name: 'desktop', width: 1440, height: 1000, mobile: false },
    { name: 'mobile', width: 390, height: 844, mobile: true },
  ];
  for (const [pagePath, expectedH1] of pages) {
    await fetchWithRetry(`${baseUrl}${pagePath}`);
    for (const viewport of viewports) {
      const scope = `${pagePath} ${viewport.name}`;
      const result = { path: pagePath, viewport: viewport.name };
      let session;
      try {
        session = await loadPage(port, `${baseUrl}${pagePath}`, viewport);
        Object.assign(result, await pageState(session));
        if (result.forbidden) fail(scope, 'rendered a 403 Forbidden page');
        if (result.h1.length !== 1 || result.h1[0] !== expectedH1) fail(scope, `H1 mismatch: ${JSON.stringify(result.h1)}`);
        if (result.overflow > 2) fail(scope, `horizontal overflow is ${result.overflow}px`);
        if (!result.headerVisible) fail(scope, 'header is not visible');
        if (!result.footerVisible) fail(scope, 'footer is not visible');
        const destination = path.join(evidenceDir, `${safeName(pagePath)}-${viewport.name}.png`);
        await captureFullPage(session, destination, viewport);
        result.screenshot = path.basename(destination);
        result.screenshot_bytes = fs.statSync(destination).size;
        if (result.screenshot_bytes < 15000) fail(scope, `screenshot is unexpectedly small (${result.screenshot_bytes} bytes)`);
      } catch (error) {
        fail(scope, error instanceof Error ? error.message : String(error));
      } finally {
        session?.close();
      }
      report.pages.push(result);
    }
  }
}

/**
 * Audits the desktop mega-menu navigation and captures its visual state.
 * @param {number} port - The Chrome DevTools Protocol port.
 */
async function auditDesktopNavigation(port) {
  const scope = 'desktop mega-menu';
  const result = {};
  let session;
  try {
    session = await loadPage(port, `${baseUrl}/`, { width: 1440, height: 1000, mobile: false });
    const initial = await session.evaluate(`(() => {
      const links = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li > a')).map((node) => node.textContent.trim().toUpperCase());
      const item = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li')).find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /protocolos signature/i.test(link.textContent);
      });
      const link = item ? Array.from(item.children).find((child) => child.tagName === 'A') : null;
      const rect = link?.getBoundingClientRect();
      return { links, x: rect ? rect.left + rect.width / 2 : 0, y: rect ? rect.top + rect.height / 2 : 0 };
    })()`);
    result.top_level_links = initial.links;
    const required = ['INICIO', 'SOLUCIONES', 'PROTOCOLOS SIGNATURE', 'TECNOLOGÍA', 'CASOS CLÍNICOS', 'EQUIPO MÉDICO', 'CLÍNICAS', 'JOURNAL', 'CONTACTO'];
    for (const label of required) if (!initial.links.includes(label)) fail(scope, `missing top-level item: ${label}`);
    if (!initial.x || !initial.y) throw new Error('Protocolos Signature desktop link was not found.');
    await session.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: initial.x, y: initial.y });
    await sleep(500);
    const opened = await session.evaluate(`(() => {
      const item = Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li')).find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /protocolos signature/i.test(link.textContent);
      });
      const submenu = item ? Array.from(item.children).find((child) => child.classList?.contains('sub-menu')) : null;
      if (!submenu) return { visible: false, text: '', overflow: 999 };
      const style = getComputedStyle(submenu);
      const rect = submenu.getBoundingClientRect();
      return {
        visible: style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity || 1) > 0 && rect.width > 0 && rect.height > 0,
        text: submenu.innerText.replace(/\\s+/g, ' ').trim(),
        overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      };
    })()`);
    Object.assign(result, opened);
    if (!opened.visible) fail(scope, 'Protocolos Signature submenu did not become visible on hover');
    for (const label of ['NUVANX Contour Architecture™', 'NUVANX Post-Maternity Contour™', 'NUVANX Profile Definition™', 'NUVANX Skin Architecture™', 'NUVANX Surface Renewal™', 'NUVANX Tone Correction™']) {
      if (!opened.text.includes(label)) fail(scope, `submenu missing: ${label}`);
    }
    for (const forbidden of ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame']) if (opened.text.includes(forbidden)) fail(scope, `submenu exposes retired label: ${forbidden}`);
    if (opened.overflow > 2) fail(scope, `horizontal overflow is ${opened.overflow}px`);
    const destination = path.join(evidenceDir, 'navigation-desktop-mega.png');
    await captureViewport(session, destination);
    result.screenshot = path.basename(destination);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    session?.close();
  }
  report.navigation.desktop = result;
}

/**
 * Audits mobile navigation behavior, accessibility state, nested menu content, layout, and drawer closing.
 * @param {number} port - The local Chrome DevTools Protocol port.
 */
async function auditMobileNavigation(port) {
  const scope = 'mobile drawer';
  const result = {};
  let session;
  try {
    session = await loadPage(port, `${baseUrl}/`, { width: 390, height: 844, mobile: true });
    await session.evaluate(`document.getElementById('nvx-hamburger-btn')?.click()`);
    await sleep(250);
    const drawer = await session.evaluate(`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const button = document.getElementById('nvx-hamburger-btn');
      return {
        open: !!nav && nav.classList.contains('is-open') && nav.getAttribute('aria-hidden') === 'false',
        expanded: button?.getAttribute('aria-expanded'),
        activeId: document.activeElement?.id || '',
      };
    })()`);
    Object.assign(result, drawer);
    if (!drawer.open || drawer.expanded !== 'true') fail(scope, 'hamburger did not open the drawer with correct ARIA state');
    if (drawer.activeId !== 'nvx-mobile-close') fail(scope, `focus did not move to close button; active=${drawer.activeId || 'none'}`);

    const signatureOpened = await session.evaluate(`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const item = Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li') || []).find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /protocolos signature/i.test(link.textContent);
      });
      const toggle = item ? Array.from(item.children).find((child) => child.classList?.contains('nvx-mobile-nav__toggle')) : null;
      toggle?.click();
      return !!toggle;
    })()`);
    if (!signatureOpened) throw new Error('Protocolos Signature mobile accordion toggle was not found.');
    await sleep(300);

    const contourOpened = await session.evaluate(`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const signature = Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li') || []).find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /protocolos signature/i.test(link.textContent);
      });
      const firstSubmenu = signature ? Array.from(signature.children).find((child) => child.classList?.contains('sub-menu')) : null;
      const contour = Array.from(firstSubmenu?.children || []).find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /contour architecture/i.test(link.textContent);
      });
      const toggle = contour ? Array.from(contour.children).find((child) => child.classList?.contains('nvx-mobile-nav__toggle')) : null;
      toggle?.click();
      return !!toggle;
    })()`);
    if (!contourOpened) throw new Error('Contour Architecture nested mobile toggle was not found.');
    await sleep(350);

    const state = await session.evaluate(`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const text = nav?.innerText.replace(/\\s+/g, ' ').trim() || '';
      const expanded = Array.from(nav?.querySelectorAll('.nvx-mobile-nav__toggle[aria-expanded="true"]') || []).length;
      return {
        text,
        expanded,
        overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
        drawerWidth: nav?.getBoundingClientRect().width || 0,
        viewportWidth: document.documentElement.clientWidth,
      };
    })()`);
    Object.assign(result, state);
    if (state.expanded < 2) fail(scope, `expected two expanded accordion levels, found ${state.expanded}`);
    for (const label of ['Abdomen y flancos', 'Brazos y axila', 'Espalda y zona del sujetador', 'Muslos y región subglútea', 'Rodillas', 'Contorno masculino']) {
      if (!state.text.includes(label)) fail(scope, `nested menu missing: ${label}`);
    }
    for (const forbidden of ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame']) if (state.text.includes(forbidden)) fail(scope, `drawer exposes retired label: ${forbidden}`);
    if (state.overflow > 2) fail(scope, `horizontal overflow is ${state.overflow}px`);
    if (state.drawerWidth > state.viewportWidth + 2) fail(scope, `drawer width ${state.drawerWidth}px exceeds viewport ${state.viewportWidth}px`);

    const destination = path.join(evidenceDir, 'navigation-mobile-drawer.png');
    await captureViewport(session, destination);
    result.screenshot = path.basename(destination);

    await session.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27, nativeVirtualKeyCode: 27 });
    await session.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27, nativeVirtualKeyCode: 27 });
    await sleep(200);
    const closed = await session.evaluate(`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const button = document.getElementById('nvx-hamburger-btn');
      return {
        closed: !!nav && !nav.classList.contains('is-open') && nav.getAttribute('aria-hidden') === 'true',
        expanded: button?.getAttribute('aria-expanded'),
        activeId: document.activeElement?.id || '',
      };
    })()`);
    result.escape_close = closed;
    if (!closed.closed || closed.expanded !== 'false') fail(scope, 'Escape did not close drawer and reset ARIA state');
    if (closed.activeId !== 'nvx-hamburger-btn') fail(scope, `focus was not restored to hamburger; active=${closed.activeId || 'none'}`);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    session?.close();
  }
  report.navigation.mobile = result;
}

const chromePath = locateChrome();
report.chrome = chromePath;
const port = 9300 + Math.floor(Math.random() * 500);
const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-chrome-'));
const chrome = spawn(chromePath, [
  '--headless=new',
  '--no-sandbox',
  '--disable-dev-shm-usage',
  '--disable-gpu',
  '--no-first-run',
  '--no-default-browser-check',
  '--hide-scrollbars',
  '--remote-allow-origins=*',
  `--remote-debugging-port=${port}`,
  `--user-data-dir=${profileDir}`,
  `--user-agent=${userAgent}`,
  'about:blank',
], { stdio: ['ignore', 'pipe', 'pipe'] });
let chromeStderr = '';
chrome.stderr.on('data', (chunk) => { chromeStderr += String(chunk); });

try {
  await waitForChrome(port);
  await auditPages(port);
  await auditDesktopNavigation(port);
  await auditMobileNavigation(port);
} catch (error) {
  fail('visual QA runtime', error instanceof Error ? error.message : String(error));
} finally {
  chrome.kill('SIGTERM');
  await sleep(300);
  if (!chrome.killed) chrome.kill('SIGKILL');
  fs.rmSync(profileDir, { recursive: true, force: true });
}

report.chrome_stderr_tail = chromeStderr.slice(-4000);
fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`VISUAL_QA_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`VISUAL_QA_OK pages=${pages.length} screenshots=${report.pages.length + 2} sha=${expectedSha}`);
