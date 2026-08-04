#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const main = document.querySelector('main.nvx-main');
  const pageContent = document.querySelector('.nvx-page__content');
  const brandPage = document.querySelector('.nvx-brand-page');
  const hero = document.querySelector('.nvx-brand-hero');

  return {
    mainComputedWidth: main ? getComputedStyle(main).width : null,
    pageContentComputedWidth: pageContent ? getComputedStyle(pageContent).width : null,
    brandPageComputedWidth: brandPage ? getComputedStyle(brandPage).width : null,
    heroComputedWidth: hero ? getComputedStyle(hero).width : null,
    mainClasses: main ? main.className : null,
    viewportWidth: window.innerWidth
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
