#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1920, height: 1080 },
  ignoreHTTPSErrors: true
});
const page = await context.newPage();

await page.goto('https://staging2.nuvanx.com/endolift-facial-papada-mandibula/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const hero = document.querySelector('.nvx-brand-hero');
  if (!hero) {
    return { error: 'No hero found' };
  }

  const computed = getComputedStyle(hero);
  const computedInner = getComputedStyle(hero.querySelector('.nvx-brand-hero__inner'));

  return {
    viewport: window.innerWidth,
    hero: {
      marginInline: computed.marginInline,
      marginLeft: computed.marginLeft,
      marginRight: computed.marginRight,
      width: computed.width,
      maxWidth: computed.maxWidth,
      minHeight: computed.minHeight,
      height: computed.height,
      background: computed.background,
      position: computed.position,
      display: computed.display
    },
    heroInner: {
      width: computedInner.width,
      height: computedInner.height
    }
  };
});

console.log(JSON.stringify(result, null, 2));

// Keep browser open for 30 seconds for manual verification
console.log('\n⏳ Browser open for 30 seconds for manual verification.');
await new Promise(resolve => setTimeout(resolve, 30000));
