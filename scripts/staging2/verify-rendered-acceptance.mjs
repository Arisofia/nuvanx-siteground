#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.EXPECTED_SHA || '';
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/rendered-acceptance';

if (baseUrl !== 'https://staging2.nuvanx.com') {
  console.error(`ERROR: refusing unexpected BASE_URL: ${baseUrl}`);
  process.exit(1);
}
if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('ERROR: EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}
fs.mkdirSync(evidenceDir, { recursive: true });

const pages = [
  ['/soluciones-medicas/', 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid', 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.', 'Soluciones médicas para rostro, piel y contorno corporal.', ['Rostro y cuello', 'Contorno corporal', 'Cambios posgestacionales']],
  ['/protocolos-signature/', 'Protocolos Signature | NUVANX Madrid', 'Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado.', 'Protocolos Signature NUVANX.', ['NUVANX Contour Architecture™', 'NUVANX Profile Definition™', 'Tu primera valoración clínica']],
  ['/remodelacion-corporal-laser-madrid/', 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture', 'Valoración médica del contorno corporal por unidades anatómicas para diferenciar grasa localizada, laxitud, calidad cutánea y límites del tratamiento.', 'Remodelación corporal láser diseñada según tu anatomía.', ['Niveles de planificación, no paquetes cerrados', 'Contour Continuity', 'Cuándo no es el tratamiento adecuado']],
  ['/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tratamiento postparto abdomen Madrid | NUVANX', 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, estrías, cicatriz y alteraciones de la pared abdominal.', 'Después del embarazo, “abdomen” puede significar problemas diferentes.', ['El valor del diagnóstico médico', 'Cuándo no es el tratamiento adecuado', 'Preguntas frecuentes']],
  ['/papada-definicion-mandibular-madrid/', 'Papada y definición mandibular Madrid | NUVANX', 'Valoración médica de papada, cuello, mandíbula y mentón para diferenciar grasa, laxitud y soporte estructural antes de indicar tratamiento.', 'Papada, cuello y mandíbula forman un mismo perfil.', ['Objetivos clínicos que se valoran', 'Qué puede formar parte del plan', 'Cuándo no es el tratamiento adecuado']],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Calidad, firmeza y luminosidad de la piel Madrid | NUVANX', 'Plan médico para calidad, firmeza, densidad e hidratación de la piel según fototipo, diagnóstico y profundidad del problema.', 'Calidad de piel: firmeza, densidad e hidratación no son lo mismo.', ['El valor del diagnóstico médico', 'Qué puede formar parte del plan', 'Tu primera valoración clínica']],
  ['/cicatrices-acne-poros-textura-madrid/', 'Cicatrices de acné, poros y textura Madrid | NUVANX', 'Valoración médica de cicatrices, poros y textura para seleccionar láser CO₂, radiofrecuencia fraccionada u otras modalidades según fototipo.', 'Cicatrices, poros y textura requieren diagnóstico por profundidad.', ['Objetivos clínicos que se valoran', 'Cuándo no es el tratamiento adecuado', 'Preguntas frecuentes']],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Manchas, rojeces y fotodaño Madrid | NUVANX', 'Diagnóstico de manchas, rojeces y fotodaño con selección de IPL, cuidados o derivación según lesión, fototipo y antecedentes.', 'Manchas y rojeces no se tratan sin identificar primero la lesión.', ['Qué puede formar parte del plan', 'Cuándo no es el tratamiento adecuado', 'Preguntas frecuentes']],
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Grasa localizada abdomen y flancos Madrid | NUVANX', 'Valoración médica de abdomen y flancos para diferenciar grasa subcutánea, laxitud, pared abdominal y límites del tratamiento focal.', 'Abdomen y flancos: grasa, piel y pared abdominal son diagnósticos distintos.', ['Qué se valora', 'Límites y derivación', 'La tecnología se decide después']],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Flacidez y grasa localizada brazos Madrid | NUVANX', 'Valoración médica de brazos y axila para diferenciar grasa localizada, laxitud y calidad cutánea antes de indicar tratamiento.', 'El brazo se valora junto con la axila y el torso.', ['Objetivos clínicos posibles', 'Límites y derivación', 'Proceso de valoración']],
  ['/grasa-espalda-zona-sujetador-madrid/', 'Grasa espalda y zona del sujetador Madrid | NUVANX', 'Valoración médica de espalda, zona del sujetador y flancos para diferenciar grasa, laxitud y efecto de la prenda.', 'Espalda, sujetador y flancos forman una misma arquitectura.', ['Qué se valora', 'Objetivos clínicos posibles', 'La tecnología se decide después']],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'Flacidez muslos internos y región subglútea Madrid | NUVANX', 'Valoración de muslos internos, externos y región subglútea para diferenciar grasa localizada, laxitud y celulitis estructural.', 'No tratamos “piernas”: estudiamos continuidad, laxitud y proporción.', ['Límites y derivación', 'Proceso de valoración', 'La tecnología se decide después']],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Grasa y flacidez en rodillas Madrid | NUVANX', 'Valoración médica de la región de las rodillas para diferenciar grasa localizada, laxitud, edema y continuidad con el muslo.', 'La región de la rodilla exige precisión y expectativas proporcionadas.', ['Qué se valora', 'Límites y derivación', 'Proceso de valoración']],
  ['/contorno-corporal-masculino-madrid/', 'Contorno corporal masculino Madrid | NUVANX', 'Valoración del contorno masculino en abdomen, cintura, pecho, espalda o mandíbula según anatomía y objetivos individuales.', 'El contorno masculino se planifica según anatomía, no según una plantilla.', ['Objetivos clínicos posibles', 'Límites y derivación', 'La tecnología se decide después']],
  ['/por-que-nuvanx/', 'Por qué NUVANX | Criterio médico en Madrid', 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.', 'Por qué NUVANX. Sin retórica de marketing.', ['Diagnóstico antes de tecnología', 'Responsabilidad médica y continuidad asistencial', 'Por qué importa']],
  ['/inversion-medicina-estetica/', 'Inversión en medicina estética | NUVANX Madrid', 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.', 'El presupuesto forma parte de una decisión informada.', ['Cómo leer estas tarifas', 'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí']],
].map(([pagePath, title, description, h1, markers]) => ({ path: pagePath, title, description, h1, markers, schemaTypes: ['WebPage'] }));

const redirects = [
  ['/tratamientos/', '/soluciones-medicas/'],
  ['/liposculpt-air/', '/remodelacion-corporal-laser-madrid/'],
  ['/v-lift-awake/', '/protocolos-signature/'],
  ['/eye-frame-rejuvenecimiento-mirada-madrid/', '/soluciones-medicas/'],
];

const forbiddenText = [
  'pending_medical_legal', 'LipoSculpt-Air™', 'V-Lift Awake™', 'Couture Sculpt™', 'Contour Sculpt™', 'NUVANX Eye Frame™',
  'garantizar resultados', 'control térmico absoluto', 'sin huellas quirúrgicas evidentes', 'absoluta discreción',
  'el estándar de oro', 'protocolo comercial estrella', 'sin bisturí ni puntos', 'todo en vigilia', 'mínima recuperación',
  'sin cicatrices', 'elimina grasa en cualquier zona', 'resultado definitivo', 'una sola sesión', 'de rostro a tobillos',
  'Tiny Tuck', 'AirTite', 'Mommy Makeover', 'destruyendo los adipocitos', 'forzando a la piel', 'obligamos a tus células',
  'obligar a los fibroblastos', 'ayudar en el cierre de la diástasis', 'garantía de que volverá', 'sin hospitalización',
];

const findings = [];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], redirects: [], navigation: {}, internal_links: {} };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);
const normalizeText = (value) => value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/&amp;/gi, '&').replace(/&quot;/gi, '"').replace(/&#39;|&apos;/gi, "'").replace(/&lt;/gi, '<').replace(/&gt;/gi, '>').replace(/\s+/g, ' ').trim();
const extractTag = (html, tagName) => {
  const match = html.match(new RegExp(`<${tagName}\\b[^>]*>([\\s\\S]*?)<\\/${tagName}>`, 'i'));
  return match ? normalizeText(match[1]) : '';
};
const extractTags = (html, tagName) => [...html.matchAll(new RegExp(`<${tagName}\\b[^>]*>`, 'gi'))].map((match) => match[0]);
const attribute = (tag, name) => {
  const match = tag.match(new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`, 'i'));
  return match ? match[2] : '';
};
const metaContent = (html, name) => {
  for (const tag of extractTags(html, 'meta')) {
    if (attribute(tag, 'name').toLowerCase() === name.toLowerCase()) return attribute(tag, 'content');
    if (attribute(tag, 'property').toLowerCase() === name.toLowerCase()) return attribute(tag, 'content');
  }
  return '';
};
const linkHrefs = (html, rel) => extractTags(html, 'link').filter((tag) => attribute(tag, 'rel').toLowerCase().split(/\s+/).includes(rel.toLowerCase())).map((tag) => attribute(tag, 'href'));
const safeFileName = (urlPath) => urlPath.replace(/^\/+|\/+$/g, '').replace(/[^a-z0-9._-]+/gi, '-') || 'home';
const fetchWithTimeout = (url, options = {}) => fetch(url, {
  ...options,
  headers: { 'user-agent': 'NUVANX-Staging2-Rendered-Acceptance/3.0', ...(options.headers || {}) },
  signal: AbortSignal.timeout(45_000),
});
function schemaTypesFrom(value, target = new Set()) {
  if (Array.isArray(value)) { for (const item of value) schemaTypesFrom(item, target); return target; }
  if (!value || typeof value !== 'object') return target;
  const type = value['@type'];
  for (const item of Array.isArray(type) ? type : type ? [type] : []) target.add(String(item));
  for (const nested of Object.values(value)) schemaTypesFrom(nested, target);
  return target;
}
function collectSchemaTypes(html, scope) {
  const types = new Set();
  const scripts = [...html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)];
  if (!scripts.length) fail(scope, 'missing JSON-LD schema');
  for (const [, jsonText] of scripts) {
    try { schemaTypesFrom(JSON.parse(jsonText.trim()), types); }
    catch (error) { fail(scope, `invalid JSON-LD: ${error.message}`); }
  }
  return [...types].sort();
}
const linkCache = new Map();
async function verifyInternalLinks(html, sourcePath) {
  const urls = new Set();
  for (const tag of extractTags(html, 'a')) {
    const href = attribute(tag, 'href');
    if (!href || href.startsWith('#') || /^(mailto:|tel:|javascript:)/i.test(href)) continue;
    let url;
    try { url = new URL(href, baseUrl); } catch { continue; }
    if (url.origin !== baseUrl || /\.(css|js|png|jpg|jpeg|webp|svg|pdf)$/i.test(url.pathname)) continue;
    urls.add(`${url.origin}${url.pathname}`);
  }
  const statuses = {};
  for (const url of urls) {
    if (!linkCache.has(url)) {
      const response = await fetchWithTimeout(url, { redirect: 'follow' });
      linkCache.set(url, { status: response.status, finalUrl: response.url });
    }
    const result = linkCache.get(url);
    statuses[url] = result;
    if (result.status >= 400) fail(`page ${sourcePath}`, `internal link ${url} returned HTTP ${result.status}`);
  }
  report.internal_links[sourcePath] = statuses;
}

for (const page of pages) {
  const scope = `page ${page.path}`;
  const url = `${baseUrl}${page.path}`;
  const result = { path: page.path, url, expected_title: page.title, expected_description: page.description, expected_markers: page.markers };
  try {
    const response = await fetchWithTimeout(url, { redirect: 'follow' });
    const html = await response.text();
    fs.writeFileSync(path.join(evidenceDir, `${safeFileName(page.path)}.html`), html);
    const canonicals = linkHrefs(html, 'canonical');
    Object.assign(result, {
      status: response.status, final_url: response.url, title: extractTag(html, 'title'), description: metaContent(html, 'description'),
      og_title: metaContent(html, 'og:title'), og_description: metaContent(html, 'og:description'), robots: metaContent(html, 'robots'),
      deploy_sha: metaContent(html, 'nvx-deploy-sha'), canonical: canonicals[0] || '', canonical_count: canonicals.length,
      og_url: metaContent(html, 'og:url'), h1_count: (html.match(/<h1\b/gi) || []).length, h1: extractTag(html, 'h1'),
      h2_count: (html.match(/<h2\b/gi) || []).length, schema_types: collectSchemaTypes(html, scope),
    });
    if (response.status !== 200) fail(scope, `returned HTTP ${response.status}`);
    if (response.url !== url) fail(scope, `resolved to ${response.url} instead of ${url}`);
    if (result.deploy_sha !== expectedSha) fail(scope, `deploy SHA ${result.deploy_sha || 'absent'} does not equal ${expectedSha}`);
    if (result.title !== page.title) fail(scope, `title is "${result.title || 'absent'}" instead of "${page.title}"`);
    if (result.description !== page.description) fail(scope, `meta description is "${result.description || 'absent'}" instead of "${page.description}"`);
    if (result.og_title !== page.title) fail(scope, `og:title is "${result.og_title || 'absent'}" instead of "${page.title}"`);
    if (result.og_description !== page.description) fail(scope, `og:description is "${result.og_description || 'absent'}" instead of "${page.description}"`);
    if (!/\bnoindex\b/i.test(result.robots) || !/\bnofollow\b/i.test(result.robots)) fail(scope, `staging robots must contain noindex,nofollow; found ${result.robots || 'absent'}`);
    if (result.h1_count !== 1) fail(scope, `expected exactly one H1, found ${result.h1_count}`);
    if (result.h1 !== page.h1) fail(scope, `H1 is "${result.h1}" instead of "${page.h1}"`);
    if (result.h2_count < 3) fail(scope, `expected at least three H2 sections, found ${result.h2_count}`);
    for (const marker of page.markers) if (!html.includes(marker)) fail(scope, `missing content marker: ${marker}`);
    if (!html.includes('/madrid/valoracion/')) fail(scope, 'missing valuation CTA');
    if (result.canonical_count > 1) fail(scope, `expected at most one canonical, found ${result.canonical_count}`);
    const allowed = new Set([`https://staging2.nuvanx.com${page.path}`, `https://nuvanx.com${page.path}`, `https://www.nuvanx.com${page.path}`]);
    for (const [label, target] of [['canonical', result.canonical], ['og:url', result.og_url]].filter(([, target]) => target)) {
      if (!allowed.has(target)) fail(scope, `${label} is ${target} and does not match the expected path`);
    }
    for (const schemaType of page.schemaTypes) if (!result.schema_types.includes(schemaType)) fail(scope, `missing schema type ${schemaType}`);
    if (!result.schema_types.includes('Organization') && !result.schema_types.includes('MedicalOrganization')) fail(scope, 'missing Organization or MedicalOrganization schema');
    for (const forbidden of forbiddenText) if (html.toLowerCase().includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
    await verifyInternalLinks(html, page.path);
  } catch (error) {
    result.error = error.message;
    fail(scope, `request failed: ${error.message}`);
  }
  report.pages.push(result);
}

for (const [sourcePath, targetPath] of redirects) {
  const scope = `redirect ${sourcePath}`;
  const expectedLocation = `${baseUrl}${targetPath}`;
  const result = { source: sourcePath, target: targetPath, expected_location: expectedLocation };
  try {
    const response = await fetchWithTimeout(`${baseUrl}${sourcePath}`, { redirect: 'manual' });
    result.status = response.status;
    result.location = response.headers.get('location') || '';
    if (response.status !== 301) fail(scope, `returned HTTP ${response.status} instead of 301`);
    if (result.location !== expectedLocation) fail(scope, `location is ${result.location || 'absent'} instead of ${expectedLocation}`);
    const targetResponse = await fetchWithTimeout(expectedLocation, { redirect: 'manual' });
    result.target_status = targetResponse.status;
    if (targetResponse.status !== 200) fail(scope, `target returned HTTP ${targetResponse.status} instead of 200`);
  } catch (error) {
    result.error = error.message;
    fail(scope, `request failed: ${error.message}`);
  }
  report.redirects.push(result);
}

try {
  const response = await fetchWithTimeout(`${baseUrl}/`, { redirect: 'follow' });
  const html = await response.text();
  const navMatch = html.match(/<nav\b[^>]*>[\s\S]*?<\/nav>/i);
  const nav = navMatch ? normalizeText(navMatch[0]) : '';
  const required = ['Soluciones', 'Protocolos Signature', 'Tecnología', 'Casos clínicos', 'Equipo médico', 'Clínicas', 'Journal', 'Contacto'];
  report.navigation = { status: response.status, required, text: nav };
  if (!nav) fail('navigation', 'primary navigation was not found');
  for (const label of required) if (!nav.includes(label)) fail('navigation', `missing menu label: ${label}`);
  if (/\bTRATAMIENTOS\b/i.test(nav)) fail('navigation', 'legacy TRATAMIENTOS item is still present');
  if (!html.includes('nvx-menu--mega') && !html.includes('nvx-nav__item--mega')) fail('navigation', 'mega-menu class is absent');
} catch (error) {
  fail('navigation', `request failed: ${error.message}`);
}

report.findings = findings;
report.ok = findings.length === 0;
fs.writeFileSync(path.join(evidenceDir, 'report.json'), `${JSON.stringify(report, null, 2)}\n`);
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('RENDERED_ACCEPTANCE_OK');
