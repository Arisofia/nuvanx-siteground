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
    path: '/soluciones-medicas/',
    title: 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid',
    description: 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.',
    h1: 'Soluciones médicas para rostro, piel y contorno corporal.',
    markers: ['Rostro y cuello', 'Contorno corporal', 'Cambios posgestacionales', 'Valoración de procedimientos previos'],
  },
  {
    path: '/protocolos-signature/',
    title: 'Protocolos Signature | NUVANX Madrid',
    description: 'Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado.',
    h1: 'Protocolos Signature: Medicina estética de diagnóstico.',
    markers: ['Nuestro estándar: La firma NUVANX', 'NUVANX Contour Architecture™', 'Post-Maternity Contour™', 'NUVANX Eye Frame™'],
  },
  {
    path: '/remodelacion-corporal-laser-madrid/',
    title: 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
    description: 'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas para grasa localizada, laxitud y continuidad tras valoración médica.',
    h1: 'Remodelación corporal láser diseñada según tu anatomía.',
    markers: ['NUVANX Contour Architecture™: El protocolo y la tecnología', 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'Cuándo no es el tratamiento adecuado'],
  },
  {
    path: '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
    title: 'Tratamiento postparto abdomen Madrid | NUVANX',
    description: 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.',
    h1: 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
    markers: ['El Protocolo NUVANX Post-Maternity Contour™', 'Las alteraciones del posparto', 'Preguntas frecuentes'],
  },
  {
    path: '/por-que-nuvanx/',
    title: 'Por qué NUVANX | Criterio médico en Madrid',
    description: 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.',
    h1: 'Por qué NUVANX. Sin retórica de marketing.',
    markers: ['Responsabilidad médica y continuidad asistencial', 'Trazabilidad de productos', 'Por qué importa'],
  },
  {
    path: '/inversion-medicina-estetica/',
    title: 'Inversión en medicina estética | NUVANX Madrid',
    description: 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.',
    h1: 'El presupuesto forma parte de una decisión informada.',
    markers: ['Cómo leer estas tarifas', 'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí'],
  },
  {
    path: '/papada-definicion-mandibular-madrid/',
    title: 'Papada y mandíbula en Madrid | Profile Definition NUVANX',
    description: 'Perfil mandibular y submentón evaluados por componente: grasa, laxitud, soporte óseo y proporción facial. No todo es papada ni todo se resuelve con Endolift.',
    h1: 'Papada y mandíbula: a veces es grasa, a veces es piel, y a veces falta hueso.',
    markers: ['Cómo evaluamos el tercio inferior', 'Cuándo no basta con tratar la grasa', 'Solicitar valoración médica'],
  },
  {
    path: '/calidad-piel-firmeza-luminosidad-madrid/',
    title: 'Calidad y firmeza de piel Madrid | Skin Architecture',
    description: 'Plan médico para firmeza, densidad y luminosidad que combina diagnóstico, tecnología y seguimiento sin modificar deliberadamente los volúmenes.',
    h1: 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.',
    markers: ['Qué evaluamos antes de indicar tecnología', 'El plan puede combinar varias herramientas', 'Solicitar valoración médica'],
  },
  {
    path: '/cicatrices-acne-poros-textura-madrid/',
    title: 'Cicatrices de acné y poros Madrid | Surface Renewal',
    description: 'Diagnóstico y tratamiento de cicatrices, poros y textura según morfología, profundidad, fototipo y mecanismo dominante.',
    h1: 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.',
    markers: ['No todas las cicatrices responden igual', 'Cómo construimos el plan', 'Solicitar valoración médica'],
  },
  {
    path: '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
    title: 'Manchas y rojeces IPL Madrid | Tone Correction NUVANX',
    description: 'Manchas marrones, rojeces y fotodaño evaluados por mecanismo: pigmento, vascularización, inflamación o combinación. El diagnóstico determina la tecnología.',
    h1: 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.',
    markers: ['Qué evaluamos antes de tratar', 'Cómo construimos el plan', 'Solicitar valoración médica'],
  },
  {
    path: '/tratamiento-ojeras-bolsas-mirada-madrid/',
    title: 'Tratamiento ojeras, bolsas y mirada Madrid | NUVANX',
    description: 'Diagnóstico médico de la región periocular por componente: pigmentación, vascularización, surco, bolsas, edema y laxitud. No todo es ácido hialurónico ni todo se resuelve con láser.',
    h1: 'No todas las ojeras son iguales. Por eso no todas se tratan igual.',
    markers: ['Qué evaluamos en la región periocular', 'Cuándo la medicina estética no es suficiente', 'Cómo construimos el plan', 'Solicitar valoración médica'],
  },
  {
    path: '/grasa-localizada-abdomen-flancos-madrid/',
    title: 'Grasa localizada abdomen y flancos Madrid | NUVANX',
    description: 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.',
    h1: 'Grasa localizada en abdomen y flancos en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
  {
    path: '/flacidez-grasa-localizada-brazos-madrid/',
    title: 'Flacidez y grasa localizada brazos Madrid | NUVANX',
    description: 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.',
    h1: 'Flacidez y grasa localizada en brazos en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
  {
    path: '/grasa-espalda-zona-sujetador-madrid/',
    title: 'Grasa espalda y zona del sujetador Madrid | NUVANX',
    description: 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.',
    h1: 'Grasa de espalda y zona del sujetador en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
  {
    path: '/flacidez-muslos-internos-subgluteo-madrid/',
    title: 'Flacidez muslos internos y subglúteo Madrid | NUVANX',
    description: 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.',
    h1: 'Flacidez en muslos internos y región subglútea en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
  {
    path: '/tratamiento-rodillas-grasa-flacidez-madrid/',
    title: 'Grasa localizada y flacidez rodillas Madrid | NUVANX',
    description: 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.',
    h1: 'Grasa localizada y flacidez en rodillas en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
  {
    path: '/contorno-corporal-masculino-madrid/',
    title: 'Contorno corporal masculino Madrid | NUVANX',
    description: 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.',
    h1: 'Contorno corporal masculino en Madrid',
    markers: ['Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos'],
  },
];

const redirects = [
  ['/tratamientos/', '/soluciones-medicas/'],
  ['/liposculpt-air/', '/remodelacion-corporal-laser-madrid/'],
  ['/v-lift-awake/', '/protocolos-signature/'],
];

const forbiddenText = [
  'Protocolo en construcción clínica', 'fase de despliegue web', 'pending_medical_legal',
  'LipoSculpt-Air™', 'V-Lift Awake™', 'Couture Sculpt™', 'Contour Sculpt™',
  'garantizar resultados', 'resultados garantizados', 'control térmico absoluto',
  'sin bisturí ni puntos', 'todo en vigilia', 'mínima recuperación', 'recuperación inmediata',
  'sin cicatrices', 'sin inflamación', 'sin dolor', 'sin riesgos', 'resultado definitivo',
  'una sola sesión', 'generalmente 3–4 sesiones', 'reducción del dolor', 'eritema reducido', 'eritema mínimo',
];

const findings = [];
const report = { base_url: baseUrl, expected_sha: expectedSha, generated_at: new Date().toISOString(), pages: [], redirects: [], findings };
const fail = (scope, message) => findings.push(`${scope}: ${message}`);
const normalizeText = (value) => value.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/&amp;/gi, '&').replace(/&quot;/gi, '"').replace(/&#39;|&apos;/gi, "'").replace(/&lt;/gi, '<').replace(/&gt;/gi, '>').replace(/\s+/g, ' ').trim();
const extractTag = (html, tagName) => {
  const match = html.match(new RegExp(`<${tagName}\\b[^>]*>([\\s\\S]*?)<\\/${tagName}>`, 'i'));
  return match ? normalizeText(match[1]) : '';
};
const extractTagTexts = (html, tagName) => [...html.matchAll(new RegExp(`<${tagName}\\b[^>]*>([\\s\\S]*?)<\\/${tagName}>`, 'gi'))].map((match) => normalizeText(match[1]));
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
const schemaTypes = (html) => {
  const types = new Set();
  for (const match of html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try {
      const graph = JSON.parse(match[1]);
      const visit = (value) => {
        if (Array.isArray(value)) return value.forEach(visit);
        if (!value || typeof value !== 'object') return;
        const type = value['@type'];
        if (Array.isArray(type)) type.forEach((item) => types.add(String(item)));
        else if (type) types.add(String(type));
        Object.values(value).forEach(visit);
      };
      visit(graph);
    } catch {}
  }
  return [...types];
};
async function fetchWithTimeout(url, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 45000);
  try {
    return await fetch(url, { ...options, signal: controller.signal, headers: { 'user-agent': 'NUVANX-Staging2-Acceptance/3.0', ...(options.headers || {}) } });
  } finally {
    clearTimeout(timeout);
  }
}

for (const page of pages) {
  const scope = page.path;
  const result = { path: page.path };
  try {
    const response = await fetchWithTimeout(`${baseUrl}${page.path}`);
    const html = await response.text();
    fs.writeFileSync(path.join(evidenceDir, page.path.replace(/^\/+|\/+$/g, '').replaceAll('/', '__') + '.html'), html);
    result.status = response.status;
    result.title = extractTag(html, 'title');
    result.description = metaContent(html, 'description');
    result.og_title = metaContent(html, 'og:title');
    result.og_description = metaContent(html, 'og:description');
    result.robots = metaContent(html, 'robots');
    result.deploy_sha = metaContent(html, 'nvx-deploy-sha');
    result.h1 = extractTagTexts(html, 'h1');
    result.h2_count = extractTagTexts(html, 'h2').length;
    result.canonicals = linkHrefs(html, 'canonical');
    result.og_url = metaContent(html, 'og:url');
    result.schema_types = schemaTypes(html);

    if (response.status !== 200) fail(scope, `returned HTTP ${response.status} instead of 200`);
    if (result.deploy_sha !== expectedSha) fail(scope, `served SHA ${result.deploy_sha || 'absent'} instead of ${expectedSha}`);
    if (result.title !== page.title) fail(scope, `title mismatch: ${JSON.stringify(result.title)}`);
    if (result.description !== page.description) fail(scope, 'meta description mismatch');
    if (result.og_title !== page.title) fail(scope, 'og:title mismatch');
    if (result.og_description !== page.description) fail(scope, 'og:description mismatch');
    if (!result.robots.toLowerCase().includes('noindex') || !result.robots.toLowerCase().includes('nofollow')) fail(scope, `robots mismatch: ${result.robots || 'absent'}`);
    if (result.h1.length !== 1) fail(scope, `expected exactly one H1, found ${result.h1.length}`);
    else if (result.h1[0] !== page.h1) fail(scope, `H1 mismatch: ${JSON.stringify(result.h1[0])}`);
    if (result.h2_count < 3) fail(scope, `expected at least 3 H2s, found ${result.h2_count}`);
    const visible = normalizeText(html);
    for (const marker of page.markers) if (!visible.includes(marker)) fail(scope, `missing marker: ${marker}`);
    if (!/valoraci[oó]n/i.test(visible)) fail(scope, 'missing medical valuation CTA or copy');

    const allowed = new Set([`https://staging2.nuvanx.com${page.path}`, `https://nuvanx.com${page.path}`, `https://www.nuvanx.com${page.path}`]);
    if (result.canonicals.length !== 1) fail(scope, `expected one canonical, found ${result.canonicals.length}`);
    else if (!allowed.has(result.canonicals[0])) fail(scope, `canonical mismatch: ${result.canonicals[0]}`);
    if (!allowed.has(result.og_url)) fail(scope, `og:url mismatch: ${result.og_url || 'absent'}`);
    if (!result.schema_types.includes('WebPage')) fail(scope, 'missing WebPage schema');
    if (!result.schema_types.includes('Organization') && !result.schema_types.includes('MedicalOrganization')) fail(scope, 'missing Organization or MedicalOrganization schema');
    for (const forbidden of forbiddenText) if (visible.toLowerCase().includes(forbidden.toLowerCase())) fail(scope, `exposes forbidden text: ${forbidden}`);
  } catch (error) {
    fail(scope, error instanceof Error ? error.message : String(error));
  }
  report.pages.push(result);
}

for (const [sourcePath, targetPath] of redirects) {
  const result = { source: sourcePath, target: targetPath };
  try {
    const response = await fetchWithTimeout(`${baseUrl}${sourcePath}`, { redirect: 'manual' });
    result.status = response.status;
    result.location = response.headers.get('location') || '';
    const expectedLocation = `${baseUrl}${targetPath}`;
    if (response.status !== 301) fail(sourcePath, `returned HTTP ${response.status} instead of 301`);
    if (result.location !== expectedLocation) fail(sourcePath, `location is ${result.location || 'absent'} instead of ${expectedLocation}`);
    if (response.status === 301 && result.location === expectedLocation) {
      const targetResponse = await fetchWithTimeout(expectedLocation, { redirect: 'manual' });
      result.target_status = targetResponse.status;
      if (targetResponse.status !== 200) fail(sourcePath, `target returned HTTP ${targetResponse.status} instead of 200`);
    }
  } catch (error) {
    fail(sourcePath, error instanceof Error ? error.message : String(error));
  }
  report.redirects.push(result);
}

fs.writeFileSync(path.join(evidenceDir, 'report.json'), JSON.stringify(report, null, 2));
if (findings.length) {
  console.error(`RENDERED_ACCEPTANCE_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}
console.log(`RENDERED_ACCEPTANCE_OK pages=${pages.length} redirects=${redirects.length} sha=${expectedSha}`);
