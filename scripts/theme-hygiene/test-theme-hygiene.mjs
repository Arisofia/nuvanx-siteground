#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const reportPath = path.join(root, 'theme-hygiene-report.txt');
const errors = [];
const read = (rel) => fs.readFileSync(path.join(theme, rel), 'utf8');
const walk = (dir) => fs.readdirSync(dir, {withFileTypes:true}).flatMap((e) => {
  if (['vendor','node_modules','.git'].includes(e.name)) return [];
  const p = path.join(dir,e.name);
  return e.isDirectory() ? walk(p) : [p];
});
const files = walk(theme);
const runtime = files.filter((f)=>/\.(php|css|js)$/i.test(f));
const rel = (f)=>path.relative(root,f).replaceAll('\\','/');
const fail = (m)=>errors.push(m);

for (const f of runtime) {
  const s=fs.statSync(f), n=path.basename(f), c=fs.readFileSync(f,'utf8'), r=rel(f);
  if (!s.size) fail(`${r}: empty file`);
  if (/(^|[-_.])(legacy|old|backup|bak|temp|tmp|deprecated|unused|orphan)([-_.]|$)/i.test(n)) fail(`${r}: obsolete filename`);
  if (/staging2\.nuvanx\.com/i.test(c) && !r.endsWith('/inc/nvx-environment-flags.php')) fail(`${r}: staging hostname`);
  if (/\.css$/i.test(f) && /!important\b/i.test(c)) fail(`${r}: !important`);
  if (/\.css$/i.test(f) && /[^{}]+\{\s*\}/m.test(c)) fail(`${r}: empty CSS rule`);
}

const templateFiles = runtime.filter((f)=>{
  if (!/\.php$/i.test(f)) return false;
  const r=path.relative(theme,f).replaceAll('\\','/');
  return !r.includes('/') || r.startsWith('template-parts/');
});
for (const f of templateFiles) {
  const c=fs.readFileSync(f,'utf8');
  if (/\sstyle\s*=\s*["']/i.test(c)) fail(`${rel(f)}: inline style`);
  if (/<style\b/i.test(c)) fail(`${rel(f)}: embedded style`);
  if (/<script\b[^>]*application\/ld\+json/i.test(c)) fail(`${rel(f)}: embedded JSON-LD`);
  if (/data:image\//i.test(c)) fail(`${rel(f)}: data-image`);
}

const functions=read('functions.php');
for (const x of ['function nvx_primary_menu_fallback','nvx_custom_body_classes','is_page( 9 )',"is_page( 'medicina-estetica-laser' )",'remove_action(']) {
  if (functions.includes(x)) fail(`functions.php: obsolete ${x}`);
}
if (/add_(action|filter)\s*\([^;]*function\s*\(/s.test(functions)) fail('functions.php: anonymous hook');
if ((functions.match(/wp_enqueue_style\(\s*'nvx-home-v3'/g)||[]).length!==1) fail('functions.php: home-v3 must be enqueued once');
for (const x of ['nvx-native-style-governance.php','nvx-treatment-hub-schema.php']) {
  if (!functions.includes(x)) fail(`functions.php: missing ${x}`);
}

const native=read('inc/nvx-native-style-governance.php');
for (const x of ["is_page_template( 'page-tratamientos.php' )",'nvx_theme_owns_complete_page_markup','nvx_theme_dequeue_native_block_styles']) {
  if (!native.includes(x)) fail(`native style module: missing ${x}`);
}
if (native.includes('remove_action(')) fail('native style module: global action removal');

const schema=read('inc/nvx-treatment-hub-schema.php');
for (const x of ['wpseo_schema_graph','PercutaneousProcedure','NoninvasiveProcedure',"'ItemList'","$item['procedureType'] = array( '@id' =>","'numberOfItems'   => count( $items )"]) {
  if (!schema.includes(x)) fail(`schema module: missing ${x}`);
}
if (/<script\b/i.test(schema)) fail('schema module: embedded script');

const front=read('front-page.php'), hub=read('page-tratamientos.php');
for (const [name,c] of [['front-page.php',front],['page-tratamientos.php',hub]]) {
  if (/<main\b/i.test(c)) fail(`${name}: nested main`);
  if (/\sstyle\s*=\s*["']/i.test(c)) fail(`${name}: inline style`);
  if (/<script\b[^>]*application\/ld\+json/i.test(c)) fail(`${name}: JSON-LD`);
}
for (const x of ['data:image/','staging2.nuvanx.com','/casos/','/clinicas/equipo-medico/','Resultados definitivos']) {
  if (front.includes(x)) fail(`front-page.php: ${x}`);
}
for (const x of ['/tratamientos/endolift/','/tratamientos/endolaser-corporal/','/tratamientos/laser-co2-fraccionado/','/tratamientos/exion/','Aval Científico y Farmacéutico','schema_medical_procedures']) {
  if (hub.includes(x)) fail(`page-tratamientos.php: ${x}`);
}

const visibleNames = [...hub.matchAll(/class="nvx-hub-catalog__item-title">([^<]+)</g)].map((m)=>m[1].trim());
const definitionsStart = schema.indexOf('$definitions = array(');
const definitionsEnd = schema.indexOf('\n\t$items = array();', definitionsStart);
const definitionsSource = definitionsStart >= 0 && definitionsEnd > definitionsStart ? schema.slice(definitionsStart, definitionsEnd) : '';
const schemaNames = [...definitionsSource.matchAll(/'name'\s*=>\s*'([^']+)'/g)].map((m)=>m[1].trim());
if (visibleNames.length !== 7) fail(`treatment hub: expected 7 visible items, found ${visibleNames.length}`);
if (schemaNames.length !== 7) fail(`treatment schema: expected 7 definitions, found ${schemaNames.length}`);
if (JSON.stringify(visibleNames) !== JSON.stringify(schemaNames)) {
  fail(`treatment hub/schema name order mismatch: visible=${JSON.stringify(visibleNames)} schema=${JSON.stringify(schemaNames)}`);
}

const header=read('assets/css/nvx-header.css'), footer=read('assets/css/nvx-footer.css');
if ((header.match(/\.nvx-mobile-nav\s*\{/g)||[]).length!==1) fail('header: mobile nav base rule count');
for (const x of ['display: none;','min-height: 100dvh;','overflow-y: auto;','.nvx-header__cta']) if (!header.includes(x)) fail(`header: missing ${x}`);
if (/#nvx-header-cta\b/.test(header)) fail('header: ID selector specificity');
if ((footer.match(/grid-template-columns: repeat\(12, minmax\(0, 1fr\)\);/g)||[]).length!==1) fail('footer: canonical 12-column grid count');
for (const x of ['max-width: 980px','max-width: 640px','.nvx-cta-banner__actions > .nvx-btn--light']) if (!footer.includes(x)) fail(`footer: missing ${x}`);
if (/#nvx-footer-cta\b/.test(footer)) fail('footer: ID selector specificity');
if (footer.includes('body.nvx-hide-closing-cta')) fail('footer: CSS CTA hiding');

const report = errors.length
  ? `FAIL: ${errors.length} theme hygiene finding(s)\n${errors.map((e)=>`- ${e}`).join('\n')}\n`
  : `PASS: theme hygiene across ${runtime.length} runtime files\n`;
fs.writeFileSync(reportPath, report);
console.log(report.trimEnd());
if (errors.length) process.exit(1);
