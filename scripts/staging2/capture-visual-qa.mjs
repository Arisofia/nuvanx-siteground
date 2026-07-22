#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-visual-qa';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/149.0.0.0 Safari/537.36';

if (baseUrl !== 'https://staging2.nuvanx.com') throw new Error(`Refusing unexpected BASE_URL: ${baseUrl}`);
if (!/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
if (typeof WebSocket !== 'function') throw new Error('Node.js WebSocket support is required.');
fs.mkdirSync(evidenceDir, { recursive: true });

const pages = [
  ['/soluciones-medicas/', 'Soluciones médicas para rostro, piel y contorno corporal.'],
  ['/protocolos-signature/', 'Protocolos Signature: Medicina estética de diagnóstico.'],
  ['/remodelacion-corporal-laser-madrid/', 'Remodelación corporal láser diseñada según tu anatomía.'],
  ['/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid'],
  ['/papada-definicion-mandibular-madrid/', 'Papada y mandíbula: a veces es grasa, a veces es piel, y a veces falta hueso.'],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.'],
  ['/cicatrices-acne-poros-textura-madrid/', 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.'],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.'],
  ['/tratamiento-ojeras-bolsas-mirada-madrid/', 'No todas las ojeras son iguales. Por eso no todas se tratan igual.'],
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
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], navigation: {}, findings };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Finds an available Google Chrome or Chromium executable.
 * @returns {string} The path to the first available browser executable.
 * @throws {Error} If no supported browser executable is found.
 */
function locateChrome() {
  for (const candidate of [process.env.CHROME_BIN, 'google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser'].filter(Boolean)) {
    if (candidate.includes(path.sep) && fs.existsSync(candidate)) return candidate;
    const found = spawnSync('which', [candidate], { encoding: 'utf8' });
    if (found.status === 0 && found.stdout.trim()) return found.stdout.trim();
  }
  throw new Error('Google Chrome or Chromium is not installed on the runner.');
}

/**
 * Verifies that a URL serves an accessible HTML page.
 * @param {string} url - The URL to check.
 * @throws {Error} If the URL does not return an acceptable response after four attempts.
 */
async function preflight(url) {
  let status = 0;
  let body = '';
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const response = await fetch(url, { redirect: 'follow', headers: { 'user-agent': userAgent, accept: 'text/html' } });
    status = response.status;
    body = await response.text();
    if (status === 200 && !/403\s*-\s*Forbidden|Access to this page is forbidden/i.test(body)) return;
    await sleep(attempt * 1200);
  }
  throw new Error(`HTTP preflight failed: status=${status} body=${body.slice(0, 100).replace(/\s+/g, ' ')}`);
}

class CDP {
  constructor(url) { this.url = url; this.ws = null; this.id = 0; this.pending = new Map(); this.waiters = new Map(); }
  async connect() {
    this.ws = new WebSocket(this.url);
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Chrome DevTools WebSocket timeout.')), 10000);
      this.ws.addEventListener('open', () => { clearTimeout(timer); resolve(); }, { once: true });
      this.ws.addEventListener('error', () => { clearTimeout(timer); reject(new Error('Chrome DevTools WebSocket failed.')); }, { once: true });
    });
    this.ws.addEventListener('message', (event) => this.receive(event));
  }
  receive(event) {
    const message = JSON.parse(String(event.data));
    if (message.id && this.pending.has(message.id)) {
      const pending = this.pending.get(message.id); this.pending.delete(message.id); clearTimeout(pending.timer);
      if (message.error) pending.reject(new Error(message.error.message || 'CDP error'));
      else pending.resolve(message.result || {});
      return;
    }
    const queue = this.waiters.get(message.method);
    if (queue?.length) {
      const waiter = queue.shift(); clearTimeout(waiter.timer); waiter.resolve(message.params || {});
      if (!queue.length) this.waiters.delete(message.method);
    }
  }
  send(method, params = {}, timeoutMs = 20000) {
    const id = ++this.id;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => { this.pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, timeoutMs);
      this.pending.set(id, { resolve, reject, timer });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }
  wait(method, timeoutMs = 30000) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error(`CDP event timeout: ${method}`)), timeoutMs);
      const queue = this.waiters.get(method) || []; queue.push({ resolve, reject, timer }); this.waiters.set(method, queue);
    });
  }
  async evaluate(expression) {
    const result = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Browser evaluation failed.');
    return result.result?.value;
  }
  close() { if (this.ws && this.ws.readyState <= 1) this.ws.close(); }
}

