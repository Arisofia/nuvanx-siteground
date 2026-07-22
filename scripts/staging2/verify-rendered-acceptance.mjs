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
  {
    path: '/tratamientos/',
    title: 'Tratamientos Medicina Estética Láser Madrid | NUVANX',
    description: 'Tratamientos de medicina estética láser en Madrid: Endolift®, Láser CO₂, EXION® BTL, IPL y medicina facial con valoración clínica.',
    h1: 'Portafolio clínico.',
    markers: ['La anatomía dicta el plan; la tecnología lo ejecuta', 'Áreas Anatómicas y Protocolos', 'Nuestro Arsenal Tecnológico', 'Tu primera valoración clínica'],
    schemaTypes: ['WebPage', 'ItemList'],
  },
  {
    path: '/soluciones-medicas/',
    title: 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid',
    description: 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.',
    h1: 'Soluciones médicas para rostro, piel y contorno corporal.',
    markers: ['Una misma preocupación puede tener causas distintas.', 'Rostro y cuello', 'Contorno corporal', 'Cambios posgestacionales', 'Valoración de procedimientos previos'],
    schemaTypes: ['WebPage'],
  },
  {
    path: '/protocolos-signature/',
    title: 'Protocolos Signature | NUVANX Madrid',
    description: 'Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado.',
    h1: 'Protocolos Signature: Medicina estética de diagnóstico.',
    markers: ['Nuestro estándar: La firma NUVANX', 'Contorno Corporal y Posgestacional', 'Post-Maternity Contour™', 'Arquitectura Facial y Calidad de Piel', 'Tu primera valoración clínica'],
    schemaTypes: ['WebPage'],
  },
  {
    path: '/remodelacion-corporal-laser-madrid/',
    title: 'Remodelación corporal láser Madrid | NUVANX',
    description: 'Remodelación corporal láser en Madrid por unidades anatómicas para grasa localizada, laxitud y continuidad del contorno tras valoración médica.',
    h1: 'Remodelación corporal láser diseñada según tu anatomía.',
    markers: ['No tratamos zonas aisladas. Diseñamos continuidad.', 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'Cartografía Anatómica: Zonas de tratamiento', 'Couture Sculpt™: El protocolo y la tecnología', 'Cuándo no es el tratamiento adecuado'],
    schemaTypes: ['WebPage'],
  },
  {
    path: '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
    title: 'Tratamiento postparto abdomen Madrid | NUVANX',
    description: 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.',
    h1: 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
    markers: ['Por qué un tratamiento posparto genérico no es suficiente', 'El Protocolo NUVANX Post-Maternity Contour™', 'Las alteraciones del posparto: qué podemos tratar y cuándo derivamos', 'La valoración médica: el paso indispensable', 'Preguntas frecuentes'],
    schemaTypes: ['WebPage'],
  },
  {
    path: '/por-que-nuvanx/',
    title: 'Por qué NUVANX | Criterio médico en Madrid',
    description: 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.',
    h1: 'Por qué NUVANX. Sin retórica de marketing.',
    markers: ['Diagnóstico antes de tecnología', 'Responsabilidad médica y continuidad asistencial', 'Trazabilidad de productos', 'Privacidad durante la atención', 'Por qué importa'],
    schemaTypes: ['WebPage'],
  },
  {
    path: '/inversion-medicina-estetica/',
    title: 'Inversión en medicina estética | NUVANX Madrid',
    description: 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.',
    h1: 'El presupuesto forma parte de una decisión informada.',
    markers: ['Cómo leer estas tarifas', 'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí', 'Sobre los precios en medicina estética en Madrid'],
    schemaTypes: ['WebPage'],
  },
];

const redirects = [
  ['/liposculpt-air/', '/remodelacion-corporal-laser-madrid/'],
  ['/v-lift-awake/', '/protocolos-signature/'],
];

const forbiddenText = [
  'Protocolo en construcción clínica',
  'fase de despliegue web',
  'pending_medical_legal',
  'LipoSculpt-Air™',
  'V-Lift Awake™',
  'garantizar resultados',
  'asegurar que cada intervención',
  'control térmico absoluto',
  'sin huellas quirúrgicas evidentes',
  'presupuesto muy bajo',
  'no usamos descuentos estacionales',
  'este procedimiento no es habitual en el sector',
  'el estándar de oro',
  'renovación epidérmica severa',
  'absoluta discreción',
  'protocolo comercial estrella',
];

