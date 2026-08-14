import { spawnSync } from 'node:child_process';
import { SITEGROUND_CAPTCHA_PATH } from './siteground-transient-classifier.mjs';

export { SITEGROUND_CAPTCHA_PATH };
export const ALLOWED_ORIGIN_SSH_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);
const SSH_BIN = '/usr/bin/ssh';

function validateRoute(route) {
  if (!/^\/[A-Za-z0-9_./%-]*$/.test(route)) {
    throw new Error(`Unsupported route characters: ${route}`);
  }
}

export function createSiteGroundOriginVerifier({
  expectedHost,
  expectedSha,
  originSshAlias = process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2',
} = {}) {
  if (!/^[a-z0-9.-]+$/.test(String(expectedHost || ''))) {
    throw new Error('EXPECTED_HOST contains unsupported characters.');
  }
  if (!/^[0-9a-f]{40}$/.test(String(expectedSha || ''))) {
    throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  }
  if (!ALLOWED_ORIGIN_SSH_ALIASES.has(originSshAlias)) {
    throw new Error(`ORIGIN_SSH_ALIAS must be one of: ${[...ALLOWED_ORIGIN_SSH_ALIASES].join(', ')}.`);
  }

  let available = null;

  function isAvailable() {
    if (available !== null) return available;
    const result = spawnSync(
      SSH_BIN,
      ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=5', '-o', 'ConnectionAttempts=1', '--', originSshAlias, 'exit'],
      { encoding: 'utf8', timeout: 15000 }
    );
    available = !result.error && result.status === 0;
    return available;
  }

  function verify(route) {
    validateRoute(route);
    const remoteScript = [
      'set -Eeuo pipefail',
      'base_url="https://$EXPECTED_HOST"',
      'headers="$(mktemp)"',
      'body="$(mktemp)"',
      'cleanup() { rm -f "$headers" "$body"; }',
      'trap cleanup EXIT',
      'result="$(curl -sS -L --max-redirs 5 --max-time 30 -A \'Mozilla/5.0 NUVANX-Origin-Verification/2.0\' -H \'Accept: text/html,application/xhtml+xml\' -D "$headers" -o "$body" -w \'%{http_code}|%{url_effective}\' "${base_url}${ROUTE}")"',
      'code="${result%%|*}"',
      'effective="${result#*|}"',
      'test "$code" = \'200\'',
      'case "$effective" in "https://${EXPECTED_HOST}/"*|"https://${EXPECTED_HOST}") ;; *) echo "ORIGIN_VERIFY_FAIL route=$ROUTE final=$effective" >&2; exit 1 ;; esac',
      `if grep -Fq '${SITEGROUND_CAPTCHA_PATH}' "$body"; then echo "ORIGIN_VERIFY_FAIL route=$ROUTE reason=captcha-body" >&2; exit 1; fi`,
      'if grep -Eiq \'^sg-captcha:[[:space:]]*challenge\' "$headers"; then echo "ORIGIN_VERIFY_FAIL route=$ROUTE reason=captcha-header" >&2; exit 1; fi',
      'extract_meta_content() {',
      String.raw`  php -r '$html=file_get_contents($argv[1]); $wanted=strtolower($argv[2]); preg_match_all("/<meta\b[^>]*>/is", $html, $tags); foreach ($tags[0] as $tag) { if (!preg_match("/\bname\s*=\s*(?:\x22([^\x22]+)\x22|\x27([^\x27]+)\x27)/is", $tag, $name)) continue; $actual=strtolower(trim(html_entity_decode($name[1] !== "" ? $name[1] : $name[2], ENT_QUOTES | ENT_HTML5, "UTF-8"))); if ($actual !== $wanted) continue; if (preg_match("/\bcontent\s*=\s*(?:\x22([^\x22]*)\x22|\x27([^\x27]*)\x27)/is", $tag, $content)) echo trim(html_entity_decode($content[1] !== "" ? $content[1] : $content[2], ENT_QUOTES | ENT_HTML5, "UTF-8")); break; }' "$body" "$1"`,
      '}',
      'deploy_sha="$(extract_meta_content nvx-deploy-sha)"',
      'test "$deploy_sha" = "$EXPECTED_SHA"',
      'robots_meta="$(extract_meta_content robots)"',
      'xrobots="$(grep -Ei \'^x-robots-tag:\' "$headers" | tail -n 1 | sed -E \'s/^[Xx]-[Rr]obots-[Tt]ag:[[:space:]]*//\' || true)"',
      'combined="${robots_meta}${xrobots:+,${xrobots}}"',
      'printf \'%s\' "$combined" | grep -Eiq \'noindex\'',
      'printf \'%s\' "$combined" | grep -Eiq \'nofollow\'',
      'if printf \'%s\' "$combined" | grep -Eiq \'(^|[^a-z])index[[:space:]]*,?[[:space:]]*follow([^a-z]|$)\'; then echo "ORIGIN_VERIFY_FAIL route=$ROUTE reason=index-follow" >&2; exit 1; fi',
      String.raw`robots_b64="$(printf '%s' "$combined" | base64 | tr -d '\n')"`,
      'echo "ORIGIN_VERIFY=PASS route=$ROUTE status=$code final=$effective sha=$deploy_sha robots_b64=$robots_b64"',
      '',
    ].join('\n');

    const remoteCommand = `EXPECTED_HOST=${expectedHost} EXPECTED_SHA=${expectedSha} ROUTE=${route} bash -se`;
    const result = spawnSync(
      SSH_BIN,
      ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=5', '-o', 'ConnectionAttempts=1', '--', originSshAlias, remoteCommand],
      { input: remoteScript, encoding: 'utf8', timeout: 60000, maxBuffer: 1024 * 1024 }
    );

    const stdout = (result.stdout || '').trim();
    const stderr = (result.stderr || '').trim();
    const statusMatch = stdout.match(/\bstatus=(\d{3})\b/);
    const shaMatch = stdout.match(/\bsha=([0-9a-f]{40})\b/);
    const robotsMatch = stdout.match(/robots_b64=([A-Za-z0-9+/=]+)/);

    return {
      attempted: true,
      pass: !result.error && result.status === 0,
      status: result.status,
      signal: result.signal || '',
      stdout,
      stderr,
      error: result.error ? result.error.message : '',
      originStatus: statusMatch ? Number.parseInt(statusMatch[1], 10) : null,
      originDeploySha: shaMatch ? shaMatch[1] : '',
      originRobots: robotsMatch ? Buffer.from(robotsMatch[1], 'base64').toString('utf8').trim() : '',
    };
  }

  return { originSshAlias, isAvailable, verify };
}

