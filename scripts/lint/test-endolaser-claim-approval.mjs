#!/usr/bin/env node
/**
 * Endoláser clinical-content approval gate.
 *
 * Protects only Endoláser clinical surfaces. Shared catalogs are compared
 * semantically so unrelated treatment changes do not require an Endoláser
 * approval record. Any inability to read or classify a changed governed
 * surface fails closed.
 */
import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

export const ENDOLASER_PATHS = Object.freeze({
  content: 'wp-content/themes/nuvanx-medical/inc/data/endolaser-page.json',
  emitter: 'wp-content/themes/nuvanx-medical/inc/nvx-endolaser-page.php',
  routes: 'wp-content/themes/nuvanx-medical/inc/data/routes.json',
  seo: 'wp-content/themes/nuvanx-medical/inc/data/seo-metadata.json',
  tariffs: 'wp-content/themes/nuvanx-medical/inc/data/tariff-catalog.json',
  structuredData: 'wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php',
});

export const ENDOLASER_ROUTE = '/endolaser-corporal-grasa-localizada/';
export const ENDOLASER_SCHEMA_ID = 'endolaser_corporal';
export const ENDOLASER_SEO_ID = 'endolaser';
export const ENDOLASER_APPROVAL_PATH = 'docs/approvals/endolaser-content-approval.json';
export const ENDOLASER_APPROVAL_SCHEMA = 'nuvanx-endolaser-content-approval/v2';

export const ENDOLASER_REFERENCED_TARIFF_KEYS = Object.freeze([
  'endolift.abdomen',
  'endolift.flancos',
  'endolift.subescapular',
  'endolift.brazos',
  'endolift.rodillas',
  'endolift.muslos_internos',
  'endolift.subgluteos',
  'endolift.cartucheras',
  'endolift_combo.abdomen_flancos',
  'endolift_combo.subgluteos_cartucheras',
  'endolift_combo.muslos_rodilla',
  'endolift_combo.sujetador_brazos',
  'endolift_combo.cartucheras_muslos',
  'endolift_combo.cartucheras_subgluteos_muslos',
]);

function nonEmpty(value) {
  return typeof value === 'string' && value.trim().length > 0;
}

function isObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function canonicalize(value) {
  if (Array.isArray(value)) return value.map(canonicalize);
  if (!isObject(value)) return value;
  return Object.fromEntries(Object.keys(value).sort().map((key) => [key, canonicalize(value[key])]));
}

function canonicalJson(value) {
  return JSON.stringify(canonicalize(value));
}

function sha256(value) {
  return createHash('sha256').update(String(value), 'utf8').digest('hex');
}

function sourceDigest(value) {
  return typeof value === 'string' ? sha256(value) : null;
}

function jsonFromSource(source, label) {
  if (typeof source !== 'string') throw new Error(`${label}_source_unavailable`);
  try {
    return JSON.parse(source);
  } catch {
    throw new Error(`${label}_invalid_json`);
  }
}

function collectObjects(value, predicate, pathLabel = '$', matches = []) {
  if (Array.isArray(value)) {
    value.forEach((item, index) => collectObjects(item, predicate, `${pathLabel}[${index}]`, matches));
    return matches;
  }
  if (!isObject(value)) return matches;
  if (predicate(value, pathLabel)) matches.push({ path: pathLabel, value });
  for (const [key, item] of Object.entries(value)) {
    collectObjects(item, predicate, `${pathLabel}.${key}`, matches);
  }
  return matches;
}

function routeProjection(source) {
  const data = jsonFromSource(source, 'routes');
  const matches = [];
  if (isObject(data) && Object.prototype.hasOwnProperty.call(data, ENDOLASER_ROUTE)) {
    matches.push({ path: ENDOLASER_ROUTE, value: data[ENDOLASER_ROUTE] });
  }
  collectObjects(data, (record) => (
    record.route === ENDOLASER_ROUTE
    || record.canonical === ENDOLASER_ROUTE
    || record.path === ENDOLASER_ROUTE
    || record.seo_id === ENDOLASER_SEO_ID
    || record.schema_id === ENDOLASER_SCHEMA_ID
  )).forEach((item) => {
    if (!matches.some((match) => canonicalJson(match.value) === canonicalJson(item.value))) matches.push(item);
  });
  if (matches.length === 0) throw new Error('routes_endolaser_record_missing');
  return canonicalJson(matches);
}

