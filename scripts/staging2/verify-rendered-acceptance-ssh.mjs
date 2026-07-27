#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').trim().replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const evidenceDir = (process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/rendered-acceptance').trim();
const sshHost = (process.env.STAGING2_SSH_ALIAS || 'nvx-staging2').trim();

if (baseUrl !== 'https://staging2.nuvanx.com') {
  console.error(`ERROR: refusing unexpected BASE_URL: ${baseUrl}`);
  process.exit(1);
}
if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('ERROR: EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
if (!/^[A-Za-z0-9._-]+$/.test(sshHost)) {
  console.error('ERROR: STAGING2_SSH_ALIAS contains unsupported characters.');
  process.exit(1);
}
fs.mkdirSync(evidenceDir, { recursive: true });

import { phasePageDefinitions } from './staging2-contract-common.mjs';

const commonMarkers = ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos', 'Tu primera valoración clínica'];
const pages = [
  {
    path: '/soluciones-medicas/',
    title: 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid',
    description: 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.',
    h1: 'Soluciones médicas para rostro, piel y contorno corporal.',
    markers: ['Una misma preocupación puede tener causas distintas.', 'Rostro y cuello', 'Contorno corporal', 'Cambios posgestacionales', 'Valoración de procedimientos previos'],
  },
  {
    path: '/protocolos-signature/',
    title: 'Protocolos Signature | NUVANX Madrid',
    description: 'Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado.',
    h1: 'Protocolos Signature: Medicina estética de diagnóstico.',
    markers: ['Nuestro estándar: La firma NUVANX', 'NUVANX Contour Architecture™', 'Post-Maternity Contour™', 'Arquitectura Facial y Calidad de Piel'],
  },
  {
    path: '/remodelacion-corporal-laser-madrid/',
    title: 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
    description: 'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas para grasa localizada, laxitud y continuidad tras valoración médica.',
    h1: 'Remodelación corporal láser diseñada según tu anatomía.',
    markers: ['NUVANX Contour Architecture™: El protocolo y la tecnología', 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'Cartografía Anatómica: Zonas de tratamiento', 'Cuándo no es el tratamiento adecuado'],
  },
  {
    path: '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
    title: 'Tratamiento postparto abdomen Madrid | NUVANX',
    description: 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.',
    h1: 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
    markers: ['Por qué un tratamiento posparto genérico no es suficiente', 'El Protocolo NUVANX Post-Maternity Contour™', 'Las alteraciones del posparto: qué podemos tratar y cuándo derivamos', 'Preguntas frecuentes'],
  },
  {
    path: '/por-que-nuvanx/',
    title: 'Por qué NUVANX | Criterio médico en Madrid',
    description: 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.',
    h1: 'Por qué NUVANX. Sin retórica de marketing.',
    markers: ['Diagnóstico antes de tecnología', 'Responsabilidad médica y continuidad asistencial', 'Trazabilidad de productos', 'Privacidad durante la atención', 'Por qué importa'],
  },
  {
    path: '/inversion-medicina-estetica/',
    title: 'Inversión en medicina estética | NUVANX Madrid',
    description: 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.',
    h1: 'El presupuesto forma parte de una decisión informada.',
    markers: ['Cómo leer estas tarifas', 'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí', 'Sobre los precios en medicina estética en Madrid'],
  },
  ...phasePageDefinitions.map(([path, title, description, h1]) => ({ path, title, description, h1, markers: commonMarkers })),
];

const retiredRoutes = [
  '/mas-informacion-sobre-las-cookies/',
  '/politica-de-cookies/',
  '/politica-de-privacidad/',
  '/tratamiento-retirado/',
  '/tratamientos/',
  '/liposculpt-air/',
  '/v-lift-awake/',
  '/dr-javier-rivera-tejeda/',
  '/eye-frame-rejuvenecimiento-mirada-madrid/',
  '/eye-frame/',
];

const forbiddenText = [
  'Protocolo en construcción clínica', 'fase de despliegue web', 'pending_medical_legal',
  'LipoSculpt-Air™', 'V-Lift Awake™', 'Couture Sculpt™', 'Contour Sculpt™', 'NUVANX Eye Frame™',
  'garantizar resultados', 'resultados garantizados', 'control térmico absoluto', 'sin huellas quirúrgicas evidentes',
  'sin bisturí ni puntos', 'todo en vigilia', 'mínima recuperación', 'recuperación inmediata', 'cero recuperación',
  'sin cicatrices', 'sin inflamación', 'sin dolor', 'sin riesgos', 'elimina grasa en cualquier zona', 'resultado definitivo',
  'una sola sesión', 'generalmente 3–4 sesiones', 'reducción del dolor', 'eritema reducido', 'eritema mínimo',
  'presupuesto muy bajo', 'no usamos descuentos estacionales', 'el estándar de oro', 'absoluta discreción',
];

const remoteCurlScript = [
  'set -Eeuo pipefail',
  'url="$1"',
  'redirect_mode="$2"',
  'headers_file="$(mktemp)"',
  'body_file="$(mktemp)"',
  'trap \'rm -f "$headers_file" "$body_file"\' EXIT',
  'common_args=(--silent --show-error --http1.1 --compressed --connect-timeout 15 --max-time 45)',
  'common_args+=(--user-agent \'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/150.0.0.0 Safari/537.36\')',
  'common_args+=(--header \'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8\')',
  'common_args+=(--header \'Accept-Language: es-ES,es;q=0.9,en;q=0.7\')',
  'common_args+=(--header \'Cache-Control: no-cache\' --header \'Pragma: no-cache\')',
  'common_args+=(--dump-header "$headers_file" --output "$body_file" --write-out \'%{http_code}\')',
  'set +e',
  'if [[ "$redirect_mode" == "follow" ]]; then',
  '  status="$(curl "${common_args[@]}" --location --max-redirs 5 "$url")"',
  'else',
  '  status="$(curl "${common_args[@]}" --max-redirs 0 "$url")"',
  'fi',
  'curl_status=$?',
  'set -e',
  'if [[ -z "$status" ]]; then status=000; fi',
  String.raw`printf '__NVX_CURL_EXIT__:%s\n' "$curl_status"`,
  String.raw`printf '__NVX_STATUS__:%s\n' "$status"`,
  String.raw`printf '__NVX_HEADERS_BEGIN__\n'`,
  'cat "$headers_file"',
  String.raw`printf '\n__NVX_HEADERS_END__\n__NVX_BODY_BEGIN__\n'`,
  'cat "$body_file"',
  String.raw`printf '\n__NVX_BODY_END__\n'`,
].join('\n');

const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
const findings = [];
const report = { base_url: baseUrl, transport: `ssh:${sshHost}`, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], retired_routes: [], findings };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);

