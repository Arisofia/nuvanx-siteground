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
  const pageContent = hero.closest('.nvx-page__content');
  const brandPage = hero.closest('.nvx-brand-page');

  // Get computed margin values
  const computed = getComputedStyle(hero);
  const marginInline = computed.marginInline;
  const marginLeft = computed.marginLeft;
  const marginRight = computed.marginRight;

  // Get all CSS rules affecting this element
  const styleDeclarations = [];
  const sheets = document.styleSheets;

  for (const sheet of sheets) {
    try {
      const rules = sheet.cssRules || sheet.rules;
      for (const rule of rules) {
        if (rule.type === CSSRule.STYLE_RULE) {
          const selector = rule.selectorText;
          if (hero.matches(selector)) {
            const marginInlineRule = rule.style.marginInline;
            const marginLeftRule = rule.style.marginLeft;
            const marginRightRule = rule.style.marginRight;
            const widthRule = rule.style.width;
            const maxWidthRule = rule.style.maxWidth;

            if (marginInlineRule || marginLeftRule || marginRightRule || widthRule || maxWidthRule) {
              styleDeclarations.push({
                selector: selector,
                marginInline: marginInlineRule,
                marginLeft: marginLeftRule,
                marginRight: marginRightRule,
                width: widthRule,
                maxWidth: maxWidthRule,
                cssText: rule.cssText.substring(0, 200) + '...'
              });
            }
          }
        }
      }
    } catch (e) {
      // Skip rules we can't access (CORS, etc.)
    }
  }

  return {
    heroClasses: hero.className,
    parentClassName: parent ? parent.className : null,
    pageContentExists: !!pageContent,
    pageContentClassName: pageContent ? pageContent.className : null,
    brandPageExists: !!brandPage,
    brandPageClassName: brandPage ? brandPage.className : null,
    computed: {
      marginInline,
      marginLeft,
      marginRight
    },
    styleDeclarations: styleDeclarations.slice(0, 20) // Limit to first 20
  };
});

console.log(JSON.stringify(result, null, 2));

await context.close();
await browser.close();
