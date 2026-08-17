#!/usr/bin/env node
/**
 * Blocks Endoláser content/schema/tariff changes without an explicit, versioned
 * approval record. The record contains references only; evidence itself remains
 * in the approved private clinical/compliance repository.
 */
import fs from 'node:fs/promises';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const protectedPaths = new Set([
  'wp-content/themes/nuvanx-medical/inc/data/endolaser-page.json',
  'wp-content/themes/nuvanx-medical/inc/nvx-endolaser-page.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php',
  'wp-content/themes/nuvanx-medical/inc/data/seo-metadata.json',
  'wp-content/themes/nuvanx-medical/inc/data/tariff-catalog.json',
  'wp-content/themes/nuvanx-medical/inc/data/routes.json',
]);
const approvalPath = 'docs/approvals/endolaser-content-approval.json';

function nonEmpty(value) {
  return typeof value === 'string' && value.trim().length > 0;
}

function gitRefExists(ref) {
  try {
    execFileSync('git', ['rev-parse', '--verify', ref], { cwd: repoRoot, stdio: 'ignore' });
    return true;
  } catch {
    return false;
  }
}

function githubPullRequestBaseSha() {
  const eventPath = process.env.GITHUB_EVENT_PATH;
  if (!nonEmpty(eventPath)) {
    return '';
  }
  try {
    const event = JSON.parse(readFileSync(eventPath, 'utf8'));
    return typeof event?.pull_request?.base?.sha === 'string' ? event.pull_request.base.sha.trim() : '';
  } catch {
    return '';
  }
}

function resolveApprovalBase() {
  if (nonEmpty(process.env.ENDOLASER_APPROVAL_BASE)) {
    return process.env.ENDOLASER_APPROVAL_BASE.trim();
  }

  const eventBaseSha = githubPullRequestBaseSha();
  if (eventBaseSha && gitRefExists(eventBaseSha)) {
    return eventBaseSha;
  }

  const githubBaseRef = nonEmpty(process.env.GITHUB_BASE_REF) ? process.env.GITHUB_BASE_REF.trim() : '';
  if (githubBaseRef) {
    const remoteRef = `origin/${githubBaseRef}`;
    if (gitRefExists(remoteRef)) {
      return remoteRef;
    }
    if (gitRefExists(githubBaseRef)) {
      return githubBaseRef;
    }
  }

  for (const candidate of ['origin/master', 'origin/main', 'master', 'main']) {
    if (gitRefExists(candidate)) {
      return candidate;
    }
  }

  return '';
}

function changedFiles() {
  const base = resolveApprovalBase();
  if (!nonEmpty(base)) {
    console.error('ENDOLASER_APPROVAL=FAIL reason=base_diff_unavailable base=unresolved');
    process.exit(1);
  }
  try {
    return execFileSync('git', ['diff', '--name-only', `${base}...HEAD`], { cwd: repoRoot, encoding: 'utf8' })
      .split('\n').map((item) => item.trim()).filter(Boolean);
  } catch {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=base_diff_unavailable base=${base}`);
    process.exit(1);
  }
}

function validApproval(value) {
  return value && typeof value === 'object'
    && nonEmpty(value.approved_by)
    && nonEmpty(value.approved_at)
    && Array.isArray(value.evidence_references)
    && value.evidence_references.length > 0
    && value.evidence_references.every(nonEmpty);
}

const changed = changedFiles();
const touchedProtectedPath = changed.some((file) => protectedPaths.has(file));
if (!touchedProtectedPath) {
  console.log('ENDOLASER_APPROVAL=PASS reason=no_protected_change');
  process.exit(0);
}

if (!changed.includes(approvalPath)) {
  console.error('ENDOLASER_APPROVAL=FAIL reason=approval_record_not_changed');
  process.exit(1);
}

let approval;
try {
  approval = JSON.parse(await fs.readFile(path.join(repoRoot, approvalPath), 'utf8'));
} catch {
  console.error('ENDOLASER_APPROVAL=FAIL reason=approval_record_invalid_json');
  process.exit(1);
}

const required = ['equipment', 'technique', 'claims', 'identity', 'tariff', 'taxonomy'];
const missing = required.filter((key) => !validApproval(approval[key]));
if (approval.status !== 'APPROVED' || missing.length > 0) {
  console.error(`ENDOLASER_APPROVAL=FAIL reason=incomplete_or_unapproved_record missing=${missing.join(',') || 'none'}`);
  process.exit(1);
}

console.log('ENDOLASER_APPROVAL=PASS protected_change_with_approved_record');
