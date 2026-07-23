#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

function getAcceptanceConfig() {
  const envUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').trim().replace(/\/+$/, '');
  const shaToken = (process.env.EXPECTED_SHA || '').trim();
  const dirPath = (process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/rendered-acceptance').trim();

  if (envUrl !== 'https://staging2.nuvanx.com') {
    throw new Error(`refusing unexpected BASE_URL: ${envUrl}`);
  }
  if (!/^[0-9a-f]{40}$/.test(shaToken)) {
    throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  }
  fs.mkdirSync(dirPath, { recursive: true });
  return { baseUrl: envUrl, expectedSha: shaToken, evidenceDir: dirPath };
}

let baseUrl, expectedSha, evidenceDir;
try {
  ({ baseUrl, expectedSha, evidenceDir } = getAcceptanceConfig());
} catch (err) {
  console.error(`ERROR: ${err.message}`);
  process.exit(1);
}

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
];

const treatmentPages = [
  ['/papada-definicion-mandibular-madrid/', 'Papada y definición mandibular Madrid | NUVANX', 'Valoración médica de papada, cuello y mandíbula en Madrid para diferenciar grasa, laxitud y soporte antes de indicar Endolift® u otra opción.', 'Tratamiento médico de papada y definición mandibular en Madrid.', commonMarkers],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Calidad y firmeza de la piel Madrid | NUVANX', 'Tratamiento médico para calidad, firmeza y luminosidad de la piel en Madrid con tecnología seleccionada tras diagnóstico, fototipo y valoración.', 'Tratamiento médico para firmeza, densidad y calidad cutánea.', commonMarkers],
  ['/cicatrices-acne-poros-textura-madrid/', 'Cicatrices de acné, poros y textura Madrid | NUVANX', 'Tratamiento de cicatrices de acné, poros y textura en Madrid con CO₂ o Fractional RF según morfología, fototipo y valoración médica.', 'Tratamiento médico de cicatrices, poros dilatados y textura cutánea.', commonMarkers],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Manchas, rojeces y fotodaño Madrid | NUVANX', 'Tratamiento de manchas, rojeces y fotodaño en Madrid con IPL seleccionada según diagnóstico, fototipo y valoración médica.', 'Tratamiento médico de manchas, rojeces y daño solar.', commonMarkers]
].map(([path, title, description, h1, markers]) => ({ path, title, description, h1, markers }));

const anatomicalPages = [
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Grasa localizada abdomen y flancos Madrid | NUVANX', 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.', 'Esa grasa del abdomen que no se va ni a dieta ni a gimnasio.'],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Flacidez y grasa localizada brazos Madrid | NUVANX', 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.', 'Para que la manga caiga bien — sin que la piel quede colgando después.'],
  ['/grasa-espalda-zona-sujetador-madrid/', 'Grasa espalda y zona del sujetador Madrid | NUVANX', 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.', 'El pliegue que marca la ropa, aunque tu peso esté bien.'],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'Flacidez muslos internos y subglúteo Madrid | NUVANX', 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.', 'La piel más delicada del cuerpo merece el abordaje más cuidadoso.'],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Grasa localizada y flacidez rodillas Madrid | NUVANX', 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.', 'Una zona pequeña que cambia toda la línea de la pierna.'],
  ['/contorno-corporal-masculino-madrid/', 'Contorno corporal masculino Madrid | NUVANX', 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.', 'Pensado para el cuerpo de un hombre, no adaptado del de una mujer.'],
].map(([path, title, description, h1]) => ({ path, title, description, h1, markers: commonMarkers }));

pages.push(...treatmentPages, ...anatomicalPages);

const redirects = [
  ['/tratamientos/', '/soluciones-medicas/'],
  ['/liposculpt-air/', '/remodelacion-corporal-laser-madrid/'],
  ['/v-lift-awake/', '/protocolos-signature/'],
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

const findings = [];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], redirects: [], findings };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);

const cleanHtmlText = (val) => val.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/&amp;/gi, '&').replace(/&quot;/gi, '"').replace(/&#39;|&apos;/gi, "'").replace(/&lt;/gi, '<').replace(/&gt;/gi, '>').replace(/\s+/g, ' ').trim();

function parseSingleTag(html, name) {
  const match = html.match(new RegExp(String.raw`<${name}\b[^>]*>(.*?)<\/${name}>`, 'is'));
  return match ? cleanHtmlText(match[1]) : '';
}

function parseMultipleTagTexts(html, name) {
  return [...html.matchAll(new RegExp(String.raw`<${name}\b[^>]*>(.*?)<\/${name}>`, 'gis'))].map((m) => cleanHtmlText(m[1]));
}

function queryHtmlTags(html, name) {
  return [...html.matchAll(new RegExp(String.raw`<${name}\b[^>]*>`, 'gi'))].map((m) => m[0]);
}

function getAttrVal(tagString, attrName) {
  const m = tagString.match(new RegExp(String.raw`\b${attrName}\s*=\s*(["'])(.*?)\1`, 'i'));
  return m ? m[2] : '';
}

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
      const visitNode = (val) => {
        if (Array.isArray(val)) return val.forEach(visitNode);
        if (!val || typeof val !== 'object') return;
        const t = val['@type'];
        if (Array.isArray(t)) t.forEach((item) => types.add(String(item)));
        else if (t) types.add(String(t));
        Object.values(val).forEach(visitNode);
      };
      visitNode(JSON.parse(match[1]));
    } catch (e) { /* ignore parse error */ }
  }
  return [...types];
}