function parseTransportOutput(stdout, stderr) {
  const curlExit = Number(stdout.match(/^__NVX_CURL_EXIT__:(\d+)$/m)?.[1] ?? 1);
  const status = Number(stdout.match(/^__NVX_STATUS__:(\d{3})$/m)?.[1] ?? 0);
  const headers = stdout.match(/__NVX_HEADERS_BEGIN__\n([\s\S]*?)\n__NVX_HEADERS_END__/m)?.[1] || '';
  const body = stdout.match(/__NVX_BODY_BEGIN__\n([\s\S]*?)\n__NVX_BODY_END__/m)?.[1] || '';
  if (curlExit !== 0) throw new Error(`remote curl exited ${curlExit}: ${stderr.trim() || 'no stderr'}`);
  return { status, headers, body };
}

function headerValue(headers, name) {
  const values = headers.split(/\r?\n/)
    .filter((line) => line.toLowerCase().startsWith(`${name.toLowerCase()}:`))
    .map((line) => line.slice(line.indexOf(':') + 1).trim());
  return values.at(-1) || '';
}

/**
 * Fetches a URL through the configured SSH origin transport.
 * @param {string} url - The URL to fetch.
 * @param {string} [redirectMode='manual'] - Whether redirects are followed by the remote request.
 * @returns {Promise<Object>} The parsed transport response.
 * @throws {Error} If all transport attempts fail without producing a response.
 */
async function originFetch(url, redirectMode = 'manual') {
  let lastError = null;
  let lastResponse = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const result = spawnSync('/usr/bin/ssh', [sshHost, 'bash', '-s', '--', url, redirectMode], {
      input: remoteCurlScript,
      encoding: 'utf8',
      maxBuffer: 25 * 1024 * 1024,
      timeout: 60000,
    });
    if (result.error) lastError = result.error;
    else if (result.status !== 0) lastError = new Error(result.stderr || `ssh exited ${result.status}`);
    else {
      try {
        lastResponse = parseTransportOutput(result.stdout, result.stderr || '');
        if (![202, 429].includes(lastResponse.status) && lastResponse.status < 500) return lastResponse;
      } catch (error) {
        lastError = error;
      }
    }
    if (attempt < 4) await sleep(attempt * 1500);
  }
  if (lastResponse) return lastResponse;
  throw lastError || new Error('origin transport failed without response');
}