/**
 * Waits for the Chrome DevTools endpoint to become available.
 * @param {number} port - The local debugging port used by Chrome.
 * @throws {Error} If the endpoint is not ready after the polling period.
 */
async function waitForChrome(port) {
  for (let i = 0; i < 80; i += 1) {
    try { if ((await fetch(`http://127.0.0.1:${port}/json/version`)).ok) return; } catch {}
    await sleep(250);
  }
  throw new Error('Chrome DevTools endpoint did not become ready.');
}

/**
 * Opens a page in a configured Chrome target and waits for it to finish loading.
 * @param {number} port - The Chrome remote debugging port.
 * @param {string} url - The page URL to navigate to.
 * @param {{width: number, height: number, mobile: boolean}} viewport - The viewport dimensions and device mode.
 * @returns {CDP} The connected CDP session for the page.
 */
async function openPage(port, url, viewport) {
  const targetResponse = await fetch(`http://127.0.0.1:${port}/json/new?${encodeURIComponent('about:blank')}`, { method: 'PUT' });
  if (!targetResponse.ok) throw new Error(`Unable to create Chrome target: HTTP ${targetResponse.status}`);
  const target = await targetResponse.json();
  const session = new CDP(target.webSocketDebuggerUrl);
  await session.connect();
  await session.send('Page.enable'); await session.send('Runtime.enable'); await session.send('Network.enable');
  await session.send('Network.setUserAgentOverride', { userAgent, platform: viewport.mobile ? 'Android' : 'Windows' });
  await session.send('Emulation.setDeviceMetricsOverride', { width: viewport.width, height: viewport.height, deviceScaleFactor: 1, mobile: viewport.mobile, screenWidth: viewport.width, screenHeight: viewport.height });
  const loaded = session.wait('Page.loadEventFired');
  const navigation = await session.send('Page.navigate', { url }, 30000);
  if (navigation.errorText) throw new Error(navigation.errorText);
  await loaded;
  await session.evaluate(`new Promise((resolve) => { const done=()=>setTimeout(resolve,600); document.fonts?.ready?.then(done,done) || done(); })`);
  return session;
}

/**
 * Extract key page state used for visual and structural checks.
 * @param {object} session - The CDP session used to evaluate the page.
 * @returns {Promise<object>} The page's H1 texts, access-block status, horizontal overflow, and header and footer visibility.
 */
async function state(session) {
  return session.evaluate(`(() => {
    const text=(document.body?.innerText||'').replace(/\\s+/g,' ').trim();
    const h1=Array.from(document.querySelectorAll('h1')).map((n)=>n.textContent.trim());
    const header=document.querySelector('#nvx-header'); const footer=document.querySelector('footer');
    return { h1, forbidden:/403\\s*-\\s*Forbidden|Access to this page is forbidden/i.test(text), overflow:Math.max(0,document.documentElement.scrollWidth-document.documentElement.clientWidth), headerVisible:!!header&&header.getBoundingClientRect().height>0, footerVisible:!!footer&&footer.getBoundingClientRect().height>0 };
  })()`);
}

/**
 * Captures a page screenshot and writes it as a PNG file.
 * @param {object} session - The connected CDP session.
 * @param {string} destination - The output file path.
 * @param {object} viewport - The viewport dimensions and mobile setting.
 * @param {boolean} [full=true] - Whether to capture the full page content.
 */
