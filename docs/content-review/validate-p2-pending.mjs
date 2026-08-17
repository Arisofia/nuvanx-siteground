import fs from 'node:fs/promises';

const file = process.argv[2];
if (!file) throw new Error('Usage: node validate-p2-pending.mjs path/to/p2-endolaser-page-pending.json');

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
