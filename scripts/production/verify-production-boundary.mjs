import fs from 'node:fs/promises';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import {
  extractMetaContent,
  validGitHubRunId,
  validateDeployIdentity,
} from './deploy-identity-contract.mjs';

const CANONICAL_PROD_ROOT = '/home/customer/www/nuvanx.com/public_html';
const ALLOWED_ORIGIN_ALIASES = new Set(['nvx-prod', 'nvx-prod-audit', 'nvx-prod-hubspot', 'production-siteground']);
const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || 'nuvanx.com';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
// EXPECTED_RUN_ID is intentionally explicit. A verification-only workflow has
// its own GITHUB_RUN_ID, which is not the run that deployed the live release.
const expectedRunId = (process.env.EXPECTED_RUN_ID || '').trim();
const originSshAlias = (process.env.ORIGIN_SSH_ALIAS || 'nvx-prod').trim().toLowerCase();
const prodRoot = process.env.PROD_ROOT || CANONICAL_PROD_ROOT;
const prodDbName = (process.env.PROD_DB_NAME || 'db0ecrycwv2tgb').trim();
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
const SITEGROUND_CAPTCHA_PATH = '/.well-known/sgcaptcha/';

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
if (expectedRunId && !validGitHubRunId(expectedRunId)) {
  console.error('EXPECTED_RUN_ID must be numeric when supplied.');
  process.exit(1);
}
if (!ALLOWED_ORIGIN_ALIASES.has(originSshAlias)) {
  console.error(`Unsupported ORIGIN_SSH_ALIAS: ${originSshAlias}`);
  process.exit(1);
}
if (prodRoot !== CANONICAL_PROD_ROOT) {
  console.error(`Refusing unexpected production root: ${prodRoot}`);
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

function robotsTokens(value) {
  return new Set(
    value
      .toLowerCase()
      .split(/[\s,]+/)
      .map((token) => token.trim())
      .filter(Boolean),
  );
}

function parseOriginIdentity(output) {
  const match = output.match(
    /PRODUCTION_ORIGIN_IDENTITY=PASS sha=([0-9a-f]{40}) run_id=(\d+) timestamp=(\S+) release_id=([A-Za-z0-9_-]+)/,
  );
  if (!match) throw new Error('Origin verification did not emit a parseable four-field deploy identity.');
  return {
    DEPLOY_SHA: match[1],
    DEPLOY_RUN_ID: match[2],
    DEPLOY_TIMESTAMP: match[3],
    RELEASE_ID: match[4],
  };
}

/**
 * Verify the live WordPress root and origin-rendered HTML against one immutable
 * deploy stamp. Attribute order and quote style in meta tags are not part of
 * the contract; the four semantic values are.
 * @return {string} Trimmed verification output from SiteGround origin.
 */
function verifyFromSiteGroundOrigin() {
  const remoteScript = String.raw`set -Eeuo pipefail
cd "$PROD_ROOT"
test "$(tr -d '\r\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)" = "$EXPECTED_SHA"
test "$(wp config get DB_NAME)" = "$PROD_DB_NAME"
test "$(wp option get home)" = 'https://nuvanx.com'
test "$(wp option get siteurl)" = 'https://nuvanx.com'
test "$(wp option get blog_public)" = '1'
test "$(wp theme list --status=active --field=name)" = 'nuvanx-medical'
test -f 'wp-content/themes/nuvanx-medical/.nvx-deploy-stamp.json'

stamp_sha="$(wp eval 'if ( ! function_exists("nvx_get_deploy_stamp_value") ) { require get_template_directory() . "/inc/nvx-deploy-stamp.php"; } echo nvx_get_deploy_stamp_value("DEPLOY_SHA");' --allow-root)"
stamp_run_id="$(wp eval 'if ( ! function_exists("nvx_get_deploy_stamp_value") ) { require get_template_directory() . "/inc/nvx-deploy-stamp.php"; } echo nvx_get_deploy_stamp_value("DEPLOY_RUN_ID");' --allow-root)"
stamp_timestamp="$(wp eval 'if ( ! function_exists("nvx_get_deploy_stamp_value") ) { require get_template_directory() . "/inc/nvx-deploy-stamp.php"; } echo nvx_get_deploy_stamp_value("DEPLOY_TIMESTAMP");' --allow-root)"
stamp_release="$(wp eval 'if ( ! function_exists("nvx_get_deploy_stamp_value") ) { require get_template_directory() . "/inc/nvx-deploy-stamp.php"; } echo nvx_get_deploy_stamp_value("RELEASE_ID");' --allow-root)"

test "$stamp_sha" = "$EXPECTED_SHA"
[[ "$stamp_run_id" =~ ^[0-9]+$ ]]
if [[ -n "$EXPECTED_RUN_ID" ]]; then test "$stamp_run_id" = "$EXPECTED_RUN_ID"; fi
php -r '$v=$argv[1]; $d=DateTimeImmutable::createFromFormat("Y-m-d\\TH:i:s\\Z", $v, new DateTimeZone("UTC")); if ($d === false || $d->format("Y-m-d\\TH:i:s\\Z") !== $v) { exit(1); }' "$stamp_timestamp"
[[ "$stamp_release" =~ ^[A-Za-z0-9_-]+$ ]]
echo "PRODUCTION_ORIGIN_IDENTITY=PASS sha=$stamp_sha run_id=$stamp_run_id timestamp=$stamp_timestamp release_id=$stamp_release"

assert_meta_equals() {
  local body="$1" name="$2" expected="$3" tag
  tag="$(tr '\r\n' '  ' < "$body" | grep -Eio '<meta[[:space:]][^>]*>' | grep -Ei "name[[:space:]]*=[[:space:]]*['\"]${name}['\"]" | head -n 1 || true)"
  if [[ -z "$tag" ]]; then
    echo "PRODUCTION_ORIGIN_FAIL reason=missing_meta name=$name" >&2
    return 1
  fi
  if ! printf '%s' "$tag" | grep -Eq "content[[:space:]]*=[[:space:]]*['\"]${expected}['\"]"; then
    echo "PRODUCTION_ORIGIN_FAIL reason=meta_mismatch name=$name expected=$expected tag=$tag" >&2
    return 1
  fi
}

meta_tag_for() {
  local body="$1" name="$2"
  tr '\r\n' '  ' < "$body" | grep -Eio '<meta[[:space:]][^>]*>' | grep -Ei "name[[:space:]]*=[[:space:]]*['\"]${name}['\"]" | head -n 1 || true
}

ua='NUVANX-Production-Origin-Boundary/1.1'
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

  assert_meta_equals "$body" 'nvx-deploy-sha' "$stamp_sha"
  assert_meta_equals "$body" 'nvx-deploy-run-id' "$stamp_run_id"
  assert_meta_equals "$body" 'nvx-deploy-timestamp' "$stamp_timestamp"
  assert_meta_equals "$body" 'nvx-release-id' "$stamp_release"

  robots_meta="$(meta_tag_for "$body" 'robots')"
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

echo "PRODUCTION_ORIGIN_BOUNDARY=PASS sha=$stamp_sha run_id=$stamp_run_id routes=8 identity_fields=4"
`;

  const output = execFileSync(
    '/usr/bin/ssh',
    [
      originSshAlias,
      `PROD_ROOT=${prodRoot} BASE_URL=${baseUrl} EXPECTED_SHA=${expectedSha} EXPECTED_RUN_ID=${expectedRunId} SITEGROUND_CAPTCHA_PATH=${SITEGROUND_CAPTCHA_PATH} PROD_DB_NAME=${prodDbName} bash -se`,
    ],
    {
      input: remoteScript,
      encoding: 'utf8',
      timeout: 180000,
      maxBuffer: 1024 * 1024,
    },
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
        'user-agent': 'NUVANX-Production-Boundary/1.3',
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
  expectedRunId: expectedRunId || null,
  originSshAlias,
  checkedAt: new Date().toISOString(),
  requestTimeoutMs,
  origin: { pass: false, output: '', issue: '', identity: null },
  external: { pass: false, inconclusiveAntiBot: false },
  routes: [],
  failures: [],
  pass: false,
};

try {
  report.origin.output = verifyFromSiteGroundOrigin();
  report.origin.identity = parseOriginIdentity(report.origin.output);
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
      const identity = {
        DEPLOY_SHA: extractMetaContent(html, 'nvx-deploy-sha'),
        DEPLOY_RUN_ID: extractMetaContent(html, 'nvx-deploy-run-id'),
        DEPLOY_TIMESTAMP: extractMetaContent(html, 'nvx-deploy-timestamp'),
        RELEASE_ID: extractMetaContent(html, 'nvx-release-id'),
      };
      const xRobotsTag = response.headers.get('x-robots-tag') || '';
      const tokens = robotsTokens(`${robots},${xRobotsTag}`);

      Object.assign(result, {
        status: response.status,
        finalUrl: finalUrl.toString(),
        finalHost: finalUrl.hostname,
        redirects: hops,
        robots,
        xRobotsTag,
        deploySha: identity.DEPLOY_SHA,
        deployRunId: identity.DEPLOY_RUN_ID,
        deployTimestamp: identity.DEPLOY_TIMESTAMP,
        releaseId: identity.RELEASE_ID,
        issues: [],
      });

      if (response.status !== 200) result.issues.push(`Expected HTTP 200, got ${response.status}`);
      if (finalUrl.hostname !== expectedHost) result.issues.push(`Final hostname ${finalUrl.hostname} != ${expectedHost}`);
      if (tokens.has('noindex') || tokens.has('nofollow')) {
        result.issues.push(`Production exposes noindex/nofollow: meta="${robots}" x-robots="${xRobotsTag}"`);
      }
      if (!tokens.has('index') || !tokens.has('follow')) {
        result.issues.push(`Expected production robots index,follow; meta="${robots || '(missing)'}" x-robots="${xRobotsTag || '(missing)'}"`);
      }
      result.issues.push(...validateDeployIdentity(identity, { expectedSha, expectedRunId }));
      if (identity.DEPLOY_RUN_ID !== report.origin.identity.DEPLOY_RUN_ID) {
        result.issues.push(`Edge/origin run ID mismatch: edge=${identity.DEPLOY_RUN_ID || '(missing)'} origin=${report.origin.identity.DEPLOY_RUN_ID}`);
      }
      if (identity.DEPLOY_TIMESTAMP !== report.origin.identity.DEPLOY_TIMESTAMP) {
        result.issues.push(`Edge/origin timestamp mismatch: edge=${identity.DEPLOY_TIMESTAMP || '(missing)'} origin=${report.origin.identity.DEPLOY_TIMESTAMP}`);
      }
      if (identity.RELEASE_ID !== report.origin.identity.RELEASE_ID) {
        result.issues.push(`Edge/origin release ID mismatch: edge=${identity.RELEASE_ID || '(missing)'} origin=${report.origin.identity.RELEASE_ID}`);
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
        failure.issues.every((issue) => /SiteGround challenge .*HTTP 202 .*sg-captcha=challenge/i.test(String(issue))),
    );
  report.pass = report.origin.pass && (report.external.pass || report.external.inconclusiveAntiBot);
}

await fs.writeFile(
  path.join(outputDir, 'production-boundary.json'),
  `${JSON.stringify(report, null, 2)}\n`,
  'utf8',
);

if (!report.pass) {
  console.error('Production boundary verification FAILED.');
  for (const failure of report.failures) {
    console.error(`- ${failure.route}: ${failure.issues.join('; ')}`);
  }
  process.exit(1);
}

const verifiedRunId = report.origin.identity?.DEPLOY_RUN_ID || expectedRunId || '(unknown)';
if (report.external.inconclusiveAntiBot) {
  console.log(
    `Production boundary PASS via SiteGround origin: ${routes.length} routes are 200, index/follow, expose the exact 4-field identity for SHA ${expectedSha} / run ${verifiedRunId}, and Signature hubs match the canonical shell. External GitHub probe=INCONCLUSIVE_ANTIBOT.`,
  );
} else {
  console.log(
    `Production boundary PASS: origin and external probes agree across ${routes.length} routes; index/follow and exact 4-field identity SHA ${expectedSha} / run ${verifiedRunId} verified.`,
  );
}
