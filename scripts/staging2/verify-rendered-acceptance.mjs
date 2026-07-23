#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';

function getAcceptanceConfig() {
  const envUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').trim().replace(/\/$/, '');
  const shaToken = (process.env.EXPECTED_SHA || '').trim();
  const dirPath = (process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/rendered-acceptance').trim();

  if (envUrl !== 'https://staging2.nuvanx.com') throw new Error(`refusing unexpected BASE_URL: ${envUrl}`);
  if (!/^[0-9a-f]{40}$/.test(shaToken)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
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
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Manchas, rojeces y fotodaño Madrid | NUVANX', 'Tratamiento de manchas, rojeces y fotodaño en Madrid con IPL seleccionada según diagnóstico, fototipo y valoración médica.', 'Tratamiento médico de manchas, rojeces y daño solar.', commonMarkers],
].map(([pagePath, title, description, h1, markers]) => ({ path: pagePath, title, description, h1, markers }));

const anatomicalPages = [
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Grasa localizada abdomen y flancos Madrid | NUVANX', 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.', 'Esa grasa del abdomen que no se va ni a dieta ni a gimnasio.'],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Flacidez y grasa localizada brazos Madrid | NUVANX', 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.', 'Para que la manga caiga bien — sin que la piel quede colgando después.'],
  ['/grasa-espalda-zona-sujetador-madrid/', 'Grasa espalda y zona del sujetador Madrid | NUVANX', 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.', 'El pliegue que marca la ropa, aunque tu peso esté bien.'],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'Flacidez muslos internos y subglúteo Madrid | NUVANX', 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.', 'La piel más delicada del cuerpo merece el abordaje más cuidadoso.'],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Grasa localizada y flacidez rodillas Madrid | NUVANX', 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.', 'Una zona pequeña que cambia toda la línea de la pierna.'],
  ['/contorno-corporal-masculino-madrid/', 'Contorno corporal masculino Madrid | NUVANX', 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.', 'Pensado para el cuerpo de un hombre, no adaptado del de una mujer.'],
].map(([pagePath, title, description, h1]) => ({ path: pagePath, title, description, h1, markers: commonMarkers }));

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

const browserHeaders = {
  'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
  accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
  'accept-language': 'es-ES,es;q=0.9,en;q=0.7',
  'cache-control': 'no-cache',
  pragma: 'no-cache',
  'sec-ch-ua': '"Chromium";v="150", "Not_A Brand";v="99"',
  'sec-ch-ua-mobile': '?0',
  'sec-ch-ua-platform': '"Windows"',
  'sec-fetch-dest': 'document',
  'sec-fetch-mode': 'navigate',
  'sec-fetch-site': 'none',
  'upgrade-insecure-requests': '1',
};
const findings = [];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], redirects: [], findings };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));
let sessionCookie = '';

function cleanHtmlText(value) {
  if (!value) return '';
  return value
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;|&apos;/gi, "'")
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/\s+/g, ' ')
    .trim();
}

const parseSingleTag = (html, name) => cleanHtmlText(html.match(new RegExp(String.raw`<${name}\b[^>]*>(.*?)<\/${name}>`, 'is'))?.[1] || '');
const parseMultipleTagTexts = (html, name) => [...html.matchAll(new RegExp(String.raw`<${name}\b[^>]*>(.*?)<\/${name}>`, 'gis'))].map((match) => cleanHtmlText(match[1]));
const queryHtmlTags = (html, name) => [...html.matchAll(new RegExp(String.raw`<${name}\b[^>]*>`, 'gi'))].map((match) => match[0]);
const getAttrVal = (tag, attr) => tag.match(new RegExp(String.raw`\b${attr}\s*=\s*(["'])(.*?)\1`, 'i'))?.[2] || '';

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
    } catch { /* ignore malformed third-party schema */ }
  }
  return [...types];
}

function rememberCookies(response) {
  const values = typeof response.headers.getSetCookie === 'function'
    ? response.headers.getSetCookie()
    : [response.headers.get('set-cookie')].filter(Boolean);
  const cookies = values.map((value) => value.split(';', 1)[0]).filter(Boolean);
  if (cookies.length) sessionCookie = cookies.join('; ');
}

async function fetchWithTimeout(url, options = {}) {
  let lastResponse = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 45000);
    try {
      const extraHeaders = options.headers || {};
      const headers = sessionCookie
        ? { ...browserHeaders, cookie: sessionCookie, ...extraHeaders }
        : { ...browserHeaders, ...extraHeaders };
      lastResponse = await fetch(url, { ...options, signal: controller.signal, headers });
      rememberCookies(lastResponse);
    } finally {
      clearTimeout(timeout);
    }

    if (![202, 429].includes(lastResponse.status) && lastResponse.status < 500) return lastResponse;
    if (attempt < 4) {
      await lastResponse.arrayBuffer();
      await sleep(attempt * 2000);
    }
  }
  return lastResponse;
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

