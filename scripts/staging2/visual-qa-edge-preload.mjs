import fs from 'node:fs';
import http from 'node:http';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

const nativeFetch = globalThis.fetch;
const nativeExit = process.exit.bind(process);
const nativeConsoleError = console.error.bind(console);
const visualQaAttempt = Number.parseInt(process.env.NVX_VISUAL_QA_ATTEMPT || '1', 10);
const visualQaErrors = [];
const expectedSha = process.env.EXPECTED_SHA || '';
const stagingHost = 'staging2.nuvanx.com';
const stagingPort = 443;
const configuredSshAlias = process.env.STAGING2_SSH_ALIAS || 'nvx-staging2';
const sshAlias = 'nvx-staging2';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-visual-qa';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';
const shellSingleQuoteEscape = "'\"'\"'";

if (typeof nativeFetch !== 'function') {
  throw new TypeError('Node.js native fetch is required for the visual QA preload.');
}
if (configuredSshAlias !== sshAlias) {
  throw new TypeError(`STAGING2_SSH_ALIAS must equal ${sshAlias}.`);
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
  const escaped = String(value).replaceAll("'", shellSingleQuoteEscape);
  return `'${escaped}'`;
}

function parseConnectAuthority(authority) {
  const separator = authority.lastIndexOf(':');
  if (separator < 1) return null;
  const host = authority.slice(0, separator).trim().toLowerCase();
  const port = Number.parseInt(authority.slice(separator + 1), 10);
  if (!/^[a-z0-9.-]+$/.test(host) || !Number.isInteger(port) || port < 1 || port > 65535) return null;
  return { host, port };
}

function runCurlProbe(args) {
  return new Promise((resolve) => {
    const child = spawn('/usr/bin/curl', args, { stdio: ['ignore', 'pipe', 'pipe'] });
    let stdout = '';
    let stderr = '';
    let settled = false;
    const maxBuffer = 8 * 1024 * 1024;
    const finish = (status, error = null) => {
      if (settled) return;
      settled = true;
      resolve({ status, stdout, stderr: error ? `${stderr}\n${error.message}` : stderr });
    };
    const append = (current, chunk) => {
      const next = current + String(chunk);
      if (Buffer.byteLength(next, 'utf8') > maxBuffer) {
        child.kill('SIGTERM');
        return next.slice(-maxBuffer);
      }
      return next;
    };
    child.stdout.on('data', (chunk) => { stdout = append(stdout, chunk); });
    child.stderr.on('data', (chunk) => { stderr = append(stderr, chunk); });
    child.once('error', (error) => finish(null, error));
    child.once('close', (status) => finish(status));
  });
}

const remoteBridgePhp = String.raw`
$host = 'staging2.nuvanx.com';
$port = 443;
$socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 20);
if (!is_resource($socket)) {
    fwrite(STDERR, "bridge connect failed: {$errno} {$errstr}\n");
    exit(69);
}
stream_set_blocking($socket, false);
stream_set_blocking(STDIN, false);
while (true) {
    $read = array();
    if (!feof(STDIN)) { $read[] = STDIN; }
    if (!feof($socket)) { $read[] = $socket; }
    if (array() === $read) { break; }
    $write = null;
    $except = null;
    $selected = @stream_select($read, $write, $except, 30);
    if (false === $selected) { break; }
    if (0 === $selected) { continue; }
    foreach ($read as $source) {
        $chunk = fread($source, 65536);
        if (false === $chunk) { exit(74); }
        if ('' === $chunk) { continue; }
        $target = ($source === STDIN) ? $socket : STDOUT;
        while ('' !== $chunk) {
            $written = fwrite($target, $chunk);
            if (false === $written || 0 === $written) { exit(74); }
            $chunk = (string) substr($chunk, $written);
        }
        fflush($target);
    }
}
fclose($socket);
`;

const realChrome = resolveRealChrome();
process.env.NVX_REAL_CHROME_BIN = realChrome;
const bridgeCode = Buffer.from(remoteBridgePhp, 'utf8').toString('base64');
const controlPath = path.join(os.tmpdir(), `nvx-ssh-control-${process.pid}-${visualQaAttempt}`);
const bridgeLogPath = path.join(evidenceDir, `visual-qa-ssh-bridge-attempt-${visualQaAttempt}.log`);
const bridgeLogFd = fs.openSync(bridgeLogPath, 'a');
const bridgeProcesses = new Set();
const clientSockets = new Set();
let chromeWrapper = '';
let proxyCleaned = false;

function logBridge(message) {
  fs.writeSync(bridgeLogFd, `${new Date().toISOString()} ${message}\n`);
}

