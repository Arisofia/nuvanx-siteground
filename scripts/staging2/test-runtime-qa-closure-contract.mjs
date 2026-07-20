#!/usr/bin/env node
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));

const shell = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php');
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonical = read('wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php');
const contact = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const doctor = read('wp-content/themes/nuvanx-medical/inc/nvx-dr-rivera-page.php');

assert.match(
  shell,
  /function_exists\( 'nvx_strategy_current_page_key' \)[\s\S]{0,140}null !== nvx_strategy_current_page_key\(\)/,
  'strategy routes must be treated as managed editorial pages so the shell does not emit a second H1',
);

assert.match(
  closure,
  /require_once __DIR__ \. '\/nvx-staging2-canonical-closure\.php';/,
  'the non-public canonical closure must be loaded before the late visual contract',
);
assert.match(canonical, /https:\/\/nuvanx\.com/, 'approved Staging2 routes must point to the public canonical host');
assert.match(canonical, /nvx_staging2_canonical_is_protected_review/, 'protected review routes must be recognized');
assert.match(canonical, /'approved_for_publication'/, 'only explicitly approved strategy routes may expose a public canonical');
assert.match(canonical, /add_filter\( 'wpseo_canonical'/, 'Yoast canonical output must be filtered');
assert.match(canonical, /add_filter\( 'wpseo_opengraph_url'/, 'Open Graph URL must follow the same public route mapping');

assert.match(closure, /\.nvx-page__content > \.nvx-strategy-page/, 'strategy pages need a full-width root closure');
assert.match(closure, /\.nvx-strategy-page > \.nvx-brand-hero/, 'strategy hero layout must be explicit');
assert.match(closure, /\.nvx-strategy-page > \.nvx-brand-section/, 'strategy body sections must return to the canonical shell');
assert.match(closure, /\.nvx-page--contact \.nvx-clinics-grid/, 'contact clinic cards must use a canonical grid');
assert.match(closure, /grid-template-columns: repeat\(2, minmax\(0, 1fr\)\)/, 'contact desktop must use two equal clinic columns');
assert.match(closure, /min-height: var\(--nvx-control-size\)/, 'native summary controls must preserve the interaction-size token');

assert.doesNotMatch(contact, /<iframe[\s\S]{0,240}\sstyle=/i, 'contact map iframes must not contain inline styles');
assert.match(closure, /\.nvx-page--contact \.nvx-clinic-card__map iframe[\s\S]{0,100}border: 0/, 'map border presentation must live in CSS');
assert.doesNotMatch(doctor, /<p class="nvx-brand-kicker" style=/, 'doctor authority kicker must not use inline spacing');
assert.match(doctor, /nvx-brand-kicker nvx-dr-rivera-kicker/, 'doctor authority kicker needs a dedicated presentation class');

assert.ok(exists('googlee8160480bf01506f.html'), 'Google Search Console verification file must be retained until ownership is verified');
assert.ok(!exists('scratch/replace_pixels.mjs'), 'one-time pixel replacement script must stay deleted');

console.log('PASS: runtime QA closure (canonical, H1, strategy layout, contact and retained verification)');
