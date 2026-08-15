import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { DEFAULT_ROUTES } from './shared-routes.mjs';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);

function assertConfig(host, sha, alias) {
  if (!/^[a-z0-9.-]+$/.test(host)) throw new Error('EXPECTED_HOST contains unsupported characters');
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);
}

function assertRoute(route) {
  if (!/^[A-Za-z0-9_./-]+$/.test(route)) throw new Error(`Route contains unsupported characters: ${route}`);
}

function fetchOriginHtml(route, host, alias) {
  assertRoute(route);
  const remoteScript = [
    'set -Eeuo pipefail',
    'url="https://${EXPECTED_HOST}${ROUTE}"',
    // SECURITY NOTE: -k is used because --resolve points to 127.0.0.1 which invalidates the cert.
    // This is acceptable in controlled staging environment with manual DNS resolution.
    // For production deployments, proper TLS verification would be required.
    'curl -kS -L --max-redirs 5 --max-time 45 --resolve "${EXPECTED_HOST}:443:127.0.0.1" -H \'Cache-Control: no-cache\' -H \'Pragma: no-cache\' -H \'Accept: text/html,application/xhtml+xml\' -A \'Mozilla/5.0 NUVANX-Single-JSONLD-Contract/1.0\' -w "\nNVX_HTTP_STATUS:%{http_code}\n" "$url"',
  ].join('\n');
  const remoteCommand = `EXPECTED_HOST='${host}' ROUTE='${route}' bash -se`;
  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=8', '-o', 'ConnectionAttempts=1', '--', alias, remoteCommand],
    { input: remoteScript, encoding: 'utf8', timeout: 60000, maxBuffer: 8 * 1024 * 1024 },
  );
  if (result.error || result.status !== 0) {
    throw new Error(`Origin fetch failed for ${route}: ${(result.stderr || result.error?.message || `exit ${result.status}`).trim()}`);
  }
  const stdout = result.stdout || '';
  const statusMatch = stdout.match(/NVX_HTTP_STATUS:(\d+)/);
  const httpStatus = statusMatch ? parseInt(statusMatch[1], 10) : 0;
  if (httpStatus !== 200) {
    throw new Error(`Origin fetch failed for ${route}: expected HTTP 200, got ${httpStatus}`);
  }
  const markerIndex = stdout.lastIndexOf('NVX_HTTP_STATUS:');
  return markerIndex > 0 ? stdout.slice(0, markerIndex).trim() : stdout;
}

function deploySha(html) {
  const tags = html.match(/<meta\b[^>]*>/gi) || [];
  for (const tag of tags) {
    if (!/\bname\s*=\s*["']nvx-deploy-sha["']/i.test(tag)) continue;
    const match = tag.match(/\bcontent\s*=\s*["']([^"']+)["']/i);
    return match ? match[1].trim() : '';
  }
  return '';
}

function jsonLdBlocks(html) {
  const blocks = [];
  const regex = /<script\b([^>]*)>([\s\S]*?)<\/script>/gi;
  let match;
  while ((match = regex.exec(html)) !== null) {
    // Match type attribute with optional quotes to align with rendered-schema-contract.mjs and content-hygiene PCRE
    if (/\btype\s*=\s*["']?application\/ld\+json["']?/i.test(match[1] || '')) {
      const content = (match[2] || '').trim();
      if (content) blocks.push(content);
    }
  }
  return blocks;
}

export async function runSingleJsonLdSourceContract(options = {}) {
  const host = options.expectedHost || process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
  const sha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  const routes = options.routes || DEFAULT_ROUTES;
  assertConfig(host, sha, alias);
  routes.forEach(assertRoute);
  await fs.mkdir(outputDir, { recursive: true });

  const report = { schema: 1, checkedAt: new Date().toISOString(), host, sha, routes: [], issues: [] };
  for (const route of routes) {
    try {
      const html = fetchOriginHtml(route, host, alias);
      const actualSha = deploySha(html);
      const blocks = jsonLdBlocks(html);
      const item = { route, deploySha: actualSha, jsonLdBlocks: blocks.length };
      report.routes.push(item);
      if (actualSha !== sha) report.issues.push(`${route}: deploy SHA mismatch actual=${actualSha || '(missing)'} expected=${sha}`);
      if (blocks.length !== 1) report.issues.push(`${route}: expected exactly one application/ld+json block, found ${blocks.length}`);
      if (blocks.length === 1) {
        try {
          const parsed = JSON.parse(blocks[0]);
          const context = parsed?.['@context'];
          if (!(context === 'https://schema.org' || context === 'http://schema.org' || Array.isArray(parsed?.['@graph']))) {
            report.issues.push(`${route}: canonical JSON-LD block does not look like governed Schema.org graph`);
          }
        } catch (error) {
          report.issues.push(`${route}: canonical JSON-LD block is invalid JSON: ${error.message}`);
        }
      }
    } catch (error) {
      report.routes.push({ route, deploySha: '', jsonLdBlocks: 0, error: String(error?.message || error) });
      report.issues.push(`${route}: ${String(error?.message || error)}`);
    }
  }

  report.pass = report.issues.length === 0;
  await fs.writeFile(path.join(outputDir, 'single-jsonld-source-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
  if (!report.pass) {
    console.error(`SINGLE_JSONLD_SOURCE_CONTRACT=FAIL issues=${report.issues.length}`);
    report.issues.forEach((issue) => console.error(`- ${issue}`));
    throw new Error(`Single JSON-LD source contract failed with ${report.issues.length} issue(s)`);
  }
  console.log(`SINGLE_JSONLD_SOURCE_CONTRACT=PASS routes=${report.routes.length} sha=${sha}`);
  return report;
}
