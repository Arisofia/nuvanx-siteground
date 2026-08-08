import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const originSshAlias = 'nvx-staging2';
const routes = [
  '/',
  '/soluciones-medicas/',
  '/equipo-medico/',
  '/blog/',
  '/endolift-primeras-72-horas-que-esperar/',
];

const transientAttempts = Number.parseInt(
  process.env.STAGING_BOUNDARY_TRANSIENT_ATTEMPTS || '1',
  10
);
const transientBaseDelayMs = Number.parseInt(
  process.env.STAGING_BOUNDARY_TRANSIENT_DELAY_MS || '3000',
  10
);
const requestTimeoutMs = Number.parseInt(
  process.env.STAGING_BOUNDARY_REQUEST_TIMEOUT_MS || '15000',
  10
);

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
if (!/^[a-z0-9.-]+$/.test(expectedHost)) {
  console.error('EXPECTED_HOST contains unsupported characters.');
  process.exit(1);
}
const parsedBaseUrl = new URL(baseUrl);
if (parsedBaseUrl.protocol !== 'https:' || parsedBaseUrl.hostname !== expectedHost) {
  console.error(`BASE_URL must be HTTPS on ${expectedHost}.`);
  process.exit(1);
}
if (!Number.isInteger(transientAttempts) || transientAttempts < 1 || transientAttempts > 10) {
  console.error('STAGING_BOUNDARY_TRANSIENT_ATTEMPTS must be an integer from 1 to 10.');
  process.exit(1);
}
if (
  !Number.isInteger(transientBaseDelayMs) ||
  transientBaseDelayMs < 250 ||
  transientBaseDelayMs > 30000
) {
  console.error('STAGING_BOUNDARY_TRANSIENT_DELAY_MS must be an integer from 250 to 30000.');
  process.exit(1);
}
if (!Number.isInteger(requestTimeoutMs) || requestTimeoutMs < 1000 || requestTimeoutMs > 60000) {
  console.error('STAGING_BOUNDARY_REQUEST_TIMEOUT_MS must be an integer from 1000 to 60000.');
  process.exit(1);
}
for (const route of routes) {
  if (!/^\/[A-Za-z0-9_./-]*$/.test(route)) {
    console.error(`Unsupported route characters: ${route}`);
    process.exit(1);
  }
}

const outputDir = path.resolve('scripts/staging2/artifacts');
await fs.mkdir(outputDir, { recursive: true });