async function screenshot(session, destination, viewport, full = true) {
  if (full) {
    const metrics = await session.send('Page.getLayoutMetrics');
    const size = metrics.cssContentSize || metrics.contentSize;
    const height = Math.max(viewport.height, Math.min(Math.ceil(size.height), 20000));
    await session.send('Emulation.setDeviceMetricsOverride', { width: viewport.width, height, deviceScaleFactor: 1, mobile: viewport.mobile, screenWidth: viewport.width, screenHeight: height });
    const shot = await session.send('Page.captureScreenshot', { format: 'png', fromSurface: true, captureBeyondViewport: true, clip: { x: 0, y: 0, width: viewport.width, height, scale: 1 } }, 30000);
    fs.writeFileSync(destination, Buffer.from(shot.data, 'base64'));
  } else {
    const shot = await session.send('Page.captureScreenshot', { format: 'png', fromSurface: true });
    fs.writeFileSync(destination, Buffer.from(shot.data, 'base64'));
  }
}

const safe = (value) => value.replace(/^\/+|\/+$/g, '').replaceAll('/', '__') || 'home';

/**
 * Audits each configured page across desktop and mobile viewports, recording page state and screenshot evidence.
 * @param {number} port - The local Chrome remote debugging port.
 */
async function auditPages(port) {
  const viewports = [{ name: 'desktop', width: 1440, height: 1000, mobile: false }, { name: 'mobile', width: 390, height: 844, mobile: true }];
  for (const [pagePath, expectedH1] of pages) {
    await preflight(`${baseUrl}${pagePath}`);
    for (const viewport of viewports) {
      const scope = `${pagePath} ${viewport.name}`; const row = { path: pagePath, viewport: viewport.name }; let session;
      try {
        session = await openPage(port, `${baseUrl}${pagePath}`, viewport); Object.assign(row, await state(session));
        if (row.forbidden) fail(scope, 'rendered a 403 Forbidden page');
        if (row.h1.length !== 1 || row.h1[0] !== expectedH1) fail(scope, `H1 mismatch: ${JSON.stringify(row.h1)}`);
        if (row.overflow > 2) fail(scope, `horizontal overflow is ${row.overflow}px`);
        if (!row.headerVisible || !row.footerVisible) fail(scope, 'header or footer is not visible');
        const destination = path.join(evidenceDir, `${safe(pagePath)}-${viewport.name}.png`);
        await screenshot(session, destination, viewport, true); row.screenshot = path.basename(destination); row.bytes = fs.statSync(destination).size;
        if (row.bytes < 15000) fail(scope, `screenshot is unexpectedly small (${row.bytes} bytes)`);
      } catch (error) { fail(scope, error instanceof Error ? error.message : String(error)); }
      finally { session?.close(); }
      report.pages.push(row);
    }
  }
}

/**
 * Audits the desktop mega-menu for expected navigation items, submenu content, visibility, and horizontal overflow.
 * @param {number} port - The local Chrome DevTools Protocol debugging port.
 */
