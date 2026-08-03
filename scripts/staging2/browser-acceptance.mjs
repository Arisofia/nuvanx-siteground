import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs/promises';

async function checkSkipLink(page, route, issues) {
  if (route !== '/') return;
  await page.keyboard.press('Tab');
  const isSkipLinkFocused = await page.evaluate(() => document.activeElement?.classList.contains('nvx-skip-link'));
  if (!isSkipLinkFocused) issues.push('Skip-link is not focused on first Tab');
  if (isSkipLinkFocused) {
    await page.keyboard.press('Enter');
    const isMainFocused = await page.evaluate(() => document.activeElement?.id === 'nvx-main');
    if (!isMainFocused) issues.push('Skip-link did not move focus to #nvx-main');
  }
}

async function checkHeadingHierarchy(page, issues) {
  const headings = await page.evaluate(() => {
    // Look only inside main and avoid elements in dialogs or hidden
    const container = document.querySelector('#nvx-main, main, [role="main"]') || document;
    const els = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
    return Array.from(els)
      .filter(el => !el.closest('dialog') && !el.closest('[hidden]') && !el.closest('.screen-reader-text') && el.offsetParent !== null)
      .map(el => Number.parseInt(el.tagName.replace('H', ''), 10));
  });
  let currentLevel = 0;
  headings.forEach((level, idx) => {
    if (currentLevel > 0 && level > currentLevel + 1) {
      issues.push(`Heading hierarchy skip detected: H${currentLevel} followed by H${level} at index ${idx}`);
    }
    currentLevel = level;
  });
}

async function checkGridLayout(page, issues) {
  const hasCollapsedGrids = await page.evaluate(() => {
    const containers = document.querySelectorAll('.nvx-blog-card, .nvx-brand-grid > *');
    let collapsed = false;
    containers.forEach(el => {
      const style = window.getComputedStyle(el);
      // We look for missing grid placement only if the element actually renders 
      // but doesn't have an explicit grid context if it's supposed to. 
      // Instead, checking offsetTop equality detects stacking.
      if (style.display !== 'none') {
        const parent = el.parentElement;
        if (parent && window.getComputedStyle(parent).display.includes('grid')) {
             const children = Array.from(parent.children).filter(c => window.getComputedStyle(c).display !== 'none');
             if (children.length > 1) {
                 // Check if first two children stack vertically but are supposed to be a grid
                 const r0 = children[0].getBoundingClientRect();
                 const r1 = children[1].getBoundingClientRect();
                 if (r0.bottom <= r1.top && r0.left === r1.left) {
                     // They stacked. This is expected on mobile, but if viewport is desktop, it's collapsed
                     if (window.innerWidth >= 1024) collapsed = true;
                 }
             }
        }
      }
    });
    return collapsed;
  });
  if (hasCollapsedGrids) issues.push('Grid layout collapsed (cards stacked vertically on desktop)');
}

async function checkOrphanClasses(page, issues) {
  const orphanClasses = await page.evaluate(() => {
    const allElements = document.querySelectorAll('*');
    const usedClasses = new Set();
    allElements.forEach(el => {
      el.classList.forEach(cls => {
        if (cls.startsWith('nvx-')) {
          usedClasses.add(cls);
        }
      });
    });
    
    if (usedClasses.size === 0) return [];

    const cssRulesClasses = new Set();
    function extractClasses(rules) {
      if (!rules) return;
      for (let j = 0; j < rules.length; j++) {
        const rule = rules[j];
        if (rule.selectorText) {
          const matches = rule.selectorText.match(/\.nvx-[a-zA-Z0-9_-]+/g);
          if (matches) {
            matches.forEach(m => cssRulesClasses.add(m.substring(1)));
          }
        } else if (rule.cssRules) {
          extractClasses(rule.cssRules);
        }
      }
    }

    for (let i = 0; i < document.styleSheets.length; i++) {
      try {
        const sheet = document.styleSheets[i];
        extractClasses(sheet.cssRules);
      } catch (e) {
        // Cross-origin stylesheet access might throw
      }
    }

    const orphans = [];
    usedClasses.forEach(cls => {
      if (!cssRulesClasses.has(cls)) {
        orphans.push(cls);
      }
    });
    return orphans;
  });

  if (orphanClasses.length > 0) {
    issues.push(`Orphan classes found (no CSS rules): ${orphanClasses.join(', ')}`);
  }
}

