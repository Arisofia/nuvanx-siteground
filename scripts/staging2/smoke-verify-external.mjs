#!/usr/bin/env node

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = process.env.DEPLOY_SHA || process.env.EXPECTED_SHA || '';
const userAgent = 'NUVANX-Staging2-Acceptance/3.0';
const findings = [];
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

if (!['https://staging2.nuvanx.com', 'https://nuvanx.com'].includes(baseUrl)) {
  console.error(`ERROR: refusing unexpected BASE_URL: ${baseUrl}`);
  process.exit(1);
}
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('ERROR: DEPLOY_SHA or EXPECTED_SHA must be a full lowercase 40-character SHA.');
  process.exit(1);
}

const pages = [
  ['/soluciones-medicas/', ['Soluciones médicas para rostro, piel y contorno corporal.', 'Rostro y cuello', 'Contorno corporal', 'Cambios posgestacionales', 'Valoración de procedimientos previos']],
  ['/protocolos-signature/', ['Protocolos Signature: Medicina estética de diagnóstico.', 'Nuestro estándar: La firma NUVANX', 'NUVANX Contour Architecture', 'Post-Maternity Contour', 'Tu primera valoración clínica']],
  ['/remodelacion-corporal-laser-madrid/', ['NUVANX Contour Architecture™: El protocolo y la tecnología', 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'Cuándo no es el tratamiento adecuado']],
  ['/tratamiento-postparto-abdomen-contorno-corporal-madrid/', ['Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid', 'El Protocolo NUVANX Post-Maternity Contour', 'Las alteraciones del posparto', 'Preguntas frecuentes']],
  ['/por-que-nuvanx/', ['Por qué NUVANX. Sin retórica de marketing.', 'Responsabilidad médica y continuidad asistencial', 'Trazabilidad de productos', 'Por qué importa']],
  ['/inversion-medicina-estetica/', ['El presupuesto forma parte de una decisión informada.', 'Cómo leer estas tarifas', 'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí']],
  ['/papada-definicion-mandibular-madrid/', ['Tratamiento médico de papada y definición mandibular en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/calidad-piel-firmeza-luminosidad-madrid/', ['Tratamiento médico para firmeza, densidad y calidad cutánea', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/cicatrices-acne-poros-textura-madrid/', ['Tratamiento médico de cicatrices, poros dilatados y textura cutánea', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', ['Tratamiento médico de manchas, rojeces y daño solar', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/grasa-localizada-abdomen-flancos-madrid/', ['Grasa localizada en abdomen y flancos en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/flacidez-grasa-localizada-brazos-madrid/', ['Flacidez y grasa localizada en brazos en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/grasa-espalda-zona-sujetador-madrid/', ['Grasa de espalda y zona del sujetador en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/flacidez-muslos-internos-subgluteo-madrid/', ['Flacidez en muslos internos y región subglútea en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', ['Grasa localizada y flacidez en rodillas en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
  ['/contorno-corporal-masculino-madrid/', ['Contorno corporal masculino en Madrid', 'Qué se valora', 'Cómo se decide el plan', 'Límites y cuándo derivamos']],
];

const redirects = [
  ['/tratamientos/', '/soluciones-medicas/'],
  ['/liposculpt-air/', '/remodelacion-corporal-laser-madrid/'],
  ['/v-lift-awake/', '/protocolos-signature/'],
];

const forbiddenMarkers = [
  'Protocolo en construcción clínica',
  'fase de despliegue web',
  'pending_medical_legal',
  'LipoSculpt-Air',
  'V-Lift Awake',
  'Couture Sculpt',
  'Contour Sculpt',
  'Eye Frame',
  'Sin bisturí ni puntos',
  'Todo en vigilia',
  'Mínima recuperación',
  'Recuperación inmediata',
  'Sin cicatrices',
  'Sin inflamación',
  'Sin dolor',
  'Sin riesgos',
  'Elimina grasa en cualquier zona',
  'Resultado definitivo',
  'Resultados garantizados',
  'Una sola sesión',
  'Generalmente 3–4 sesiones',
  'Reducción del dolor',
  'Eritema reducido',
  'Eritema mínimo',
  'Control térmico absoluto',
];

async function requestWithRetry(path, redirect = 'manual') {
  let lastResponse = null;
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 45000);
    try {
      lastResponse = await fetch(`${baseUrl}${path}`, {
        redirect,
        signal: controller.signal,
        headers: {
          'user-agent': userAgent,
          accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          'accept-language': 'es-ES,es;q=0.9,en;q=0.7',
          'cache-control': 'no-cache',
        },
      });
    } finally {
      clearTimeout(timeout);
    }

    if (![202, 429].includes(lastResponse.status) && lastResponse.status < 500) {
      return { response: lastResponse, attempt };
    }
    if (attempt < 4) await sleep(attempt * 2000);
  }
  return { response: lastResponse, attempt: 4 };
}

function readMeta(html, name) {
  const tags = [...html.matchAll(/<meta\b[^>]*>/gi)].map((match) => match[0]);
  for (const tag of tags) {
    const nameMatch = tag.match(/\bname\s*=\s*(["'])(.*?)\1/i);
    if (nameMatch?.[2].toLowerCase() !== name.toLowerCase()) continue;
    return tag.match(/\bcontent\s*=\s*(["'])(.*?)\1/i)?.[2] || '';
  }
  return '';
}

async function verifyRedirect(sourcePath, targetPath) {
  const { response, attempt } = await requestWithRetry(sourcePath);
  const location = response.headers.get('location') || '';
  const expectedLocation = `${baseUrl}${targetPath}`;
  if (response.status !== 301) findings.push(`${sourcePath}: returned HTTP ${response.status} instead of 301`);
  if (location !== expectedLocation) findings.push(`${sourcePath}: location is ${location || 'absent'} instead of ${expectedLocation}`);
  console.log(`CHECK redirect ${sourcePath} status=${response.status} attempts=${attempt}`);
}

async function verifyPage(pagePath, markers) {
  const { response, attempt } = await requestWithRetry(pagePath);
  const html = await response.text();
  if (response.status !== 200) findings.push(`${pagePath}: returned HTTP ${response.status} instead of 200`);
  for (const marker of markers) {
    if (!html.includes(marker)) findings.push(`${pagePath}: missing marker: ${marker}`);
  }
  for (const forbidden of forbiddenMarkers) {
    if (html.includes(forbidden)) findings.push(`${pagePath}: exposes forbidden marker: ${forbidden}`);
  }
  if (expectedSha) {
    const deployedSha = readMeta(html, 'nvx-deploy-sha');
    if (deployedSha !== expectedSha) findings.push(`${pagePath}: served SHA ${deployedSha || 'absent'} instead of ${expectedSha}`);
  }
  console.log(`CHECK page ${pagePath} status=${response.status} attempts=${attempt}`);
}

for (const [sourcePath, targetPath] of redirects) {
  await sleep(2000);
  await verifyRedirect(sourcePath, targetPath);
}
for (const [pagePath, markers] of pages) {
  await sleep(2000);
  await verifyPage(pagePath, markers);
}

if (findings.length) {
  console.error(`SMOKE_VERIFY_FAILED findings=${findings.length}`);
  for (const finding of findings) console.error(`- ${finding}`);
  process.exit(1);
}

console.log(`SMOKE_VERIFY_OK base_url=${baseUrl} pages=${pages.length} redirects=${redirects.length}`);
