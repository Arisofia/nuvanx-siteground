import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');

const routes = [
  '/',
  '/madrid/',
  '/madrid/valoracion/',
  '/soluciones-medicas/',
  '/protocolos-signature/',
  '/por-que-nuvanx/',
  '/inversion-medicina-estetica/',
  '/nosotros/',
  '/equipo-medico/',
  '/contacto/',
  '/blog/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/medicina-estetica-chamberi/',
  '/medicina-estetica-goya-barrio-salamanca/',
  '/endolift-facial-papada-mandibula/',
  '/endolaser-corporal-grasa-localizada/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  '/exion-btl/',
  '/exion-face/',
  '/exion-fractional/',
  '/btl-exilite-ipl-madrid/',
  '/medicina-estetica-laser/',
  '/medicina-estetica/',
  '/estetica-avanzada/',
  '/bioestimuladores-colageno-madrid/',
  '/ojeras-surco-lagrimal-madrid/',
  '/rinomodelacion-sin-cirugia-madrid/',
  '/labios-acido-hialuronico-madrid/',
  '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  '/papada-definicion-mandibular-madrid/',
  '/calidad-piel-firmeza-luminosidad-madrid/',
  '/cicatrices-acne-poros-textura-madrid/',
  '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
  '/grasa-localizada-abdomen-flancos-madrid/',
  '/flacidez-grasa-localizada-brazos-madrid/',
  '/grasa-espalda-zona-sujetador-madrid/',
  '/flacidez-muslos-internos-subgluteo-madrid/',
  '/tratamiento-rodillas-grasa-flacidez-madrid/',
  '/contorno-corporal-masculino-madrid/',
  '/gracias/',
  '/politica-de-cookies-ue/',
  '/politica-privacidad/',
  '/aviso-legal/',
  '/politica-de-cookies/',
  '/mas-informacion-sobre-las-cookies/',
  '/casos-de-pacientes/',
  '/equipo-medico-clinica-goya/'
];