async function checkSpacing(page, issues) {
  const spacingIssues = await page.evaluate(() => {
    const errs = [];
    const hero = document.querySelector('.nvx-brand-hero, .nvx-page-header, .nvx-hero');
    if (hero) {
      const height = hero.getBoundingClientRect().height;
      // Depending on screen size, anything above 1600px is likely a broken layout
      if (height > 1600) {
        errs.push(`Hero section height is excessively large (${height}px)`);
      }
      if (height === 0) {
        errs.push('Hero section is collapsed (0px height)');
      }
    }

    const sections = document.querySelectorAll('main > section');
    for (let i = 0; i < sections.length - 1; i++) {
      const current = sections[i];
      const next = sections[i + 1];
      
      const currentRect = current.getBoundingClientRect();
      const nextRect = next.getBoundingClientRect();
      
      if (currentRect.height > 0 && nextRect.height > 0 && nextRect.top > currentRect.bottom) {
        const gap = Math.round(nextRect.top - currentRect.bottom);
        // Arbitrary max gap: if more than 400px of pure empty whitespace exists between sections, it's likely a bug
        if (gap > 400) {
          errs.push(`Excessive vertical gap (${gap}px) between sections`);
        }
      }
    }
    return errs;
  });

  if (spacingIssues.length > 0) {
    spacingIssues.forEach(err => issues.push(err));
  }
}

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();

if (!expectedSha || !/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error(`EXPECTED_SHA must be set to a full lowercase 40-character SHA. Received: "${expectedSha}"`);
  process.exit(1);
}

const routesJsonPath = new URL('../../wp-content/themes/nuvanx-medical/inc/data/routes.json', import.meta.url);
const routesRaw = await fs.readFile(routesJsonPath, 'utf8');
const routes = Object.keys(JSON.parse(routesRaw));

async function safeGoto(page, url) {
  const maxAttempts = 5;
  let attempt = 1;
  while (attempt <= maxAttempts) {
    try {
      return await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 35000 });
    } catch (e) {
      const msg = String(e.message || '');
      if (
        (msg.includes('ERR_SOCKS_CONNECTION_FAILED') || msg.includes('ERR_CONNECTION_') || msg.includes('Timeout'))
        && attempt < maxAttempts
      ) {
        const delay = attempt * 2000;
        console.warn(`Goto failed with network/timeout error (attempt ${attempt}/${maxAttempts}): ${msg.split('\n')[0]}. Retrying in ${delay}ms...`);
        await page.waitForTimeout(delay);
        attempt++;
        continue;
      }
      throw e;
    }
  }
  throw new Error(`Failed to goto ${url} after ${maxAttempts} attempts.`);
}

/**
 * Runs browser acceptance tests for all configured routes and writes audit artifacts.
 *
 * Exits the process with status 1 when any route fails its acceptance checks.
 */
