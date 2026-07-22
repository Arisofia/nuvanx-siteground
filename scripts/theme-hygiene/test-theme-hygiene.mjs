#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const reportPath = path.join(root, 'theme-hygiene-report.txt');
const errors = [];
const rel = (file) => path.relative(root, file).replaceAll('\\', '/');
const fail = (message) => errors.push(message);

function read(relativePath) {
  const target = path.join(theme, relativePath);
  try { return fs.readFileSync(target, 'utf8'); }
  catch (error) { fail(`${rel(target)}: unable to read (${error.code || error})`); return ''; }
}
function walk(dir) {
  try {
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
      if (['vendor', 'node_modules', '.git'].includes(entry.name)) return [];
      const target = path.join(dir, entry.name);
      return entry.isDirectory() ? walk(target) : [target];
    });
  } catch (error) { fail(`${rel(dir)}: unable to scan (${error.code || error})`); return []; }
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
  } catch (error) { fail(`${relativePath}: unable to inspect (${error.code || error})`); }
}

const templateFiles = runtime.filter((file) => {
  if (!/\.php$/i.test(file)) return false;
  const relativePath = path.relative(theme, file).replaceAll('\\', '/');
  return !relativePath.includes('/') || relativePath.startsWith('template-parts/');
});
for (const file of templateFiles) {
  const content = fs.readFileSync(file, 'utf8');
  const relativePath = rel(file);
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
  'inc/nvx-editorial-seo-extension.php',
];
for (const modulePath of requiredModules) {
  const absolutePath = path.join(theme, modulePath);
  if (!fs.existsSync(absolutePath)) fail(`theme: missing ${modulePath}`);
  if (modulePath !== 'inc/nvx-editorial-seo-extension.php' && !functions.includes(path.basename(modulePath))) fail(`functions.php: missing ${path.basename(modulePath)}`);
}

const integrations = read('inc/nvx-integrations.php');
for (const marker of [
  "'liposculpt-air'", "'/remodelacion-corporal-laser-madrid/'", "'v-lift-awake'", "'/protocolos-signature/'",
  "remove_action( 'init', 'nvx_strategy_seed_staging2_pages', 31 )", 'function nvx_production_readiness_governed_pages',
  'foreach ( nvx_production_readiness_governed_pages()', "require_once __DIR__ . '/nvx-editorial-seo-extension.php';",
]) if (!integrations.includes(marker)) fail(`integrations: missing production-readiness contract ${marker}`);
if (integrations.includes("'tratamiento-postparto-abdomen-contorno-corporal-madrid' =>")) fail('integrations: published Post-Maternity remains governed as retired');
if ((integrations.match(/'liposculpt-air'\s*=>/g) || []).length !== 1) fail('integrations: governed LipoSculpt slug count');
if ((integrations.match(/'v-lift-awake'\s*=>/g) || []).length !== 1) fail('integrations: governed V-Lift slug count');

