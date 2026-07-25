#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const failures = [];

function read(relativePath) {
  const target = path.join(theme, relativePath);
  if (!fs.existsSync(target)) {
    failures.push(`missing ${relativePath}`);
    return '';
  }
  return fs.readFileSync(target, 'utf8');
}

function walkPhp(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (['vendor', 'node_modules', '.git'].includes(entry.name)) return [];
    const target = path.join(directory, entry.name);
    if (entry.isDirectory()) return walkPhp(target);
    return entry.isFile() && entry.name.endsWith('.php') ? [target] : [];
  });
}

const structuredData = read('inc/nvx-structured-data.php');
const jsonldHelpers = read('inc/nvx-jsonld-content.php');
const environmentFlags = read('inc/nvx-environment-flags.php');
const runtimeCompatibility = read('inc/nvx-runtime-compatibility.php');
const contentPresentation = read('inc/nvx-content-presentation.php');
const equipoPage = read('inc/nvx-equipo-page.php');
const drRiveraPage = read('inc/nvx-dr-rivera-page.php');
const pageHygiene = read('inc/nvx-page-hygiene.php');
const aestheticPages = read('inc/nvx-aesthetic-treatment-pages.php');
const functions = read('functions.php');

const registeredJsonldCallback = structuredData.match(
  /add_filter\s*\(\s*['"]the_content['"]\s*,\s*['"]([^'"]+)['"]\s*,\s*5\s*\)/,
)?.[1] || '';

if (!registeredJsonldCallback) {
  failures.push('structured data module does not register the JSON-LD the_content callback at priority 5');
} else {
  const escapedName = registeredJsonldCallback.replace(/[.*+?^${}()|[\]\\]/g, String.raw`\$&`);
  const declaration = new RegExp(String.raw`function\s+${escapedName}\s*\(`);
  if (!declaration.test(jsonldHelpers)) {
    failures.push(`registered callback ${registeredJsonldCallback} is not declared by nvx-jsonld-content.php`);
  }
}

for (const marker of [
  'function nvxFilterStripEmbeddedJsonld(',
  'function nvx_filter_strip_embedded_jsonld(',
  'return nvxFilterStripEmbeddedJsonld( $content );',
]) {
  if (!jsonldHelpers.includes(marker)) failures.push(`JSON-LD callback compatibility marker missing: ${marker}`);
}

for (const marker of [
  'function nvx_environment_host(): string',
  'function nvx_environment_is_staging2(): bool',
  'function nvxEnvironmentIsStaging2(): bool',
  "'staging2.nuvanx.com' === $host",
  "apply_filters( 'nvx_environment_is_staging2'",
  "require_once __DIR__ . '/nvx-runtime-compatibility.php';",
]) {
  if (!environmentFlags.includes(marker)) failures.push(`environment runtime marker missing: ${marker}`);
}
if (environmentFlags.includes('nvxEnvironmentFilterHeroBlackout')) {
  failures.push('environment runtime must not restore the retired hero blackout filter');
}
for (const [source, label] of [
  [pageHygiene, 'page hygiene'],
  [aestheticPages, 'aesthetic page seeder'],
]) {
  if (!source.includes("function_exists( 'nvx_environment_is_staging2' )")) {
    failures.push(`${label} does not guard its shared staging2 environment dependency`);
  }
}

if (!contentPresentation.includes('function nvx_cta_pair_markup(')) {
  failures.push('canonical CTA pair implementation is missing');
}
for (const marker of [
  "function_exists( 'nvxCtaPairMarkup' )",
  'function nvxCtaPairMarkup(',
  "function_exists( 'nvx_cta_pair_markup' )",
  'return nvx_cta_pair_markup( $extra_class );',
]) {
  if (!runtimeCompatibility.includes(marker)) failures.push(`CTA compatibility marker missing: ${marker}`);
}
for (const [source, label] of [
  [equipoPage, 'equipo renderer'],
  [drRiveraPage, 'Dr. Rivera renderer'],
]) {
  if (source.includes('nvxCtaPairMarkup(') && !runtimeCompatibility.includes('function nvxCtaPairMarkup(')) {
    failures.push(`${label} calls nvxCtaPairMarkup without a declared compatibility adapter`);
  }
}

const constantNames = ['NVX_REGEX_WHITESPACE', 'NVX_REGEX_WHITESPACE_U'];
const phpFiles = walkPhp(theme);
for (const constantName of constantNames) {
  const definitions = [];
  const pattern = new RegExp(String.raw`define\s*\(\s*['"]${constantName}['"]`, 'g');
  for (const file of phpFiles) {
    const source = fs.readFileSync(file, 'utf8');
    const count = (source.match(pattern) || []).length;
    if (count) definitions.push({ file: path.relative(theme, file).replaceAll(String.raw`\\`, '/'), count });
  }

  const total = definitions.reduce((sum, item) => sum + item.count, 0);
  if (total !== 1 || definitions[0]?.file !== 'functions.php') {
    failures.push(`${constantName} must be defined exactly once in functions.php; found ${JSON.stringify(definitions)}`);
  }
  if (!functions.includes(`if ( ! defined( '${constantName}' ) ) {`)) {
    failures.push(`${constantName} definition is not guarded with defined()`);
  }
}

if (failures.length) {
  console.error(`RUNTIME_BOOTSTRAP_CONTRACT_FAILED findings=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log(`RUNTIME_BOOTSTRAP_CONTRACT_OK callback=${registeredJsonldCallback} environment=staging2 cta=compatible`);