const findings = [];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], redirects: [] };
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
const linkHref = (html, rel) => {
  for (const tag of extractTags(html, 'link')) {
    if (attribute(tag, 'rel').toLowerCase().split(/\s+/).includes(rel.toLowerCase())) return attribute(tag, 'href');
  }
  return '';
};
function schemaTypesFrom(value, target = new Set()) {
  if (Array.isArray(value)) {
    for (const item of value) schemaTypesFrom(item, target);
    return target;
  }
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
const safeFileName = (urlPath) => urlPath.replace(/^\/+|\/+$/g, '').replace(/[^a-z0-9._-]+/gi, '-') || 'home';
const fetchWithTimeout = (url, options = {}) => fetch(url, {
  ...options,
  headers: { 'user-agent': 'NUVANX-Staging2-Rendered-Acceptance/2.0', ...(options.headers || {}) },
  signal: AbortSignal.timeout(45_000),
});

for (const page of pages) {
  const scope = `page ${page.path}`;
  const url = `${baseUrl}${page.path}`;
  const result = { path: page.path, url, expected_title: page.title, expected_description: page.description, expected_markers: page.markers };
  try {
    const response = await fetchWithTimeout(url, { redirect: 'follow' });
    const html = await response.text();
    fs.writeFileSync(path.join(evidenceDir, `${safeFileName(page.path)}.html`), html);
    Object.assign(result, {
      status: response.status,
      final_url: response.url,
      title: extractTag(html, 'title'),
      description: metaContent(html, 'description'),
      og_title: metaContent(html, 'og:title'),
      og_description: metaContent(html, 'og:description'),
      robots: metaContent(html, 'robots'),
      deploy_sha: metaContent(html, 'nvx-deploy-sha'),
      canonical: linkHref(html, 'canonical'),
      og_url: metaContent(html, 'og:url'),
      h1_count: (html.match(/<h1\b/gi) || []).length,
      h1: extractTag(html, 'h1'),
      h2_count: (html.match(/<h2\b/gi) || []).length,
      schema_types: collectSchemaTypes(html, scope),
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

    const allowedCanonicalTargets = new Set([
      `https://staging2.nuvanx.com${page.path}`,
      `https://nuvanx.com${page.path}`,
      `https://www.nuvanx.com${page.path}`,
    ]);
    const seoTargets = [['canonical', result.canonical], ['og:url', result.og_url]].filter(([, target]) => target);
    if (seoTargets.length === 0) fail(scope, 'canonical or og:url is absent');
    for (const [label, target] of seoTargets) if (!allowedCanonicalTargets.has(target)) fail(scope, `${label} is ${target} and does not match the expected page path on an allowed NUVANX host`);

    for (const schemaType of page.schemaTypes) if (!result.schema_types.includes(schemaType)) fail(scope, `missing schema type ${schemaType}`);
    if (!result.schema_types.includes('Organization') && !result.schema_types.includes('MedicalOrganization')) fail(scope, 'missing Organization or MedicalOrganization schema');
    for (const forbidden of forbiddenText) if (html.toLowerCase().includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
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
    if (response.status === 301 && result.location === expectedLocation) {
      const targetResponse = await fetchWithTimeout(expectedLocation, { redirect: 'manual' });
      result.target_status = targetResponse.status;
      if (targetResponse.status !== 200) fail(scope, `target returned HTTP ${targetResponse.status} instead of 200`);
    }
  } catch (error) {
    result.error = error.message;
    fail(scope, `request failed: ${error.message}`);
  }
  report.redirects.push(result);
}

report.ok = findings.length === 0;
report.findings = findings;
fs.writeFileSync(path.join(evidenceDir, 'report.json'), `${JSON.stringify(report, null, 2)}\n`);
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`RENDERED_ACCEPTANCE_OK base_url=${baseUrl} sha=${expectedSha}`);
