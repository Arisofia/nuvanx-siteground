#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const reportPath = path.join(root, 'theme-hygiene-report.txt');
const errors = [];
const rel = (file) => path.relative(root, file).replaceAll('\\', '/');

/**
 * Records a theme hygiene finding.
 * @param {string} message - The finding to add to the report.
 */
function fail(message) {
  errors.push(message);
}

/**
 * Reads a theme file as UTF-8 text.
 * @param {string} relativePath - The file path relative to the theme directory.
 * @return {string} The file contents, or an empty string when the file cannot be read.
 */
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

/**
 * Recursively collects files from a directory while skipping excluded directories.
 * @param {string} dir - The directory to scan.
 * @returns {string[]} The paths of files found under the directory.
 */
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
    if (content.charCodeAt(0) === 0xfeff) fail(`${relativePath}: UTF-8 BOM`);
    if (/[ÃÂ]|â(?:€|€™|€œ|€)/u.test(content)) fail(`${relativePath}: probable mojibake`);
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

const requiredModules = [
  'inc/nvx-native-style-governance.php',
  'inc/nvx-treatment-hub-schema.php',
  'inc/nvx-portfolio-hub.php',
  'inc/nvx-protocol-hub.php',
  'inc/nvx-protocol-pages.php',
];
for (const modulePath of requiredModules) {
  const absolutePath = path.join(theme, modulePath);
  const filename = path.basename(modulePath);
  if (!fs.existsSync(absolutePath)) fail(`theme: missing ${modulePath}`);
  if (!functions.includes(filename)) fail(`functions.php: missing ${filename}`);
}
if (functions.includes('nvx-treatments-catalog.php')) fail('functions.php: obsolete nvx-treatments-catalog.php');
if (fs.existsSync(path.join(theme, 'inc/nvx-treatments-catalog.php'))) fail('theme: obsolete inc/nvx-treatments-catalog.php');

const oldP5 = path.join(root, 'docs/content-strategy-2026/NUVANX-P5-TRATAMIENTOS.md');
const newP5 = path.join(root, 'docs/content-strategy-2026/NUVANX-P5-PORTAFOLIO-CLINICO.md');
if (fs.existsSync(oldP5)) fail(`${rel(oldP5)}: obsolete content contract filename`);
if (!fs.existsSync(newP5)) fail(`${rel(newP5)}: missing canonical content contract`);

const integrations = read('inc/nvx-integrations.php');
for (const marker of [
  "'liposculpt-air'",
  "'/remodelacion-corporal-laser-madrid/'",
  "'v-lift-awake'",
  "'/papada-definicion-mandibular-madrid/'",
  "'tratamiento-postparto-abdomen-contorno-corporal-madrid'",
  "'/protocolos-signature/'",
  "remove_action( 'init', 'nvx_strategy_seed_staging2_pages', 31 )",
  'function nvx_production_readiness_governed_pages',
  'foreach ( nvx_production_readiness_governed_pages()',
]) {
  if (!integrations.includes(marker)) fail(`integrations: missing production-readiness contract ${marker}`);
}
if (integrations.includes('nvx_strategy_seed_approved_staging2_pages')) fail('integrations: runtime approved-page seeder');
if ((integrations.match(/'liposculpt-air'\s*=>/g) || []).length !== 1) fail('integrations: governed slug duplicated outside shared contract');
if ((integrations.match(/'v-lift-awake'\s*=>/g) || []).length !== 1) fail('integrations: governed slug duplicated outside shared contract');

const strategy = read('inc/nvx-strategy-pages.php');
for (const marker of ['liposculpt_air', 'v_lift_awake', 'liposculpt-air', 'v-lift-awake', 'pending_medical_legal', 'nvx_strategy_protocol_review_markup']) {
  if (strategy.includes(marker)) fail(`strategy pages: retired prototype marker ${marker}`);
}
if (/add_action\(\s*'init'\s*,\s*'nvx_strategy_seed/u.test(strategy)) fail('strategy pages: runtime seeder');

const protocolHub = read('inc/nvx-protocol-hub.php');
const protocolPages = read('inc/nvx-protocol-pages.php');
const portfolioHub = read('inc/nvx-portfolio-hub.php');
for (const [name, content] of [
  ['protocol hub', protocolHub],
  ['protocol pages', protocolPages],
  ['portfolio hub', portfolioHub],
]) {
  for (const marker of ['Post-Maternity', 'Protocolo en construcción clínica', 'fase de despliegue web', '/post-maternity/']) {
    if (content.includes(marker)) fail(`${name}: unpublished marker ${marker}`);
  }
}
if (protocolHub.includes('/couture-sculpt/')) fail('protocol hub: non-canonical Couture Sculpt route');
if (!protocolHub.includes('/remodelacion-corporal-laser-madrid/')) fail('protocol hub: missing canonical Couture Sculpt route');
if (!protocolPages.includes("if ( 'couture-sculpt' !== $key )")) fail('protocol pages: unsupported renderer must fail safely');
if (/add_action\(\s*'init'\s*,\s*'nvx_seed_protocol/u.test(protocolHub + protocolPages)) fail('protocol modules: runtime seeder');

const migration = path.join(root, 'scripts/wp/nvx-production-readiness-command.php');
if (!fs.existsSync(migration)) {
  fail(`${rel(migration)}: missing migration command`);
} else {
  const migrationContent = fs.readFileSync(migration, 'utf8');
  for (const marker of [
    'retire-prototypes',
    'staging2.nuvanx.com',
    '--allow-production',
    'nvx_production_readiness_governed_pages',
    'validate_invocation',
    'apply_approved_pages',
    'apply_governed_pages',
    'wp_trash_post',
    "WP_CLI::add_command( 'nvx production-readiness'",
  ]) {
    if (!migrationContent.includes(marker)) fail(`migration command: missing ${marker}`);
  }
  if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migrationContent)) fail('migration command: direct trash status update');
  if ((migrationContent.match(/'liposculpt-air'\s*=>/g) || []).length !== 0) fail('migration command: duplicated governed-page definitions');
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
