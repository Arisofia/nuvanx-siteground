import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);

function assertConfig(host, sha, alias) {
  if (!/^[a-z0-9.-]+$/.test(host)) throw new Error('EXPECTED_HOST contains unsupported characters');
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);
}

function validateSchema(manifest, schema) {
  // Basic structural validation
  if (!manifest.schema || manifest.schema !== 'nuvanx-publication-manifest') {
    throw new Error('Invalid manifest schema identifier');
  }

  if (!manifest.version || !/^\d+\.\d+\.\d+$/.test(manifest.version)) {
    throw new Error('Invalid manifest version format');
  }

  if (!manifest.generated_at || !Date.parse(manifest.generated_at)) {
    throw new Error('Invalid generated_at timestamp');
  }

  if (!manifest.source || !manifest.source.startsWith('https://')) {
    throw new Error('Invalid source URL');
  }

  if (!manifest.routes || typeof manifest.routes !== 'object') {
    throw new Error('Invalid routes structure');
  }

  const routeErrors = [];
  for (const [route, config] of Object.entries(manifest.routes)) {
    if (!route.startsWith('/')) {
      routeErrors.push(`${route}: route must start with /`);
    }

    if (typeof config.post_id !== 'number' || config.post_id < 0) {
      routeErrors.push(`${route}: invalid post_id`);
    }

    if (!config.post_type || typeof config.post_type !== 'string') {
      routeErrors.push(`${route}: invalid post_type`);
    }

    if (!config.status || typeof config.status !== 'string') {
      routeErrors.push(`${route}: invalid status`);
    }

    if (!config.robots || typeof config.robots !== 'object') {
      routeErrors.push(`${route}: invalid robots configuration`);
    } else {
      if (typeof config.robots.index !== 'boolean') {
        routeErrors.push(`${route}: robots.index must be boolean`);
      }
      if (typeof config.robots.follow !== 'boolean') {
        routeErrors.push(`${route}: robots.follow must be boolean`);
      }
    }

    if (config.schema && typeof config.schema === 'object') {
      if (config.schema.group && typeof config.schema.group !== 'string') {
        routeErrors.push(`${route}: schema.group must be string`);
      }
      if (config.schema.type && typeof config.schema.type !== 'string') {
        routeErrors.push(`${route}: schema.type must be string`);
      }
    }
  }

  if (routeErrors.length > 0) {
    throw new Error(`Route validation failed:\n${routeErrors.join('\n')}`);
  }

  return true;
}

export async function runPublicationManifestContract(options = {}) {
  const host = options.expectedHost || process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
  const sha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  assertConfig(host, sha, alias);
  await fs.mkdir(outputDir, { recursive: true });

  // Load schema
  const schemaPath = path.resolve('wp-content/themes/nuvanx-medical/inc/data/publication-manifest.schema.json');
  const schema = JSON.parse(await fs.readFile(schemaPath, 'utf8'));

  // Generate manifest via SSH
  const remoteScript = [
    'set -Eeuo pipefail',
    'cd /home/customer/www/nuvanx-siteground/public_html',
    'wp eval "require \'tools/migrations/generate-publication-manifest.php\';" --allow-root',
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 90000, maxBuffer: 8 * 1024 * 1024 },
  );

  if (result.error || result.status !== 0) {
    const reason = result.error?.message || result.stderr?.trim() || `exit ${result.status}`;
    throw new Error(`Manifest generation failed: ${reason}`);
  }

  const manifestOutput = result.stdout || '';
  const manifestPath = path.join(outputDir, 'publication-manifest.json');
  await fs.writeFile(manifestPath, manifestOutput, 'utf8');

  // Validate manifest
  const manifest = JSON.parse(manifestOutput);
  validateSchema(manifest, schema);

  // Check validation results (bidirectional blocking checks)
  if (!manifest.validation) {
    throw new Error('Manifest missing validation results');
  }

  const validationErrors = manifest.validation.errors || [];
  const validationPass = manifest.validation.pass !== false;

  if (!validationPass || validationErrors.length > 0) {
    const report = {
      schema: 'publication-manifest-contract',
      checkedAt: new Date().toISOString(),
      host,
      sha,
      manifestVersion: manifest.version,
      manifestSource: manifest.source,
      routeCount: Object.keys(manifest.routes).length,
      validation: 'FAIL',
      issues: validationErrors,
      missing: manifest.validation.missing || [],
      surplus: manifest.validation.surplus || [],
      changed: manifest.validation.changed || [],
    };

    await fs.writeFile(path.join(outputDir, 'publication-manifest-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

    console.error(`PUBLICATION_MANIFEST_CONTRACT=FAIL errors=${validationErrors.length}`);
    validationErrors.forEach((error) => console.error(`- ${error}`));

    if (report.missing.length > 0) {
      console.error(`Missing expected URLs: ${report.missing.join(', ')}`);
    }
    if (report.surplus.length > 0) {
      console.error(`Surplus published URLs: ${report.surplus.join(', ')}`);
    }
    if (report.changed.length > 0) {
      console.error(`Changed attributes: ${report.changed.length} route(s)`);
    }

    throw new Error(`Publication manifest contract failed with ${validationErrors.length} error(s). EXPECTED_PUBLIC_URLS !== ACTUAL_PUBLIC_URLS`);
  }

  // Write validation report
  const report = {
    schema: 'publication-manifest-contract',
    checkedAt: new Date().toISOString(),
    host,
    sha,
    manifestVersion: manifest.version,
    manifestSource: manifest.source,
    routeCount: Object.keys(manifest.routes).length,
    validation: 'PASS',
    issues: [],
    missing: [],
    surplus: [],
    changed: [],
  };

  await fs.writeFile(path.join(outputDir, 'publication-manifest-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

  console.log(`PUBLICATION_MANIFEST_CONTRACT=PASS routes=${report.routeCount} version=${manifest.version}`);
  return report;
}