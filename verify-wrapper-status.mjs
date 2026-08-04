#!/usr/bin/env node
import { chromium } from 'playwright';
const pages = [
  'https://staging2.nuvanx.com/endolift-facial-papada-mandibula/',
  'https://staging2.nuvanx.com/medicina-estetica/',
  'https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/'
];
const browser = await chromium.launch();
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage({ viewport: { width: 1440, height: 900 } });
await page.route('**/*', route => {
  route.continue({ headers: { 'Cache-Control': 'no-cache' } });
});
for (const url of pages) {
  console.log(`\n=== ${url} ===`);
  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 25000 });
    const result = await page.evaluate(() => {
      const brandPages = document.querySelectorAll('.nvx-brand-page');
      const hero = document.querySelector('.nvx-brand-hero');
      const sectionInner = document.querySelector('.nvx-brand-section__inner, .nvx-aes-section__inner');
      return {
        brandPageCount: brandPages.length,
        heroStyle: hero ? getComputedStyle(hero).marginInline : null,
        heroWidth: hero ? getComputedStyle(hero).width : null,
        sectionInnerStyle: sectionInner ? getComputedStyle(sectionInner).marginInline : null,
        sectionInnerWidth: sectionInner ? getComputedStyle(sectionInner).width : null
      };
    });
    console.log(`Brand pages: ${result.brandPageCount}`);
    console.log(`Hero margin: ${result.heroStyle}`);
    console.log(`Hero width: ${result.heroWidth}`);
    console.log(`Section inner margin: ${result.sectionInnerStyle}`);
    console.log(`Section inner width: ${result.sectionInnerWidth}`);
  } catch (e) {
    console.log(`Error: ${e.message}`);
  }
}
await context.close();
await browser.close();
