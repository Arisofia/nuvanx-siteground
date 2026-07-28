#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
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

function readRoot(relativePath) {
  const target = path.join(root, relativePath);
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
const equipoLayout = read('inc/nvx-equipo-layout-contract.php');
const equipoLayoutCss = read('assets/css/nvx-equipo-layout-contract.css');
const pageHygiene = read('inc/nvx-page-hygiene.php');
const aestheticPages = read('inc/nvx-aesthetic-treatment-pages.php');
const functions = read('functions.php');
const visualQaPreload = readRoot('scripts/staging2/visual-qa-edge-preload.mjs');

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
  'return is_front_page() || is_singular();',
]) {
  if (!jsonldHelpers.includes(marker)) failures.push(`JSON-LD callback compatibility marker missing: ${marker}`);
}

const btlDetail = read('inc/nvx-btl-detail-pages.php');
const seoReadiness = read('inc/nvx-seo-production-readiness.php');
const integrations = read('inc/nvx-integrations.php');

for (const marker of [
  'function nvxBtlDetailRegistry(): array',
  'function nvx_btl_detail_registry(): array',
  'return nvxBtlDetailRegistry();',
]) {
  if (!btlDetail.includes(marker)) failures.push(`BTL detail registry alias marker missing: ${marker}`);
}

for (const marker of [
  'function nvxSeoSchemaMaterializeLogoNode(',
  'function nvxSeoSchemaEnsureWebpageMainEntity(',
  'nvxSeoSchemaMaterializeLogoNode( $graph )',
  'nvxSeoSchemaEnsureWebpageMainEntity( $graph, $current_url )',
]) {
  if (!seoReadiness.includes(marker)) failures.push(`SEO readiness graph marker missing: ${marker}`);
}

for (const marker of [
  'function nvxSchemaLinkWebpageMainEntity(',
  'nvxSchemaLinkWebpageMainEntity( $graph, (string) $treatment[\'url\'], (string) $treatment[\'@id\'] )',
]) {
  if (!structuredData.includes(marker)) failures.push(`structured-data mainEntity marker missing: ${marker}`);
}