const safePathName = (val) => String(val || '').split('/').filter(Boolean).join('__') || 'home';
function cleanHtmlText(value) {
  if (!value) return '';
  return String(value)
    .split('<').map((part, index) => {
      if (index === 0) return part;
      const closeIndex = part.indexOf('>');
      return closeIndex !== -1 ? part.slice(closeIndex + 1) : part;
    }).join(' ')
    .replace(/&[a-z#0-9]{2,6};/gi, (entity) => {
      const HTML_ENTITIES = { '&nbsp;': ' ', '&amp;': '&', '&quot;': '"', '&#39;': "'", '&apos;': "'", '&lt;': '<', '&gt;': '>' };
      return HTML_ENTITIES[entity.toLowerCase()] ?? ' ';
    })
    .replace(/\s+/g, ' ')
    .trim();
}

function extractTagContent(html, name) {
  const openTagMatch = html.match(new RegExp(String.raw`<${name}\b[^>]*>`, 'i'));
  if (!openTagMatch) return '';
  const startIndex = openTagMatch.index + openTagMatch[0].length;
  const closeTagIndex = html.toLowerCase().indexOf(`</${name.toLowerCase()}>`, startIndex);
  if (closeTagIndex === -1) return '';
  return html.slice(startIndex, closeTagIndex);
}

function extractAllTagContents(html, name) {
  const results = [];
  const openTagRegex = new RegExp(String.raw`<${name}\b[^>]*>`, 'gi');
  let match;
  while ((match = openTagRegex.exec(html)) !== null) {
    const startIndex = match.index + match[0].length;
    const closeTagIndex = html.toLowerCase().indexOf(`</${name.toLowerCase()}>`, startIndex);
    if (closeTagIndex !== -1) {
      results.push(html.slice(startIndex, closeTagIndex));
      openTagRegex.lastIndex = closeTagIndex + name.length + 3;
    }
  }
  return results;
}

const parseSingleTag = (html, name) => cleanHtmlText(extractTagContent(html, name));
const parseMultipleTagTexts = (html, name) => extractAllTagContents(html, name).map(cleanHtmlText);
const queryHtmlTags = (html, name) => [...html.matchAll(new RegExp(String.raw`<${name}\b[^>]*>`, 'gi'))].map((match) => match[0]);
const getAttrVal = (tag, attr) => {
  const match = tag.match(new RegExp(String.raw`\b${attr}\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))`, 'i'));
  if (!match) {
    return '';
  }
  if (match[1] !== undefined) {
    return match[1];
  }
  if (match[2] !== undefined) {
    return match[2];
  }
  return match[3] || '';
};

function findMetaAttr(html, attr) {
  for (const tag of queryHtmlTags(html, 'meta')) {
    if (getAttrVal(tag, 'name').toLowerCase() === attr.toLowerCase()) return getAttrVal(tag, 'content');
    if (getAttrVal(tag, 'property').toLowerCase() === attr.toLowerCase()) return getAttrVal(tag, 'content');
  }
  return '';
}

function findLinkHrefs(html, relType) {
  return queryHtmlTags(html, 'link')
    .filter((tag) => getAttrVal(tag, 'rel').toLowerCase().split(/\s+/).includes(relType.toLowerCase()))
    .map((tag) => getAttrVal(tag, 'href'));
}

function parseSchemaTypes(html) {
  const types = new Set();
  for (const match of html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>(.*?)<\/script>/gis)) {
    try {
      const visit = (value) => {
        if (Array.isArray(value)) return value.forEach(visit);
        if (!value || typeof value !== 'object') return;
        const type = value['@type'];
        if (Array.isArray(type)) type.forEach((item) => types.add(String(item)));
        else if (type) types.add(String(type));
        Object.values(value).forEach(visit);
      };
      visit(JSON.parse(match[1]));
    } catch { /* Ignore malformed third-party schema. */ }
  }
  return [...types];
}

function parseHtmlPage(html) {
  return {
    title: parseSingleTag(html, 'title'),
    description: findMetaAttr(html, 'description'),
    ogTitle: findMetaAttr(html, 'og:title'),
    ogDescription: findMetaAttr(html, 'og:description'),
    robots: findMetaAttr(html, 'robots'),
    deploySha: findMetaAttr(html, 'nvx-deploy-sha'),
    h1List: parseMultipleTagTexts(html, 'h1'),
    h2Count: parseMultipleTagTexts(html, 'h2').length,
    canonicals: findLinkHrefs(html, 'canonical'),
    ogUrl: findMetaAttr(html, 'og:url'),
    schemas: parseSchemaTypes(html),
    bodyText: cleanHtmlText(html),
  };
}

function validatePageMetadata(page, parsed, scope) {
  if (parsed.deploySha !== expectedSha) fail(scope, `served SHA ${parsed.deploySha || 'absent'} instead of ${expectedSha}`);
  if (parsed.title !== page.title) fail(scope, `title mismatch: ${JSON.stringify(parsed.title)}`);
  if (parsed.description !== page.description) fail(scope, 'meta description mismatch');
  if (parsed.ogTitle !== page.title) fail(scope, 'og:title mismatch');
  if (parsed.ogDescription !== page.description) fail(scope, 'og:description mismatch');
  if (!parsed.robots.toLowerCase().includes('noindex') || !parsed.robots.toLowerCase().includes('nofollow')) fail(scope, `robots mismatch: ${parsed.robots || 'absent'}`);
}