async function auditDesktopMenu(port) {
  const scope = 'desktop mega-menu'; const row = {}; let session;
  try {
    session = await openPage(port, `${baseUrl}/`, { width: 1440, height: 1000, mobile: false });
    const target = await session.evaluate(`(() => { const links=Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li > a')).map((n)=>n.textContent.trim().toUpperCase()); const link=Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li > a')).find((n)=>/protocolos signature/i.test(n.textContent)); const r=link?.getBoundingClientRect(); return {links,x:r?r.left+r.width/2:0,y:r?r.top+r.height/2:0}; })()`);
    row.links = target.links;
    for (const label of ['INICIO','SOLUCIONES','PROTOCOLOS SIGNATURE','TECNOLOGÍA','CASOS CLÍNICOS','EQUIPO MÉDICO','CLÍNICAS','JOURNAL','CONTACTO']) if (!target.links.includes(label)) fail(scope, `missing top-level item: ${label}`);
    if (!target.x) throw new Error('Protocolos Signature desktop link was not found.');
    await session.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: target.x, y: target.y }); await sleep(500);
    const menu = await session.evaluate(`(() => { const item=Array.from(document.querySelectorAll('.nvx-nav > .nvx-nav__list > li')).find((n)=>Array.from(n.children).some((c)=>c.tagName==='A'&&/protocolos signature/i.test(c.textContent))); const submenu=item?Array.from(item.children).find((c)=>c.classList?.contains('sub-menu')):null; if(!submenu)return{visible:false,text:'',overflow:999}; const s=getComputedStyle(submenu),r=submenu.getBoundingClientRect(); return{visible:s.display!=='none'&&s.visibility!=='hidden'&&r.width>0&&r.height>0,text:submenu.innerText.replace(/\\s+/g,' ').trim(),overflow:Math.max(0,document.documentElement.scrollWidth-document.documentElement.clientWidth)}; })()`);
    Object.assign(row, menu); if (!menu.visible) fail(scope, 'submenu did not become visible on hover');
    for (const label of ['NUVANX Contour Architecture™','NUVANX Post-Maternity Contour™','NUVANX Profile Definition™','NUVANX Eye Frame™','NUVANX Skin Architecture™','NUVANX Surface Renewal™','NUVANX Tone Correction™']) if (!menu.text.includes(label)) fail(scope, `submenu missing: ${label}`);
    for (const retired of ['Couture Sculpt','Contour Sculpt']) if (menu.text.includes(retired)) fail(scope, `submenu exposes retired label: ${retired}`);
    if (menu.overflow > 2) fail(scope, `horizontal overflow is ${menu.overflow}px`);
    const destination = path.join(evidenceDir, 'navigation-desktop-mega.png'); await screenshot(session, destination, { width: 1440, height: 1000, mobile: false }, false); row.screenshot = path.basename(destination);
  } catch (error) { fail(scope, error instanceof Error ? error.message : String(error)); }
  finally { session?.close(); }
  report.navigation.desktop = row;
}

/**
 * Audits the mobile navigation drawer and its nested accordion behavior.
 * @param {number} port - The local Chrome DevTools Protocol port.
 */