if (!integrations.includes("yoast-schema-graph")) {
  failures.push('integrations public document normalizer must preserve yoast-schema-graph');
}
if (!integrations.includes('schema\\.org|@graph\\b|"@type"\\s*:')) {
  // Accept either escaped regex form used in PHP string
  if (!/schema\.org\|@graph/.test(integrations)) {
    failures.push('integrations public document normalizer must strip residual Schema.org ld+json');
  }
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

for (const marker of [
  'function nvx_cta_pair_markup(',
  'function nvx_html_attrs_add_class(',
]) {
  if (!contentPresentation.includes(marker)) failures.push(`canonical presentation helper missing: ${marker}`);
}
for (const marker of [
  "function_exists( 'nvxCtaPairMarkup' )",
  'function nvxCtaPairMarkup(',
  "function_exists( 'nvx_cta_pair_markup' )",
  'return nvx_cta_pair_markup( $extraClass );',
  "function_exists( 'nvxHtmlAttrsAddClass' )",
  'function nvxHtmlAttrsAddClass(',
  "function_exists( 'nvx_html_attrs_add_class' )",
  'return nvx_html_attrs_add_class( $attrs, $class_token );',
  "function_exists( 'nvxSchemaCurrentPath' )",
  'function nvxSchemaCurrentPath(',
  "function_exists( 'nvx_schema_current_path' )",
  'return (string) nvx_schema_current_path( $page_id );',
  "require_once __DIR__ . '/nvx-equipo-layout-contract.php';",
]) {
  if (!runtimeCompatibility.includes(marker)) failures.push(`runtime compatibility marker missing: ${marker}`);
}
for (const [source, label] of [
  [equipoPage, 'equipo renderer'],
  [drRiveraPage, 'Dr. Rivera renderer'],
]) {
  if (source.includes('nvxCtaPairMarkup(') && !runtimeCompatibility.includes('function nvxCtaPairMarkup(')) {
    failures.push(`${label} calls nvxCtaPairMarkup without a declared compatibility adapter`);
  }
  if (source.includes('nvxHtmlAttrsAddClass(') && !runtimeCompatibility.includes('function nvxHtmlAttrsAddClass(')) {
    failures.push(`${label} calls nvxHtmlAttrsAddClass without a declared compatibility adapter`);
  }
}

for (const marker of [
  'function nvxEquipoLayoutContractApplies(): bool',
  'function nvxEquipoLayoutContractAssets(): void',
  "is_page( 'equipo-medico' )",
  "array( 'nvx-ui-regressions' )",
  "add_action( 'wp_enqueue_scripts', 'nvxEquipoLayoutContractAssets', 110 )",
]) {
  if (!equipoLayout.includes(marker)) failures.push(`Equipo layout contract marker missing: ${marker}`);
}
for (const marker of [
  '.nvx-equipo-editorial .nvx-editorial-grid-list',
  'repeat(2, minmax(0, 1fr))',
]) {
  if (!equipoLayoutCss.includes(marker)) failures.push(`Equipo layout CSS marker missing: ${marker}`);
}

for (const marker of [
  'NVX_VISUAL_QA_ATTEMPT',
  'VISUAL_QA_RETRY_TRANSIENT_EDGE',
  'spawn(process.execPath',
  '...process.execArgv',
  'cleanupProxyForRetry',
  'retryInProgress',
  'Inspected target navigated or closed',
  'Promise was collected',
  'visualQaAttempt < 3',
  String.raw`staging2\.nuvanx\.com`,
  'const stagingHost =',
  'configuredSshAlias !== sshAlias',
  'target.host !== stagingHost || target.port !== stagingPort',
  'HTTP/1.1 403 Forbidden',
  'http.createServer',
  "proxyServer.on('connect'",
  'stream_socket_client',
  'ControlMaster=auto',
  'ControlPersist=60',
  'ControlPath=',
  "'--proxy'",
  'VISUAL_QA_SSH_BRIDGE_READY',
  'VISUAL_QA_SSH_BRIDGE_UNAVAILABLE',
  '--proxy-server=',
  'const proxyUrl =',
  'waitForCompletion',
  'args.map(String)',
  'process.env.CHROME_BIN = chromeWrapper',
  'NVX_REAL_CHROME_BIN',
  'cleanupProxy',
]) {
  if (!visualQaPreload.includes(marker)) failures.push(`visual QA bridge marker missing: ${marker}`);
}
for (const forbidden of [
  "'-D'",
  '--socks5-hostname',
  'VISUAL_QA_SSH_PROXY_READY',
  'Atomics.wait',
  'spawnSync(process.execPath',
  "from 'node:net'",
  'net.connect(',
  'spawnRemoteBridge(host, port)',
  'shellQuote(host)',
]) {
  if (visualQaPreload.includes(forbidden)) failures.push(`visual QA retains prohibited SSH forwarding marker: ${forbidden}`);
}

const callableContractPath = path.join(root, 'scripts/theme-hygiene/test-nvx-callable-contract.php');
const phpBin = process.env.PHP_BIN || 'php';
const callableContract = spawnSync(phpBin, [callableContractPath], { encoding: 'utf8' });
if (callableContract.error) {
  failures.push(`unable to run ${phpBin}: ${callableContract.error.message}`);
} else if (callableContract.status !== 0) {
  failures.push(`NUVANX callable contract failed: ${String(callableContract.stderr || callableContract.stdout || callableContract.error || '').trim()}`);
} else if (callableContract.stdout.trim()) {
  console.log(callableContract.stdout.trim());
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

console.log(`RUNTIME_BOOTSTRAP_CONTRACT_OK callback=${registeredJsonldCallback} environment=staging2 compatibility=complete equipo=2-column visual-retry=guarded visual-bridge=ssh-exec`);
