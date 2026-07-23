#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';

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
  ['/', 'Medicina estética con criterio. Madrid.'],
  ['/casos-de-pacientes/', 'La evolución necesita contexto, no una promesa.'],
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
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
const safeName = (value) => value.replace(/^\/+|\/+$/g, '').replaceAll('/', '__') || 'home';

function locateChrome() {
  const candidates = [
    process.env.CHROME_BIN,
    'google-chrome-stable',
    'google-chrome',
    'chromium',
    'chromium-browser',
  ].filter(Boolean);
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

async function fetchWithRetry(url) {
  let lastStatus = 0;
  let lastBody = '';
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const response = await fetch(url, {
      redirect: 'follow',
      headers: { 'user-agent': userAgent, accept: 'text/html' },
    });
    lastStatus = response.status;
    lastBody = await response.text();
    if (response.status === 200 && !/(?:403\s*-\s*Forbidden)|(?:Access to this page is forbidden)/i.test(lastBody)) {
      return;
    }
    await sleep(attempt * 1500);
  }
  throw new Error(`HTTP preflight failed with status ${lastStatus}; body marker=${lastBody.slice(0, 120).replace(/\s+/g, ' ')}`);
}

class CDPSession {
  constructor(webSocketUrl) {
    this.webSocketUrl = webSocketUrl;
    this.webSocket = null;
    this.nextId = 0;
    this.pending = new Map();
    this.waiters = new Map();
  }

  async connect() {
    this.webSocket = new WebSocket(this.webSocketUrl);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Timed out opening Chrome DevTools WebSocket.')), 10000);
      this.webSocket.addEventListener('open', () => {
        clearTimeout(timer);
        resolve();
      }, { once: true });
      this.webSocket.addEventListener('error', () => {
        clearTimeout(timer);
        reject(new Error('Unable to open Chrome DevTools WebSocket.'));
      }, { once: true });
    });
    this.webSocket.addEventListener('message', (event) => this.handleMessage(event));
  }

  handleMessage(event) {
    const message = JSON.parse(String(event.data));
    if (message.id && this.pending.has(message.id)) {
      const pending = this.pending.get(message.id);
      clearTimeout(pending.timer);
      this.pending.delete(message.id);
      if (message.error) {
        pending.reject(new Error(`${message.error.message || 'CDP error'} (${message.error.code || 'unknown'})`));
      } else {
        pending.resolve(message.result || {});
      }
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
      const timer = setTimeout(() => {
        const queue = this.waiters.get(method) || [];
        this.waiters.set(method, queue.filter((entry) => entry.timer !== timer));
        reject(new Error(`CDP event timed out: ${method}`));
      }, timeoutMilliseconds);
      const queue = this.waiters.get(method) || [];
      queue.push({ resolve, reject, timer });
      this.waiters.set(method, queue);
    });
  }

  async evaluate(expression, awaitPromise = true) {
    const result = await this.send('Runtime.evaluate', {
      expression,
      returnByValue: true,
      awaitPromise,
    });
    if (result.exceptionDetails) {
      throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text || 'Browser evaluation failed.');
    }
    return result.result?.value;
  }

  call(functionSource, ...args) {
    const serializedArgs = args.map((value) => JSON.stringify(value)).join(',');
    return this.evaluate(`(${functionSource})(${serializedArgs})`);
  }

  closeSocket() {
    if (this.webSocket && this.webSocket.readyState <= 1) this.webSocket.close();
  }
}

async function waitForChrome(port) {
  for (let attempt = 0; attempt < 80; attempt += 1) {
    try {
      const response = await fetch(`http://127.0.0.1:${port}/json/version`);
      if (response.ok) return;
    } catch {
      // Chrome may still be starting.
    }
    await sleep(250);
  }
  throw new Error('Chrome DevTools endpoint did not become ready.');
}

