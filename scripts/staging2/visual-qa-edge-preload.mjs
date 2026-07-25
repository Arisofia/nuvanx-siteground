import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawn, spawnSync } from 'node:child_process';

const nativeFetch = globalThis.fetch;
const nativeExit = process.exit.bind(process);
const nativeConsoleError = console.error.bind(console);
const visualQaAttempt = Number.parseInt(process.env.NVX_VISUAL_QA_ATTEMPT || '1', 10);
const visualQaErrors = [];
const expectedSha = process.env.EXPECTED_SHA || '';
const sshAlias = process.env.STAGING2_SSH_ALIAS || 'nvx-staging2';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-visual-qa';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';

if (typeof nativeFetch !== 'function') {
  throw new TypeError('Node.js native fetch is required for the visual QA preload.');
}
if (!/^[A-Za-z0-9._-]+$/.test(sshAlias)) {
  throw new TypeError('STAGING2_SSH_ALIAS contains unsupported characters.');
}
if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  throw new TypeError('EXPECTED_SHA must be a full lowercase 40-character SHA.');
}

fs.mkdirSync(evidenceDir, { recursive: true });

function resolveRealChrome() {
  const configured = process.env.NVX_REAL_CHROME_BIN;
  if (configured && fs.existsSync(configured)) return configured;

  for (const candidate of ['google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser']) {
    const result = spawnSync('/usr/bin/which', [candidate], { encoding: 'utf8' });
    const resolved = String(result.stdout || '').trim();
    if (result.status === 0 && resolved && fs.existsSync(resolved)) return resolved;
  }
  throw new Error('Google Chrome or Chromium is not installed on the runner.');
}

function shellQuote(value) {
  return `'${String(value).replaceAll("'", `'"'"'`)}'`;
}

const realChrome = resolveRealChrome();
process.env.NVX_REAL_CHROME_BIN = realChrome;

const socksPort = 12000 + crypto.randomInt(0, 20000);
const proxyLogPath = path.join(evidenceDir, `visual-qa-ssh-proxy-attempt-${visualQaAttempt}.log`);
const proxyLogFd = fs.openSync(proxyLogPath, 'a');
const sshProxy = spawn(
  '/usr/bin/ssh',
  [
    '-N',
    '-D',
    `127.0.0.1:${socksPort}`,
    '-o',
    'ExitOnForwardFailure=yes',
    '-o',
    'ServerAliveInterval=15',
    '-o',
    'ServerAliveCountMax=2',
    sshAlias,
  ],
  { stdio: ['ignore', proxyLogFd, proxyLogFd] },
);

let proxyCleaned = false;
function cleanupProxy() {
  if (proxyCleaned) return;
  proxyCleaned = true;
  if (sshProxy.exitCode === null && !sshProxy.killed) sshProxy.kill('SIGTERM');
  try { fs.closeSync(proxyLogFd); } catch { /* already closed */ }
  try { fs.unlinkSync(process.env.CHROME_BIN || ''); } catch { /* wrapper may not exist */ }
}
process.once('exit', cleanupProxy);

let proxyReady = false;
let lastProbe = '';
for (let attempt = 1; attempt <= 6; attempt += 1) {
  if (sshProxy.exitCode !== null) break;
  const probe = spawnSync(
    '/usr/bin/curl',
    [
      '--silent',
      '--show-error',
      '--fail',
      '--max-time',
      '30',
      '--socks5-hostname',
      `127.0.0.1:${socksPort}`,
      '--user-agent',
      userAgent,
      'https://staging2.nuvanx.com/',
    ],
    { encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 },
  );
  lastProbe = String(probe.stderr || probe.stdout || '').slice(-1200);
  if (probe.status === 0 && String(probe.stdout || '').includes(expectedSha)) {
    proxyReady = true;
    break;
  }
  Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, attempt * 750);
}

if (!proxyReady) {
  cleanupProxy();
  let proxyLog = '';
  try { proxyLog = fs.readFileSync(proxyLogPath, 'utf8').slice(-1200); } catch { /* no log */ }
  throw new Error(`VISUAL_QA_SSH_PROXY_UNAVAILABLE alias=${sshAlias} port=${socksPort} probe=${lastProbe} ssh=${proxyLog}`);
}

const chromeWrapper = path.join(os.tmpdir(), `nvx-chrome-via-staging2-${process.pid}-${visualQaAttempt}`);
fs.writeFileSync(
  chromeWrapper,
  [
    '#!/usr/bin/env bash',
    'set -euo pipefail',
    `exec ${shellQuote(realChrome)} \\\`,
    `  --proxy-server=${shellQuote(`socks5://127.0.0.1:${socksPort}`)} \\\`,
    `  --host-resolver-rules=${shellQuote('MAP * ~NOTFOUND, EXCLUDE localhost, EXCLUDE 127.0.0.1')} \\\`,
    `  --proxy-bypass-list=${shellQuote('localhost;127.0.0.1')} \\\`,
    '  "$@"',
    '',
  ].join('\n'),
  { mode: 0o700 },
);
process.env.CHROME_BIN = chromeWrapper;
nativeConsoleError(`VISUAL_QA_SSH_PROXY_READY alias=${sshAlias} port=${socksPort} attempt=${visualQaAttempt}`);

globalThis.fetch = async (input, init) => {
  const url = typeof input === 'string' ? input : input?.url;
  if (typeof url === 'string' && url.startsWith('https://staging2.nuvanx.com/')) {
    return new Response('<!doctype html><title>Chrome navigation required</title>', {
      status: 200,
      headers: { 'content-type': 'text/html; charset=utf-8' },
    });
  }
  return nativeFetch(input, init);
};

console.error = (...args) => {
  visualQaErrors.push(args.map((value) => String(value)).join(' '));
  nativeConsoleError(...args);
};

process.exit = (code = 0) => {
  const output = visualQaErrors.join('\n');
  const transientEdgeFailure = /H1 mismatch: \["staging2\.nuvanx\.com"\]|Inspected target navigated or closed|Promise was collected|rendered a 403 Forbidden page/i.test(output);

  if (Number(code) !== 0 && transientEdgeFailure && visualQaAttempt < 3) {
    const nextAttempt = visualQaAttempt + 1;
    const delayMilliseconds = visualQaAttempt * 5000;
    nativeConsoleError(`VISUAL_QA_RETRY_TRANSIENT_EDGE attempt=${nextAttempt} delay_ms=${delayMilliseconds}`);
    cleanupProxy();
    Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, delayMilliseconds);

    const result = spawnSync(process.execPath, process.argv.slice(1), {
      env: {
        ...process.env,
        CHROME_BIN: '',
        NVX_VISUAL_QA_ATTEMPT: String(nextAttempt),
      },
      stdio: 'inherit',
    });
    nativeExit(Number.isInteger(result.status) ? result.status : 1);
  }

  nativeExit(Number(code));
};
