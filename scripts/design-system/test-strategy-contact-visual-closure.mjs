#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];
const requireText = (source, text, message) => { if (!source.includes(text)) failures.push(message); };
const forbidText = (source, text, message) => { if (source.includes(text)) failures.push(message); };

// Ownership boundary: shell in PHP, reusable clinic cards in canonical CSS, external aliases in the late closure.
const strategy = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const components = read('wp-content/themes/nuvanx-medical/assets/css/nvx-components.css');
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');

requireText(strategy, 'nvx-strategy-page--review nvx-shell', 'review article must use the canonical shell');
const investmentStart = strategy.indexOf('function nvx_strategy_investment_markup(): string');
const investmentEnd = strategy.indexOf('function nvx_strategy_page_markup', investmentStart);
requireText(strategy.slice(investmentStart, investmentEnd), 'nvx-strategy-page nvx-shell', 'investment article must use the canonical shell');

forbidText(closure, '.nvx-page__content > .nvx-strategy-page', 'late CSS must not override the restored strategy shell');
requireText(closure, '.nvx-strategy-page > .nvx-brand-hero', 'strategy hero presentation must remain available');
requireText(closure, '.nvx-strategy-page > .nvx-brand-section', 'strategy section presentation must remain available');

requireText(components, '/* CONTACT CLINIC CARDS — canonical editorial grid */', 'clinic cards must live in components.css');
requireText(components, '.nvx-page--contact .nvx-clinics-grid', 'clinic grid selector is missing');
requireText(components, '.nvx-page--contact .nvx-clinics-grid', 'clinic grid selector must be unique — no @media duplicate');
requireText(components, 'auto-fill, minmax(min(100%', 'clinic grid must use a single responsive auto-fill declaration');
requireText(components, '.nvx-page--contact .nvx-clinic-card', 'clinic card component is missing');
requireText(components, '.nvx-page--contact .nvx-clinic-card__map iframe', 'clinic map component is missing');
forbidText(closure, 'Contact cards use the same shell', 'contact cards must not remain in late inline CSS');
forbidText(components, '!important', 'clinic cards must not use !important');

if (failures.length) {
  console.error('Strategy/contact visual contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: strategy shell and canonical contact-card CSS');
