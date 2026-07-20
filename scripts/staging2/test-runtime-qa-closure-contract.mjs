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
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonical = read('wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php');
const contact = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const doctor = read('wp-content/themes/nuvanx-medical/inc/nvx-dr-rivera-page.php');

requireText(shell, "function_exists( 'nvx_strategy_current_page_key' )", 'page shell must recognize strategy routes');
requireText(shell, 'null !== nvx_strategy_current_page_key()', 'strategy route recognition must require a resolved strategy key');
requireText(shell, '! $has_managed_editorial', 'managed editorial pages must suppress the fallback shell H1');

requireText(closure, "require_once __DIR__ . '/nvx-staging2-canonical-closure.php';", 'external closure must load the non-public canonical module');
requireText(canonical, 'https://nuvanx.com', 'approved Staging2 routes must point to the public canonical host');
requireText(canonical, 'nvx_staging2_canonical_is_protected_review', 'protected review routes must be recognized');
requireText(canonical, "'approved_for_publication'", 'only explicitly approved strategy routes may expose a public canonical');
requireText(canonical, "add_filter( 'wpseo_canonical'", 'Yoast canonical output must be filtered');
requireText(canonical, "add_filter( 'wpseo_opengraph_url'", 'Open Graph URL must use the same public mapping');

for (const selector of [
  '.nvx-page__content > .nvx-strategy-page',
  '.nvx-strategy-page > .nvx-brand-hero',
  '.nvx-strategy-page > .nvx-brand-section',
  '.nvx-page--contact .nvx-clinics-grid',
  '.nvx-page--contact .nvx-clinic-card__map iframe',
]) {
  requireText(closure, selector, `missing visual closure selector: ${selector}`);
}
requireText(closure, 'grid-template-columns: repeat(2, minmax(0, 1fr));', 'contact desktop must use two clinic columns');
requireText(closure, 'min-height: var(--nvx-control-size);', 'summary controls must use the interaction-size token');
requireText(closure, 'border: 0;', 'map border presentation must live in CSS');

forbidText(contact, 'style="border:0;"', 'contact iframe still contains the legacy inline border style');
forbidText(contact, "style='border:0;'", 'contact iframe still contains a single-quoted inline border style');
forbidText(doctor, '<p class="nvx-brand-kicker" style=', 'doctor authority kicker still uses inline spacing');
requireText(doctor, 'nvx-brand-kicker nvx-dr-rivera-kicker', 'doctor authority kicker needs its dedicated class');

if (!exists('googlee8160480bf01506f.html')) failures.push('Google Search Console verification file must be retained');
if (exists('scratch/replace_pixels.mjs')) failures.push('one-time pixel replacement script must stay deleted');

if (failures.length) {
  console.error('Runtime QA closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: runtime QA closure (canonical, H1, strategy layout, contact and retained verification)');
