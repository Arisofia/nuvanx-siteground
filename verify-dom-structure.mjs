#!/usr/bin/env node
import { chromium } from 'playwright';
const url = 'https://staging2.nuvanx.com/protocolos-signature/';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto(url, { waitUntil: 'networkidle', timeout: 25000 });
const structure = await page.evaluate(() => {
  const main = document.querySelector('main#nvx-main');
  return {
    mainHTML: main?.outerHTML.substring(0, 500) || null,
    mainClasses: main?.className || null,
    childrenCount: main?.children.length || 0,
    firstChildHTML: main?.firstElementChild?.outerHTML.substring(0, 300) || null,
    firstChildClasses: main?.firstElementChild?.className || null
  };
});
console.log(JSON.stringify(structure, null, 2));
await browser.close();
