import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || 'nuvanx.com';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const routes = [
  '/',
  '/soluciones-medicas/',
  '/equipo-medico/',
  '/blog/',
  '/endolift-primeras-72-horas-que-esperar/',
];

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const outputDir = path.join(scriptDir, 'artifacts');
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

function robotsTokens(value) {
  return new Set(
    value
      .toLowerCase()
      .split(/[\s,]+/)
      .map((token) => token.trim())
      .filter(Boolean)
  );
}

async function fetchSameHost(url, maxRedirects = 5) {
  let current = new URL(url);
  const hops = [];

  for (let i = 0; i <= maxRedirects; i += 1) {
    if (current.hostname !== expectedHost) {
      throw new Error(`Refusing cross-host request: ${current.hostname} != ${expectedHost}`);
    }

    const response = await fetch(current, {
      redirect: 'manual',
      headers: {
        'user-agent': 'NUVANX-Production-Boundary/1.0',
        accept: 'text/html,application/xhtml+xml',
      },
    });

    const status = response.status;
    const location = response.headers.get('location');
    const sgCaptcha = response.headers.get('sg-captcha') || '';
    hops.push({ url: current.toString(), status, location, sgCaptcha });

    if (status === 202 || sgCaptcha) {
      throw new Error(`SiteGround challenge at ${current}: HTTP ${status} sg-captcha=${sgCaptcha || '(missing)'}`);
    }

    if (status >= 300 && status < 400) {
      if (!location) throw new Error(`Redirect ${status} without Location at ${current}`);
      const next = new URL(location, current);
      if (next.hostname !== expectedHost) {
        throw new Error(`Cross-host redirect detected: ${current.hostname} -> ${next.hostname}`);
      }
      current = next;
      continue;
    }

    return { response, finalUrl: current, hops };
  }

  throw new Error(`Too many redirects for ${url}`);
}

const report = {
  baseUrl,
  expectedHost,
  expectedSha,
  checkedAt: new Date().toISOString(),
  routes: [],
  failures: [],
};

for (const route of routes) {
  const requested = new URL(route, `${baseUrl}/`).toString();
  const result = { route, requested, pass: false };

  try {
    const { response, finalUrl, hops } = await fetchSameHost(requested);
    const html = await response.text();
    const robots = extractMetaContent(html, 'robots');
    const deploySha = extractMetaContent(html, 'nvx-deploy-sha');
    const xRobotsTag = response.headers.get('x-robots-tag') || '';
    const tokens = robotsTokens(`${robots},${xRobotsTag}`);

    result.status = response.status;
    result.finalUrl = finalUrl.toString();
    result.finalHost = finalUrl.hostname;
    result.redirects = hops;
    result.robots = robots;
    result.xRobotsTag = xRobotsTag;
    result.deploySha = deploySha;
    result.issues = [];

    if (response.status !== 200) result.issues.push(`Expected HTTP 200, got ${response.status}`);
    if (finalUrl.hostname !== expectedHost) result.issues.push(`Final hostname ${finalUrl.hostname} != ${expectedHost}`);
    if (tokens.has('noindex') || tokens.has('nofollow')) {
      result.issues.push(`Production exposes noindex/nofollow: meta="${robots}" x-robots="${xRobotsTag}"`);
    }
    if (!tokens.has('index') || !tokens.has('follow')) {
      result.issues.push(`Expected production robots index,follow; meta="${robots || '(missing)'}" x-robots="${xRobotsTag || '(missing)'}"`);
    }
    if (deploySha !== expectedSha) {
      result.issues.push(`Deployment SHA mismatch: meta=${deploySha || '(missing)'} expected=${expectedSha}`);
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
  path.join(outputDir, 'production-boundary.json'),
  `${JSON.stringify(report, null, 2)}\n`,
  'utf8'
);

if (!report.pass) {
  console.error('Production boundary verification FAILED.');
  for (const failure of report.failures) {
    console.error(`- ${failure.route}: ${failure.issues.join('; ')}`);
  }
  process.exit(1);
}

console.log(
  `Production boundary PASS: ${routes.length} routes stayed on ${expectedHost}, are index/follow, and expose SHA ${expectedSha}.`
);