function spawnRemoteBridge() {
  const phpEval = `eval(base64_decode("${bridgeCode}"));`;
  const remoteCommand = `php -r ${shellQuote(phpEval)}`;
  const child = spawn(
    '/usr/bin/ssh',
    [
      '-o',
      'ControlMaster=auto',
      '-o',
      'ControlPersist=60',
      '-o',
      `ControlPath=${controlPath}`,
      sshAlias,
      remoteCommand,
    ],
    { stdio: ['pipe', 'pipe', 'pipe'] },
  );
  bridgeProcesses.add(child);
  child.stderr.on('data', (chunk) => logBridge(`remote ${stagingHost}:${stagingPort} ${String(chunk).trim()}`));
  child.once('exit', (code, signal) => {
    bridgeProcesses.delete(child);
    if (code && code !== 0) logBridge(`remote ${stagingHost}:${stagingPort} exit=${code} signal=${signal || ''}`);
  });
  return child;
}

const proxyServer = http.createServer((_request, response) => {
  response.writeHead(501, { 'content-type': 'text/plain; charset=utf-8' });
  response.end('CONNECT is required');
});

proxyServer.on('connect', (request, clientSocket, head) => {
  const target = parseConnectAuthority(request.url || '');
  if (!target) {
    clientSocket.end('HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n');
    return;
  }
  if (target.host !== stagingHost || target.port !== stagingPort) {
    clientSocket.end('HTTP/1.1 403 Forbidden\r\nConnection: close\r\n\r\n');
    return;
  }

  clientSockets.add(clientSocket);
  clientSocket.once('close', () => clientSockets.delete(clientSocket));
  clientSocket.on('error', (error) => logBridge(`client ${stagingHost}:${stagingPort} ${error.message}`));

  const bridge = spawnRemoteBridge();
  const closeBridge = () => {
    if (!bridge.stdin.destroyed) bridge.stdin.end();
    if (bridge.exitCode === null && !bridge.killed) bridge.kill('SIGTERM');
  };
  clientSocket.once('close', closeBridge);
  bridge.once('error', (error) => {
    logBridge(`spawn ${stagingHost}:${stagingPort} ${error.message}`);
    clientSocket.destroy(error);
  });
  bridge.once('spawn', () => {
    clientSocket.write('HTTP/1.1 200 Connection Established\r\nProxy-Agent: NUVANX-SSH-Bridge\r\n\r\n');
    if (head.length) bridge.stdin.write(head);
    clientSocket.pipe(bridge.stdin);
    bridge.stdout.pipe(clientSocket);
  });
  bridge.once('exit', () => {
    if (!clientSocket.destroyed) clientSocket.end();
  });
});

await new Promise((resolve, reject) => {
  proxyServer.once('error', reject);
  proxyServer.listen(0, '127.0.0.1', resolve);
});
proxyServer.unref();
const proxyAddress = proxyServer.address();
if (!proxyAddress || typeof proxyAddress === 'string') {
  throw new Error('VISUAL_QA_SSH_BRIDGE_UNAVAILABLE local proxy did not expose a TCP port');
}
const proxyPort = proxyAddress.port;
const proxyUrl = `http://127.0.0.1:${proxyPort}`;

function stopProxyResources() {
  for (const socket of clientSockets) socket.destroy();
  for (const child of bridgeProcesses) {
    if (!child.stdin.destroyed) child.stdin.end();
    if (child.exitCode === null && !child.killed) child.kill('SIGTERM');
  }
}

function removeProxyFiles() {
  try { fs.closeSync(bridgeLogFd); } catch { /* already closed */ }
  try { fs.unlinkSync(chromeWrapper); } catch { /* wrapper may not exist */ }
  try { fs.unlinkSync(controlPath); } catch { /* control socket may not exist */ }
}

function waitForCompletion(register, onTimeout = () => {}, timeoutMilliseconds = 3000) {
  return new Promise((resolve) => {
    let settled = false;
    const finish = () => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      resolve();
    };
    const timer = setTimeout(() => {
      onTimeout();
      finish();
    }, timeoutMilliseconds);

    try {
      register(finish);
    } catch {
      finish();
    }
  });
}

function waitForChild(child, timeoutMilliseconds = 3000) {
  if (child.exitCode !== null || child.signalCode !== null) {
    return Promise.resolve();
  }

  return waitForCompletion(
    (finish) => {
      child.once('error', finish);
      child.once('exit', finish);
    },
    () => {
      if (child.exitCode === null && !child.killed) child.kill('SIGKILL');
    },
    timeoutMilliseconds,
  );
}

function closeProxyServer() {
  if (!proxyServer.listening) return Promise.resolve();
  return waitForCompletion((finish) => proxyServer.close(finish));
}

function closeSshControl() {
  const child = spawn('/usr/bin/ssh', ['-S', controlPath, '-O', 'exit', sshAlias], { stdio: 'ignore' });
  return waitForCompletion(
    (finish) => {
      child.once('error', finish);
      child.once('close', finish);
    },
    () => {
      if (child.exitCode === null && !child.killed) child.kill('SIGTERM');
    },
  );
}

