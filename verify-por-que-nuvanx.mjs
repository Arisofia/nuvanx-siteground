#!/usr/bin/env node
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1920, height: 1080 },
  ignoreHTTPSErrors: true
});
const page = await context.newPage();

await page.goto('https://staging2.nuvanx.com/por-que-nuvanx/', { waitUntil: 'networkidle', timeout: 25000 });

const result = await page.evaluate(() => {
  const article = document.querySelector('article.nvx-strategy-page');
  if (!article) {
    return { error: 'No article found' };
  }

  const computed = getComputedStyle(article);

  return {
    viewport: window.innerWidth,
    article: {
      marginLeft: computed.marginLeft,
      marginRight: computed.marginRight,
      marginInline: computed.marginInline,
      width: computed.width,
      maxWidth: computed.maxWidth,
      paddingInline: computed.paddingInline
    },
    articleClasses: article.className
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
