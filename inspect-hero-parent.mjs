#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const hero = document.querySelector('.nvx-brand-hero');
  if (!hero) {
    return { error: 'No hero found' };
  }

  const parent = hero.parentElement;
  const grandparent = parent ? parent.parentElement : null;

  return {
    heroComputedWidth: getComputedStyle(hero).width,
    parentTagName: parent ? parent.tagName : null,
    parentClassName: parent ? parent.className : null,
    parentComputedWidth: parent ? getComputedStyle(parent).width : null,
    grandparentTagName: grandparent ? grandparent.tagName : null,
    grandparentClassName: grandparent ? grandparent.className : null,
    grandparentComputedWidth: grandparent ? getComputedStyle(grandparent).width : null
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
