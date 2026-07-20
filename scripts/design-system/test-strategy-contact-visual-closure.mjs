#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonicalPath = path.join(
  root,
  'wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php',
);

// The closure must load the new staging2 canonical module before defining the
// late-loaded CSS contract.
requireMatch(
  closure,
  /require_once __DIR__ \. '\/nvx-staging2-canonical-closure\.php';/,
  'external visual closure must load the staging2 canonical closure module',
);
if (!fs.existsSync(canonicalPath)) {
  failures.push('nvx-staging2-canonical-closure.php must exist alongside the external visual closure');
}

// Strategy pages own one full-width editorial hierarchy.
requireMatch(
  closure,
  /\.nvx-page__content > \.nvx-strategy-page\s*\{[\s\S]*width:\s*100%;[\s\S]*max-width:\s*none;/,
  'strategy page content must be full width with no max-width clamp',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*display:\s*flex;/,
  'strategy page hero must be defined as a flex layout',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero \.nvx-brand-title\s*\{[\s\S]*max-width:\s*22ch;/,
  'strategy page hero title must clamp its measure to 22ch',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-section\s*\{[\s\S]*width:\s*var\(--nvx-shell\);/,
  'strategy page sections must use the canonical shell width token',
);
requireMatch(
  closure,
  /\.nvx-strategy-page \.nvx-endolift-price-table-wrap\s*\{[\s\S]*overflow-x:\s*auto;/,
  'strategy page price tables must scroll horizontally on overflow',
);

// Contact cards share the shell, card and control contracts with treatment pages.
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinics-grid\s*\{[\s\S]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/,
  'contact clinics grid must render a two-column layout by default',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card\s*\{[\s\S]*display:\s*flex;[\s\S]*flex-direction:\s*column;/,
  'contact clinic card must be a column flex layout',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card__map iframe\s*\{[\s\S]*border:\s*0;/,
  'contact clinic map iframe must own its border removal via CSS, not inline markup',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card > \.nvx-btn\s*\{[\s\S]*align-self:\s*flex-start;/,
  'contact clinic card CTA must align to the start of the flex column',
);

// Native details controls must preserve a 44px interaction target.
requireMatch(
  closure,
  /\.nvx-faq summary,\s*\n\.nvx-brand-faq-accordion summary,\s*\n\.nvx-home-faq-editorial summary\s*\{[\s\S]*min-height:\s*var\(--nvx-control-size\);/,
  'FAQ summary controls must enforce the canonical control size',
);

// Closing CTA presentation moved out of markup attributes into CSS.
requireMatch(
  closure,
  /\.nvx-cta-banner\s*\{[\s\S]*text-align:\s*center;/,
  'CTA banner must be centered via CSS',
);
requireMatch(
  closure,
  /#nvx-footer-cta\s*\{[\s\S]*text-transform:\s*uppercase;/,
  'footer CTA must be uppercased via CSS',
);

// Responsive collapse for small viewports.
requireMatch(
  closure,
  /@media \(max-width: 720px\)\s*\{[\s\S]*\.nvx-page--contact \.nvx-clinics-grid\s*\{[\s\S]*grid-template-columns:\s*1fr;/,
  'contact clinics grid must collapse to a single column under 720px',
);
requireMatch(
  closure,
  /@media \(max-width: 720px\)\s*\{[\s\S]*\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*min-height:/,
  'strategy hero min-height must be reduced under 720px',
);

requireAbsent(closure, /!important/i, 'external closure must not use !important');

// Raw px/rem literals are only acceptable inside comments and @media breakpoint
// conditions; declaration values must resolve through canonical size tokens.
const closureWithoutCommentsAndMediaConditions = closure
  .replace(/\/\*[\s\S]*?\*\//g, '')
  .replace(/@media\s*\([^)]*\)/g, '');
requireAbsent(
  closureWithoutCommentsAndMediaConditions,
  /(?:\d+(?:\.\d+)?px|\d+(?:\.\d+)?rem)/i,
  'external closure declarations must use canonical size tokens only, not raw px/rem literals',
);

if (failures.length) {
  console.error('Strategy and contact visual closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: strategy page and contact card CSS contract, staging2 canonical module load');