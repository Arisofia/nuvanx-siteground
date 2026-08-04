#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage();

await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const html = document.documentElement;
  const body = document.body;
  const main = document.querySelector('main.nvx-main');

  return {
    htmlComputedWidth: getComputedStyle(html).width,
    bodyComputedWidth: getComputedStyle(body).width,
    mainComputedWidth: main ? getComputedStyle(main).width : null,
    mainInlineWidth: main ? getComputedStyle(main).width : null,
    htmlClientWidth: html.clientWidth,
    bodyClientWidth: body.clientWidth,
    mainClientWidth: main ? main.clientWidth : null,
    windowInnerWidth: window.innerWidth,
    windowInnerHeight: window.innerHeight,
    windowOuterWidth: window.outerWidth,
    screenAvailWidth: screen.availWidth
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
