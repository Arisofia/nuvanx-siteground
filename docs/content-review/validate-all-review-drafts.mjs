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
  'README.md',
  'EXISTING-PAGE-CHANGE-MAP.md',
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
const map = await fs.readFile(path.join(root, 'EXISTING-PAGE-CHANGE-MAP.md'), 'utf8');
for (const route of [
  '/endolift-primeras-72-horas-que-esperar/',
  '/endolift-vs-hifu-diferencias-reales/',
  '/plan-anual-medicina-estetica-sin-sobretratar/',
  '/well-aging-estrategia-medica-global/',
  '/endolaser-corporal-vs-no-invasivos-grasa-localizada/',
]) {
  if (!map.includes(route)) throw new Error(`ALL_REVIEW_DRAFTS=FAIL map_missing_route=${route}`);
}
if (!map.includes('No modificar') || !map.includes('Ubicación exacta')) {
  throw new Error('ALL_REVIEW_DRAFTS=FAIL map_missing_change_instruction');
}

console.log('ALL_REVIEW_DRAFTS_STRUCTURE=PASS');
console.log('ALL_REVIEW_DRAFTS_CANONICAL_URLS=PASS');
console.log('ALL_REVIEW_DRAFTS_GOVERNANCE=PASS');
console.log('ALL_REVIEW_DRAFTS_EXISTING_PAGE_MAP=PASS');
