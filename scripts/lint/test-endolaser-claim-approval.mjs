#!/usr/bin/env node
/**
 * Endoláser clinical-content approval gate.
 *
 * This gate protects only Endoláser clinical surfaces. Shared catalogs are
 * compared semantically so unrelated treatment changes do not require an
 * Endoláser approval record. Any inability to read or classify a changed
 * governed surface fails closed.
 */
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

/**
 * Current Endoláser price surface. These keys are deliberately explicit until
 * the approved taxonomy introduces a canonical Endoláser namespace. Keeping
 * the list here makes every protected legacy Endolift price reviewable and
 * regression-testable rather than relying on an ambiguous repository grep.
 */
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

function jsonFromSource(source, label) {
  if (typeof source !== 'string') {
    throw new Error(`${label}_source_unavailable`);
  }
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
  const projection = {
    namespaces: {},
    referenced: {},
  };
  for (const [namespace, value] of Object.entries(data)) {
    if (namespace === 'endolaser' || namespace.startsWith('endolaser.')) {
      projection.namespaces[namespace] = value;
    }
  }
  for (const key of ENDOLASER_REFERENCED_TARIFF_KEYS) {
    projection.referenced[key] = valueAtTariffKey(data, key) ?? null;
  }
  return canonicalJson(projection);
}