const strategy = read('inc/nvx-strategy-pages.php');
for (const marker of [
  "'solutions' =>", "'slug'          => 'soluciones-medicas'", 'Soluciones médicas para rostro, piel y contorno corporal.',
  'Una misma preocupación puede tener causas distintas.', 'Valoración de procedimientos previos',
  'Por qué NUVANX. Sin retórica de marketing.', 'Responsabilidad médica y continuidad asistencial',
  'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí', 'Una promoción utilizada para cambiar la indicación clínica',
]) if (!strategy.includes(marker)) fail(`strategy pages: missing editorial parity marker ${marker}`);
for (const marker of ['liposculpt_air', 'v_lift_awake', 'pending_medical_legal', 'nvx_strategy_protocol_review_markup']) {
  if (strategy.includes(marker)) fail(`strategy pages: retired prototype marker ${marker}`);
}
if (/add_action\(\s*'init'\s*,\s*'nvx_strategy_seed/u.test(strategy)) fail('strategy pages: runtime seeder');

const protocolHub = read('inc/nvx-protocol-hub.php');
const protocolPages = read('inc/nvx-protocol-pages.php');
for (const marker of ['Contorno Corporal y Posgestacional', 'Post-Maternity Contour™', '/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tu primera valoración clínica']) {
  if (!protocolHub.includes(marker)) fail(`protocol hub: missing ${marker}`);
}
for (const marker of ["'post-maternity' =>", 'nvx_protocol_pages_post_maternity_markup', 'Las alteraciones del posparto', 'Preguntas frecuentes', 'nvx_protocol_pages_markup']) {
  if (!protocolPages.includes(marker)) fail(`protocol pages: missing ${marker}`);
}
for (const content of [protocolHub, protocolPages]) {
  for (const forbidden of ['Protocolo en construcción clínica', 'fase de despliegue web', '/post-maternity/']) {
    if (content.includes(forbidden)) fail(`protocol content: unpublished marker ${forbidden}`);
  }
}

const migrationPath = path.join(root, 'scripts/wp/nvx-production-readiness-command.php');
const migration = fs.existsSync(migrationPath) ? fs.readFileSync(migrationPath, 'utf8') : '';
for (const marker of [
  'retire-prototypes', 'staging2.nuvanx.com', '--allow-production', 'nvx_production_readiness_governed_pages',
  'apply_approved_pages', 'apply_governed_pages', 'wp_trash_post', "'soluciones-medicas' =>",
  "'tratamiento-postparto-abdomen-contorno-corporal-madrid' =>", "'promote_draft' => true",
]) if (!migration.includes(marker)) fail(`migration command: missing ${marker}`);
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration command: direct trash status update');
if ((migration.match(/'liposculpt-air'\s*=>/g) || []).length !== 0) fail('migration command: duplicated governed definitions');

const native = read('inc/nvx-native-style-governance.php');
for (const marker of ['nvx_theme_owns_complete_page_markup']) {
  if (!native.includes(marker)) fail(`native style module: missing ${marker}`);
}
if (native.includes('remove_action(')) fail('native style module: global action removal');

const pageShell = read('template-parts/content/nvx-page-shell.php');
for (const marker of ["function_exists( 'nvx_strategy_current_page_key' )", "function_exists( 'nvx_content_is_protocol_hub' )", "function_exists( 'nvx_protocol_pages_current_key' )"]) {
  if (!pageShell.includes(marker)) fail(`page shell: missing managed marker ${marker}`);
}

const schema = read('inc/nvx-treatment-hub-schema.php');
for (const marker of ['wpseo_schema_graph', 'PercutaneousProcedure', 'NoninvasiveProcedure', "'ItemList'"]) if (!schema.includes(marker)) fail(`schema module: missing ${marker}`);
if (!/['"]numberOfItems['"]\s*=>\s*count\s*\(\s*\$items\s*\)/.test(schema)) fail('schema module: missing dynamic numberOfItems');
if (/<script\b/i.test(schema)) fail('schema module: embedded script');

// Hub tests removed due to retirement of page-tratamientos.php
const header = read('assets/css/nvx-header.css');
const footer = read('assets/css/nvx-footer.css');
if ((header.match(/\.nvx-mobile-nav\s*\{/g) || []).length !== 1) fail('header: mobile nav base rule count');
for (const marker of ['display: none;', 'min-height: 100dvh;', 'overflow-y: auto;', '.nvx-header__cta']) if (!header.includes(marker)) fail(`header: missing ${marker}`);
if ((footer.match(/grid-template-columns: repeat\(12, minmax\(0, 1fr\)\);/g) || []).length !== 1) fail('footer: canonical 12-column grid count');

const report = errors.length
  ? `FAIL: ${errors.length} theme hygiene finding(s)\n${errors.map((error) => `- ${error}`).join('\n')}\n`
  : `PASS: theme hygiene across ${runtime.length} runtime files\n`;
fs.writeFileSync(reportPath, report);
console.log(report.trimEnd());
if (errors.length) process.exit(1);
