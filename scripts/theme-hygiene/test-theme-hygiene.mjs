#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const reportPath = path.join(root, 'theme-hygiene-report.txt');
const errors = [];
const rel = (file) => path.relative(root, file).replaceAll('\\', '/');

function fail(message) {
  errors.push(message);
}

function read(relativePath) {
  const target = path.join(theme, relativePath);
  try {
    return fs.readFileSync(target, 'utf8');
  } catch (error) {
    const reason = error && typeof error === 'object' && 'code' in error ? error.code : String(error);
    fail(`${rel(target)}: unable to read (${reason})`);
    return '';
  }
}

function walk(dir) {
  try {
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
      if (['vendor', 'node_modules', '.git'].includes(entry.name)) return [];
      const target = path.join(dir, entry.name);
      return entry.isDirectory() ? walk(target) : [target];
    });
  } catch (error) {
    const reason = error && typeof error === 'object' && 'code' in error ? error.code : String(error);
    fail(`${rel(dir)}: unable to scan (${reason})`);
    return [];
  }
}

const files = walk(theme);
const runtime = files.filter((file) => /\.(php|css|js)$/i.test(file));

for (const file of runtime) {
  const relativePath = rel(file);
  try {
    const stats = fs.statSync(file);
    const name = path.basename(file);
    const content = fs.readFileSync(file, 'utf8');
    if (!stats.size) fail(`${relativePath}: empty file`);
    if (/(^|[-_.])(legacy|old|backup|bak|temp|tmp|deprecated|unused|orphan)([-_.]|$)/i.test(name)) fail(`${relativePath}: obsolete filename`);
    if (/staging2\.nuvanx\.com/i.test(content) && !relativePath.endsWith('/inc/nvx-environment-flags.php')) fail(`${relativePath}: staging hostname`);
    if (/\.css$/i.test(file) && /!important\b/i.test(content)) fail(`${relativePath}: !important`);
    if (/\.css$/i.test(file) && /[^{}]+\{\s*\}/m.test(content)) fail(`${relativePath}: empty CSS rule`);
  } catch (error) {
    const reason = error && typeof error === 'object' && 'code' in error ? error.code : String(error);
    fail(`${relativePath}: unable to inspect (${reason})`);
  }
}

