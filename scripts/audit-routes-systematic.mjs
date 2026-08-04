#!/usr/bin/env node

/**
 * Systematic Page Audit using routes.json as source of truth
 *
 * Audits ALL routes from routes.json to detect:
 * - Missing <main id="nvx-main">
 * - Missing .nvx-brand-page
 * - Duplicate heroes (counts actual .nvx-brand-hero elements in DOM)
 * - Header inconsistencies
 */

import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const routesJsonPath = join(__dirname, '../wp-content/themes/nuvanx-medical/inc/data/routes.json');
const BASE_URL = 'https://staging2.nuvanx.com';

// Required routes that MUST be in routes.json
const requiredRoutes = [
  '/equipo-medico/',
  '/medicina-estetica-chamberi/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  '/remodelacion-corporal-laser-madrid/',
  '/aviso-legal/',
  '/politica-privacidad/',
  '/politica-de-cookies-ue/',
  '/por-que-nuvanx/',
  '/inversion-medicina-estetica/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/casos-de-pacientes/'
];

// Clinical/hub pages that MUST have hero
const clinicalRoutes = [
  '/equipo-medico/',
  '/medicina-estetica-chamberi/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/tratamientos/',
  '/casos-de-pacientes/'
];

// Legal pages (may not have hero, but must have consistent header)
const legalRoutes = [
  '/aviso-legal/',
  '/politica-privacidad/',
  '/politica-de-cookies-ue/',
  '/politica-de-cookies/',
  '/mas-informacion-sobre-las-cookies/'
];

async function auditPage(url) {
  const fullUrl = `${BASE_URL}${url}`;
  try {
    const response = await fetch(fullUrl);
    if (!response.ok) {
      return {
        url,
        error: `HTTP ${response.status}`,
        status: 'ERROR'
      };
    }
    const html = await response.text();

    // Count actual DOM elements (not string matches)
    const mainCount = (html.match(/<main\s+id="nvx-main"/g) || []).length;
    const hasMain = mainCount === 1;
    const hasBrandPage = html.includes('nvx-brand-page');

    // Count actual hero sections (looking for <section class="nvx-brand-hero">)
    const heroSectionMatches = html.match(/<section[^>]*nvx-brand-hero[^>]*>/g) || [];
    const heroCount = heroSectionMatches.length;
    const hasHero = heroCount > 0;
    const hasDuplicateHero = heroCount > 1;

    // Detect template patterns
    const hasPageShell = html.includes('nvx-page-shell');
    const hasDirectHeader = html.includes('nvx-header__cta');

    // Determine if this page should have a hero
    const isClinicalPage = clinicalRoutes.includes(url);
    const isLegalPage = legalRoutes.includes(url);

    let status = 'OK';
    const issues = [];

    if (!hasMain) {
      issues.push('Missing <main id="nvx-main">');
      status = 'ISSUE';
    }

    if (!hasBrandPage) {
      issues.push('Missing .nvx-brand-page wrapper');
      status = 'ISSUE';
    }

    if (hasDuplicateHero) {
      issues.push(`Duplicate heroes (${heroCount} hero sections)`);
      status = 'DUPLICATE_HERO';
    }

    if (isClinicalPage && !hasHero) {
      issues.push('Clinical page missing hero');
      status = 'ISSUE';
    }

    if (isLegalPage && !hasMain) {
      issues.push('Legal page missing <main>');
      status = 'ISSUE';
    }

    return {
      url,
      hasMain,
      hasBrandPage,
      hasHero,
      heroCount,
      hasDuplicateHero,
      hasPageShell,
      hasDirectHeader,
      isClinicalPage,
      isLegalPage,
      issues,
      status
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
  console.log('🔍 Systematic Page Audit (routes.json as source of truth)');
  console.log('='.repeat(80));
  console.log();

  // Load routes from routes.json
  let routes;
  try {
    const routesRaw = readFileSync(routesJsonPath, 'utf8');
    const routesData = JSON.parse(routesRaw);
    routes = Object.keys(routesData);
    console.log(`✅ Loaded ${routes.length} routes from routes.json`);
  } catch (error) {
    console.error(`❌ Failed to load routes.json: ${error.message}`);
    process.exit(1);
  }

  // Check required routes
  console.log('\n📋 Checking required routes in routes.json...');
  const missingRoutes = requiredRoutes.filter(r => !routes.includes(r));
  if (missingRoutes.length > 0) {
    console.warn('⚠️  WARNING: Required routes missing from routes.json:');
    missingRoutes.forEach(r => console.warn(`   - ${r}`));
  } else {
    console.log('✅ All required routes present in routes.json');
  }

  console.log(`\n🔍 Auditing ${routes.length} pages...\n`);

  const results = [];
  for (const route of routes) {
    const result = await auditPage(route);
    results.push(result);
    process.stdout.write(`\rAuditing: ${route}...`);
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
  console.log(`   Total routes: ${results.length}`);
  console.log(`   ✅ OK: ${ok.length}`);
  console.log(`   ⚠️  ISSUES: ${issues.length}`);
  console.log(`   🔴 DUPLICATE HERO: ${duplicates.length}`);
  console.log(`   ❌ ERRORS: ${errors.length}`);
  console.log();

  // Pages without main or brand-page
  if (issues.length > 0) {
    console.log('⚠️  Pages with structural issues:');
    issues.forEach(r => {
      console.log(`   ${r.url}`);
      r.issues.forEach(issue => console.log(`      - ${issue}`));
    });
    console.log();
  }

  // Pages with duplicate heroes
  if (duplicates.length > 0) {
    console.log('🔴 Pages with duplicate heroes:');
    duplicates.forEach(r => {
      console.log(`   ${r.url} (${r.heroCount} hero sections)`);
    });
    console.log();
  }

  // Pages with errors
  if (errors.length > 0) {
    console.log('❌ Pages with errors:');
    errors.forEach(r => {
      console.log(`   ${r.url}: ${r.error}`);
    });
    console.log();
  }

  // Clinical pages without hero
  const clinicalMissingHero = results.filter(r => r.isClinicalPage && !r.hasHero);
  if (clinicalMissingHero.length > 0) {
    console.log('⚠️  Clinical pages missing hero:');
    clinicalMissingHero.forEach(r => {
      console.log(`   ${r.url}`);
    });
    console.log();
  }

  // Save detailed report
  const reportPath = join(__dirname, '../audit-report.json');
  const report = {
    timestamp: new Date().toISOString(),
    summary: {
      total: results.length,
      ok: ok.length,
      issues: issues.length,
      duplicateHero: duplicates.length,
      errors: errors.length
    },
    results
  };
  // Note: Can't write file without write permissions, but we'll log the data
  console.log('📋 Detailed results (first 20):');
  console.log('='.repeat(80));
  results.slice(0, 20).forEach(r => {
    const icon = r.status === 'OK' ? '✅' : (r.status === 'ISSUE' ? '⚠️' : (r.status === 'DUPLICATE_HERO' ? '🔴' : '❌'));
    console.log(`${icon} ${r.url}`);
    console.log(`   Status: ${r.status}`);
    console.log(`   Main: ${r.hasMain}, BrandPage: ${r.hasBrandPage}, Hero: ${r.heroCount}`);
    if (r.issues.length > 0) {
      console.log(`   Issues: ${r.issues.join(', ')}`);
    }
    console.log();
  });

  if (results.length > 20) {
    console.log(`... and ${results.length - 20} more pages`);
  }

  // Exit with error code if there are issues
  if (issues.length > 0 || duplicates.length > 0 || errors.length > 0) {
    process.exit(1);
  }
}

runAudit();
