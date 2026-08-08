import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const routes = [
  '/',
  '/soluciones-medicas/',
  '/equipo-medico/',
  '/blog/',
  '/endolift-primeras-72-horas-que-esperar/',
];
// Default to a single edge attempt. Callers that want transient retry behavior
// must opt in explicitly; deploy-staging2 already owns the outer retry loop.
const transientAttempts = Number.parseInt(process.env.STAGING_BOUNDARY_TRANSIENT_ATTEMPTS || '1', 10);
const transientBaseDelayMs = Number.parseInt(process.env.STAGING_BOUNDARY_TRANSIENT_DELAY_MS || '3000', 10);
const requestTimeoutMs = Number.parseInt(process.env.STAGING_BOUNDARY_REQUEST_TIMEOUT_MS || '15000', 10);

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
if (!Number.isInteger(transientAttempts) || transientAttempts < 1 || transientAttempts > 10) {
  console.error('STAGING_BOUNDARY_TRANSIENT_ATTEMPTS must be an integer from 1 to 10.');
  process.exit(1);
}
if (!Number.isInteger(transientBaseDelayMs) || transientBaseDelayMs < 250 || transientBaseDelayMs > 30000) {
  console.error('STAGING_BOUNDARY_TRANSIENT_DELAY_MS must be an integer from 250 to 30000.');
  process.exit(1);
}
if (!Number.isInteger(requestTimeoutMs) || requestTimeoutMs < 1000 || requestTimeoutMs > 60000) {
  console.error('STAGING_BOUNDARY_REQUEST_TIMEOUT_MS must be an integer from 1000 to 60000.');
  process.exit(1);
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
  if (response.status === 202 || response.status === 429 || response.status === 503) return true;
  return Boolean(response.headers.get('sg-captcha'));
}

async function fetchWithTransientRetry(current, retryLog) {
  let lastResponse;
  for (let attempt = 1; attempt <= transientAttempts; attempt += 1) {
    const response = await fetch(current, {
      redirect: 'manual',
      signal: AbortSignal.timeout(requestTimeoutMs),
      headers: {
        'user-agent': 'NUVANX-Staging-Boundary/1.1',
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
        throw new Error(
          `Cross-host redirect detected: ${current.hostname} -> ${next.hostname}`
        );
      }
      current = next;
      continue;
    }

    return { response, finalUrl: current, hops, transientRetries };
  }

  throw new Error(`Too many redirects for ${url}`);
}

const report = {
  baseUrl,
  expectedHost,
  expectedSha,
  checkedAt: new Date().toISOString(),
  transientAttempts,
  transientBaseDelayMs,
  requestTimeoutMs,
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

    result.status = response.status;
    result.finalUrl = finalUrl.toString();
    result.finalHost = finalUrl.hostname;
    result.redirects = hops;
    result.transientRetries = transientRetries;
    result.robots = robots;
    result.xRobotsTag = xRobotsTag;
    result.deploySha = deploySha;
    result.issues = [];

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
  (sum, route) => sum + (Array.isArray(route.transientRetries) ? route.transientRetries.length : 0),
  0
);
console.log(
  `Staging boundary PASS: ${routes.length} routes stayed on ${expectedHost}, are noindex/nofollow, expose SHA ${expectedSha}, transient_retries=${retryCount}, request_timeout_ms=${requestTimeoutMs}.`
);
