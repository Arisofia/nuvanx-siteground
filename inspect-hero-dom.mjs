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

  // Get the inner structure
  const inner = hero.querySelector('.nvx-brand-hero__inner');
  const shell = hero.querySelector('.nvx-shell');
  const copy = hero.querySelector('.nvx-brand-hero__copy');

  return {
    heroChildren: Array.from(hero.children).map(c => ({
      tagName: c.tagName,
      className: c.className,
      computedWidth: getComputedStyle(c).width
    })),
    innerExists: !!inner,
    innerClasses: inner ? inner.className : null,
    innerComputedWidth: inner ? getComputedStyle(inner).width : null,
    shellExists: !!shell,
    shellClasses: shell ? shell.className : null,
    shellComputedWidth: shell ? getComputedStyle(shell).width : null,
    copyExists: !!copy,
    copyClasses: copy ? copy.className : null,
    copyComputedWidth: copy ? getComputedStyle(copy).width : null
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
