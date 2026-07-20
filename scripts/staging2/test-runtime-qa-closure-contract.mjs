#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];
const requireText = (source, text, message) => { if (!source.includes(text)) failures.push(message); };
const forbidText = (source, text, message) => { if (source.includes(text)) failures.push(message); };

const shell = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php');
const strategy = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const components = read('wp-content/themes/nuvanx-medical/assets/css/nvx-components.css');
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonical = read('wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php');
const contact = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const doctor = read('wp-content/themes/nuvanx-medical/inc/nvx-dr-rivera-page.php');

requireText(shell, "function_exists( 'nvx_strategy_current_page_key' )", 'page shell must recognize strategy routes');
requireText(shell, 'null !== nvx_strategy_current_page_key()', 'strategy route recognition must require a resolved key');
requireText(strategy, 'nvx-strategy-page--review nvx-shell', 'review strategy article must use nvx-shell');
const investmentStart = strategy.indexOf('function nvx_strategy_investment_markup(): string');
const investmentEnd = strategy.indexOf('function nvx_strategy_page_markup', investmentStart);
requireText(strategy.slice(investmentStart, investmentEnd), 'nvx-strategy-page nvx-shell', 'investment strategy article must use nvx-shell');
forbidText(closure, '.nvx-page__content > .nvx-strategy-page', 'late CSS must not override the strategy shell');

requireText(closure, "require_once __DIR__ . '/nvx-staging2-canonical-closure.php';", 'external closure must load the canonical module');
requireText(canonical, 'https://nuvanx.com', 'approved Staging2 routes must target the public canonical host');
requireText(canonical, 'nvx_staging2_canonical_is_approved_strategy', 'strategy canonical eligibility must require explicit approval');
requireText(canonical, "add_filter( 'wpseo_canonical'", 'Yoast canonical output must be filtered');

requireText(contact, '<div class="nvx-clinics-grid">', 'contact template must wrap clinics in the grid');
forbidText(contact, 'style="border:0;"', 'contact iframe still has inline border style');
requireText(components, '.nvx-page--contact .nvx-clinics-grid', 'clinic grid must live in components.css');
requireText(components, '.nvx-page--contact .nvx-clinic-card__map iframe', 'clinic maps must be styled in components.css');
requireText(components, 'border: 0;', 'clinic map border reset must live in components.css');
forbidText(closure, 'Contact cards use the same shell', 'contact styles must not remain in the external closure');

forbidText(doctor, '<p class="nvx-brand-kicker" style=', 'doctor authority kicker still uses inline spacing');
requireText(doctor, 'nvx-brand-kicker nvx-dr-rivera-kicker', 'doctor authority kicker class is missing');

if (!exists('googlee8160480bf01506f.html')) failures.push('Google Search Console verification file must be retained');
if (exists('scratch/replace_pixels.mjs')) failures.push('one-time pixel replacement script must stay deleted');

if (failures.length) {
  console.error('Runtime QA closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: runtime QA closure and visual blocker plan');