async function createTarget(port) {
  const response = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent('about:blank')}`, { method: 'PUT' });
  if (!response.ok) throw new Error(`Unable to create Chrome target: HTTP ${response.status}`);
  return response.json();
}

async function closeSession(session) {
  if (!session) return;
  try {
    await session.send('Page.close', {}, 3000);
  } catch {
    // The target may already be closed.
  }
  session.closeSocket();
}

async function loadPage(port, url, viewport) {
  const target = await createTarget(port);
  const session = new CDPSession(target.webSocketDebuggerUrl);
  await session.connect();
  await session.send('Page.enable');
  await session.send('Runtime.enable');
  await session.send('Network.enable');
  await session.send('Network.setUserAgentOverride', {
    userAgent,
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

  const loaded = session.waitFor('Page.loadEventFired');
  const navigation = await session.send('Page.navigate', { url });
  if (navigation.errorText) throw new Error(`Navigation failed: ${navigation.errorText}`);
  await loaded;
  await session.evaluate(`new Promise((resolve) => {
    const finish = () => setTimeout(resolve, 700);
    if (document.fonts?.ready) document.fonts.ready.then(finish, finish); else finish();
  })`);
  return session;
}

async function pageState(session) {
  return session.evaluate(String.raw`(() => {
    const text = (document.body?.innerText || '').replace(/\s+/g, ' ').trim();
    const h1 = Array.from(document.querySelectorAll('h1')).map((node) => node.textContent.trim());
    const header = document.querySelector('#nvx-header');
    const footer = document.querySelector('footer');
    const primaryCta = document.querySelector('#nvx-header-cta, .nvx-header__cta');
    const documentWidth = document.documentElement.scrollWidth;
    const viewportWidth = document.documentElement.clientWidth;
    return {
      url: location.href,
      title: document.title,
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      h1,
      forbidden: /(?:403\s*-\s*Forbidden)|(?:Access to this page is forbidden)/i.test(text),
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

async function prepareFullPage(session) {
  await session.evaluate(`new Promise(async (resolve) => {
    const max = Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight || 0);
    for (let y = 0; y < max; y += 700) {
      window.scrollTo(0, y);
      await new Promise((next) => setTimeout(next, 35));
    }
    window.scrollTo(0, 0);
    setTimeout(resolve, 250);
  })`);
}

async function captureFullPage(session, destination, viewport) {
  await prepareFullPage(session);
  const metrics = await session.send('Page.getLayoutMetrics');
  const size = metrics.cssContentSize || metrics.contentSize;
  const fullHeight = Math.max(viewport.height, Math.min(Math.ceil(size.height), 20000));
  const fullWidth = viewport.width;
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
  });
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

/**
 * Captures the current viewport as a PNG file.
 * @param {object} session - The CDP session used to capture the screenshot.
 * @param {string} destination - The path where the PNG file is written.
 */
async function captureViewport(session, destination) {
  const screenshot = await session.send('Page.captureScreenshot', {
    format: 'png',
    fromSurface: true,
  });
  fs.writeFileSync(destination, Buffer.from(screenshot.data, 'base64'));
}

/**
 * Audits the Equipo Médico page's editorial structure and responsive layout.
 * @param {CDPSession} session - The browser session used to inspect the page.
 * @param {{mobile: boolean}} viewport - The viewport configuration being audited.
 * @param {string} scope - The finding scope used for audit failures.
 * @param {Object} result - The report object to receive the editorial audit state.
 */
async function auditEquipoEditorial(session, viewport, scope, result) {
  const state = await session.evaluate(String.raw`(() => {
    const columnCount = (selector) => {
      const nodes = Array.from(document.querySelectorAll(selector));
      if (!nodes.length) return 0;
      const counts = nodes.map(node => {
        const value = getComputedStyle(node).gridTemplateColumns.trim();
        return value && value !== 'none' ? value.split(/\s+/).length : 0;
      });
      const uniqueCounts = [...new Set(counts)];
      return uniqueCounts.length === 1 ? uniqueCounts[0] : -1;
    };
    const allClasses = Array.from(document.querySelectorAll('[class]'))
      .flatMap((node) => Array.from(node.classList));
    const crossed = allClasses.filter((name) =>
      /^nvx-endolift-(?:section|kicker|heading|body|diagnosis|panel|editorial|hero)/.test(name) ||
      /^nvx-endolaser-zone/.test(name)
    );
    return {
      stylesheet: Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .some((node) => /nvx-editorial-coherence\.css/.test(node.href)),
      editorialSections: document.querySelectorAll('.nvx-editorial-section').length,
      gridLists: document.querySelectorAll('.nvx-editorial-grid-list').length,
      factLists: document.querySelectorAll('.nvx-editorial-fact-list').length,
      profiles: document.querySelectorAll('.nvx-equipo-profile-layout').length,
      gridListColumns: columnCount('.nvx-editorial-grid-list'),
      factListColumns: columnCount('.nvx-editorial-fact-list'),
      profileColumns: columnCount('.nvx-equipo-profile-layout'),
      crossed: [...new Set(crossed)],
    };
  })()`);
  result.equipo_editorial = state;
  if (!state.stylesheet) fail(scope, 'editorial coherence stylesheet is not loaded');
  if (state.crossed.length) fail(scope, `crossed treatment classes remain in DOM: ${state.crossed.join(', ')}`);
  if (state.editorialSections < 6) fail(scope, `expected at least 6 editorial sections, found ${state.editorialSections}`);
  if (!state.gridLists || !state.factLists || !state.profiles) fail(scope, 'missing editorial grid, fact list or profile layout');
  const expectedColumns = viewport.mobile ? 1 : 2;
  for (const [name, count] of [
    ['editorial grid list', state.gridListColumns],
    ['editorial fact list', state.factListColumns],
    ['medical profile', state.profileColumns],
  ]) {
    if (count !== expectedColumns) fail(scope, `${name} has ${count} columns; expected ${expectedColumns}`);
  }
}

/**
 * Audits a page at a single viewport and records its state and evidence.
 * @param {number} port - The Chrome remote debugging port.
 * @param {string} pagePath - The page path to audit.
 * @param {string} expectedH1 - The expected page heading.
 * @param {Object} viewport - The viewport configuration used for loading and capturing the page.
 */
async function auditSingleViewport(port, pagePath, expectedH1, viewport) {
  const scope = `${pagePath} ${viewport.name}`;
  const result = { path: pagePath, viewport: viewport.name };
  let session;
  try {
    session = await loadPage(port, `${baseUrl}${pagePath}`, viewport);
    Object.assign(result, await pageState(session));
    if (result.forbidden) fail(scope, 'rendered a 403 Forbidden page');
    if (result.deploySha !== expectedSha) fail(scope, `served SHA ${result.deploySha || 'absent'} instead of ${expectedSha}`);
    if (result.h1.length !== 1 || result.h1[0] !== expectedH1) fail(scope, `H1 mismatch: ${JSON.stringify(result.h1)}`);
    if (result.overflow > 2) fail(scope, `horizontal overflow is ${result.overflow}px`);
    if (!result.headerVisible) fail(scope, 'header is not visible');
    if (!result.footerVisible) fail(scope, 'footer is not visible');

    if (pagePath === '/equipo-medico/') await auditEquipoEditorial(session, viewport, scope, result);

    if (['/nosotros/', '/equipo-medico/', '/contacto/', '/por-que-nuvanx/'].includes(pagePath)) {
      const hasTreatmentInjection = await session.evaluate(`(() => {
         return !!document.querySelector('.nvx-endolift-process, .nvx-endolift-effects, .nvx-endolaser-zone, .nvx-editorial-effects, .nvx-editorial-price-table-wrap');
      })()`);
      if (hasTreatmentInjection) fail(scope, `institutional page incorrectly received treatment block injections`);
    }

    const destination = path.join(evidenceDir, `${safeName(pagePath)}-${viewport.name}.png`);
    await captureFullPage(session, destination, viewport);
    result.screenshot = path.basename(destination);
    result.screenshot_bytes = fs.statSync(destination).size;
    if (result.screenshot_bytes < 15000) fail(scope, `screenshot is unexpectedly small (${result.screenshot_bytes} bytes)`);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.pages.push(result);
}

async function auditPages(port) {
  const viewports = [
    { name: 'desktop', width: 1440, height: 1000, mobile: false },
    { name: 'mobile', width: 390, height: 844, mobile: true },
  ];
  for (const [pagePath, expectedH1] of pages) {
    await fetchWithRetry(`${baseUrl}${pagePath}`);
    for (const viewport of viewports) {
      await auditSingleViewport(port, pagePath, expectedH1, viewport);
    }
  }
}

async function auditDesktopNavigation(port) {
  const scope = 'desktop mega-menu';
  const result = {};
  let session;
  try {
    await fetchWithRetry(`${baseUrl}/`);
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
    const required = ['INICIO', 'SOLUCIONES MÉDICAS', 'PROTOCOLOS SIGNATURE', 'TECNOLOGÍA', 'CASOS CLÍNICOS', 'EQUIPO MÉDICO', 'CLÍNICAS', 'JOURNAL', 'CONTACTO'];
    for (const label of required) if (!initial.links.includes(label)) fail(scope, `missing top-level item: ${label}`);
    if (!initial.x || !initial.y) throw new Error('Protocolos Signature desktop link was not found.');

    await session.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: initial.x, y: initial.y });
    await sleep(500);
    const opened = await session.evaluate(String.raw`(() => {
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
        text: submenu.innerText.replace(/\s+/g, ' ').trim(),
        overflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      };
    })()`);
    Object.assign(result, opened);
    if (!opened.visible) fail(scope, 'Protocolos Signature submenu did not become visible on hover');
    for (const label of ['NUVANX Contour Architecture™', 'NUVANX Post-Maternity Contour™', 'NUVANX Profile Definition™', 'NUVANX Skin Architecture™', 'NUVANX Surface Renewal™', 'NUVANX Tone Correction™']) {
      if (!opened.text.includes(label)) fail(scope, `submenu missing: ${label}`);
    }
    for (const forbidden of ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame']) {
      if (opened.text.includes(forbidden)) fail(scope, `submenu exposes retired label: ${forbidden}`);
    }
    if (opened.overflow > 2) fail(scope, `horizontal overflow is ${opened.overflow}px`);

    const destination = path.join(evidenceDir, 'navigation-desktop-mega.png');
    await captureViewport(session, destination);
    result.screenshot = path.basename(destination);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  } finally {
    await closeSession(session);
  }
  report.navigation.desktop = result;
}

async function openMobileAccordion(session, linkPattern, parentSubmenuClass = null) {
  return session.call((pattern, parentClass) => {
    const nav = document.getElementById('nvx-mobile-nav');
    const regex = new RegExp(pattern, 'i');
    let scopeElement = nav?.querySelector('.nvx-mobile-nav__list');
    if (parentClass) {
      const parentItems = Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li') || []);
      const parentItem = parentItems.find((node) => {
        const link = Array.from(node.children).find((child) => child.tagName === 'A');
        return link && /protocolos signature/i.test(link.textContent);
      });
      scopeElement = parentItem ? Array.from(parentItem.children).find((child) => child.classList?.contains(parentClass)) : null;
    }
    const items = Array.from(scopeElement?.querySelectorAll(':scope > li') || []);
    const item = items.find((node) => {
      const link = Array.from(node.children).find((child) => child.tagName === 'A');
      return link && regex.test(link.textContent);
    });
    const toggle = item ? Array.from(item.children).find((child) => child.classList?.contains('nvx-mobile-nav__toggle')) : null;
    toggle?.click();
    return Boolean(toggle);
  }, linkPattern, parentSubmenuClass);
}

async function auditMobileNavigation(port) {
  const scope = 'mobile drawer';
  const result = {};
  let session;
  try {
    session = await loadPage(port, `${baseUrl}/`, { width: 390, height: 844, mobile: true });
    await session.evaluate(`(() => {
      document.getElementById('nvx-hamburger-btn')?.focus();
      document.getElementById('nvx-hamburger-btn')?.click();
    })()`);
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

    const signatureOpened = await openMobileAccordion(session, 'protocolos signature');
    if (!signatureOpened) throw new Error('Protocolos Signature mobile accordion toggle was not found.');
    await sleep(300);
    const contourOpened = await openMobileAccordion(session, 'contour architecture', 'sub-menu');
    if (!contourOpened) throw new Error('Contour Architecture nested mobile toggle was not found.');
    await sleep(350);

    const state = await session.evaluate(String.raw`(() => {
      const nav = document.getElementById('nvx-mobile-nav');
      const text = nav?.textContent.replace(/\s+/g, ' ').trim() || '';
      return {
        text,
        expanded: Array.from(nav?.querySelectorAll('.nvx-mobile-nav__toggle[aria-expanded="true"]') || []).length,
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
    for (const forbidden of ['Couture Sculpt', 'Contour Sculpt', 'Eye Frame']) {
      if (state.text.includes(forbidden)) fail(scope, `drawer exposes retired label: ${forbidden}`);
    }
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
    await closeSession(session);
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
  `--remote-debugging-port=${port}`,
  `--user-data-dir=${profileDir}`,
  'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });

let runtimeError = null;
try {
  await waitForChrome(port);
  await auditPages(port);
  await auditDesktopNavigation(port);
  await auditMobileNavigation(port);
} catch (error) {
  runtimeError = error instanceof Error ? error.message : String(error);
  fail('visual QA runtime', runtimeError);
} finally {
  chrome.kill('SIGTERM');
  await sleep(250);
  fs.rmSync(profileDir, { recursive: true, force: true });
}

report.runtime_error = runtimeError;
fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));

if (findings.length) {
  console.error(`VISUAL_QA_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}

console.log(`VISUAL_QA_OK pages=${report.pages.length} screenshots=${report.pages.length + 2} sha=${expectedSha}`);
