import fs from 'node:fs';
import http from 'node:http';
import net from 'node:net';
import os from 'node:os';
import path from 'node:path';
import { spawn, spawnSync } from 'node:child_process';

const nativeFetch = globalThis.fetch;
const expectedSha = process.env.EXPECTED_SHA || '';
const stagingHost = 'staging2.nuvanx.com';
const stagingPort = 443;
const sshAlias = process.env.STAGING2_SSH_ALIAS || 'nvx-staging2';
const evidenceDir = process.env.EVIDENCE_DIR || 'institutional-headers-form-evidence';
const userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36';
const shellEscape = "'\"'\"'";

if (typeof nativeFetch !== 'function') throw new TypeError('Node.js native fetch is required.');
if (!/^[0-9a-f]{40}$/.test(expectedSha)) throw new TypeError('EXPECTED_SHA must be a full commit SHA.');
if (sshAlias !== 'nvx-staging2') throw new TypeError('STAGING2_SSH_ALIAS must equal nvx-staging2.');
fs.mkdirSync(evidenceDir, { recursive: true });

function resolveChrome() {
  const configured = process.env.NVX_REAL_CHROME_BIN;
  if (configured && fs.existsSync(configured)) return configured;
  for (const candidate of ['google-chrome-stable', 'google-chrome', 'chromium', 'chromium-browser']) {
    const result = spawnSync('/usr/bin/which', [candidate], { encoding: 'utf8' });
    const resolved = String(result.stdout || '').trim();
    if (result.status === 0 && resolved && fs.existsSync(resolved)) return resolved;
  }
  throw new Error('Chrome is not installed on the runner.');
}

function quote(value) {
  return `'${String(value).replaceAll("'", shellEscape)}'`;
}

function parseAuthority(value) {
  const separator = value.lastIndexOf(':');
  if (separator < 1) return null;
  const host = value.slice(0, separator).trim().toLowerCase();
  const port = Number.parseInt(value.slice(separator + 1), 10);
  if (!/^[a-z0-9.-]+$/.test(host) || !Number.isInteger(port) || port < 1 || port > 65535) return null;
  return { host, port };
}

const remoteBridgePhp = String.raw`
$socket = @stream_socket_client('tcp://staging2.nuvanx.com:443', $errno, $errstr, 20);
if (!is_resource($socket)) { fwrite(STDERR, "bridge connect failed: {$errno} {$errstr}\n"); exit(69); }
stream_set_blocking($socket, false);
stream_set_blocking(STDIN, false);
while (true) {
    $read = array();
    if (!feof(STDIN)) { $read[] = STDIN; }
    if (!feof($socket)) { $read[] = $socket; }
    if (array() === $read) { break; }
    $write = null; $except = null;
    $selected = @stream_select($read, $write, $except, 30);
    if (false === $selected) { exit(74); }
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

const realChrome = resolveChrome();
process.env.NVX_REAL_CHROME_BIN = realChrome;
const bridgeCode = Buffer.from(remoteBridgePhp, 'utf8').toString('base64');
const controlPath = path.join(os.tmpdir(), `nvx-institutional-ssh-${process.pid}`);
const logPath = path.join(evidenceDir, 'institutional-qa-proxy.log');
const logFd = fs.openSync(logPath, 'a');
const clientSockets = new Set();
const bridgeProcesses = new Set();
let chromeWrapper = '';
let cleaned = false;

function log(message) {
  fs.writeSync(logFd, `${new Date().toISOString()} ${message}\n`);
}

function openStagingBridge() {
  const phpEval = `eval(base64_decode("${bridgeCode}"));`;
  const child = spawn('/usr/bin/ssh', [
    '-o', 'ControlMaster=auto',
    '-o', 'ControlPersist=60',
    '-o', `ControlPath=${controlPath}`,
    sshAlias,
    `php -r ${quote(phpEval)}`,
  ], { stdio: ['pipe', 'pipe', 'pipe'] });
  bridgeProcesses.add(child);
  child.stderr.on('data', (chunk) => log(`staging bridge: ${String(chunk).trim()}`));
  child.once('exit', () => bridgeProcesses.delete(child));
  return child;
}

function registerClient(socket) {
  clientSockets.add(socket);
  socket.once('close', () => clientSockets.delete(socket));
}

function connectStaging(clientSocket, head) {
  const bridge = openStagingBridge();
  const closeBridge = () => {
    if (!bridge.stdin.destroyed) bridge.stdin.end();
    if (bridge.exitCode === null && !bridge.killed) bridge.kill('SIGTERM');
  };
  clientSocket.once('close', closeBridge);
  bridge.once('error', (error) => clientSocket.destroy(error));
  bridge.once('spawn', () => {
    clientSocket.write('HTTP/1.1 200 Connection Established\r\nProxy-Agent: NUVANX-Institutional-QA\r\n\r\n');
    if (head.length) bridge.stdin.write(head);
    clientSocket.pipe(bridge.stdin);
    bridge.stdout.pipe(clientSocket);
  });
  bridge.once('exit', () => {
    if (!clientSocket.destroyed) clientSocket.end();
  });
}

function connectExternal(target, clientSocket, head) {
  const upstream = net.connect({ host: target.host, port: target.port });
  upstream.setTimeout(30000);
  upstream.once('connect', () => {
    upstream.setTimeout(0);
    clientSocket.write('HTTP/1.1 200 Connection Established\r\nProxy-Agent: NUVANX-Institutional-QA\r\n\r\n');
    if (head.length) upstream.write(head);
    clientSocket.pipe(upstream);
    upstream.pipe(clientSocket);
  });
  upstream.once('timeout', () => upstream.destroy(new Error(`Timeout connecting to ${target.host}:${target.port}`)));
  upstream.once('error', (error) => {
    log(`external ${target.host}:${target.port}: ${error.message}`);
    if (!clientSocket.destroyed) clientSocket.end('HTTP/1.1 502 Bad Gateway\r\nConnection: close\r\n\r\n');
  });
  clientSocket.once('close', () => upstream.destroy());
}

const proxy = http.createServer((_request, response) => {
  response.writeHead(501, { 'content-type': 'text/plain; charset=utf-8' });
  response.end('CONNECT is required');
});

proxy.on('connect', (request, clientSocket, head) => {
  const target = parseAuthority(request.url || '');
  if (!target) {
    clientSocket.end('HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n');
    return;
  }
  registerClient(clientSocket);
  clientSocket.on('error', (error) => log(`client ${target.host}:${target.port}: ${error.message}`));
  if (target.host === stagingHost && target.port === stagingPort) {
    connectStaging(clientSocket, head);
  } else {
    connectExternal(target, clientSocket, head);
  }
});

await new Promise((resolve, reject) => {
  proxy.once('error', reject);
  proxy.listen(0, '127.0.0.1', resolve);
});
proxy.unref();
const address = proxy.address();
if (!address || typeof address === 'string') throw new Error('QA proxy did not expose a TCP port.');
const proxyUrl = `http://127.0.0.1:${address.port}`;

