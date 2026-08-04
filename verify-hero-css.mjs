#!/usr/bin/env node
import { chromium } from 'playwright';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });
const result = await page.evaluate(() => {
  const hero = document.querySelector('.nvx-brand-hero');
  const brandPage = document.querySelector('.nvx-brand-page');
  const pageContent = document.querySelector('.nvx-page__content');
  return {
    heroExists: !!hero,
    brandPageExists: !!brandPage,
    pageContentExists: !!pageContent,
    heroClasses: hero ? hero.className : null,
    brandPageClasses: brandPage ? brandPage.className : null,
    pageContentClasses: pageContent ? pageContent.className : null,
    heroParent: hero ? hero.parentElement.className : null,
    computedHero: hero ? {
      marginInline: getComputedStyle(hero).marginInline,
      marginLeft: getComputedStyle(hero).marginLeft,
      marginRight: getComputedStyle(hero).marginRight,
      width: getComputedStyle(hero).width,
      maxWidth: getComputedStyle(hero).maxWidth,
      position: getComputedStyle(hero).position,
      display: getComputedStyle(hero).display
    } : null
  };
});
console.log(JSON.stringify(result, null, 2));
await browser.close();