function seoProjection(source) {
  const data = jsonFromSource(source, 'seo');
  const matches = [];
  if (isObject(data) && Object.prototype.hasOwnProperty.call(data, ENDOLASER_SEO_ID)) {
    matches.push({ path: ENDOLASER_SEO_ID, value: data[ENDOLASER_SEO_ID] });
  }
  collectObjects(data, (record) => record.seo_id === ENDOLASER_SEO_ID).forEach((item) => {
    if (!matches.some((match) => canonicalJson(match.value) === canonicalJson(item.value))) matches.push(item);
  });
  if (matches.length === 0) throw new Error('seo_endolaser_record_missing');
  return canonicalJson(matches);
}

function valueAtTariffKey(catalog, key) {
  const [namespace, item] = key.split('.');
  if (!namespace || !item || !isObject(catalog) || !isObject(catalog[namespace])) return undefined;
  return catalog[namespace][item];
}

function tariffProjection(source) {
  const data = jsonFromSource(source, 'tariffs');
  if (!isObject(data)) throw new Error('tariffs_invalid_shape');
  const projection = { namespaces: {}, referenced: {} };
  for (const [namespace, value] of Object.entries(data)) {
    if (namespace === 'endolaser' || namespace.startsWith('endolaser.')) projection.namespaces[namespace] = value;
  }
  for (const key of ENDOLASER_REFERENCED_TARIFF_KEYS) {
    projection.referenced[key] = valueAtTariffKey(data, key) ?? null;
  }
  return canonicalJson(projection);
}

function skipPhpComment(source, index) {
  if (source[index] === '/' && source[index + 1] === '/') {
    const end = source.indexOf('\n', index + 2);
    return end === -1 ? source.length : end;
  }
  if (source[index] === '#') {
    const end = source.indexOf('\n', index + 1);
    return end === -1 ? source.length : end;
  }
  if (source[index] === '/' && source[index + 1] === '*') {
    const end = source.indexOf('*/', index + 2);
    if (end === -1) throw new Error('structured_data_unterminated_comment');
    return end + 2;
  }
  return index;
}

function readBalanced(source, opening, openChar, closeChar, label) {
  let depth = 0;
  let quote = '';
  let escaped = false;
  for (let index = opening; index < source.length; index += 1) {
    const character = source[index];
    if (quote) {
      if (escaped) escaped = false;
      else if (character === '\\') escaped = true;
      else if (character === quote) quote = '';
      continue;
    }
    if (character === "'" || character === '"') {
      quote = character;
      continue;
    }
    const commentEnd = skipPhpComment(source, index);
    if (commentEnd !== index) {
      index = commentEnd - 1;
      continue;
    }
    if (character === openChar) depth += 1;
    else if (character === closeChar) {
      depth -= 1;
      if (depth === 0) return { start: opening, end: index + 1 };
    }
  }
  throw new Error(`${label}_unbalanced`);
}

function readPhpSegment(source, start, terminator, label) {
  let quote = '';
  let escaped = false;
  let paren = 0;
  let bracket = 0;
  let brace = 0;
  for (let index = start; index < source.length; index += 1) {
    const character = source[index];
    if (quote) {
      if (escaped) escaped = false;
      else if (character === '\\') escaped = true;
      else if (character === quote) quote = '';
      continue;
    }
    if (character === "'" || character === '"') {
      quote = character;
      continue;
    }
    const commentEnd = skipPhpComment(source, index);
    if (commentEnd !== index) {
      index = commentEnd - 1;
      continue;
    }
    if (character === '(') paren += 1;
    else if (character === ')') paren -= 1;
    else if (character === '[') bracket += 1;
    else if (character === ']') bracket -= 1;
    else if (character === '{') brace += 1;
    else if (character === '}') brace -= 1;
    if (paren < 0 || bracket < 0 || brace < 0) throw new Error(`${label}_unbalanced`);
    if (character === terminator && paren === 0 && bracket === 0 && brace === 0) {
      return { start, end: index + 1 };
    }
  }
  throw new Error(`${label}_unterminated`);
}