async function run() {
  console.log(`Starting Browser Acceptance Tests against ${baseUrl}...`);
  // Use SOCKS5 proxy if running in CI to bypass SiteGround edge blocking
  const browserArgs = {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  };
  if (process.env.HTTP_PROXY) {
    browserArgs.proxy = { server: process.env.HTTP_PROXY };
    console.log(`Using proxy: ${process.env.HTTP_PROXY}`);
  }

  const browser = await chromium.launch(browserArgs);
  const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
    ignoreHTTPSErrors: true
  });

  const auditResults = [];
  const consoleErrors = [];
  const networkErrors = [];
  const csvRows = ['URL,Status,FinalURL,Canonical,H1,Title,JS_Errors,Network_Errors'];
  let totalFailures = 0;

  for (const route of routes) {
    const page = await context.newPage();
    const url = `${baseUrl}${route}`;
    console.log(`Navigating to ${url}...`);
    
    let mainResponseStatus = 0;
    let finalUrl = '';
    const currentConsoleErrors = [];
    const currentNetworkErrors = [];

    page.on('console', msg => {
      if (msg.type() === 'error' || msg.type() === 'warning') {
        const text = msg.text();
        if (text.includes('Failed to load resource')) return; // Handled by network requests
        currentConsoleErrors.push({ type: msg.type(), text });
      }
    });

    page.on('pageerror', exception => {
      currentConsoleErrors.push({ type: 'uncaught', text: exception.message });
    });

    page.on('requestfailed', request => {
      currentNetworkErrors.push({
        url: request.url(),
        error: request.failure()?.errorText || 'Unknown failure'
      });
    });

    page.on('response', response => {
      if (!response.ok() && response.status() >= 400 && response.status() !== 404) { // We track 404s specifically later
        currentNetworkErrors.push({
          url: response.url(),
          status: response.status()
        });
      }
    });

    try {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
      mainResponseStatus = response ? response.status() : 0;
      finalUrl = page.url();

      // Wait a bit for JS to execute (like HubSpot)
      await page.waitForTimeout(2000);

      const title = await page.title();
      const h1Count = await page.locator('h1').count();
      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href').catch(() => null);
      
      const isRedirectExpected = ['/politica-de-cookies/', '/mas-informacion-sobre-las-cookies/', '/medicina-estetica-goya-barrio-salamanca/'].includes(route);
      const is404Expected = ['/equipo-medico-clinica-goya/'].includes(route);
      const isGated = ['/casos-de-pacientes/'].includes(route);

      // Assertions
      const issues = [];
      if (!is404Expected && !isRedirectExpected && mainResponseStatus !== 200) {
        issues.push(`Expected 200, got ${mainResponseStatus}`);
      }
      if (is404Expected && mainResponseStatus !== 404) {
        issues.push(`Expected 404, got ${mainResponseStatus}`);
      }
      if (isRedirectExpected) {
        if (finalUrl === url) issues.push('Expected redirect, but URL did not change');
        if (mainResponseStatus === 404) issues.push('Redirect route returned 404');
      }

      if (!is404Expected && !isRedirectExpected && h1Count !== 1) {
        issues.push(`Expected exactly 1 H1, found ${h1Count}`);
      }
      
      if (!is404Expected && !isRedirectExpected && !canonical) {
        issues.push('Missing canonical link');
      }

      if (currentConsoleErrors.length > 0) {
        issues.push(`${currentConsoleErrors.length} JS/Console errors`);
      }
      if (currentNetworkErrors.length > 0) {
        issues.push(`${currentNetworkErrors.length} network errors (assets/API)`);
      }

      auditResults.push({
        route,
        status: mainResponseStatus,
        finalUrl,
        title,
        h1Count,
        canonical,
        issues,
        consoleErrors: currentConsoleErrors,
        networkErrors: currentNetworkErrors
      });

      csvRows.push(`${route},${mainResponseStatus},${finalUrl},${canonical},${h1Count},"${title.replace(/"/g, '""')}",${currentConsoleErrors.length},${currentNetworkErrors.length}`);

      if (issues.length > 0) {
        console.error(`❌ ${route} FAILED:`);
        issues.forEach(i => console.error(`   - ${i}`));
        totalFailures++;
      } else {
        console.log(`✅ ${route} PASS`);
      }
      
      if (currentConsoleErrors.length) consoleErrors.push({ route, errors: currentConsoleErrors });
      if (currentNetworkErrors.length) networkErrors.push({ route, errors: currentNetworkErrors });

    } catch (e) {
      console.error(`❌ ${route} FATAL: ${e.message}`);
      totalFailures++;
      auditResults.push({ route, fatal: e.message });
      csvRows.push(`${route},FATAL,,,,,,`);
    } finally {
      await page.close();
    }
  }

  await browser.close();

  // Write Artifacts
  await fs.mkdir('artifacts', { recursive: true });
  await fs.writeFile('artifacts/staging2-audit.json', JSON.stringify(auditResults, null, 2));
  await fs.writeFile('artifacts/staging2-console-errors.json', JSON.stringify(consoleErrors, null, 2));
  await fs.writeFile('artifacts/staging2-network-errors.json', JSON.stringify(networkErrors, null, 2));
  await fs.writeFile('artifacts/staging2-audit.csv', csvRows.join('\n'));
  
  let summaryMd = `# Staging2 Browser Acceptance Summary\n\nTotal Routes: ${routes.length}\nFailures: ${totalFailures}\n\n`;
  if (totalFailures === 0) summaryMd += '✅ All routes passed strictly.\n';
  else summaryMd += '❌ Failures detected.\n';
  await fs.writeFile('artifacts/staging2-summary.md', summaryMd);

  console.log(`\nAcceptance complete. Total Failures: ${totalFailures}`);
  if (totalFailures > 0) {
    process.exit(1);
  }
}

run().catch(e => {
  console.error('Fatal execution error:', e);
  process.exit(1);
});
