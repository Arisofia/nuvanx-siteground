#!/usr/bin/env node
/**
 * Auditoría integral de diseño + GEO/SEO — todas las rutas de routes.json.
 * Valores canónicos: nvx-tokens.css. Requiere: playwright.
 * Uso: NVX_BASE=https://staging2.nuvanx.com node audit-design-full.mjs > out.txt
 */
import { readFileSync } from 'fs';
import { chromium } from 'playwright';
const BASE = process.env.NVX_BASE || 'https://staging2.nuvanx.com';
const routes = Object.keys(JSON.parse(
  readFileSync('wp-content/themes/nuvanx-medical/inc/data/routes.json', 'utf8')
));
const EXPECT = {
  serif: 'Playfair Display',                 // nvx-tokens.css:129
  sans: 'Manrope',                           // nvx-tokens.css:130
  ink: 'rgb(17, 17, 17)',                    // #111111  nvx-tokens.css:11
  paper: 'rgb(241, 241, 239)',               // #f1f1ef  nvx-tokens.css:10
  accent: 'rgb(74, 74, 74)',                 // #4a4a4a  nvx-tokens.css:15
  iconSizes: [16, 24, 32],                   // nvx-tokens.css:108-110 (NO 12, NO lg)
};
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
const results = [];
for (const route of routes) {
  const url = `${BASE}${route}`;
  try {
    const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 25000 });
    const status = resp?.status() ?? 0;
    if (status !== 200) { results.push({ route, status, skip: true }); continue; }
    const a = await page.evaluate((EXPECT) => {
      const o = {};
      const cs = (el, pseudo) => el ? getComputedStyle(el, pseudo) : null;
      // ---- LAYOUT ----
      const bp = document.querySelectorAll('.nvx-brand-page');
      o.brandPageCount = bp.length;
      o.nestedBrandPage = [...bp].some(el => el.querySelector('.nvx-brand-page'));
      const inner = document.querySelector('.nvx-page__content .nvx-brand-section__inner, .nvx-brand-section__inner, .nvx-aes-section__inner, .nvx-laser-section__inner, .nvx-endolift-section__inner, .nvx-catalog__inner, .nvx-shell');
      if (inner) {
        const r = inner.getBoundingClientRect(), s = cs(inner);
        const ml = parseFloat(s.marginLeft)||0, mr = parseFloat(s.marginRight)||0;
        o.gutterApplied = ml > 8 && Math.abs(ml-mr) < 6;
        o.stuckLeft = ml < 8 && r.width < innerWidth - 40;
        o.innerWidthPx = Math.round(r.width);
      } else o.innerMissing = true;
      o.horizontalScroll = document.documentElement.scrollWidth > innerWidth + 4;
      // ---- HERO full-bleed ----
      const hero = document.querySelector('.nvx-brand-hero');
      if (hero) {
        const r = hero.getBoundingClientRect();
        o.heroFullBleed = Math.abs(r.left) < 4 && r.width >= innerWidth - 4;
        o.heroLeftPx = Math.round(r.left);
      }
      // ---- HEADER (suprimido en valoración: header.php:28) ----
      o.isValoracion = location.pathname.includes('/valoracion/');
      o.headerPresent = !!document.querySelector('header.nvx-header');
      o.headerHasLogo = !!document.querySelector('.nvx-header .nvx-logo');
      o.headerHasNav = !!document.querySelector('.nvx-header .nvx-nav');
      o.headerHasCta = !!document.querySelector('.nvx-header__cta');
      // ---- FOOTER ----
      o.footerPresent = !!document.querySelector('footer.nvx-footer');
      o.footerSections = document.querySelectorAll('.nvx-footer__section').length;
      o.footerHasLegal = !!document.querySelector('.nvx-footer__legal-nav');
      o.footerHasRegistrations = !!document.querySelector('.nvx-footer__registrations');
      o.mainCount = document.querySelectorAll('main#nvx-main').length; // debe ser 1
      // ---- TIPOGRAFÍA ----
      const h1 = document.querySelector('h1');
      o.h1IsSerif = h1 ? cs(h1).fontFamily.includes(EXPECT.serif) : null;
      o.bodyIsSans = cs(document.body).fontFamily.includes(EXPECT.sans);
      o.h1FontRaw = h1 ? cs(h1).fontFamily : null;
      // ---- COLORES ----
      o.bodyColor = cs(document.body).color;
      o.bodyBg = cs(document.body).backgroundColor;
      o.colorOk = cs(document.body).color === EXPECT.ink;
      const kicker = document.querySelector('.nvx-brand-kicker, .nvx-eyebrow');
      o.kickerColor = kicker ? cs(kicker).color : null;
      // ---- ICONOS ----
      const uses = document.querySelectorAll('svg use');
      o.spriteIcons = uses.length;
      o.brokenIconRefs = [...uses].filter(u => {
        const h = u.getAttribute('href') || u.getAttribute('xlink:href') || '';
        return !h.startsWith('#');
      }).length;
      o.zeroSizeIcons = [...document.querySelectorAll('svg')].filter(s => {
        const r = s.getBoundingClientRect();
        return r.width === 0 && r.height === 0 && s.querySelector('use');
      }).length;
      // Tamaños fuera de {16,24,32}
      o.offTokenIcons = [...document.querySelectorAll('.nvx-icon-inline svg, .nvx-value__icon svg, .nvx-brand-card__icon svg')]
        .filter(s => { const w = Math.round(s.getBoundingClientRect().width); return w && !EXPECT.iconSizes.includes(w); }).length;
      // ---- NUMERACIÓN (01,02 — NO romanos) ----
      const step = document.querySelector('.nvx-treatment-process__step');
      o.stepNumberContent = step ? cs(step, '::before').content : null;
      o.romanNumerals = /(?:^|\n|\s)(?:I{1,3}|IV|VI{0,3}|IX|X)[.\)]\s/.test(document.body.innerText);
      // ---- ESTRUCTURA / JERARQUÍA ----
      o.h1Count = document.querySelectorAll('h1').length; // debe ser 1
      const heads = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].map(h=>+h.tagName[1]);
      o.headingSkip = heads.some((lvl,i)=> i>0 && lvl-heads[i-1] > 1);
      // ---- SEO ----
      o.title = document.title;
      o.metaDesc = document.querySelector('meta[name="description"]')?.content || null;
      o.canonical = document.querySelector('link[rel="canonical"]')?.href || null;
      o.robots = document.querySelector('meta[name="robots"]')?.content || null;
      o.docContract = !!document.querySelector('meta[name="nvx-document-contract"]');
      o.ogTitle = document.querySelector('meta[property="og:title"]')?.content || null;
      o.hreflang = document.querySelectorAll('link[rel="alternate"][hreflang]').length;
      // ---- GEO / SCHEMA (JSON-LD graph) ----
      const scripts = [...document.querySelectorAll('script.yoast-schema-graph, script[type="application/ld+json"]')];
      o.jsonLdBlocks = scripts.length;
      const types = scripts.flatMap(s => {
        try {
          const g = JSON.parse(s.textContent);
          return (g['@graph'] || [g]).flatMap(n => [].concat(n['@type'] || []));
        } catch { return []; }
      });
      o.schemaTypes = [...new Set(types)];
      o.hasMedicalOrg = types.some(t => /MedicalOrganization|MedicalClinic/.test(t));
      o.hasProcedure = types.some(t => /MedicalProcedure|Service/.test(t));
      o.hasFAQ = types.includes('FAQPage');
      o.hasPhysician = types.includes('Physician');
      return o;
    }, EXPECT);
    // ---- VEREDICTO ----
    const p = [];
    // Layout
    if (a.nestedBrandPage) p.push('DOUBLE .nvx-brand-page');
    if (a.stuckLeft) p.push('CONTENT STUCK LEFT');
    if (a.horizontalScroll) p.push('HORIZONTAL SCROLL');
    if (a.heroFullBleed === false) p.push('HERO NOT FULL-BLEED');
    if (a.innerMissing) p.push('no section__inner');
    // Header / Footer / wrapper
    if (!a.headerPresent && !a.isValoracion) p.push('MISSING HEADER');
    if (!a.headerHasLogo && !a.isValoracion) p.push('header without logo');
    if (!a.footerPresent) p.push('MISSING FOOTER');
    if (!a.footerHasLegal) p.push('footer without legal nav');
    if (!a.footerHasRegistrations) p.push('footer without sanitary registrations');
    if (a.mainCount !== 1) p.push(`main count=${a.mainCount}`);
    // Tipografía / color
    if (a.h1IsSerif === false) p.push('H1 NOT Playfair');
    if (a.bodyIsSans === false) p.push('BODY NOT Manrope');
    if (a.colorOk === false) p.push(`body color=${a.bodyColor} (esperado rgb(17,17,17))`);
    // Iconos
    if (a.brokenIconRefs > 0) p.push(`${a.brokenIconRefs} BROKEN ICON refs`);
    if (a.zeroSizeIcons > 0) p.push(`${a.zeroSizeIcons} ZERO-SIZE icons`);
    if (a.offTokenIcons > 0) p.push(`${a.offTokenIcons} icons off-token (≠16/24/32)`);
    // Numeración / estructura
    if (a.romanNumerals) p.push('ROMAN NUMERALS (esperado 01,02)');
    if (a.h1Count !== 1) p.push(`H1 count=${a.h1Count} (esperado 1)`);
    if (a.headingSkip) p.push('HEADING LEVEL SKIP');
    // SEO
    if (!a.canonical) p.push('NO canonical');
    if (!a.docContract) p.push('NO nvx-document-contract');
    if (!a.title) p.push('NO <title>');
    if (!a.metaDesc) p.push('NO meta description');
    if (a.hreflang === 0) p.push('NO hreflang');
    // GEO / schema
    if (a.jsonLdBlocks === 0) p.push('NO JSON-LD');
    if (!a.hasMedicalOrg) p.push('schema: sin MedicalOrganization/Clinic');
    results.push({ route, status, ...a, problems: p });
  } catch (e) {
    results.push({ route, error: e.message });
  }
  process.stdout.write(`\r${route}...            `);
}
await browser.close();
// ---- REPORTE ----
console.log('\n\n=== AUDITORÍA INTEGRAL (diseño + GEO/SEO) — ' + BASE + ' ===\n');
const bad = results.filter(r => r.problems?.length);
const skipped = results.filter(r => r.skip);
const errored = results.filter(r => r.error);
console.log(`Total rutas: ${results.length}`);
console.log(`✅ Sin problemas: ${results.length - bad.length - skipped.length - errored.length}`);
console.log(`🔴 Con problemas: ${bad.length}`);
console.log(`⏭️  No-200 (404/redirect): ${skipped.length}`);
console.log(`❌ Error de carga: ${errored.length}\n`);
for (const r of results) {
  if (r.error) { console.log(`❌ ${r.route}: ${r.error}`); continue; }
  if (r.skip)  { console.log(`⏭️  ${r.route}: HTTP ${r.status}`); continue; }
  const icon = r.problems.length ? '🔴' : '✅';
  console.log(`${icon} ${r.route}`);
  if (r.problems.length) {
    r.problems.forEach(pr => console.log(`     - ${pr}`));
    console.log(`     [schema: ${r.schemaTypes?.join(', ') || 'ninguno'}]`);
  }
}
// JSON completo para análisis posterior
console.log('\n\n=== JSON COMPLETO ===');
console.log(JSON.stringify(results, null, 2));
