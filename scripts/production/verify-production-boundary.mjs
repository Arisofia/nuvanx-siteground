import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || 'nuvanx.com';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const prodRoot = process.env.PROD_ROOT || '/home/customer/www/nuvanx.com/public_html';
const requestTimeoutMs = Number.parseInt(process.env.PRODUCTION_BOUNDARY_REQUEST_TIMEOUT_MS || '15000', 10);
const routes = [
  '/',
  '/soluciones-medicas/',
  '/equipo-medico/',
  '/blog/',
  '/endolift-primeras-72-horas-que-esperar/',
  '/protocolos-signature/',
  '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
];

// Provider-specific paths for captcha detection
const SITEGROUND_CAPTCHA_PATH = '/.well-known/sgcaptcha/';

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
if (!Number.isInteger(requestTimeoutMs) || requestTimeoutMs < 1000 || requestTimeoutMs > 60000) {
  console.error('PRODUCTION_BOUNDARY_REQUEST_TIMEOUT_MS must be an integer from 1000 to 60000.');
  process.exit(1);
}

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const outputDir = (process.env.PRODUCTION_BOUNDARY_ARTIFACTS_DIR || process.env.ARTIFACTS_DIR)
  ? path.resolve(process.env.PRODUCTION_BOUNDARY_ARTIFACTS_DIR || process.env.ARTIFACTS_DIR)
  : path.join(scriptDir, 'artifacts');
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

/**
 * Verifies the production site from its SiteGround origin.
 * @return {string} The trimmed verification output from the remote origin.
 */