const templateFiles = runtime.filter((file) => {
  if (!/\.php$/i.test(file)) return false;
  const relativePath = path.relative(theme, file).replaceAll('\\', '/');
  return !relativePath.includes('/') || relativePath.startsWith('template-parts/');
});
for (const file of templateFiles) {
  const relativePath = rel(file);
  let content = '';
  try {
    content = fs.readFileSync(file, 'utf8');
  } catch (error) {
    const reason = error && typeof error === 'object' && 'code' in error ? error.code : String(error);
    fail(`${relativePath}: unable to read (${reason})`);
    continue;
  }
  if (/\sstyle\s*=\s*["']/i.test(content)) fail(`${relativePath}: inline style`);
  if (/<style\b/i.test(content)) fail(`${relativePath}: embedded style`);
  if (/<script\b[^>]*application\/ld\+json/i.test(content)) fail(`${relativePath}: embedded JSON-LD`);
  if (/data:image\//i.test(content)) fail(`${relativePath}: data-image`);
}

const functions = read('functions.php');
for (const marker of ['function nvx_primary_menu_fallback', 'nvx_custom_body_classes', 'is_page( 9 )', "is_page( 'medicina-estetica-laser' )", 'remove_action(']) {
  if (functions.includes(marker)) fail(`functions.php: obsolete ${marker}`);
}
if (/add_(action|filter)\s*\([^;]*function\s*\(/s.test(functions)) fail('functions.php: anonymous hook');
if ((functions.match(/wp_enqueue_style\(\s*'nvx-home-v3'/g) || []).length !== 1) fail('functions.php: home-v3 must be enqueued once');
for (const marker of ['nvx-native-style-governance.php', 'nvx-treatment-hub-schema.php']) {
  if (!functions.includes(marker)) fail(`functions.php: missing ${marker}`);
}

const native = read('inc/nvx-native-style-governance.php');
for (const marker of ["is_page_template( 'page-tratamientos.php' )", 'nvx_theme_owns_complete_page_markup', 'nvx_theme_dequeue_native_block_styles']) {
  if (!native.includes(marker)) fail(`native style module: missing ${marker}`);
}
if (native.includes('remove_action(')) fail('native style module: global action removal');

const schema = read('inc/nvx-treatment-hub-schema.php');
for (const marker of ['wpseo_schema_graph', 'PercutaneousProcedure', 'NoninvasiveProcedure', "'ItemList'"]) {
  if (!schema.includes(marker)) fail(`schema module: missing ${marker}`);
}
if (!/\$item\s*\[\s*['"]procedureType['"]\s*\]\s*=\s*array\s*\(\s*['"]@id['"]\s*=>/.test(schema)) {
  fail('schema module: missing procedureType @id assignment');
}
if (!/['"]numberOfItems['"]\s*=>\s*count\s*\(\s*\$items\s*\)/.test(schema)) {
  fail('schema module: missing dynamic numberOfItems');
}
if (/<script\b/i.test(schema)) fail('schema module: embedded script');

const front = read('front-page.php');
const hub = read('page-tratamientos.php');
for (const [name, content] of [['front-page.php', front], ['page-tratamientos.php', hub]]) {
  if (/<main\b/i.test(content)) fail(`${name}: nested main`);
  if (/\sstyle\s*=\s*["']/i.test(content)) fail(`${name}: inline style`);
  if (/<script\b[^>]*application\/ld\+json/i.test(content)) fail(`${name}: JSON-LD`);
}
for (const marker of ['data:image/', 'staging2.nuvanx.com', '/casos/', '/clinicas/equipo-medico/', 'Resultados definitivos']) {
  if (front.includes(marker)) fail(`front-page.php: ${marker}`);
}
for (const marker of ['/tratamientos/endolift/', '/tratamientos/endolaser-corporal/', '/tratamientos/laser-co2-fraccionado/', '/tratamientos/exion/', 'Aval Científico y Farmacéutico', 'schema_medical_procedures']) {
  if (hub.includes(marker)) fail(`page-tratamientos.php: ${marker}`);
}

const visibleNames = [...hub.matchAll(/class="nvx-hub-catalog__item-title">([^<]+)/g)].map((match) => match[1].trim());
const definitionsMatch = /\$definitions\s*=\s*array\s*\(/.exec(schema);
let definitionsSource = '';
if (!definitionsMatch) {
  fail('treatment schema: definitions block not found');
} else {
  const schemaTail = schema.slice(definitionsMatch.index);
  const itemsMatch = /\n\s*\$items\s*=\s*array\s*\(\s*\)\s*;/.exec(schemaTail);
  if (!itemsMatch) {
    fail('treatment schema: definitions block terminator not found');
  } else {
    definitionsSource = schemaTail.slice(0, itemsMatch.index);
  }
}
const schemaNames = [...definitionsSource.matchAll(/'name'\s*=>\s*'([^']+)'/g)].map((match) => match[1].trim());
if (visibleNames.length !== 7) fail(`treatment hub: expected 7 visible items, found ${visibleNames.length}`);
if (schemaNames.length !== 7) fail(`treatment schema: expected 7 definitions, found ${schemaNames.length}`);
if (JSON.stringify(visibleNames) !== JSON.stringify(schemaNames)) {
  fail(`treatment hub/schema name order mismatch: visible=${JSON.stringify(visibleNames)} schema=${JSON.stringify(schemaNames)}`);
}

const header = read('assets/css/nvx-header.css');
const footer = read('assets/css/nvx-footer.css');
if ((header.match(/\.nvx-mobile-nav\s*\{/g) || []).length !== 1) fail('header: mobile nav base rule count');
for (const marker of ['display: none;', 'min-height: 100dvh;', 'overflow-y: auto;', '.nvx-header__cta']) {
  if (!header.includes(marker)) fail(`header: missing ${marker}`);
}
if (/#nvx-header-cta\b/.test(header)) fail('header: ID selector specificity');
if ((footer.match(/grid-template-columns: repeat\(12, minmax\(0, 1fr\)\);/g) || []).length !== 1) fail('footer: canonical 12-column grid count');
for (const marker of ['max-width: 980px', 'max-width: 640px', '.nvx-cta-banner__actions > .nvx-btn--light']) {
  if (!footer.includes(marker)) fail(`footer: missing ${marker}`);
}
if (/#nvx-footer-cta\b/.test(footer)) fail('footer: ID selector specificity');
if (footer.includes('body.nvx-hide-closing-cta')) fail('footer: CSS CTA hiding');

const report = errors.length
  ? `FAIL: ${errors.length} theme hygiene finding(s)\n${errors.map((error) => `- ${error}`).join('\n')}\n`
  : `PASS: theme hygiene across ${runtime.length} runtime files\n`;
fs.writeFileSync(reportPath, report);
console.log(report.trimEnd());
if (errors.length) process.exit(1);