function extractMetaContent(html, name) {
  const tags = html.match(/<meta\b[^>]*>/gi) || [];
  for (const tag of tags) {
    const nameMatch = tag.match(/\bname\s*=\s*["']([^"']+)["']/i);
    if (!nameMatch || nameMatch[1].toLowerCase() !== name.toLowerCase()) continue;
    const contentMatch = tag.match(/\bcontent\s*=\s*["']([^"']*)["']/i);
    return contentMatch ? contentMatch[1].trim() : '';
  }
  return '';
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function isTransientSiteGroundChallenge(response) {
  if ([202, 429, 503].includes(response.status)) return true;
  return Boolean(response.headers.get('sg-captcha'));
}

async function sshAliasConfigured(alias) {
  const home = process.env.HOME || '';
  if (!home) return false;
  try {
    const config = await fs.readFile(path.join(home, '.ssh', 'config'), 'utf8');
    return config.split(/\r?\n/).some((line) => {
      const parts = line.trim().split(/\s+/);
      return (
        parts.length > 1 &&
        parts[0].toLowerCase() === 'host' &&
        parts.slice(1).includes(alias)
      );
    });
  } catch {
    return false;
  }
}

function verifyViaSiteGroundOrigin(route) {
  const remoteScript = [
    'set -Eeuo pipefail',
    'base_url="https://${EXPECTED_HOST}"',
    'headers="$(mktemp)"',
    'body="$(mktemp)"',
    'cleanup() { rm -f "$headers" "$body"; }',
    'trap cleanup EXIT',
    'result="$(curl -sS -L --max-redirs 5 --max-time 30 -A \'NUVANX-Staging-Origin-Boundary/1.1\' -H \'Accept: text/html,application/xhtml+xml\' -D "$headers" -o "$body" -w \'%{http_code}|%{url_effective}\' "${base_url}${ROUTE}")"',
    'code="${result%%|*}"',
    'effective="${result#*|}"',
    'test "$code" = \'200\'',
    'case "$effective" in',
    '  "https://${EXPECTED_HOST}/"*|"https://${EXPECTED_HOST}") ;;',
    '  *) echo "ORIGIN_BOUNDARY_FAIL route=$ROUTE final=$effective" >&2; exit 1 ;;',
    'esac',
    '! grep -Fq \'/.well-known/sgcaptcha/\' "$body"',
    '! grep -Eiq \'^sg-captcha:[[:space:]]*challenge\' "$headers"',
    'grep -Fq "$EXPECTED_SHA" "$body"',
    'robots_meta="$(grep -Eio \'<meta[^>]+name=[^ >]*robots[^ >]*[^>]*>\' "$body" | head -n 1 || true)"',
    'xrobots="$(grep -Ei \'^x-robots-tag:\' "$headers" | tail -n 1 || true)"',
    'combined="${robots_meta} ${xrobots}"',
    'printf \'%s\' "$combined" | grep -Eiq \'noindex\'',
    'printf \'%s\' "$combined" | grep -Eiq \'nofollow\'',
    'if printf \'%s\' "$combined" | grep -Eiq \'(^|[^a-z])index[[:space:]]*,?[[:space:]]*follow\'; then',
    '  echo "ORIGIN_BOUNDARY_FAIL route=$ROUTE reason=index-follow" >&2',
    '  exit 1',
    'fi',
    'echo "ORIGIN_BOUNDARY=PASS route=$ROUTE status=$code final=$effective sha=$EXPECTED_SHA"',
    '',
  ].join('\n');

  const remoteCommand = `EXPECTED_HOST=${expectedHost} EXPECTED_SHA=${expectedSha} ROUTE=${route} bash -se`;
  const result = spawnSync('/usr/bin/ssh', [originSshAlias, remoteCommand], {
    input: remoteScript,
    encoding: 'utf8',
    timeout: 60000,
    maxBuffer: 1024 * 1024,
  });

  return {
    attempted: true,
    pass: result.status === 0,
    status: result.status,
    signal: result.signal || '',
    stdout: (result.stdout || '').trim(),
    stderr: (result.stderr || '').trim(),
    error: result.error ? result.error.message : '',
  };
}

async function fetchWithTransientRetry(current, retryLog) {
  let lastResponse;
  for (let attempt = 1; attempt <= transientAttempts; attempt += 1) {
    const response = await fetch(current, {
      redirect: 'manual',
      signal: AbortSignal.timeout(requestTimeoutMs),
      headers: {
        'user-agent': 'NUVANX-Staging-Boundary/1.2',
        accept: 'text/html,application/xhtml+xml',
        'cache-control': 'no-cache',
        pragma: 'no-cache',
      },
    });
    lastResponse = response;

    if (!isTransientSiteGroundChallenge(response)) return response;

    retryLog.push({
      attempt,
      status: response.status,
      sgCaptcha: response.headers.get('sg-captcha') || '',
      url: current.toString(),
    });

    if (attempt < transientAttempts) {
      await response.body?.cancel().catch(() => {});
      await sleep(transientBaseDelayMs * attempt);
    }
  }
  return lastResponse;
}

async function fetchSameHost(url, maxRedirects = 5) {
  let current = new URL(url);
  const hops = [];
  const transientRetries = [];

  for (let i = 0; i <= maxRedirects; i += 1) {
    if (current.hostname !== expectedHost) {
      throw new Error(`Refusing cross-host request: ${current.hostname} != ${expectedHost}`);
    }

    const response = await fetchWithTransientRetry(current, transientRetries);
    const status = response.status;
    const location = response.headers.get('location');
    hops.push({ url: current.toString(), status, location });

    if (status >= 300 && status < 400) {
      if (!location) throw new Error(`Redirect ${status} without Location at ${current}`);
      const next = new URL(location, current);
      if (next.hostname !== expectedHost) {
        throw new Error(`Cross-host redirect detected: ${current.hostname} -> ${next.hostname}`);
      }
      current = next;
      continue;
    }

    return { response, finalUrl: current, hops, transientRetries };
  }

  throw new Error(`Too many redirects for ${url}`);
}

const originFallbackAvailable = await sshAliasConfigured(originSshAlias);
const report = {
  baseUrl,
  expectedHost,
  expectedSha,
  checkedAt: new Date().toISOString(),
  transientAttempts,
  transientBaseDelayMs,
  requestTimeoutMs,
  originFallbackAlias: originSshAlias,
  originFallbackAvailable,
  routes: [],
  failures: [],
};

for (const route of routes) {
  const requested = new URL(route, `${baseUrl}/`).toString();
  const result = { route, requested, pass: false };

  try {
    const { response, finalUrl, hops, transientRetries } = await fetchSameHost(requested);
    const html = await response.text();
    const robots = extractMetaContent(html, 'robots');
    const deploySha = extractMetaContent(html, 'nvx-deploy-sha');
    const xRobotsTag = response.headers.get('x-robots-tag') || '';
    const transientEdge = isTransientSiteGroundChallenge(response);

    result.status = response.status;
    result.finalUrl = finalUrl.toString();
    result.finalHost = finalUrl.hostname;
    result.redirects = hops;
    result.transientRetries = transientRetries;
    result.robots = robots;
    result.xRobotsTag = xRobotsTag;
    result.deploySha = deploySha;
    result.issues = [];

    if (transientEdge && originFallbackAvailable) {
      result.externalInconclusive = true;
      result.originFallback = verifyViaSiteGroundOrigin(route);
      if (result.originFallback.pass) {
        result.pass = true;
        report.routes.push(result);
        continue;
      }
      result.issues.push(
        `SiteGround origin fallback failed: ${
          result.originFallback.stderr ||
          result.originFallback.error ||
          `exit ${result.originFallback.status}`
        }`
      );
    }

    if (response.status !== 200) {
      result.issues.push(`Expected HTTP 200, got ${response.status}`);
    }
    if (finalUrl.hostname !== expectedHost) {
      result.issues.push(`Final hostname ${finalUrl.hostname} != ${expectedHost}`);
    }

    const robotsLower = robots.toLowerCase();
    if (!robotsLower.includes('noindex') || !robotsLower.includes('nofollow')) {
      result.issues.push(`Expected robots noindex,nofollow; got "${robots || '(missing)'}"`);
    }
    if (/\bindex\s*,\s*follow\b/i.test(robots)) {
      result.issues.push(`Staging exposes index,follow robots content: "${robots}"`);
    }
    if (deploySha !== expectedSha) {
      result.issues.push(
        `Deployment SHA mismatch: meta=${deploySha || '(missing)'} expected=${expectedSha}`
      );
    }

    result.pass = result.issues.length === 0;
    if (!result.pass) report.failures.push({ route, issues: result.issues });
  } catch (error) {
    result.issues = [error instanceof Error ? error.message : String(error)];
    report.failures.push({ route, issues: result.issues });
  }

  report.routes.push(result);
}

report.pass = report.failures.length === 0;
await fs.writeFile(
  path.join(outputDir, 'staging2-boundary.json'),
  `${JSON.stringify(report, null, 2)}\n`,
  'utf8'
);

if (!report.pass) {
  console.error('Staging boundary verification FAILED.');
  for (const failure of report.failures) {
    console.error(`- ${failure.route}: ${failure.issues.join('; ')}`);
  }
  process.exit(1);
}

const retryCount = report.routes.reduce(
  (sum, route) =>
    sum + (Array.isArray(route.transientRetries) ? route.transientRetries.length : 0),
  0
);
const originFallbackCount = report.routes.filter((route) => route.originFallback?.pass).length;
console.log(
  `Staging boundary PASS: ${routes.length} routes stayed on ${expectedHost}; strict edge/origin verification exposes SHA ${expectedSha}; transient_retries=${retryCount}; origin_fallbacks=${originFallbackCount}; request_timeout_ms=${requestTimeoutMs}.`
);
