import { chromium } from 'playwright';

const baseUrl = process.env.BASE_URL || 'https://staging2.nuvanx.com';
const expectedHost = process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
let base;
try {
  base = new URL(baseUrl);
} catch {
  throw new Error(`BASE_URL must be an absolute URL; received=${JSON.stringify(baseUrl)}`);
}
if (base.protocol !== 'https:' || base.hostname !== expectedHost || base.username || base.password) {
  throw new Error(`Refusing unexpected staging target: base=${base.origin} expected_host=${expectedHost}`);
}

const pages = [
  '/',
  '/bioestimuladores-colageno-madrid/',
  '/blog/',
  '/btl-exilite-ipl-madrid/',
  '/calidad-piel-firmeza-luminosidad-madrid/',
  '/cicatrices-acne-poros-textura-madrid/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  '/contacto/',
  '/contorno-corporal-masculino-madrid/',
  '/emfusion/',
  '/endolaser-corporal-grasa-localizada/',
  '/endolift-facial-papada-mandibula/',
  '/equipo-medico/',
  '/estetica-avanzada/',
  '/exion-body/',
  '/exion-btl/',
  '/exion-face/',
  '/exion-fractional/',
  '/flacidez-grasa-localizada-brazos-madrid/',
  '/flacidez-muslos-internos-subgluteo-madrid/',
  '/grasa-espalda-zona-sujetador-madrid/',
  '/grasa-localizada-abdomen-flancos-madrid/',
  '/inversion-medicina-estetica/',
  '/labios-acido-hialuronico-madrid/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  '/madrid/',
  '/madrid/valoracion/',
  '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
  '/medicina-estetica-chamberi/',
  '/medicina-estetica-laser/',
  '/medicina-estetica/',
  '/nosotros/',
  '/ojeras-surco-lagrimal-madrid/',
  '/papada-definicion-mandibular-madrid/',
  '/politica-privacidad/',
  '/por-que-nuvanx/',
  '/protocolos-signature/',
  '/remodelacion-corporal-laser-madrid/',
  '/rinomodelacion-sin-cirugia-madrid/',
  '/soluciones-medicas/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  '/tratamiento-rodillas-grasa-flacidez-madrid/',
  '/tratamientos/',
];

const blogArticles = [
  '/botox-madrid-precio-neuromoduladores/',
  '/endolaser-corporal-vs-no-invasivos-grasa-localizada/',
  '/endolift-ciencia-laser-subdermico/',
  '/endolift-primeras-72-horas-que-esperar/',
  '/endolift-vs-hifu-diferencias-reales/',
  '/endolift-vs-lifting-quirurgico-cuando-operarse/',
  '/exion-btl-fractional-rf-face-body/',
  '/exion-fractional-rf-vs-morpheus8-comparativa/',
  '/exposoma-cutaneo-envejecimiento-piel-factores-externos/',
  '/intrusismo-tratamientos-inyectables-riesgos/',
  '/ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento/',
  '/laser-co2-vs-radiofrecuencia-cuando-elegir/',
  '/orden-tratamientos-faciales-que-tratar-primero/',
  '/papada-sin-cirugia-madrid-opciones-endolift/',
  '/plan-anual-medicina-estetica-sin-sobretratar/',
  '/rinomodelacion-sin-cirugia-madrid-guia/',
  '/tratamientos-faciales-sin-cirugia-guia-medica-diagnostico/',
  '/well-aging-48-cambios-hormonales-piel/',
  '/well-aging-estrategia-medica-global/',
];

const allUrls = [...pages, ...blogArticles].map((path) => new URL(path, base).href);
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];

function crossHostNavigationUrls(response, finalUrl) {
  const urls = [finalUrl];
  let request = response?.request();
  while (request) {
    urls.push(request.url());
    request = request.redirectedFrom();
  }

  return [...new Set(urls)].filter((url) => {
    try {
      return new URL(url).hostname !== expectedHost;
    } catch {
      return true;
    }
  });
}

for (const url of allUrls) {
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1100 },
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();
  
  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    const status = response?.status() || 0;
    const crossHostUrls = crossHostNavigationUrls(response, page.url());
    
    const issues = [];
    if (status !== 200) issues.push(`HTTP ${status}`);
    if (crossHostUrls.length > 0) issues.push(`Navigation left ${expectedHost}: ${crossHostUrls.join(', ')}`);
    
    // Check for basic structure
    const hasBody = await page.locator('body').count() > 0;
    if (!hasBody) issues.push('Missing body element');
    
    results.push({ url, status, issues, pass: issues.length === 0 });
    console.log(`${issues.length === 0 ? 'PASS' : 'FAIL'} ${url} ${status}`);
    if (issues.length > 0) issues.forEach(i => console.error(`  ${i}`));
  } catch (error) {
    results.push({ url, status: 0, issues: [error.message], pass: false });
    console.log(`FAIL ${url} ${error.message}`);
  }
  
  await context.close();
}

await browser.close();

const failed = results.filter(r => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} pages passed`);
if (failed.length > 0) {
  console.log(`\nFailed pages:`);
  failed.forEach(r => console.log(`  ${r.url}`));
  process.exit(1);
}
console.log('\nAll pages passed');
