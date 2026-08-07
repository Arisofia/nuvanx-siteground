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

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
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
        'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        accept: 'text/html,application/xhtml+xml',
      },
    });

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

    result.status = response.status;
    result.finalUrl = finalUrl.toString();
    result.finalHost = finalUrl.hostname;
    result.redirects = hops;
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

console.log(
  `Staging boundary PASS: ${routes.length} routes stayed on ${expectedHost}, are noindex/nofollow, and expose SHA ${expectedSha}.`
);