function cleanupProxy() {
  if (proxyCleaned) return;
  proxyCleaned = true;
  stopProxyResources();
  proxyServer.close();
  spawnSync('/usr/bin/ssh', ['-S', controlPath, '-O', 'exit', sshAlias], { stdio: 'ignore' });
  removeProxyFiles();
}

async function cleanupProxyForRetry() {
  if (proxyCleaned) return;
  proxyCleaned = true;
  const bridgeWaiters = Array.from(bridgeProcesses, (child) => waitForChild(child));
  stopProxyResources();
  await Promise.allSettled([closeProxyServer(), ...bridgeWaiters]);
  await closeSshControl();
  removeProxyFiles();
}
process.once('exit', cleanupProxy);

let proxyReady = false;
let lastProbe = '';
for (let attempt = 1; attempt <= 4; attempt += 1) {
  const probe = await runCurlProbe([
    '--silent',
    '--show-error',
    '--fail',
    '--max-time',
    '45',
    '--noproxy',
    '',
    '--proxy',
    proxyUrl,
    '--user-agent',
    userAgent,
    'https://staging2.nuvanx.com/',
  ]);
  lastProbe = String(probe.stderr || probe.stdout || '').slice(-1200);
  if (probe.status === 0 && String(probe.stdout || '').includes(expectedSha)) {
    proxyReady = true;
    break;
  }
  await new Promise((resolve) => setTimeout(resolve, attempt * 1000));
}

if (!proxyReady) {
  cleanupProxy();
  let bridgeLog = '';
  try { bridgeLog = fs.readFileSync(bridgeLogPath, 'utf8').slice(-1600); } catch { /* no log */ }
  throw new Error(`VISUAL_QA_SSH_BRIDGE_UNAVAILABLE alias=${sshAlias} port=${proxyPort} probe=${lastProbe} bridge=${bridgeLog}`);
}

chromeWrapper = path.join(os.tmpdir(), `nvx-chrome-via-staging2-${process.pid}-${visualQaAttempt}`);
const chromeCommand = [
  `exec ${shellQuote(realChrome)}`,
  `--proxy-server=${shellQuote(proxyUrl)}`,
  `--proxy-bypass-list=${shellQuote('localhost;127.0.0.1')}`,
  '"$@"',
].join(' ');
fs.writeFileSync(
  chromeWrapper,
  `#!/usr/bin/env bash\nset -euo pipefail\n${chromeCommand}\n`,
  { mode: 0o700 },
);
process.env.CHROME_BIN = chromeWrapper;
nativeConsoleError(`VISUAL_QA_SSH_BRIDGE_READY alias=${sshAlias} port=${proxyPort} attempt=${visualQaAttempt}`);

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
  visualQaErrors.push(args.map(String).join(' '));
  nativeConsoleError(...args);
};

function runRetryAttempt(nextAttempt) {
  return new Promise((resolve) => {
    const child = spawn(process.execPath, [...process.execArgv, ...process.argv.slice(1)], {
      env: {
        ...process.env,
        CHROME_BIN: '',
        NVX_VISUAL_QA_ATTEMPT: String(nextAttempt),
      },
      stdio: 'inherit',
    });
    let settled = false;
    const finish = (status) => {
      if (settled) return;
      settled = true;
      resolve(Number.isInteger(status) ? status : 1);
    };
    child.once('error', () => finish(1));
    child.once('close', finish);
  });
}

let retryInProgress = false;

async function relaunchRetry(nextAttempt, delayMilliseconds) {
  await cleanupProxyForRetry();
  await new Promise((resolve) => setTimeout(resolve, delayMilliseconds));
  const status = await runRetryAttempt(nextAttempt);
  nativeExit(status);
}

process.exit = (code = 0) => {
  const output = visualQaErrors.join('\n');
  const transientEdgeFailure = /H1 mismatch: \["staging2\.nuvanx\.com"\]|Inspected target navigated or closed|Promise was collected|rendered a 403 Forbidden page/i.test(output);

  if (Number(code) !== 0 && transientEdgeFailure && visualQaAttempt < 3) {
    if (retryInProgress) return;
    retryInProgress = true;
    const nextAttempt = visualQaAttempt + 1;
    const delayMilliseconds = visualQaAttempt * 5000;
    nativeConsoleError(`VISUAL_QA_RETRY_TRANSIENT_EDGE attempt=${nextAttempt} delay_ms=${delayMilliseconds}`);
    void relaunchRetry(nextAttempt, delayMilliseconds).catch((error) => {
      nativeConsoleError(`VISUAL_QA_RETRY_FAILED ${error instanceof Error ? error.message : String(error)}`);
      nativeExit(1);
    });
    return;
  }

  nativeExit(Number(code));
};
