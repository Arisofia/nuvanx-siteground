import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const files = [
  'p1-endolift-page-review.json',
  'p2-endolaser-page-pending.json',
  'p3-neuromoduladores-faciales-review.html',
  'p4-medicina-estetica-chamberi-review.html',
  'p5-valoracion-review.html',
  'p6-enlazado-interno-top1.md',
];

for (const file of files) {
  const content = await fs.readFile(path.join(root, file), 'utf8');
  if (file.endsWith('.json')) JSON.parse(content);
  for (const pattern of [
    /neuromoduladores-botox-madrid/i,
    /recognizingAuthority/i,
    /"performer"/i,
    /SmartLipo/i,
    /\bDEKA\b/i,
    /\[nvx_tariff key=/i,
    /\b\d+(?:[.,]\d+)?\s*€/,
  ]) {
    if (pattern.test(content)) throw new Error(`ALL_REVIEW_DRAFTS=FAIL file=${file} pattern=${pattern}`);
  }
}

const p3 = await fs.readFile(path.join(root, 'p3-neuromoduladores-faciales-review.html'), 'utf8');
if (!p3.includes('/madrid/valoracion/')) throw new Error('ALL_REVIEW_DRAFTS=FAIL p3_missing_value_link');
const p6 = await fs.readFile(path.join(root, 'p6-enlazado-interno-top1.md'), 'utf8');
if (!p6.includes('/neuromoduladores-faciales-madrid/')) throw new Error('ALL_REVIEW_DRAFTS=FAIL p6_missing_canonical_neuromodulators_url');

console.log('ALL_REVIEW_DRAFTS_STRUCTURE=PASS');
console.log('ALL_REVIEW_DRAFTS_CANONICAL_URLS=PASS');
console.log('ALL_REVIEW_DRAFTS_GOVERNANCE=PASS');