function cleanup() {
  if (cleaned) return;
  cleaned = true;
  for (const socket of clientSockets) socket.destroy();
  for (const child of bridgeProcesses) {
    if (!child.stdin.destroyed) child.stdin.end();
    if (child.exitCode === null && !child.killed) child.kill('SIGTERM');
  }
  try { proxy.close(); } catch { /* already closed */ }
  spawnSync('/usr/bin/ssh', ['-S', controlPath, '-O', 'exit', sshAlias], { stdio: 'ignore' });
  try { fs.unlinkSync(chromeWrapper); } catch { /* absent */ }
  try { fs.unlinkSync(controlPath); } catch { /* absent */ }
  try { fs.closeSync(logFd); } catch { /* already closed */ }
}
process.once('exit', cleanup);

const probe = spawnSync('/usr/bin/curl', [
  '--silent', '--show-error', '--fail', '--max-time', '45', '--noproxy', '',
  '--proxy', proxyUrl, '--user-agent', userAgent, `https://${stagingHost}/`,
], { encoding: 'utf8', maxBuffer: 8 * 1024 * 1024 });
if (probe.status !== 0 || !String(probe.stdout || '').includes(expectedSha)) {
  cleanup();
  throw new Error(`Institutional QA staging bridge failed: ${String(probe.stderr || '').slice(-600)}`);
}

chromeWrapper = path.join(os.tmpdir(), `nvx-institutional-chrome-${process.pid}`);
fs.writeFileSync(chromeWrapper, [
  '#!/usr/bin/env bash',
  'set -euo pipefail',
  `exec ${quote(realChrome)} --proxy-server=${quote(proxyUrl)} --proxy-bypass-list=${quote('localhost;127.0.0.1')} "$@"`,
  '',
].join('\n'), { mode: 0o700 });
process.env.CHROME_BIN = chromeWrapper;
console.error(`INSTITUTIONAL_QA_HYBRID_PROXY_READY port=${address.port}`);

globalThis.fetch = async (input, init) => {
  const url = typeof input === 'string' ? input : input?.url;
  if (typeof url === 'string' && url.startsWith(`https://${stagingHost}/`)) {
    return new Response('<!doctype html><title>Chrome navigation required</title>', {
      status: 200,
      headers: { 'content-type': 'text/html; charset=utf-8' },
    });
  }
  return nativeFetch(input, init);
};