async function auditMobileMenu(port) {
  const scope = 'mobile drawer'; const row = {}; let session;
  try {
    session = await openPage(port, `${baseUrl}/`, { width: 390, height: 844, mobile: true });
    await session.evaluate(`document.getElementById('nvx-hamburger-btn')?.click()`); await sleep(250);
    const opened = await session.evaluate(`(() => { const nav=document.getElementById('nvx-mobile-nav'),button=document.getElementById('nvx-hamburger-btn'); return{open:!!nav&&nav.classList.contains('is-open')&&nav.getAttribute('aria-hidden')==='false',expanded:button?.getAttribute('aria-expanded'),active:document.activeElement?.id||''}; })()`);
    Object.assign(row, opened); if (!opened.open || opened.expanded !== 'true') fail(scope, 'hamburger did not open drawer'); if (opened.active !== 'nvx-mobile-close') fail(scope, `focus did not move to close button: ${opened.active}`);
    const signature = await session.evaluate(`(() => { const nav=document.getElementById('nvx-mobile-nav'); const item=Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li')||[]).find((n)=>Array.from(n.children).some((c)=>c.tagName==='A'&&/protocolos signature/i.test(c.textContent))); const toggle=item?Array.from(item.children).find((c)=>c.classList?.contains('nvx-mobile-nav__toggle')):null; toggle?.click(); return !!toggle; })()`);
    if (!signature) throw new Error('Protocolos Signature mobile accordion toggle was not found.'); await sleep(250);
    const contour = await session.evaluate(`(() => { const nav=document.getElementById('nvx-mobile-nav'); const signature=Array.from(nav?.querySelectorAll('.nvx-mobile-nav__list > li')||[]).find((n)=>Array.from(n.children).some((c)=>c.tagName==='A'&&/protocolos signature/i.test(c.textContent))); const submenu=signature?Array.from(signature.children).find((c)=>c.classList?.contains('sub-menu')):null; const item=Array.from(submenu?.children||[]).find((n)=>Array.from(n.children).some((c)=>c.tagName==='A'&&/contour architecture/i.test(c.textContent))); const toggle=item?Array.from(item.children).find((c)=>c.classList?.contains('nvx-mobile-nav__toggle')):null; toggle?.click(); return !!toggle; })()`);
    if (!contour) throw new Error('Contour Architecture nested mobile toggle was not found.'); await sleep(300);
    const state = await session.evaluate(`(() => { const nav=document.getElementById('nvx-mobile-nav'); return{text:nav?.innerText.replace(/\\s+/g,' ').trim()||'',expanded:Array.from(nav?.querySelectorAll('.nvx-mobile-nav__toggle[aria-expanded="true"]')||[]).length,overflow:Math.max(0,document.documentElement.scrollWidth-document.documentElement.clientWidth),drawerWidth:nav?.getBoundingClientRect().width||0,viewportWidth:document.documentElement.clientWidth}; })()`);
    Object.assign(row, state); if (state.expanded < 2) fail(scope, `expected two expanded levels, found ${state.expanded}`);
    for (const label of ['Abdomen y flancos','Brazos y axila','Espalda y zona del sujetador','Muslos y región subglútea','Rodillas','Contorno masculino']) if (!state.text.includes(label)) fail(scope, `nested menu missing: ${label}`);
    if (state.overflow > 2 || state.drawerWidth > state.viewportWidth + 2) fail(scope, 'drawer causes horizontal overflow');
    const destination = path.join(evidenceDir, 'navigation-mobile-drawer.png'); await screenshot(session, destination, { width: 390, height: 844, mobile: true }, false); row.screenshot = path.basename(destination);
    await session.send('Input.dispatchKeyEvent', { type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27,nativeVirtualKeyCode:27 });
    await session.send('Input.dispatchKeyEvent', { type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27,nativeVirtualKeyCode:27 }); await sleep(200);
    const closed = await session.evaluate(`(() => { const nav=document.getElementById('nvx-mobile-nav'),button=document.getElementById('nvx-hamburger-btn'); return{closed:!!nav&&!nav.classList.contains('is-open')&&nav.getAttribute('aria-hidden')==='true',expanded:button?.getAttribute('aria-expanded'),active:document.activeElement?.id||''}; })()`);
    row.escape = closed; if (!closed.closed || closed.expanded !== 'false') fail(scope, 'Escape did not close drawer'); if (closed.active !== 'nvx-hamburger-btn') fail(scope, 'focus was not restored to hamburger');
  } catch (error) { fail(scope, error instanceof Error ? error.message : String(error)); }
  finally { session?.close(); }
  report.navigation.mobile = row;
}

const chromePath = locateChrome(); report.chrome = chromePath;
const port = 9300 + Math.floor(Math.random() * 500);
const profileDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-chrome-'));
const chrome = spawn(chromePath, ['--headless=new','--no-sandbox','--disable-dev-shm-usage','--disable-gpu','--no-first-run','--hide-scrollbars','--remote-allow-origins=*',`--remote-debugging-port=${port}`,`--user-data-dir=${profileDir}`,`--user-agent=${userAgent}`,'about:blank'], { stdio:['ignore','ignore','pipe'] });
let stderr = ''; chrome.stderr.on('data', (chunk) => { stderr += String(chunk); });
try { await waitForChrome(port); await auditPages(port); await auditDesktopMenu(port); await auditMobileMenu(port); }
catch (error) { fail('visual QA runtime', error instanceof Error ? error.message : String(error)); }
finally { chrome.kill('SIGTERM'); await sleep(250); fs.rmSync(profileDir, { recursive:true, force:true }); }
report.chrome_stderr_tail = stderr.slice(-4000);
fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`VISUAL_QA_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`VISUAL_QA_OK pages=${pages.length} screenshots=${report.pages.length + 2} sha=${expectedSha}`);