export function isBlockCTransientSiteGroundFailure(result, baseUrl) {
  if (!result || result.status === 'PASS') return false;
  const blockers = Array.isArray(result.blockers) ? result.blockers.map(String) : [];
  const issues = Array.isArray(result.issues) ? result.issues.map(String) : [];
  const networkErrors = Array.isArray(result.networkErrors) ? result.networkErrors.map(String) : [];
  const status = Number(result.httpStatus || 0);

  if (
    result.status === 'BLOCKED' &&
    blockers.length > 0 &&
    blockers.every((message) => /SiteGround Antibot challenge prevented visual validation/i.test(message)) &&
    issues.length === 0 &&
    [202, 429, 503].includes(status)
  ) return true;

  if (
    result.status === 'BLOCKED' &&
    status === 0 &&
    result.geometry == null &&
    blockers.length > 0 &&
    blockers.every((message) => /^Navigation returned no HTTP response$/i.test(message)) &&
    issues.length === 0 &&
    networkErrors.every((message) =>
      message.startsWith(`${baseUrl}${SITEGROUND_CAPTCHA_PATH}`) && message.endsWith(': net::ERR_ABORTED')
    )
  ) return true;

  if (
    result.status === 'FIX' &&
    blockers.length === 0 &&
    issues.length > 0 &&
    issues.every((message) => /^\d+ same-origin network error\(s\)$/i.test(message)) &&
    networkErrors.length > 0
  ) {
    const expectedDocumentUrl = `${baseUrl}${String(result.route || '')}`;
    return networkErrors.every((message) =>
      message === `${expectedDocumentUrl}: net::ERR_ABORTED` ||
      (message.startsWith(`${baseUrl}${SITEGROUND_CAPTCHA_PATH}`) && message.endsWith(': net::ERR_ABORTED'))
    );
  }

  return false;
}
