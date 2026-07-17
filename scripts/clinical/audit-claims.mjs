#!/usr/bin/env node

import { mkdir, readFile, readdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const registryPath = 'docs/clinical-claims/claims-register.json';
const deployableRoot = 'wp-content/themes/nuvanx-medical';
const evidenceDir = 'qa/clinical-claims';

async function walk(directory) {
  const output = [];
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) output.push(...await walk(full));
    else if (entry.isFile() && /\.(?:php|js|mjs|css)$/i.test(entry.name)) output.push(full);
  }
  return output;
}

function validateRegistry(registry) {
  const errors = [];
  const statuses = new Set(registry.allowed_statuses || []);
  const ids = new Set();

  if (registry.version !== 1) errors.push('Registry version must be 1.');
  if (!Array.isArray(registry.claims) || registry.claims.length === 0) errors.push('Registry must contain claims.');

  for (const [index, claim] of (registry.claims || []).entries()) {
    const label = claim.id || `claim-${index}`;
    if (!claim.id || !/^NVX-[A-Z0-9-]+$/.test(claim.id)) errors.push(`${label}: invalid or missing id.`);
    if (ids.has(claim.id)) errors.push(`${label}: duplicate id.`);
    ids.add(claim.id);
    if (!statuses.has(claim.status)) errors.push(`${label}: invalid status ${claim.status}.`);
    if (!claim.category || !claim.statement || !Array.isArray(claim.scope) || !claim.scope.length || !claim.owner) {
      errors.push(`${label}: category, statement, scope and owner are required.`);
    }
    if (claim.status === 'approved' && (!claim.source || !claim.review_due)) {
      errors.push(`${label}: approved claims require source and review_due.`);
    }
    if (claim.status === 'pending' && !claim.source_required) {
      errors.push(`${label}: pending claims require source_required.`);
    }
    if (claim.status === 'rejected' && !claim.reason) {
      errors.push(`${label}: rejected claims require reason.`);
    }
  }
  return errors;
}

function stripNonExecutableText(source) {
  return source
    .replace(/\/\*[\s\S]*?\*\//g, ' ')
    .replace(/(^|[^:])\/\/.*$/gm, '$1 ')
    .replace(/^\s*#.*$/gm, ' ');
}

async function run() {
  const registry = JSON.parse(await readFile(registryPath, 'utf8'));
  const validationErrors = validateRegistry(registry);
  const files = await walk(deployableRoot);
  const rejected = registry.claims.filter((claim) => claim.status === 'rejected');
  const matches = [];

  for (const file of files) {
    const raw = await readFile(file, 'utf8');
    const executable = stripNonExecutableText(raw);
    for (const claim of rejected) {
      let pattern;
      try {
        pattern = new RegExp(claim.statement, 'iu');
      } catch (error) {
        validationErrors.push(`${claim.id}: invalid rejected regex: ${error.message}`);
        continue;
      }
      if (pattern.test(executable)) {
        matches.push({ claim_id: claim.id, path: file, pattern: claim.statement });
      }
    }
  }

  const counts = Object.fromEntries(
    ['approved', 'pending', 'rejected'].map((status) => [status, registry.claims.filter((claim) => claim.status === status).length]),
  );
  const report = {
    generated_at: new Date().toISOString(),
    registry: registryPath,
    deployable_root: deployableRoot,
    files_scanned: files.length,
    status_counts: counts,
    validation_errors: validationErrors,
    rejected_matches: matches,
  };

  await mkdir(evidenceDir, { recursive: true });
  await writeFile(path.join(evidenceDir, 'claims-audit.json'), `${JSON.stringify(report, null, 2)}\n`);
  await writeFile(
    path.join(evidenceDir, 'claims-audit.md'),
    [
      '# Clinical claims audit',
      '',
      `Files scanned: **${files.length}**`,
      `Approved: **${counts.approved}** · Pending: **${counts.pending}** · Rejected: **${counts.rejected}**`,
      `Registry errors: **${validationErrors.length}**`,
      `Rejected deployable matches: **${matches.length}**`,
      '',
      ...validationErrors.map((error) => `- REGISTRY: ${error}`),
      ...matches.map((match) => `- REJECTED ${match.claim_id}: \`${match.path}\``),
      '',
    ].join('\n'),
  );

  console.log(JSON.stringify({ files: files.length, counts, validation_errors: validationErrors.length, rejected_matches: matches.length }));
  if (validationErrors.length || matches.length) process.exitCode = 1;
}

run().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
