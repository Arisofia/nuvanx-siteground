import fs from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

const SSH_BIN = '/usr/bin/ssh';
const SUDO_BIN = '/usr/bin/sudo';
const allowedAliases = new Set(['nvx-staging2', 'nvx-staging2-pr']);
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = new URL(baseUrl).hostname;
const originSshAlias = process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
const matrixScript = fileURLToPath(new URL('./block-c-matrix.mjs', import.meta.url));
const resultsUrl = new URL('./block-c-artifacts/block-c-results.json', import.meta.url);
const publicEdgeEvidenceUrl = new URL('./artifacts/block-c-public-edge-transient.json', import.meta.url);
const hostsMarker = `# NUVANX_BLOCK_C_ORIGIN_BROWSER_${process.pid}`;
const tunnelPidfile = `/tmp/nuvanx-block-c-tunnel-${process.pid}.pid`;

if (!allowedAliases.has(originSshAlias)) {
  throw new Error(`ORIGIN_SSH_ALIAS must be one of: ${[...allowedAliases].join(', ')}`);
}
if (expectedHost !== 'staging2.nuvanx.com') {
  throw new Error(`Origin browser fallback is restricted to staging2.nuvanx.com, got ${expectedHost}`);
}

function run(cmd, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(cmd, args, { stdio: options.stdio || 'inherit', env: options.env || process.env });
    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) reject(new Error(`${cmd} terminated by signal ${signal}`));
      else resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

async function preservePublicEdgeEvidence() {
  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
  } catch {
    return;
  }
  await fs.mkdir(new URL('./artifacts/', import.meta.url), { recursive: true });
  const inconclusive = Array.isArray(results)
    ? results.filter((result) => result?.externalInconclusive === true || result?.status !== 'PASS')
    : [];
  await fs.writeFile(
    publicEdgeEvidenceUrl,
    `${JSON.stringify({
      schema: 1,
      capturedAt: new Date().toISOString(),
      transport: 'public-edge',
      reason: process.env.BLOCK_C_FALLBACK_REASON || 'siteground-antibot-transient',
      totalResults: Array.isArray(results) ? results.length : 0,
      inconclusive,
    }, null, 2)}\n`,
    'utf8'
  );
}

async function appendHostsMapping() {
  const line = `127.0.0.1 ${expectedHost} ${hostsMarker}`;
  const command = `printf '%s\\n' '${line}' | tee -a /etc/hosts >/dev/null`;
  const code = await run(SUDO_BIN, ['-n', 'sh', '-c', command]);
  if (code !== 0) throw new Error(`Unable to add temporary /etc/hosts mapping for ${expectedHost}`);
}

async function removeHostsMapping() {
  // Do not use `sed -i` on /etc/hosts: on containerized/hosted runners it may
  // be a bind mount that cannot be atomically renamed. Rewrite the existing
  // inode instead, so cleanup remains safe on GitHub-hosted runners.
  const command = [
    'tmp="$(mktemp)"',
    `grep -Fv '${hostsMarker}' /etc/hosts > "$tmp"`,
    'if [ -s "$tmp" ]; then',
    '  cat "$tmp" > /etc/hosts',
    'fi',
    'rm -f "$tmp"',
  ].join('; ');
  await run(SUDO_BIN, ['-n', 'sh', '-c', command]).catch(() => {});
}

async function startTunnel() {
  const configPath = `${process.env.HOME}/.ssh/config`;
  const args = [
    '-n',
    'sh',
    '-c',
    `echo $$ > ${tunnelPidfile} && exec ${SSH_BIN} -F ${configPath} -o BatchMode=yes -o ExitOnForwardFailure=yes -o ServerAliveInterval=15 -o ServerAliveCountMax=2 -N -L 127.0.0.1:443:127.0.0.1:443 -- ${originSshAlias}`,
  ];
  const child = spawn(SUDO_BIN, args, { stdio: ['ignore', 'inherit', 'inherit'], env: process.env });
  let exited = null;
  child.once('exit', (code, signal) => { exited = { code, signal }; });
  child.once('error', (err) => {
    console.error(`BLOCK_C_TUNNEL_ERROR=${err.message}`);
  });
  await delay(1800);
  if (exited) throw new Error(`Origin SSH tunnel exited before validation: code=${exited.code} signal=${exited.signal || ''}`);
  return child;
}

async function stopTunnel(child) {
  if (!child || child.exitCode !== null) return;
  
  // Use privileged termination via sudo since tunnel runs as root
  try {
    const pidData = await fs.readFile(tunnelPidfile, 'utf8');
    const pid = pidData.trim();
    if (pid) {
      await run(SUDO_BIN, ['-n', 'kill', '-TERM', pid]);
      await Promise.race([
        new Promise((resolve) => child.once('exit', resolve)),
        delay(3000),
      ]);
    }
  } catch (err) {
    console.warn(`BLOCK_C_TUNNEL_STOP_WARNING=${err instanceof Error ? err.message : String(err)}`);
  }
  
  // Fallback to direct kill if privileged termination failed
  if (child.exitCode === null) {
    try {
      child.kill('SIGTERM');
    } catch (err) {
      console.warn(`BLOCK_C_TUNNEL_DIRECT_KILL_WARNING=${err instanceof Error ? err.message : String(err)}`);
    }
  }
  
  // Cleanup pidfile
  try {
    await fs.unlink(tunnelPidfile);
  } catch {
    // Ignore cleanup failures
  }
}