function normalizePhpFragment(source, range) {
  return source.slice(range.start, range.end).replace(/\s+/g, ' ').trim();
}

function rangesForRegex(source, regex, reader) {
  const ranges = [];
  for (const match of source.matchAll(regex)) ranges.push(reader(match));
  return ranges;
}

function extractPhpFunctionDefinitions(source) {
  const definitions = new Map();
  const declaration = /function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*(?::\s*[^\s{]+)?\s*\{/g;
  for (const match of source.matchAll(declaration)) {
    const opening = match.index + match[0].lastIndexOf('{');
    const block = readBalanced(source, opening, '{', '}', `structured_data_function_${match[1]}`);
    definitions.set(match[1], {
      start: match.index,
      end: block.end,
      text: source.slice(match.index, block.end),
    });
  }
  return definitions;
}

function functionCalls(fragment, definitions) {
  const calls = new Set();
  for (const match of fragment.matchAll(/\b([A-Za-z_][A-Za-z0-9_]*)\s*\(/g)) {
    if (definitions.has(match[1])) calls.add(match[1]);
  }
  return calls;
}

function transitiveFunctionDependencies(source, rootFragments) {
  const definitions = extractPhpFunctionDefinitions(source);
  const queue = [];
  for (const fragment of rootFragments) queue.push(...functionCalls(fragment, definitions));
  const visited = new Set();
  const dependencies = [];
  while (queue.length > 0) {
    const name = queue.shift();
    if (visited.has(name)) continue;
    visited.add(name);
    const definition = definitions.get(name);
    if (!definition) continue;
    dependencies.push([name, definition.text.replace(/\s+/g, ' ').trim()]);
    for (const dependency of functionCalls(definition.text, definitions)) {
      if (!visited.has(dependency)) queue.push(dependency);
    }
  }
  return dependencies.sort(([left], [right]) => left.localeCompare(right));
}

function constantUseLines(source, constantName) {
  const lines = source.split('\n');
  return [...new Set(lines
    .filter((line) => line.includes(constantName))
    .map((line) => line.replace(/\s+/g, ' ').trim())
    .filter(Boolean))].sort();
}

function structuredDataProjection(source) {
  if (typeof source !== 'string') throw new Error('structured_data_source_unavailable');

  const ranges = [];
  const add = (label, newRanges) => newRanges.forEach((range, index) => ranges.push({ label: `${label}_${index}`, ...range }));

  add('catalog_assignment', rangesForRegex(
    source,
    /\$catalog\s*\[\s*['"]endolaser_corporal['"]\s*\]\s*=/g,
    (match) => readPhpSegment(source, match.index, ';', 'structured_data_endolaser_catalog_assignment'),
  ));

  add('faq_fallback', rangesForRegex(
    source,
    /if\s*\(\s*empty\s*\(\s*\$catalog\s*\[\s*['"]endolaser_corporal['"]\s*\]\s*\)\s*\)\s*\{/g,
    (match) => {
      const opening = source.indexOf('{', match.index + match[0].lastIndexOf('{'));
      const block = readBalanced(source, opening, '{', '}', 'structured_data_endolaser_faq_fallback');
      return { start: match.index, end: block.end };
    },
  ));

  add('treatment_branch', rangesForRegex(
    source,
    /if\s*\(\s*(?:['"]endolaser_corporal['"]\s*===\s*\$key|\$key\s*===\s*['"]endolaser_corporal['"])\s*\)\s*\{/g,
    (match) => {
      const opening = source.indexOf('{', match.index + match[0].lastIndexOf('{'));
      const block = readBalanced(source, opening, '{', '}', 'structured_data_endolaser_treatment_branch');
      return { start: match.index, end: block.end };
    },
  ));

  add('array_entry', rangesForRegex(
    source,
    /['"]endolaser_corporal['"]\s*=>/g,
    (match) => readPhpSegment(source, match.index, ',', 'structured_data_endolaser_array_entry'),
  ));

  add('label_define', rangesForRegex(
    source,
    /define\s*\(\s*['"]NVX_SD_ENDOLASER_CORPORAL['"]\s*,/g,
    (match) => readPhpSegment(source, match.index, ';', 'structured_data_endolaser_label_define'),
  ));

  const literalOccurrences = [...source.matchAll(/['"]endolaser_corporal['"]/g)];
  if (literalOccurrences.length === 0) throw new Error('structured_data_endolaser_anchor_missing');
  const uncovered = literalOccurrences.filter((match) => !ranges.some((range) => match.index >= range.start && match.index < range.end));
  if (uncovered.length > 0) throw new Error(`structured_data_endolaser_occurrence_unresolved_${uncovered.length}`);

  const rawFragments = ranges.map((range) => source.slice(range.start, range.end));
  const fragments = ranges
    .map((range) => [range.label, normalizePhpFragment(source, range)])
    .sort(([left], [right]) => left.localeCompare(right));
  const constantLines = constantUseLines(source, 'NVX_SD_ENDOLASER_CORPORAL');
  const dependencies = transitiveFunctionDependencies(source, [...rawFragments, ...constantLines]);

  const anchorCounts = {
    schema_id_literals: literalOccurrences.length,
    label_constant_uses: [...source.matchAll(/\bNVX_SD_ENDOLASER_CORPORAL\b/g)].length,
    page_catalog_uses: [...source.matchAll(/endolaser-page\.json/g)].length,
  };

  return canonicalJson({ anchorCounts, constantLines, dependencies, fragments });
}

function semanticSurfaceDiff({ label, baseSource, headSource, projection }) {
  try {
    const baseProjection = projection(baseSource);
    const headProjection = projection(headSource);
    return {
      changed: baseProjection !== headProjection,
      baseProjection,
      headProjection,
    };
  } catch (error) {
    return { indeterminate: `${label}:${error.message}` };
  }
}

export function evaluateEndolaserChanges({ changedPaths, baseFiles, headFiles }) {
  const changed = new Set(changedPaths);
  const signals = [];
  const binding = {};

  if (changed.has(ENDOLASER_PATHS.content)) {
    signals.push('content');
    binding.content = {
      base_sha256: sourceDigest(baseFiles[ENDOLASER_PATHS.content]),
      head_sha256: sourceDigest(headFiles[ENDOLASER_PATHS.content]),
    };
  }

  // Fail closed by design: any edit to the dedicated renderer requires approval.
  if (changed.has(ENDOLASER_PATHS.emitter)) {
    signals.push('emitter');
    binding.emitter = {
      base_sha256: sourceDigest(baseFiles[ENDOLASER_PATHS.emitter]),
      head_sha256: sourceDigest(headFiles[ENDOLASER_PATHS.emitter]),
    };
  }

  for (const [label, file, projection] of [
    ['route', ENDOLASER_PATHS.routes, routeProjection],
    ['seo', ENDOLASER_PATHS.seo, seoProjection],
    ['tariff', ENDOLASER_PATHS.tariffs, tariffProjection],
    ['schema', ENDOLASER_PATHS.structuredData, structuredDataProjection],
  ]) {
    if (!changed.has(file)) continue;
    const result = semanticSurfaceDiff({ label, baseSource: baseFiles[file], headSource: headFiles[file], projection });
    if (result.indeterminate) {
      signals.push(`indeterminate:${result.indeterminate}`);
      binding[label] = { indeterminate: result.indeterminate };
      continue;
    }
    if (result.changed) {
      signals.push(label);
      binding[label] = {
        base_projection_sha256: sha256(result.baseProjection),
        head_projection_sha256: sha256(result.headProjection),
      };
    }
  }

  const fingerprint = sha256(canonicalJson({ binding, signals: [...signals].sort() }));
  return { protected: signals.length > 0, signals, binding, fingerprint };
}

export function validApproval(value) {
  return value && typeof value === 'object'
    && nonEmpty(value.approved_by)
    && nonEmpty(value.approved_at)
    && Array.isArray(value.evidence_references)
    && value.evidence_references.length > 0
    && value.evidence_references.every(nonEmpty);
}

export function hasCompleteEndolaserApproval(approval, expectedBinding = {}) {
  const required = ['equipment', 'technique', 'claims', 'identity', 'tariff', 'taxonomy'];
  const missing = required.filter((key) => !validApproval(approval?.[key]));
  const revision = isObject(approval?.revision) ? approval.revision : {};
  const bindingErrors = [];

  if (approval?.schema !== ENDOLASER_APPROVAL_SCHEMA) bindingErrors.push('schema');
  if (!/^[0-9a-f]{40}$/.test(String(revision.base_sha || ''))) bindingErrors.push('revision.base_sha');
  if (!/^[0-9a-f]{40}$/.test(String(revision.head_sha || ''))) bindingErrors.push('revision.head_sha');
  if (!/^[0-9a-f]{64}$/.test(String(revision.protected_fingerprint || ''))) bindingErrors.push('revision.protected_fingerprint');
  if (expectedBinding.baseSha && revision.base_sha !== expectedBinding.baseSha) bindingErrors.push('revision.base_sha_mismatch');
  if (expectedBinding.protectedFingerprint && revision.protected_fingerprint !== expectedBinding.protectedFingerprint) {
    bindingErrors.push('revision.protected_fingerprint_mismatch');
  }

  return {
    complete: approval?.status === 'APPROVED' && missing.length === 0 && bindingErrors.length === 0,
    missing,
    bindingErrors,
  };
}

function gitRefExists(ref) {
  try {
    execFileSync('git', ['rev-parse', '--verify', ref], { cwd: repoRoot, stdio: 'ignore' });
    return true;
  } catch {
    return false;
  }
}

function fullCommitSha(ref) {
  try {
    return execFileSync('git', ['rev-parse', `${ref}^{commit}`], { cwd: repoRoot, encoding: 'utf8' }).trim();
  } catch {
    return '';
  }
}

function isAncestor(ancestor, descendant) {
  try {
    execFileSync('git', ['merge-base', '--is-ancestor', ancestor, descendant], { cwd: repoRoot, stdio: 'ignore' });
    return true;
  } catch {
    return false;
  }
}

function githubEventBaseSha() {
  const eventPath = process.env.GITHUB_EVENT_PATH;
  if (!nonEmpty(eventPath)) return '';
  try {
    const event = JSON.parse(readFileSync(eventPath, 'utf8'));
    const pullRequestBase = typeof event?.pull_request?.base?.sha === 'string' ? event.pull_request.base.sha.trim() : '';
    if (/^[0-9a-f]{40}$/.test(pullRequestBase)) return pullRequestBase;
    const before = typeof event?.before === 'string' ? event.before.trim() : '';
    if (/^[0-9a-f]{40}$/.test(before) && !/^0{40}$/.test(before)) return before;
    return '';
  } catch {
    return '';
  }
}

function resolveApprovalBase() {
  if (nonEmpty(process.env.ENDOLASER_APPROVAL_BASE)) {
    const explicit = process.env.ENDOLASER_APPROVAL_BASE.trim();
    return gitRefExists(explicit) ? explicit : '';
  }

  const eventBaseSha = githubEventBaseSha();
  if (eventBaseSha) return gitRefExists(eventBaseSha) ? eventBaseSha : '';

  const eventName = String(process.env.GITHUB_EVENT_NAME || '');
  if (process.env.GITHUB_ACTIONS === 'true' && (eventName === 'pull_request' || eventName === 'push')) {
    return '';
  }

  const githubBaseRef = nonEmpty(process.env.GITHUB_BASE_REF) ? process.env.GITHUB_BASE_REF.trim() : '';
  if (githubBaseRef) {
    if (gitRefExists(`origin/${githubBaseRef}`)) return `origin/${githubBaseRef}`;
    if (gitRefExists(githubBaseRef)) return githubBaseRef;
  }

  // Local/manual lint fallback only. Pull-request and push CI paths above fail closed.
  for (const candidate of ['origin/master', 'origin/main', 'master', 'main']) {
    if (gitRefExists(candidate)) return candidate;
  }
  return '';
}

function changedFiles(base) {
  try {
    return execFileSync('git', ['diff', '--name-only', `${base}...HEAD`], { cwd: repoRoot, encoding: 'utf8' })
      .split('\n').map((item) => item.trim()).filter(Boolean);
  } catch {
    throw new Error(`base_diff_unavailable base=${base}`);
  }
}

function fileAtRef(ref, file) {
  try {
    return execFileSync('git', ['show', `${ref}:${file}`], { cwd: repoRoot, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
  } catch {
    return undefined;
  }
}

async function headFile(file) {
  try {
    return await fs.readFile(path.join(repoRoot, file), 'utf8');
  } catch {
    return undefined;
  }
}

async function run() {
  const base = resolveApprovalBase();
  if (!nonEmpty(base)) {
    console.error('ENDOLASER_APPROVAL=FAIL reason=base_diff_unavailable base=unresolved');
    process.exit(1);
  }

  const baseSha = fullCommitSha(base);
  const currentHeadSha = fullCommitSha('HEAD');
  if (!/^[0-9a-f]{40}$/.test(baseSha) || !/^[0-9a-f]{40}$/.test(currentHeadSha)) {
    console.error('ENDOLASER_APPROVAL=FAIL reason=revision_identity_unavailable');
    process.exit(1);
  }

  let changed;
  try {
    changed = changedFiles(base);
  } catch (error) {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=${error.message}`);
    process.exit(1);
  }

  const governedFiles = Object.values(ENDOLASER_PATHS);
  const changedGovernedFiles = changed.filter((file) => governedFiles.includes(file));
  const baseFiles = {};
  const headFiles = {};
  for (const file of changedGovernedFiles) {
    baseFiles[file] = fileAtRef(base, file);
    headFiles[file] = await headFile(file);
  }

  const decision = evaluateEndolaserChanges({ changedPaths: changed, baseFiles, headFiles });
  if (!decision.protected) {
    console.log('ENDOLASER_APPROVAL=PASS reason=no_protected_semantic_change');
    process.exit(0);
  }

  const indeterminate = decision.signals.filter((signal) => signal.startsWith('indeterminate:'));
  if (indeterminate.length > 0) {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=protected_surface_indeterminate protected=${indeterminate.join(',')}`);
    process.exit(1);
  }

  if (!changed.includes(ENDOLASER_APPROVAL_PATH)) {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=approval_record_not_changed protected=${decision.signals.join(',')}`);
    process.exit(1);
  }

  let approval;
  try {
    approval = JSON.parse(await fs.readFile(path.join(repoRoot, ENDOLASER_APPROVAL_PATH), 'utf8'));
  } catch {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=approval_record_invalid_json protected=${decision.signals.join(',')}`);
    process.exit(1);
  }

  const approvalDecision = hasCompleteEndolaserApproval(approval, {
    baseSha,
    protectedFingerprint: decision.fingerprint,
  });
  if (!approvalDecision.complete) {
    console.error(
      `ENDOLASER_APPROVAL=FAIL reason=incomplete_unbound_or_unapproved_record missing=${approvalDecision.missing.join(',') || 'none'} binding=${approvalDecision.bindingErrors.join(',') || 'none'} protected=${decision.signals.join(',')}`,
    );
    process.exit(1);
  }

  const approvedHeadSha = approval.revision.head_sha;
  if (!gitRefExists(approvedHeadSha) || !isAncestor(approvedHeadSha, currentHeadSha)) {
    console.error('ENDOLASER_APPROVAL=FAIL reason=approved_head_not_ancestor');
    process.exit(1);
  }

  console.log(`ENDOLASER_APPROVAL=PASS protected_change_with_bound_approved_record protected=${decision.signals.join(',')} fingerprint=${decision.fingerprint}`);
}

function runSemanticRegressionContract() {
  const contractPath = path.join(repoRoot, 'scripts/lint/test-endolaser-approval-semantic-contract.mjs');
  execFileSync(process.execPath, [contractPath], {
    cwd: repoRoot,
    stdio: 'inherit',
  });
}

const invokedPath = process.argv[1] ? path.resolve(process.argv[1]) : '';
if (invokedPath === fileURLToPath(import.meta.url)) {
  runSemanticRegressionContract();
  await run();
}

export { canonicalJson, routeProjection, seoProjection, tariffProjection, structuredDataProjection };