function verifyFromSiteGroundOrigin() {
  const remoteScript = String.raw`set -Eeuo pipefail
cd "$PROD_ROOT"
test "$(tr -d '\r\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)" = "$EXPECTED_SHA"
test "$(wp config get DB_NAME)" = 'db0ecrycwv2tgb'
test "$(wp option get home)" = 'https://nuvanx.com'
test "$(wp option get siteurl)" = 'https://nuvanx.com'
test "$(wp option get blog_public)" = '1'
test "$(wp theme list --status=active --field=name)" = 'nuvanx-medical'

ua='NUVANX-Production-Origin-Boundary/1.0'
for route in \
  '/' \
  '/soluciones-medicas/' \
  '/equipo-medico/' \
  '/blog/' \
  '/endolift-primeras-72-horas-que-esperar/' \
  '/protocolos-signature/' \
  '/remodelacion-corporal-laser-madrid/' \
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'
do
  headers="$(mktemp)"
  body="$(mktemp)"
  result="$(curl -sS -L --max-redirs 5 --max-time 30 -A "$ua" -H 'Accept: text/html,application/xhtml+xml' -H 'Cache-Control: no-cache' -D "$headers" -o "$body" -w '%{http_code}|%{url_effective}' "$BASE_URL$route")"
  code="$(printf '%s' "$result" | cut -d'|' -f1)"
  effective="$(printf '%s' "$result" | cut -d'|' -f2-)"
  test "$code" = '200'
  case "$effective" in
    https://nuvanx.com/*|https://nuvanx.com) ;;
    *) echo "PRODUCTION_ORIGIN_FAIL route=$route final=$effective" >&2; rm -f "$headers" "$body"; exit 1 ;;
  esac
  ! grep -Fq '${SITEGROUND_CAPTCHA_PATH}' "$body"
  ! grep -Eiq '^sg-captcha:[[:space:]]*challenge' "$headers"
  grep -Fq "$EXPECTED_SHA" "$body"

  robots_meta="$(grep -Eio "<meta[^>]+name=['\"]robots['\"][^>]*>" "$body" | head -n 1 || true)"
  xrobots="$(grep -Ei '^x-robots-tag:' "$headers" | tail -n 1 || true)"
  combined="$robots_meta $xrobots"
  if printf '%s' "$combined" | grep -Eiq 'noindex|nofollow'; then
    echo "PRODUCTION_ORIGIN_FAIL route=$route reason=noindex-or-nofollow robots=$combined" >&2
    rm -f "$headers" "$body"
    exit 1
  fi
  printf '%s' "$combined" | grep -Eiq 'index'
  printf '%s' "$combined" | grep -Eiq 'follow'

  case "$route" in
    '/protocolos-signature/')
      grep -Fq 'nvx-brand-page nvx-brand-page--signature' "$body"
      grep -Fq 'Una ruta de decisión, no un paquete cerrado' "$body"
      grep -Fq 'Arquitecturas clínicas' "$body"
      ;;
    '/remodelacion-corporal-laser-madrid/')
      grep -Fq 'nvx-brand-page nvx-brand-page--signature' "$body"
      grep -Fq 'Cómo se decide el plan corporal' "$body"
      grep -Fq 'Zonas de valoración' "$body"
      grep -Fq 'Tu primera valoración clínica' "$body"
      ;;
    '/tratamiento-postparto-abdomen-contorno-corporal-madrid/')
      grep -Fq 'nvx-brand-page nvx-brand-page--signature' "$body"
      grep -Fq 'Qué se valora en postparto' "$body"
      grep -Fq 'Rutas relacionadas' "$body"
      grep -Fq 'Tu primera valoración clínica' "$body"
      ;;
  esac

  rm -f "$headers" "$body"
  echo "PRODUCTION_ORIGIN_ROUTE=PASS route=$route"
done

echo "PRODUCTION_ORIGIN_BOUNDARY=PASS sha=$EXPECTED_SHA routes=8"
`;

  const output = execFileSync(
    '/usr/bin/ssh',
    [
      'nvx-prod',
      `PROD_ROOT=${prodRoot} BASE_URL=${baseUrl} EXPECTED_SHA=${expectedSha} SITEGROUND_CAPTCHA_PATH=${SITEGROUND_CAPTCHA_PATH} bash -se`,
    ],
    {
      input: remoteScript,
      encoding: 'utf8',
      timeout: 180000,
      maxBuffer: 1024 * 1024,
    }
  );
  return output.trim();
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
      signal: AbortSignal.timeout(requestTimeoutMs),
      headers: {
        'user-agent': 'NUVANX-Production-Boundary/1.1',
        accept: 'text/html,application/xhtml+xml',
        'cache-control': 'no-cache',
        pragma: 'no-cache',
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
  requestTimeoutMs,
  origin: { pass: false, output: '', issue: '' },
  external: { pass: false, inconclusiveAntiBot: false },
  routes: [],
  failures: [],
  pass: false,
};

try {
  report.origin.output = verifyFromSiteGroundOrigin();
  report.origin.pass = true;
  console.log(report.origin.output);
} catch (error) {
  report.origin.issue = error instanceof Error ? error.message : String(error);
  report.failures.push({ route: 'origin', issues: [report.origin.issue] });
}

if (report.origin.pass) {
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

  const externalFailures = report.failures.filter((failure) => failure.route !== 'origin');
  report.external.pass = externalFailures.length === 0;
  report.external.inconclusiveAntiBot =
    externalFailures.length > 0 &&
    externalFailures.every(
      (failure) =>
        Array.isArray(failure.issues) &&
        failure.issues.length > 0 &&
        failure.issues.every((issue) => /SiteGround challenge .*HTTP 202 .*sg-captcha=challenge/i.test(String(issue)))
    );

  report.pass = report.origin.pass && (report.external.pass || report.external.inconclusiveAntiBot);
}

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

if (report.external.inconclusiveAntiBot) {
  console.log(
    `Production boundary PASS via SiteGround origin: ${routes.length} routes are 200, index/follow, expose SHA ${expectedSha}, and Signature hubs match the canonical shell. External GitHub probe=INCONCLUSIVE_ANTIBOT.`
  );
} else {
  console.log(
    `Production boundary PASS: origin and external probes agree across ${routes.length} routes; index/follow and SHA ${expectedSha} verified.`
  );
}
