import { chromium } from 'playwright';

const baseUrl = 'https://nuvanx.com';

const pages = [
  'https://nuvanx.com/',
  'https://nuvanx.com/bioestimuladores-colageno-madrid/',
  'https://nuvanx.com/blog/',
  'https://nuvanx.com/btl-exilite-ipl-madrid/',
  'https://nuvanx.com/calidad-piel-firmeza-luminosidad-madrid/',
  'https://nuvanx.com/cicatrices-acne-poros-textura-madrid/',
  'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/',
  'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  'https://nuvanx.com/contacto/',
  'https://nuvanx.com/contorno-corporal-masculino-madrid/',
  'https://nuvanx.com/emfusion/',
  'https://nuvanx.com/endolaser-corporal-grasa-localizada/',
  'https://nuvanx.com/endolift-facial-papada-mandibula/',
  'https://nuvanx.com/equipo-medico/',
  'https://nuvanx.com/estetica-avanzada/',
  'https://nuvanx.com/exion-body/',
  'https://nuvanx.com/exion-btl/',
  'https://nuvanx.com/exion-face/',
  'https://nuvanx.com/exion-fractional/',
  'https://nuvanx.com/flacidez-grasa-localizada-brazos-madrid/',
  'https://nuvanx.com/flacidez-muslos-internos-subgluteo-madrid/',
  'https://nuvanx.com/grasa-espalda-zona-sujetador-madrid/',
  'https://nuvanx.com/grasa-localizada-abdomen-flancos-madrid/',
  'https://nuvanx.com/inversion-medicina-estetica/',
  'https://nuvanx.com/labios-acido-hialuronico-madrid/',
  'https://nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  'https://nuvanx.com/madrid/',
  'https://nuvanx.com/madrid/valoracion/',
  'https://nuvanx.com/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
  'https://nuvanx.com/medicina-estetica-chamberi/',
  'https://nuvanx.com/medicina-estetica-laser/',
  'https://nuvanx.com/medicina-estetica/',
  'https://nuvanx.com/nosotros/',
  'https://nuvanx.com/ojeras-surco-lagrimal-madrid/',
  'https://nuvanx.com/papada-definicion-mandibular-madrid/',
  'https://nuvanx.com/politica-privacidad/',
  'https://nuvanx.com/por-que-nuvanx/',
  'https://nuvanx.com/protocolos-signature/',
  'https://nuvanx.com/remodelacion-corporal-laser-madrid/',
  'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid/',
  'https://nuvanx.com/soluciones-medicas/',
  'https://nuvanx.com/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  'https://nuvanx.com/tratamiento-rodillas-grasa-flacidez-madrid/',
  'https://nuvanx.com/tratamientos/',
];

const blogArticles = [
  'https://nuvanx.com/botox-madrid-precio-neuromoduladores/',
  'https://nuvanx.com/endolaser-corporal-vs-no-invasivos-grasa-localizada/',
  'https://nuvanx.com/endolift-ciencia-laser-subdermico/',
  'https://nuvanx.com/endolift-primeras-72-horas-que-esperar/',
  'https://nuvanx.com/endolift-vs-hifu-diferencias-reales/',
  'https://nuvanx.com/endolift-vs-lifting-quirurgico-cuando-operarse/',
  'https://nuvanx.com/exion-btl-fractional-rf-face-body/',
  'https://nuvanx.com/exion-fractional-rf-vs-morpheus8-comparativa/',
  'https://nuvanx.com/exposoma-cutaneo-envejecimiento-piel-factores-externos/',
  'https://nuvanx.com/intrusismo-tratamientos-inyectables-riesgos/',
  'https://nuvanx.com/ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento/',
  'https://nuvanx.com/laser-co2-vs-radiofrecuencia-cuando-elegir/',
  'https://nuvanx.com/orden-tratamientos-faciales-que-tratar-primero/',
  'https://nuvanx.com/papada-sin-cirugia-madrid-opciones-endolift/',
  'https://nuvanx.com/plan-anual-medicina-estetica-sin-sobretratar/',
  'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid-guia/',
  'https://nuvanx.com/tratamientos-faciales-sin-cirugia-guia-medica-diagnostico/',
  'https://nuvanx.com/well-aging-48-cambios-hormonales-piel/',
  'https://nuvanx.com/well-aging-estrategia-medica-global/',
];

const allUrls = [...pages, ...blogArticles];
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];

for (const url of allUrls) {
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1100 },
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();
  
  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    const status = response?.status() || 0;
    
    const issues = [];
    if (status !== 200) issues.push(`HTTP ${status}`);
    
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
