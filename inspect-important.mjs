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

  // Get inline styles (which override everything)
  const inlineStyle = hero.getAttribute('style');

  // Check if there's an inline margin style
  if (inlineStyle && (inlineStyle.includes('margin') || inlineStyle.includes('width'))) {
    return {
      hasInlineStyle: true,
      inlineStyle: inlineStyle
    };
  }

  // Check computed styles in detail
  const computed = getComputedStyle(hero);
  return {
    hasInlineStyle: false,
    computedMarginInline: computed.marginInline,
    computedMarginLeft: computed.marginLeft,
    computedMarginRight: computed.marginRight,
    computedWidth: computed.width,
    computedMaxWidth: computed.maxWidth,
    computedPosition: computed.position,
    computedDisplay: computed.display
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
