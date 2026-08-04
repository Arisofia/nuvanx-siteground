#!/usr/bin/env node

/**
 * Systematic Page Audit
 *
 * Audits all pages from sitemap to detect:
 * - Missing <main id="nvx-main">
 * - Missing .nvx-brand-page
 * - Missing .nvx-brand-hero
 * - Duplicate heroes
 * - Inconsistent templates
 */

const sitemapPages = [
  '/', '/exion-face/', '/exion-body/', '/exion-fractional/', '/emfusion/',
  '/labios-acido-hialuronico-madrid/', '/rinomodelacion-sin-cirugia-madrid/',
  '/ojeras-surco-lagrimal-madrid/', '/bioestimuladores-colageno-madrid/',
  '/contacto/', '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  '/nosotros/', '/equipo-medico/', '/clinicas-de-medicina-estetica-nuvanx/',
  '/madrid/valoracion/', '/por-que-nuvanx/', '/inversion-medicina-estetica/',
  '/soluciones-medicas/', '/protocolos-signature/', '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/', '/papada-definicion-mandibular-madrid/',
  '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', '/cicatrices-acne-poros-textura-madrid/',
  '/calidad-piel-firmeza-luminosidad-madrid/', '/grasa-localizada-abdomen-flancos-madrid/',
  '/flacidez-grasa-localizada-brazos-madrid/', '/contorno-corporal-masculino-madrid/',
  '/tratamiento-rodillas-grasa-flacidez-madrid/', '/flacidez-muslos-internos-subgluteo-madrid/',
  '/grasa-espalda-zona-sujetador-madrid/', '/medicina-estetica/', '/endolaser-corporal-grasa-localizada/',
  '/endolift-facial-papada-mandibula/', '/estetica-avanzada/', '/medicina-estetica-chamberi/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', '/madrid/', '/exion-btl/',
  '/tratamientos/', '/medicina-estetica-laser/', '/casos-de-pacientes/',
  '/btl-exilite-ipl-madrid/', '/politica-privacidad/'
];

const BASE_URL = 'https://staging2.nuvanx.com';

async function auditPage(url) {
  const fullUrl = `${BASE_URL}${url}`;
  try {
    const response = await fetch(fullUrl);
    const html = await response.text();

    const hasMain = html.includes('<main id="nvx-main"');
    const hasBrandPage = html.includes('nvx-brand-page');
    const heroMatches = html.match(/nvx-brand-hero/g) || [];
    const heroCount = heroMatches.length;
    const hasHero = heroCount > 0;
    const hasDuplicateHero = heroCount > 1;

    // Detect template patterns
    const hasPageShell = html.includes('nvx-page-shell');
    const hasDirectHeader = html.includes('get_header()') || html.includes('nvx-header__cta');

    return {
      url,
      hasMain,
      hasBrandPage,
      hasHero,
      heroCount,
      hasDuplicateHero,
      hasPageShell,
      hasDirectHeader,
      status: (!hasMain || !hasBrandPage) ? 'ISSUE' : (hasDuplicateHero ? 'DUPLICATE_HERO' : 'OK')
    };
  } catch (error) {
    return {
      url,
      error: error.message,
      status: 'ERROR'
    };
  }
}

async function runAudit() {
  console.log('🔍 Systematic Page Audit');
  console.log('='.repeat(80));
  console.log();

  const results = [];
  for (const page of sitemapPages) {
    const result = await auditPage(page);
    results.push(result);
    process.stdout.write(`\rAuditing: ${page}...`);
  }

  console.log('\n\n✅ Audit Complete\n');
  console.log('='.repeat(80));
  console.log();

  // Summary
  const issues = results.filter(r => r.status === 'ISSUE');
  const duplicates = results.filter(r => r.status === 'DUPLICATE_HERO');
  const errors = results.filter(r => r.status === 'ERROR');
  const ok = results.filter(r => r.status === 'OK');

  console.log(`📊 Summary:`);
  console.log(`   OK: ${ok.length}`);
  console.log(`   ISSUES: ${issues.length}`);
  console.log(`   DUPLICATE HERO: ${duplicates.length}`);
  console.log(`   ERRORS: ${errors.length}`);
  console.log();

  // Detailed report
  if (issues.length > 0) {
    console.log('⚠️  Pages with structural issues:');
    issues.forEach(r => {
      console.log(`   ${r.url}`);
      console.log(`      - hasMain: ${r.hasMain}`);
      console.log(`      - hasBrandPage: ${r.hasBrandPage}`);
    });
    console.log();
  }

  if (duplicates.length > 0) {
    console.log('⚠️  Pages with duplicate heroes:');
    duplicates.forEach(r => {
      console.log(`   ${r.url} (${r.heroCount} heroes)`);
    });
    console.log();
  }

  if (errors.length > 0) {
    console.log('❌ Pages with errors:');
    errors.forEach(r => {
      console.log(`   ${r.url}: ${r.error}`);
    });
    console.log();
  }

  // All pages detailed
  console.log('📋 Detailed results:');
  console.log('='.repeat(80));
  results.forEach(r => {
    const icon = r.status === 'OK' ? '✅' : (r.status === 'ISSUE' ? '⚠️' : (r.status === 'DUPLICATE_HERO' ? '🔴' : '❌'));
    console.log(`${icon} ${r.url}`);
    console.log(`   Status: ${r.status}`);
    console.log(`   Main: ${r.hasMain}, BrandPage: ${r.hasBrandPage}, Hero: ${r.heroCount}`);
    console.log(`   PageShell: ${r.hasPageShell}, DirectHeader: ${r.hasDirectHeader}`);
    console.log();
  });
}

runAudit();