async function verifyTunnel() {
  const code = await run('/usr/bin/curl', [
    '-kfsS', '--max-time', '20', '--resolve', `${expectedHost}:443:127.0.0.1`,
    '-H', 'Cache-Control: no-cache', '-H', 'Pragma: no-cache',
    '-o', '/dev/null', `${baseUrl}/`,
  ]);
  if (code !== 0) throw new Error('Origin browser SSH tunnel preflight failed');
}

function resultIsFullyVisualPass(result) {
  return result?.status === 'PASS'
    && result?.externalInconclusive !== true
    && result?.geometry != null
    && Number(result?.httpStatus || 0) === 200;
}

async function validateAndMarkFallbackResults() {
  const results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
  if (!Array.isArray(results) || results.length === 0) throw new Error('Origin browser fallback produced no Block C results');
  const invalid = results.filter((result) => !resultIsFullyVisualPass(result));
  if (invalid.length > 0) {
    console.error(`BLOCK_C_ORIGIN_BROWSER_FALLBACK=FAIL incomplete=${invalid.length}`);
    for (const result of invalid.slice(0, 12)) {
      console.error(`- route=${result?.route || '?'} viewport=${result?.viewport?.key || '?'} status=${result?.status || '?'} http=${result?.httpStatus ?? 0} geometry=${result?.geometry ? 'present' : 'missing'} inconclusive=${result?.externalInconclusive === true}`);
    }
    return false;
  }
  const marked = results.map((result) => ({
    ...result,
    validationTransport: 'siteground-origin-browser-ssh-tunnel',
    publicEdgeTransient: true,
  }));
  await fs.writeFile(resultsUrl, `${JSON.stringify(marked, null, 2)}\n`, 'utf8');
  console.log(`BLOCK_C_ORIGIN_BROWSER_FALLBACK=PASS results=${marked.length} transport=siteground-origin-browser-ssh-tunnel`);
  return true;
}

export async function runOriginBrowserFallback() {
  if (process.env.BLOCK_C_ORIGIN_BROWSER_FALLBACK === '0') return false;
  await preservePublicEdgeEvidence();

  let tunnel = null;
  try {
    // Start SSH before changing /etc/hosts so the SSH endpoint itself can never
    // be redirected to the local tunnel.
    tunnel = await startTunnel();
    await verifyTunnel();
    await appendHostsMapping();

    // Use trusted WP-CLI inventory preload if available to avoid public REST edge
    const wordpressPagesFile = (process.env.WORDPRESS_PAGES_FILE || '').trim();
    let preloadModule = null;
    if (wordpressPagesFile) {
      const preloadDir = new URL('./block-c-artifacts/', import.meta.url);
      const preloadUrl = new URL('./trusted-pages-preload.mjs', import.meta.url);
      const pages = JSON.parse(await fs.readFile(wordpressPagesFile, 'utf8'));
      const pagesEndpoint = `${baseUrl}/wp-json/wp/v2/pages`;
      const payload = JSON.stringify(pages);
      const source = `const nativeFetch = globalThis.fetch.bind(globalThis);\nconst pagesEndpoint = ${JSON.stringify(pagesEndpoint)};\nconst pagesPayload = ${JSON.stringify(payload)};\nglobalThis.fetch = async (input, init) => {\n  const rawUrl = typeof input === 'string' ? input : (input && typeof input.url === 'string' ? input.url : String(input));\n  if (rawUrl.startsWith(pagesEndpoint)) {\n    return new Response(pagesPayload, { status: 200, headers: { 'content-type': 'application/json; charset=utf-8', 'x-nvx-inventory-source': 'trusted-wp-cli-tunnel' } });\n  }\n  return nativeFetch(input, init);\n};\n`;
      await fs.mkdir(preloadDir, { recursive: true });
      await fs.writeFile(preloadUrl, source, 'utf8');
      preloadModule = preloadUrl.href;
    }

    const env = {
      ...process.env,
      BLOCK_C_VALIDATION_TRANSPORT: 'siteground-origin-browser-ssh-tunnel',
    };
    const args = preloadModule ? ['--import', preloadModule, matrixScript] : [matrixScript];
    const code = await run(process.execPath, args, { env });
    if (code !== 0) {
      console.error(`BLOCK_C_ORIGIN_BROWSER_FALLBACK=FAIL matrix_exit=${code}`);
      return false;
    }
    return await validateAndMarkFallbackResults();
  } catch (error) {
    console.error(`BLOCK_C_ORIGIN_BROWSER_FALLBACK=UNAVAILABLE reason=${error instanceof Error ? error.message : String(error)}`);
    return false;
  } finally {
    await removeHostsMapping();
    await stopTunnel(tunnel);
  }
}

if (process.argv[1] && import.meta.url === new URL(`file://${process.argv[1]}`).href) {
  const pass = await runOriginBrowserFallback();
  process.exit(pass ? 0 : 75);
}