function stripNonFunctionalPhpLines(source) {
  return source
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .split('\n')
    .filter((line) => !/^\s*(?:\/\/|#)/.test(line))
    .join('\n');
}

function extractPhpFunctionBlocks(source) {
  const blocks = new Map();
  const declaration = /function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*(?::\s*[^\s{]+)?\s*\{/g;
  for (const match of source.matchAll(declaration)) {
    const name = match[1];
    const start = match.index;
    const opening = start + match[0].lastIndexOf('{');
    let depth = 0;
    let quote = '';
    let escaped = false;
    let closing = -1;
    for (let index = opening; index < source.length; index += 1) {
      const character = source[index];
      if (quote) {
        if (escaped) {
          escaped = false;
        } else if (character === '\\') {
          escaped = true;
        } else if (character === quote) {
          quote = '';
        }
        continue;
      }
      if (character === "'" || character === '"') {
        quote = character;
      } else if (character === '{') {
        depth += 1;
      } else if (character === '}') {
        depth -= 1;
        if (depth === 0) {
          closing = index;
          break;
        }
      }
    }
    if (closing < 0) throw new Error(`structured_data_unbalanced_function_${name}`);
    blocks.set(name, source.slice(start, closing + 1));
  }
  return blocks;
}

function extractEndolaserConditionalBlocks(source) {
  const blocks = [];
  const conditional = /if\s*\(\s*['\"]endolaser_corporal['\"]\s*===\s*\$key\s*\)\s*\{/g;
  for (const match of source.matchAll(conditional)) {
    const opening = match.index + match[0].lastIndexOf('{');
    let depth = 0;
    let closing = -1;
    for (let index = opening; index < source.length; index += 1) {
      if (source[index] === '{') depth += 1;
      if (source[index] === '}') {
        depth -= 1;
        if (depth === 0) {
          closing = index;
          break;
        }
      }
    }
    if (closing < 0) throw new Error('structured_data_endolaser_branch_unbalanced');
    blocks.push(source.slice(match.index, closing + 1));
  }
  return blocks;
}

function structuredDataProjection(source) {
  if (typeof source !== 'string') throw new Error('structured_data_source_unavailable');
  const functional = stripNonFunctionalPhpLines(source);
  const functions = extractPhpFunctionBlocks(functional);
  const anchors = [ENDOLASER_SCHEMA_ID, 'endolaser-page.json', ENDOLASER_ROUTE];
  const relevant = [];
  for (const [name, block] of functions.entries()) {
    if (!anchors.some((anchor) => block.includes(anchor))) continue;
    const keyedBranches = extractEndolaserConditionalBlocks(block);
    if (keyedBranches.length > 0) {
      keyedBranches.forEach((branch, index) => relevant.push([`${name}:endolaser_branch_${index}`, branch.replace(/\s+/g, ' ').trim()]));
    } else {
      relevant.push([name, block.replace(/\s+/g, ' ').trim()]);
    }
  }
  const sourceHasAnchor = anchors.some((anchor) => functional.includes(anchor));
  if (sourceHasAnchor && relevant.length === 0) {
    throw new Error('structured_data_endolaser_block_unresolved');
  }
  return canonicalJson(relevant.sort(([left], [right]) => left.localeCompare(right)));
}

function semanticSurfaceChanged({ label, baseSource, headSource, projection }) {
  try {
    return projection(baseSource) !== projection(headSource);
  } catch (error) {
    return { indeterminate: `${label}:${error.message}` };
  }
}

/**
 * Determines whether a set of changed paths affects an Endoláser governed
 * surface. A boolean `protected` result always has at least one explicit
 * signal. An `indeterminate` signal is protected by design (fail closed).
 */
export function evaluateEndolaserChanges({ changedPaths, baseFiles, headFiles }) {
  const changed = new Set(changedPaths);
  const signals = [];
  const addSignal = (signal) => signals.push(signal);

  if (changed.has(ENDOLASER_PATHS.content)) addSignal('content');
  if (changed.has(ENDOLASER_PATHS.emitter)) addSignal('emitter');

  const semanticSurfaces = [
    ['route', ENDOLASER_PATHS.routes, routeProjection],
    ['seo', ENDOLASER_PATHS.seo, seoProjection],
    ['tariff', ENDOLASER_PATHS.tariffs, tariffProjection],
    ['schema', ENDOLASER_PATHS.structuredData, structuredDataProjection],
  ];

  for (const [label, file, projection] of semanticSurfaces) {
    if (!changed.has(file)) continue;
    const result = semanticSurfaceChanged({
      label,
      baseSource: baseFiles[file],
      headSource: headFiles[file],
      projection,
    });
    if (result === true) addSignal(label);
    if (isObject(result) && result.indeterminate) addSignal(`indeterminate:${result.indeterminate}`);
  }

  return { protected: signals.length > 0, signals };
}

export function validApproval(value) {
  return value && typeof value === 'object'
    && nonEmpty(value.approved_by)
    && nonEmpty(value.approved_at)
    && Array.isArray(value.evidence_references)
    && value.evidence_references.length > 0
    && value.evidence_references.every(nonEmpty);
}

export function hasCompleteEndolaserApproval(approval) {
  const required = ['equipment', 'technique', 'claims', 'identity', 'tariff', 'taxonomy'];
  const missing = required.filter((key) => !validApproval(approval?.[key]));
  return {
    complete: approval?.status === 'APPROVED' && missing.length === 0,
    missing,
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

function githubPullRequestBaseSha() {
  const eventPath = process.env.GITHUB_EVENT_PATH;
  if (!nonEmpty(eventPath)) return '';
  try {
    const event = JSON.parse(readFileSync(eventPath, 'utf8'));
    return typeof event?.pull_request?.base?.sha === 'string' ? event.pull_request.base.sha.trim() : '';
  } catch {
    return '';
  }
}

function resolveApprovalBase() {
  if (nonEmpty(process.env.ENDOLASER_APPROVAL_BASE)) return process.env.ENDOLASER_APPROVAL_BASE.trim();
  const eventBaseSha = githubPullRequestBaseSha();
  if (eventBaseSha && gitRefExists(eventBaseSha)) return eventBaseSha;
  const githubBaseRef = nonEmpty(process.env.GITHUB_BASE_REF) ? process.env.GITHUB_BASE_REF.trim() : '';
  if (githubBaseRef) {
    if (gitRefExists(`origin/${githubBaseRef}`)) return `origin/${githubBaseRef}`;
    if (gitRefExists(githubBaseRef)) return githubBaseRef;
  }
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

  const approvalDecision = hasCompleteEndolaserApproval(approval);
  if (!approvalDecision.complete) {
    console.error(`ENDOLASER_APPROVAL=FAIL reason=incomplete_or_unapproved_record missing=${approvalDecision.missing.join(',') || 'none'} protected=${decision.signals.join(',')}`);
    process.exit(1);
  }

  console.log(`ENDOLASER_APPROVAL=PASS protected_change_with_approved_record protected=${decision.signals.join(',')}`);
}

const invokedPath = process.argv[1] ? path.resolve(process.argv[1]) : '';
if (invokedPath === fileURLToPath(import.meta.url)) {
  await run();
}

export { canonicalJson, routeProjection, seoProjection, tariffProjection, structuredDataProjection };
