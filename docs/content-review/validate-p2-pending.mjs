import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const requested = process.argv[2];
if (!requested) throw new Error('Usage: node validate-p2-pending.mjs p2-endolaser-page-pending.json');
if (requested.split(/[\\/]/).includes('..')) {
  throw new Error('P2_PENDING_PATH=FAIL reason=parent_traversal');
}

const file = path.resolve(root, requested);
const relative = path.relative(root, file);
if (relative.startsWith('..') || path.isAbsolute(relative)) {
  throw new Error('P2_PENDING_PATH=FAIL reason=outside_content_review_dir');
}

const payload = JSON.parse(await fs.readFile(file, 'utf8'));
const requiredObjects = ['hero', 'mechanism', 'zones', 'exclusion', 'planning', 'faq', 'downtime', 'pricing'];
for (const key of requiredObjects) {
  if (!payload[key] || typeof payload[key] !== 'object' || Array.isArray(payload[key])) {
    throw new Error(`P2_PENDING_STRUCTURE=FAIL missing_or_invalid=${key}`);
  }
}

const forbidden = [
  /recognizingAuthority/i,
  /"performer"/i,
  /SmartLipo/i,
  /\bDEKA\b/i,
  /\bendolaser\.[a-z_]+/i,
  /\bAEMPS\b/i,
  /\bSEME\b/i,
  /\b\d+(?:[.,]\d+)?\s*€/,
  /\b\d+\s*(?:horas|días|semanas|meses)\b/i,
];
const source = JSON.stringify(payload);
for (const pattern of forbidden) {
  if (pattern.test(source)) throw new Error(`P2_PENDING_GOVERNANCE=FAIL pattern=${pattern}`);
}

if (!Array.isArray(payload.faq.items) || payload.faq.items.length === 0) {
  throw new Error('P2_PENDING_FAQ=FAIL reason=empty_would_trigger_schema_fallback');
}
if (!Array.isArray(payload.downtime.phases) || payload.downtime.phases.length !== 0) {
  throw new Error('P2_PENDING_DOWNTIME=FAIL reason=must_not_publish_recovery_timeline');
}
if (payload.pricing.reference_catalog !== '') {
  throw new Error('P2_PENDING_PRICING=FAIL reason=reference_catalog_must_be_empty');
}

console.log('P2_PENDING_STRUCTURE=PASS');
console.log('P2_PENDING_GOVERNANCE=PASS');
console.log('P2_PENDING_FAQ=PASS');
console.log('P2_PENDING_PRICING=PASS');