function validatePageHeaderMetadata(page, parsed, scope) {
  if (parsed.deploySha !== expectedSha) fail(scope, `served SHA ${parsed.deploySha || 'absent'} instead of ${expectedSha}`);
  if (parsed.title !== page.title) fail(scope, `title mismatch: ${JSON.stringify(parsed.title)}`);
  if (parsed.description !== page.description) fail(scope, 'meta description mismatch');
  if (parsed.ogTitle !== page.title) fail(scope, 'og:title mismatch');
  if (parsed.ogDescription !== page.description) fail(scope, 'og:description mismatch');
  if (!parsed.robots.toLowerCase().includes('noindex') || !parsed.robots.toLowerCase().includes('nofollow')) {
    fail(scope, `robots mismatch: ${parsed.robots || 'absent'}`);
  }
  if (parsed.h1List.length !== 1 || parsed.h1List[0] !== page.h1) fail(scope, `H1 mismatch: ${JSON.stringify(parsed.h1List)}`);
  if (parsed.h2Count < 3) fail(scope, `expected at least 3 H2s, found ${parsed.h2Count}`);
}

function validatePageBodyContent(page, parsed, scope) {
  for (const marker of page.markers) if (!parsed.bodyText.includes(marker)) fail(scope, `missing marker: ${marker}`);
  if (!/valoraci[oó]n/i.test(parsed.bodyText)) fail(scope, 'missing medical valuation CTA or copy');

  const validUrls = new Set([
    `https://staging2.nuvanx.com${page.path}`,
    `https://nuvanx.com${page.path}`,
    `https://www.nuvanx.com${page.path}`,
  ]);
  if (parsed.canonicals.length !== 1 || !validUrls.has(parsed.canonicals[0])) fail(scope, `canonical mismatch: ${parsed.canonicals[0]}`);
  if (!validUrls.has(parsed.ogUrl)) fail(scope, `og:url mismatch: ${parsed.ogUrl || 'absent'}`);
  if (!parsed.schemas.includes('WebPage')) fail(scope, 'missing WebPage schema');
  if (!parsed.schemas.includes('Organization') && !parsed.schemas.includes('MedicalOrganization')) {
    fail(scope, 'missing Organization or MedicalOrganization schema');
  }

  const lowerText = parsed.bodyText.toLowerCase();
  for (const forbidden of forbiddenText) if (lowerText.includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
}

function validatePage(page, parsed, scope) {
  validatePageHeaderMetadata(page, parsed, scope);
  validatePageBodyContent(page, parsed, scope);
}

async function verifySinglePage(page) {
  const record = { path: page.path };
  try {
    const response = await fetchWithTimeout(`${baseUrl}${page.path}`);
    const html = await response.text();
    const fileName = `${page.path.split('/').filter(Boolean).join('__') || 'home'}.html`;
    fs.writeFileSync(path.join(evidenceDir, fileName), html);
    record.status = response.status;
    if (response.status !== 200) {
      fail(page.path, `returned HTTP ${response.status} instead of 200`);
      return record;
    }
    const parsed = parseHtmlPage(html);
    Object.assign(record, {
      title: parsed.title, description: parsed.description, og_title: parsed.ogTitle,
      og_description: parsed.ogDescription, robots: parsed.robots, deploy_sha: parsed.deploySha,
      h1: parsed.h1List, h2_count: parsed.h2Count, canonicals: parsed.canonicals,
      og_url: parsed.ogUrl, schema_types: parsed.schemas,
    });
    validatePage(page, parsed, page.path);
  } catch (err) {
    fail(page.path, err instanceof Error ? err.message : String(err));
  }
  return record;
}

async function verifyRedirectRoute(sourcePath, targetPath) {
  const record = { source: sourcePath, target: targetPath };
  const targetUrl = `${baseUrl}${targetPath}`;
  try {
    const response = await fetchWithTimeout(`${baseUrl}${sourcePath}`, { redirect: 'manual' });
    record.status = response.status;
    record.location = response.headers.get('location') || '';
    if (response.status !== 301) fail(sourcePath, `returned HTTP ${response.status} instead of 301`);
    if (record.location !== targetUrl) fail(sourcePath, `location is ${record.location || 'absent'} instead of ${targetUrl}`);
    if (response.status === 301 && record.location === targetUrl) {
      const destinationResponse = await fetchWithTimeout(targetUrl, { redirect: 'manual' });
      record.target_status = destinationResponse.status;
      if (destinationResponse.status !== 200) fail(sourcePath, `target returned HTTP ${destinationResponse.status} instead of 200`);
      if (destinationResponse.status >= 300 && destinationResponse.status < 400) fail(sourcePath, 'target performs an additional redirect');
    }
  } catch (err) {
    fail(sourcePath, err instanceof Error ? err.message : String(err));
  }
  return record;
}

await fetchWithTimeout(`${baseUrl}/`);
for (const page of pages) {
  await sleep(750);
  report.pages.push(await verifySinglePage(page));
}
for (const [source, destination] of redirects) {
  await sleep(750);
  report.redirects.push(await verifyRedirectRoute(source, destination));
}

fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`RENDERED_ACCEPTANCE_OK pages=${pages.length} redirects=${redirects.length} sha=${expectedSha}`);
