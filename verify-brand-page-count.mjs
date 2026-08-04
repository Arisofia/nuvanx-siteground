#!/usr/bin/env node
import { chromium } from 'playwright';
const url = 'https://staging2.nuvanx.com/protocolos-signature/';
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto(url, { waitUntil: 'networkidle', timeout: 25000 });
const count = await page.evaluate(() => {
  const brandPages = document.querySelectorAll('.nvx-brand-page');
  return {
    count: brandPages.length,
    classes: [...brandPages].map(el => el.className),
    html: [...brandPages].map(el => el.outerHTML.substring(0, 200))
  };
});
console.log(JSON.stringify(count, null, 2));
await browser.close();
