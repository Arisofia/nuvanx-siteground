#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { randomInt } from 'node:crypto';
import {
  pages, viewports, sleep, safeName, pageState, captureFullPage,
  auditDesktopNavigation, auditMobileNavigation, locateChrome, CDPSession,
  waitForChrome, createTarget, closeSession,
} from './visual-qa-common.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/visual-qa';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36';

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

async function waitForGovernedPage(session, expectedH1) {
  let lastState = null;
  for (let attempt = 1; attempt <= 36; attempt += 1) {
    lastState = await session.evaluate(String.raw`(() => ({
      readyState: document.readyState,
      deploySha: document.querySelector('meta[name="nvx-deploy-sha"]')?.content || '',
      h1: Array.from(document.querySelectorAll('h1')).map((node) => node.textContent.trim()),
      text: (document.body?.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 240),
    }))()`);
    if (lastState.deploySha === expectedSha && lastState.h1.length === 1 && lastState.h1[0] === expectedH1) return lastState;
    if (attempt === 12 || attempt === 24) await session.send('Page.reload', { ignoreCache: true });
    await sleep(500);
  }
  throw new Error(`governed page did not become ready; sha=${lastState?.deploySha || 'absent'} h1=${JSON.stringify(lastState?.h1 || [])} body=${lastState?.text || 'empty'}`);
}

async function loadPage(port, url, viewport, expectedH1) {
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
  if (navigation.errorText) throw new Error(`Navigation failed: ${navigation.errorText}`);
  await waitForGovernedPage(session, expectedH1);
  await session.evaluate(`new Promise((resolve) => {
    const finish = () => setTimeout(resolve, 500);
    if (document.fonts?.ready) document.fonts.ready.then(finish, finish); else finish();
  })`);
  return session;
}

async function auditSingleViewport(port, pagePath, expectedH1, viewport) {
  const scope = `${pagePath} ${viewport.name}`;
  const result = { path: pagePath, viewport: viewport.name };
  let session;
  try {
    session = await loadPage(port, `${baseUrl}${pagePath}`, viewport, expectedH1);
    Object.assign(result, await pageState(session));
    if (result.forbidden) fail(scope, 'rendered a 403 Forbidden page');
    if (result.deploySha !== expectedSha) fail(scope, `served SHA ${result.deploySha || 'absent'} instead of ${expectedSha}`);
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
    await closeSession(session);
  }
  report.pages.push(result);
}

async function auditPages(port) {
  for (const [pagePath, expectedH1] of pages) {
    for (const viewport of viewports) await auditSingleViewport(port, pagePath, expectedH1, viewport);
  }
}

const chromePath = locateChrome();
report.chrome = chromePath;
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
  await auditPages(port);
  await auditDesktopNavigation(port, loadPage, closeSession, report, fail);
  await auditMobileNavigation(port, loadPage, closeSession, report, fail);
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