async function fetchWithTimeout(url, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 45000);
  const userHeaders = options.headers || {};
  try {
    return await fetch(url, { ...options, signal: controller.signal, headers: { 'user-agent': 'NUVANX-Staging2-Acceptance/3.0', ...userHeaders } });
  } finally {
    clearTimeout(timeout);
  }
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
  const { title, description, ogTitle, ogDescription, robots, deploySha, h1List, h2Count } = parsed;

  if (deploySha !== expectedSha) fail(scope, `served SHA ${deploySha || 'absent'} instead of ${expectedSha}`);
  if (title !== page.title) fail(scope, `title mismatch: ${JSON.stringify(title)}`);
  if (description !== page.description) fail(scope, 'meta description mismatch');
  if (ogTitle !== page.title) fail(scope, 'og:title mismatch');
  if (ogDescription !== page.description) fail(scope, 'og:description mismatch');
  if (!robots.toLowerCase().includes('noindex') || !robots.toLowerCase().includes('nofollow')) fail(scope, `robots mismatch: ${robots || 'absent'}`);
  if (h1List.length !== 1 || h1List[0] !== page.h1) fail(scope, `H1 mismatch: ${JSON.stringify(h1List)}`);
  if (h2Count < 3) fail(scope, `expected at least 3 H2s, found ${h2Count}`);
}

function validatePageContent(page, parsed, scope) {
  const { canonicals, ogUrl, schemas, bodyText } = parsed;

  for (const marker of page.markers) if (!bodyText.includes(marker)) fail(scope, `missing marker: ${marker}`);
  if (!/valoraci[oó]n/i.test(bodyText)) fail(scope, 'missing medical valuation CTA or copy');

  const validUrls = new Set([`https://staging2.nuvanx.com${page.path}`, `https://nuvanx.com${page.path}`, `https://www.nuvanx.com${page.path}`]);
  if (canonicals.length !== 1 || !validUrls.has(canonicals[0])) fail(scope, `canonical mismatch: ${canonicals[0]}`);
  if (!validUrls.has(ogUrl)) fail(scope, `og:url mismatch: ${ogUrl || 'absent'}`);
  if (!schemas.includes('WebPage')) fail(scope, 'missing WebPage schema');
  if (!schemas.includes('Organization') && !schemas.includes('MedicalOrganization')) fail(scope, 'missing Organization or MedicalOrganization schema');

  const lowerText = bodyText.toLowerCase();
  for (const forbidden of forbiddenText) if (lowerText.includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
}

function validatePageModel(page, parsed, scope) {
  validatePageMetadata(page, parsed, scope);
  validatePageContent(page, parsed, scope);
}

async function verifySinglePage(page) {
  const scope = page.path;
  const pageResult = { path: page.path };
  try {
    const res = await fetchWithTimeout(`${baseUrl}${page.path}`);
    const htmlText = await res.text();
    const fileName = `${page.path.replace(/^\/+|\/+$/g, '').replaceAll('/', '__') || 'home'}.html`;
    fs.writeFileSync(path.join(evidenceDir, fileName), htmlText);

    pageResult.status = res.status;
    if (res.status !== 200) {
      fail(scope, `returned HTTP ${res.status} instead of 200`);
      return pageResult;
    }

    const parsed = parseHtmlPage(htmlText);
    Object.assign(pageResult, {
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

    validatePageModel(page, parsed, scope);
  } catch (err) {
    fail(scope, err instanceof Error ? err.message : String(err));
  }
  return pageResult;
}

const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

for (const page of pages) {
  await sleep(2000);
  report.pages.push(await verifySinglePage(page));
}

async function verifyRedirectRoute(sourcePath, targetPath) {
  const scope = sourcePath;
  const targetUrl = `${baseUrl}${targetPath}`;
  const record = { source: sourcePath, target: targetPath };
  try {
    const res = await fetchWithTimeout(`${baseUrl}${sourcePath}`, { redirect: 'manual' });
    record.status = res.status;
    record.location = res.headers.get('location') || '';
    if (res.status !== 301) fail(scope, `returned HTTP ${res.status} instead of 301`);
    if (record.location !== targetUrl) fail(scope, `location is ${record.location || 'absent'} instead of ${targetUrl}`);
    if (res.status === 301 && record.location === targetUrl) {
      const destRes = await fetchWithTimeout(targetUrl, { redirect: 'manual' });
      record.target_status = destRes.status;
      if (destRes.status !== 200) fail(scope, `target returned HTTP ${destRes.status} instead of 200`);
      if (destRes.status >= 300 && destRes.status < 400) fail(scope, 'target performs an additional redirect');
    }
  } catch (err) {
    fail(scope, err instanceof Error ? err.message : String(err));
  }
  return record;
}

for (const [src, dst] of redirects) {
  await sleep(2000);
  report.redirects.push(await verifyRedirectRoute(src, dst));
}

fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`RENDERED_ACCEPTANCE_OK pages=${pages.length} redirects=${redirects.length} sha=${expectedSha}`);
