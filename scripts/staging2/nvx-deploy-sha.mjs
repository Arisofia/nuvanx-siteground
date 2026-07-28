/**
 * Shared deploy-SHA acceptance helpers for staging2 smoke / acceptance.
 *
 * Source of truth for:
 * - extracting meta name="nvx-deploy-sha"
 * - requiring the marker on every governed HTML response
 * - optional exact match against EXPECTED_SHA / DEPLOY_SHA
 *
 * Bash smoke sources the sibling nvx-deploy-sha.sh (same messages / rules).
 */

const SHA40 = /^[0-9a-f]{40}$/;

/**
 * @param {NodeJS.ProcessEnv} [env]
 * @returns {string} empty when unset; throws when set but invalid
 */
export function resolveExpectedDeploySha(env = process.env) {
  const raw = String(env.EXPECTED_SHA || env.DEPLOY_SHA || '').trim();
  if (!raw) {
    return '';
  }
  if (!SHA40.test(raw)) {
    throw new Error('DEPLOY_SHA or EXPECTED_SHA must be a full lowercase 40-character SHA.');
  }
  return raw;
}

/**
 * Extract nvx-deploy-sha from an HTML document (name/content attribute order variants).
 *
 * @param {string} html
 * @returns {string}
 */
export function extractDeployShaFromHtml(html) {
  const source = String(html || '');
  const patterns = [
    /<meta\b[^>]*\bname\s*=\s*["']nvx-deploy-sha["'][^>]*\bcontent\s*=\s*["']([a-f0-9]{40})["'][^>]*>/i,
    /<meta\b[^>]*\bcontent\s*=\s*["']([a-f0-9]{40})["'][^>]*\bname\s*=\s*["']nvx-deploy-sha["'][^>]*>/i,
  ];
  for (const pattern of patterns) {
    const match = pattern.exec(source);
    if (match?.[1]) {
      return match[1].toLowerCase();
    }
  }
  return '';
}

/**
 * @param {string} html
 * @param {{ expectedSha?: string, pagePath?: string }} [options]
 * @returns {string|null} error message, or null when OK
 */
export function assertHtmlDeploySha(html, options = {}) {
  const pagePath = options.pagePath || 'page';
  const expectedSha = options.expectedSha || '';
  const deployedSha = extractDeployShaFromHtml(html);

  if (!deployedSha) {
    return `${pagePath}: missing meta nvx-deploy-sha (stale full-page cache or theme head not executing)`;
  }
  if (expectedSha && deployedSha !== expectedSha) {
    return `${pagePath}: served SHA ${deployedSha} instead of ${expectedSha}`;
  }
  return null;
}
