import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const stagingHost = 'staging2.nuvanx.com';
const existingWrapper = process.env.CHROME_BIN || '';
const realChrome = process.env.NVX_REAL_CHROME_BIN || '';

if (!existingWrapper || !fs.existsSync(existingWrapper)) {
  throw new Error('The staging SSH proxy preload must run before the external-assets preload.');
}
if (!realChrome || !fs.existsSync(realChrome)) {
  throw new Error('NVX_REAL_CHROME_BIN was not exposed by the staging SSH proxy preload.');
}

const wrapperSource = fs.readFileSync(existingWrapper, 'utf8');
const proxyMatch = wrapperSource.match(/--proxy-server=(?:'|")?(http:\/\/127\.0\.0\.1:\d+)/);
if (!proxyMatch) {
  throw new Error('Unable to recover the local staging proxy URL.');
}

const proxyUrl = new URL(proxyMatch[1]);
const wrapperPath = path.join(os.tmpdir(), `nvx-institutional-pac-chrome-${process.pid}`);
const pacSource = [
  'function FindProxyForURL(url, host) {',
  `  if (host === ${JSON.stringify(stagingHost)}) return ${JSON.stringify(`PROXY ${proxyUrl.hostname}:${proxyUrl.port}`)};`,
  '  return "DIRECT";',
  '}',
  '',
].join('\n');

const shellEscape = "'\"'\"'";
function quote(value) {
  return `'${String(value).replaceAll("'", shellEscape)}'`;
}

const pacDataUrl = `data:application/x-ns-proxy-autoconfig;base64,${Buffer.from(pacSource, 'utf8').toString('base64')}`;
fs.writeFileSync(
  wrapperPath,
  [
    '#!/usr/bin/env bash',
    'set -euo pipefail',
    `exec ${quote(realChrome)} --proxy-pac-url=${quote(pacDataUrl)} "$@"`,
    '',
  ].join('\n'),
  { mode: 0o700 },
);

process.env.CHROME_BIN = wrapperPath;
console.error(`INSTITUTIONAL_QA_PAC_READY staging_proxy=${proxyUrl.host}`);

let cleaned = false;
function cleanup() {
  if (cleaned) return;
  cleaned = true;
  try { fs.unlinkSync(wrapperPath); } catch { /* absent */ }
}
process.once('exit', cleanup);
process.once('SIGINT', () => { cleanup(); process.exit(1); });
process.once('SIGTERM', () => { cleanup(); process.exit(1); });
process.once('SIGHUP', () => { cleanup(); process.exit(1); });