function validatePageContent(page, parsed, scope) {
  if (parsed.h1List.length !== 1 || parsed.h1List[0] !== page.h1) fail(scope, `H1 mismatch: ${JSON.stringify(parsed.h1List)}`);
  if (parsed.h2Count < 3) fail(scope, `expected at least 3 H2s, found ${parsed.h2Count}`);
  for (const marker of page.markers) if (!parsed.bodyText.includes(marker)) fail(scope, `missing marker: ${marker}`);
  if (!/valoraci[oó]n/i.test(parsed.bodyText)) fail(scope, 'missing medical valuation CTA or copy');
}

function validatePageLinksAndSchema(page, parsed, scope) {
  const validUrls = new Set([`https://staging2.nuvanx.com${page.path}`, `https://nuvanx.com${page.path}`, `https://www.nuvanx.com${page.path}`]);
  if (parsed.canonicals.length !== 1 || !validUrls.has(parsed.canonicals[0])) fail(scope, `canonical mismatch: ${parsed.canonicals[0] || 'absent'}`);
  if (!validUrls.has(parsed.ogUrl)) fail(scope, `og:url mismatch: ${parsed.ogUrl || 'absent'}`);
  if (!parsed.schemas.includes('WebPage')) fail(scope, 'missing WebPage schema');
  if (!parsed.schemas.includes('Organization') && !parsed.schemas.includes('MedicalOrganization')) fail(scope, 'missing Organization or MedicalOrganization schema');
}

function validatePage(page, parsed, scope) {
  validatePageMetadata(page, parsed, scope);
  validatePageContent(page, parsed, scope);
  validatePageLinksAndSchema(page, parsed, scope);

  const lowerText = parsed.bodyText.toLowerCase();
  for (const forbidden of forbiddenText) if (lowerText.includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
}

for (const page of pages) {
  const response = await originFetch(`${baseUrl}${page.path.replace(/\/$/, '')}/`);
  const fileName = `${safePathName(page.path)}.html`;
  fs.writeFileSync(path.join(evidenceDir, fileName), response.body);
  const record = { path: page.path, status: response.status };
  if (response.status !== 200) {
    fail(page.path, `returned HTTP ${response.status} instead of 200`);
  } else {
    const parsed = parseHtmlPage(response.body);
    Object.assign(record, {
      title: parsed.title,
      description: parsed.description,
      og_title: parsed.ogTitle,
      og_description: parsed.ogDescription,
      robots: parsed.robots,
      deploy_sha: parsed.deploySha,
      h1: parsed.h1List,
      h2_count: parsed.h2Count,
      canonicals: parsed.canonicals,
      og_url: parsed.ogUrl,
      schema_types: parsed.schemas,
    });
    validatePage(page, parsed, page.path);
  }
  report.pages.push(record);
  console.log(`CHECK origin page ${page.path} status=${response.status}`);
}

for (const sourcePath of retiredRoutes) {
  const response = await originFetch(`${baseUrl}${sourcePath}`, 'manual');
  const location = headerValue(response.headers, 'location');
  const redirectBy = headerValue(response.headers, 'x-redirect-by');
  const robots = headerValue(response.headers, 'x-robots-tag');
  const retiredMarker = headerValue(response.headers, 'x-nuvanx-retired-route');
  const record = {
    path: sourcePath,
    status: response.status,
    location,
    redirect_by: redirectBy,
    robots,
    retired_marker: retiredMarker,
  };
  if (response.status !== 410) fail(sourcePath, `returned HTTP ${response.status} instead of 410`);
  if (location) fail(sourcePath, `emitted forbidden Location header: ${location}`);
  if (redirectBy) fail(sourcePath, `emitted forbidden X-Redirect-By header: ${redirectBy}`);
  if (!robots.toLowerCase().includes('noindex') || !robots.toLowerCase().includes('nofollow')) {
    fail(sourcePath, `X-Robots-Tag mismatch: ${robots || 'absent'}`);
  }
  if (retiredMarker !== '1') fail(sourcePath, `retired route marker is ${retiredMarker || 'absent'} instead of 1`);
  report.retired_routes.push(record);
  console.log(`CHECK origin retired ${sourcePath} status=${response.status}`);
}

fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`RENDERED_ACCEPTANCE_OK transport=ssh pages=${pages.length} retired_routes=${retiredRoutes.length} sha=${expectedSha}`);
