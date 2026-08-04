#!/usr/bin/env node
import { chromium } from 'playwright';
const BASE = 'https://staging2.nuvanx.com';
const routes = ['/equipo-medico/', '/protocolos-signature/', '/remodelacion-corporal-laser-madrid/'];
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
for (const route of routes) {
  const url = `${BASE}${route}`;
  console.log(`\n=== ${route} ===`);
  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 25000 });
    const result = await page.evaluate(() => {
      const brandPages = document.querySelectorAll('.nvx-brand-page');
      const teamCard = document.querySelector('.nvx-brand-card--team');
      const gridContainer = teamCard?.closest('.nvx-equipo-staff-grid');
      const computedStyle = teamCard ? getComputedStyle(teamCard) : null;
      return {
        brandPageCount: brandPages.length,
        brandPageClasses: [...brandPages].map(el => el.className),
        teamCardExists: !!teamCard,
        gridContainerExists: !!gridContainer,
        gridColumn: computedStyle?.gridColumn || null,
        gridTemplateColumns: gridContainer ? getComputedStyle(gridContainer).gridTemplateColumns : null
      };
    });
    console.log(`.nvx-brand-page count: ${result.brandPageCount}`);
    console.log(`Classes: ${result.brandPageClasses.join(' | ')}`);
    console.log(`Team card exists: ${result.teamCardExists}`);
    console.log(`Grid container exists: ${result.gridContainerExists}`);
    console.log(`Grid column: ${result.gridColumn}`);
    console.log(`Grid template columns: ${result.gridTemplateColumns}`);
  } catch (e) {
    console.log(`Error: ${e.message}`);
  }
}
await browser.close();
