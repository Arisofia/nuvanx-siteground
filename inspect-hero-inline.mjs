#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({
  ignoreHTTPSErrors: true
});
const page = await context.newPage();

await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const hero = document.querySelector('.nvx-brand-hero');
  if (!hero) {
    return { error: 'No hero found' };
  }

  // Check inline style
  const inlineStyle = hero.getAttribute('style');

  // Check all style properties
  const computed = getComputedStyle(hero);

  return {
    inlineStyle: inlineStyle,
    hasInlineStyle: !!inlineStyle,
    computed: {
      margin: computed.margin,
      marginInline: computed.marginInline,
      marginLeft: computed.marginLeft,
      marginRight: computed.marginRight,
      marginTop: computed.marginTop,
      marginBottom: computed.marginBottom,
      width: computed.width,
      maxWidth: computed.maxWidth
    }
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