async function run() {
  console.log(`Starting Browser Acceptance Tests against ${baseUrl} with EXPECTED_SHA=${expectedSha}...`);
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
  const csvRows = [
    'URL,Status,FinalURL,Canonical,H1,Title,JS_Errors,Network_Errors,Meta_Deploy_SHA,HTML_Lang,Main_Exists,Noindex_Meta,Noindex_HTTP,HubSpot_Initial,FacebookSignal_Initial,ThirdPartySrc,RogueJsonLd,HeroCount,CtaCount,A11yViolations'
  ];
  let totalFailures = 0;

  for (const route of routes) {
    const page = await context.newPage();
    const url = `${baseUrl}${route}`;
    console.log(`Navigating to ${url}...`);
    
    let mainResponseStatus = 0;
    let finalUrl = '';
    const issues = [];
    const currentConsoleErrors = [];
    const currentNetworkErrors = [];
    let metaDeploySha = '';
    let htmlLang = '';
    let mainExists = false;
    let hasNoindexMeta = false;
    let httpNoindexHeader = '';
    let hasInitialHubspot = false;
    let hasInitialFacebookSignal = false;
    let hasRogueThirdPartySrc = false;
    let rogueJsonLdCount = 0;
    let heroCount = 0;
    let ctaCount = 0;
    let a11yViolationsCount = 0;

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
      // Track non-OK responses, except the main 404 we explicitly expect for some routes
      if (!response.ok() && response.status() >= 400 && response.status() !== 404) {
        currentNetworkErrors.push({
          url: response.url(),
          status: response.status()
        });
      }

      // Capture HTTP noindex-related headers for Staging2 protection checks
      const xRobots = response.headers()['x-robots-tag'] || response.headers()['X-Robots-Tag'];
      if (xRobots && !httpNoindexHeader) {
        httpNoindexHeader = xRobots;
      }
    });
    try {
      const response = await safeGoto(page, url);
      mainResponseStatus = response ? response.status() : 0;
      finalUrl = page.url();

      // Wait a bit for JS to execute (like HubSpot)
      await page.waitForTimeout(2000);

      const title = await page.title();
      const h1Count = await page.locator('h1').count();
      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href').catch(() => null);

      htmlLang = (await page.locator('html').getAttribute('lang').catch(() => null)) || '';
      mainExists = (await page.locator('main, [role="main"]').count()) === 1;

      await checkSkipLink(page, route, issues);
      await checkHeadingHierarchy(page, issues);
      await checkGridLayout(page, issues);
      await checkOrphanClasses(page, issues);
      await checkSpacing(page, issues);

      // Deployment SHA marker in the document
      metaDeploySha =
        (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => null)) || '';

      // Staging2 meta noindex protection
      const robotsMetaName = await page.locator('meta[name="robots"]').getAttribute('content').catch(() => null);
      const robotsMetaDefault = await page.locator('meta[content*="noindex"]').getAttribute('content').catch(() => null);
      hasNoindexMeta = Boolean(
        robotsMetaName && robotsMetaName.toLowerCase().includes('noindex')
      ) || Boolean(robotsMetaDefault && robotsMetaDefault.toLowerCase().includes('noindex'));

      // Integration invariants: initial HTML scripts must not contain HubSpot or FacebookSignal
      const initialScripts = await page.locator('script').allInnerTexts();
      hasInitialHubspot = initialScripts.some(text => /hbspt\.forms\.create|js\.hs-scripts\.com|hscollectedforms\.net|hs-analytics\.net/i.test(text));
      hasInitialFacebookSignal = initialScripts.some(text => /facebook.*signal/i.test(text));
      
      const scriptSrcs = await page.locator('script[src]').evaluateAll(els => els.map(el => el.getAttribute('src') || ''));
      hasRogueThirdPartySrc = scriptSrcs.some(src => /hsforms|hubspot|hs-scripts\.com|hscollectedforms\.net|hs-analytics\.net|facebook|fbevents/i.test(src));

      // Hero and CTAs
      heroCount = await page.locator('.nvx-brand-hero, .nvx-home-hero, .nvx-blog-hero, .nvx-strategy-intro').count();
      // On 404 the H1 acts as minimal hero.
      ctaCount = await page.locator('a.nvx-btn, a.nvx-button').count();

      // JSON-LD
      rogueJsonLdCount = await page.locator('script[type="application/ld+json"]:not(.yoast-schema-graph)').count();
      
      const isRedirectExpected = ['/politica-de-cookies/', '/mas-informacion-sobre-las-cookies/', '/medicina-estetica-goya-barrio-salamanca/'].includes(route);
      const is404Expected = ['/equipo-medico-clinica-goya/'].includes(route);
      const isGated = ['/casos-de-pacientes/'].includes(route);

      // Assertions
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

      if (!is404Expected && !isRedirectExpected && !title) {
        issues.push('Missing or empty <title>');
      }

      if (!is404Expected && !isRedirectExpected && htmlLang !== 'es-ES') {
        issues.push(`Expected html[lang]="es-ES", got "${htmlLang}"`);
      }

      if (!is404Expected && !isRedirectExpected && !mainExists) {
        issues.push('Missing <main> landmark');
      }

      // SHA invariants: every checked route must serve the expected deployment SHA marker
      if (!is404Expected && !isRedirectExpected) {
        if (!metaDeploySha) {
          issues.push('Missing meta[name="nvx-deploy-sha"] marker');
        } else if (metaDeploySha !== expectedSha) {
          issues.push(`Deployment SHA mismatch: meta nvx-deploy-sha=${metaDeploySha}, expected=${expectedSha}`);
        }
        
        if (heroCount === 0) issues.push('Missing hero section');
        if (ctaCount === 0) issues.push('Missing CTA (.nvx-btn / .nvx-button)');
        if (rogueJsonLdCount > 0) issues.push(`Found ${rogueJsonLdCount} rogue JSON-LD script(s) outside Yoast graph`);
        
        // --- Editorial / Visual QA Invariants ---
        
        // 1. NAP Icons
        const isNapPage = route === '/contacto/' || route.includes('medicina-estetica-chamberi') || route.includes('goya-barrio-salamanca');
        if (isNapPage) {
          const hasLocationIcon = await page.locator('svg use[*|href="#icon-location"], svg use[href="#icon-location"]').count() > 0;
          const hasPhoneIcon = await page.locator('svg use[*|href="#icon-phone"], svg use[href="#icon-phone"]').count() > 0;
          if (!hasLocationIcon) issues.push('Missing mandatory NAP icon: #icon-location');
          if (!hasPhoneIcon) issues.push('Missing mandatory NAP icon: #icon-phone');
        }
        
        // 2. Clinic / Hub Wrappers
        const clinicalRoutes = [
          '/endolift-facial-papada-mandibula/', '/endolaser-corporal-grasa-localizada/', '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
          '/exion-btl/', '/exion-face/', '/exion-fractional/', '/btl-exilite-ipl-madrid/', '/bioestimuladores-colageno-madrid/',
          '/ojeras-surco-lagrimal-madrid/', '/rinomodelacion-sin-cirugia-madrid/', '/labios-acido-hialuronico-madrid/',
          '/remodelacion-corporal-laser-madrid/', '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
          '/papada-definicion-mandibular-madrid/', '/calidad-piel-firmeza-luminosidad-madrid/', '/cicatrices-acne-poros-textura-madrid/',
          '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', '/grasa-localizada-abdomen-flancos-madrid/',
          '/flacidez-grasa-localizada-brazos-madrid/', '/grasa-espalda-zona-sujetador-madrid/', '/flacidez-muslos-internos-subgluteo-madrid/',
          '/tratamiento-rodillas-grasa-flacidez-madrid/', '/contorno-corporal-masculino-madrid/'
        ];
        const isClinicOrHubPage = clinicalRoutes.includes(route) || ['/clinicas-de-medicina-estetica-nuvanx/', '/contacto/', '/equipo-medico/', '/nosotros/', '/medicina-estetica-laser/'].includes(route);
        if (isClinicOrHubPage) {
          const hasWrappedHero = await page.locator('.nvx-brand-page .nvx-brand-hero, .nvx-brand-page > .nvx-brand-hero, .nvx-brand-page > * > .nvx-brand-hero').count() > 0;
          if (!hasWrappedHero) {
            issues.push('Hero structure violation: .nvx-brand-hero is not wrapped inside .nvx-brand-page');
          }
        }
        
        // 3. Strategic Page CTAs
        const isStrategicPage = ['/', '/nosotros/', '/contacto/', '/madrid/valoracion/', '/clinicas-de-medicina-estetica-nuvanx/', '/medicina-estetica-laser/'].includes(route);
        if (isStrategicPage) {
          const hasActions = await page.locator('.nvx-brand-hero__copy .nvx-brand-actions, .nvx-home-hero__copy .nvx-brand-actions').count() > 0;
          if (!hasActions) {
             issues.push('Missing standard CTA wrapper (.nvx-brand-actions) inside Hero copy');
          }
        }
        
        // 4. Managed module verification — detecta exactamente la regresión de 67dfc5e
        const managedModuleRoutes = {
          '/protocolos-signature/': '.nvx-signature-hub, .nvx-strategy-intro',
          '/por-que-nuvanx/': '.nvx-strategy-page, .nvx-strategy-intro',
          '/inversion-medicina-estetica/': '.nvx-strategy-page, .nvx-strategy-intro',
          '/endolift-facial-papada-mandibula/': '.nvx-brand-page--endolift',
          '/flacidez-grasa-localizada-brazos-madrid/': '.nvx-signature-phase-page',
          '/exion-face/': '.nvx-btl-evidence-note, .nvx-btl-detail'
        };
        if (managedModuleRoutes[route]) {
          const expectedSelector = managedModuleRoutes[route];
          const hasManagedModule = await page.locator(expectedSelector).count() > 0;
          if (!hasManagedModule) {
            issues.push(`Managed module selector missing — page likely fell back to generic page.php template (expected: ${expectedSelector})`);
          }
        }

        // 5. Schema graph — confirma que el nodo raíz de entidad médica existe, no solo "algún" ld+json
        if (!isRedirectExpected && !is404Expected) {
          const schemaTypes = await page.evaluate(() => {
            const scripts = Array.from(document.querySelectorAll('script.yoast-schema-graph, script[type="application/ld+json"]'));
            return scripts.flatMap(s => {
              try { return (JSON.parse(s.textContent)['@graph'] || []).map(n => n['@type']); }
              catch { return []; }
            });
          });
          const flatTypes = schemaTypes.flat();
          if (route === '/' && !flatTypes.some(t => String(t).includes('MedicalOrganization'))) {
            issues.push('Missing MedicalOrganization node in schema graph — nvx-structured-data.php may not be loaded');
          }
        }
        
        // 6. Axe-core accessibility scan
        const axeRoutes = ['/', '/madrid/valoracion/', '/contacto/', '/endolift-facial-papada-mandibula/', '/blog/'];
        if (axeRoutes.includes(route)) {
          try {
            const accessibilityScanResults = await new AxeBuilder({ page }).analyze();
            const violations = accessibilityScanResults.violations || [];
            const blocking = violations.filter(v => ['critical','serious'].includes(v.impact));
            a11yViolationsCount = blocking.length;
            if (blocking.length > 0) {
              issues.push(`A11y: Found ${blocking.length} accessibility violations (critical/serious): ${blocking.map(v=>v.id).join(', ')}`);
            }
          } catch (axeErr) {
            console.warn(`Axe-core failed on ${route}:`, axeErr.message);
          }
        }
      }

      // Staging2 must retain meta and HTTP noindex protection
      if (!is404Expected && !isRedirectExpected) {
        const httpTag = (httpNoindexHeader || '').toLowerCase();
        const hasHttpNoindex = httpTag.includes('noindex');
        if (!hasNoindexMeta && !hasHttpNoindex) {
          issues.push('Staging2 route is missing meta or HTTP noindex protection');
        }
      }

      // Integration invariants: HubSpot and FacebookSignal must not be in initial HTML scripts
      if (hasInitialHubspot) {
        issues.push('HubSpot found in initial HTML scripts; must be demand-loaded after user intent');
      }
      if (hasInitialFacebookSignal) {
        issues.push('FacebookSignal present in initial HTML; must not reach the browser');
      }
      if (hasRogueThirdPartySrc) {
        issues.push('Rogue third-party script src (HubSpot/Facebook) found on initial load');
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
        networkErrors: currentNetworkErrors,
        metaDeploySha,
        htmlLang,
        mainExists,
        hasNoindexMeta,
        httpNoindexHeader,
        hasInitialHubspot,
        hasInitialFacebookSignal,
        hasRogueThirdPartySrc,
        rogueJsonLdCount,
        heroCount,
        ctaCount
      });

      csvRows.push(
        `${route},${mainResponseStatus},${finalUrl},${canonical},${h1Count},"${title.replace(/"/g, '""')}",${currentConsoleErrors.length},${currentNetworkErrors.length},${metaDeploySha},${htmlLang},${mainExists},${hasNoindexMeta},${httpNoindexHeader},${hasInitialHubspot},${hasInitialFacebookSignal},${hasRogueThirdPartySrc},${rogueJsonLdCount},${heroCount},${ctaCount},${a11yViolationsCount}`
      );

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
      csvRows.push(`${route},FATAL${','.repeat(19)}`);
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
  
  let summaryMd = `# Staging2 Browser Acceptance Summary\n\nBase URL: ${baseUrl}\nExpected SHA: ${expectedSha}\nTotal Routes: ${routes.length}\nFailures: ${totalFailures}\n\n`;
  if (totalFailures === 0) {
    summaryMd += '✅ All routes passed strict document, integration and noindex invariants.\n';
  } else {
    summaryMd += '❌ Failures detected in document/integration invariants or SHA/noindex checks.\n';
  }
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
