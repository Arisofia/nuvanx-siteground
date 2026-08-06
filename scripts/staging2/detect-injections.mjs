import { chromium } from 'playwright';

async function detectInjections() {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  console.log('=== Detecting SiteGround and WordPress Injections ===\n');

  await page.goto('https://staging2.nuvanx.com/');

  const injectionAnalysis = await page.evaluate(() => {
    const results = {
      siteground: {
        optimizerAssets: false,
        optimizerCombined: false,
        sgoCache: false,
        optimizerClasses: false,
      },
      wordpress: {
        blockBlocks: false,
        blockStyles: false,
        extraScripts: 0,
        extraStyles: 0,
      },
      inlineStyles: 0,
      externalResources: [],
    };

    // Check for SiteGround Optimizer assets
    const sgAssets = document.querySelectorAll(
      'link[href*="siteground"], script[src*="siteground"]'
    );
    results.siteground.optimizerAssets = sgAssets.length > 0;

    const sgCombined = document.querySelectorAll(
      'link[href*="siteground-optimizer-combined"]'
    );
    results.siteground.optimizerCombined = sgCombined.length > 0;

    // Check for SGO cache classes
    const sgoClasses = document.querySelectorAll(
      '[class*="sgo-"], [id*="sgo-"]'
    );
    results.siteground.sgoClasses = sgoClasses.length > 0;

    // Check for WordPress block blocks
    results.wordpress.blockBlocks =
      document.querySelectorAll('.wp-block').length > 0;

    // Check for WordPress block stylesheets
    const blockStyles = document.querySelectorAll(
      'link[href*="wp-includes/css/dist/block-library"]'
    );
    results.wordpress.blockStyles = blockStyles.length > 0;

    // Count inline styles
    const inlineStyles = document.querySelectorAll('style');
    results.inlineStyles = inlineStyles.length;

    // Count external scripts
    const scripts = document.querySelectorAll('script[src]');
    results.wordpress.extraScripts = scripts.length;

    // Count external stylesheets
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    results.wordpress.extraStyles = links.length;

    // Get all external resources
    const resources = [];
    scripts.forEach((script) => {
      if (script.src) resources.push({ type: 'script', src: script.src });
    });
    links.forEach((link) => {
      if (link.href) resources.push({ type: 'stylesheet', href: link.href });
    });
    results.externalResources = resources;

    // Check for CSS minification by SiteGround
    const nvxCss = document.querySelectorAll('link[href*="nvx-"]');
    let hasMinified = false;
    nvxCss.forEach((link) => {
      if (link.href.includes('.min.css')) {
        hasMinified = true;
      }
    });
    results.siteground.minifiedNvx = hasMinified;

    return results;
  });

  console.log('SiteGround Optimizer injection:', injectionAnalysis.siteground);
  console.log('WordPress blocks injection:', injectionAnalysis.wordpress);
  console.log('Inline styles count:', injectionAnalysis.inlineStyles);
  console.log('External scripts:', injectionAnalysis.wordpress.extraScripts);
  console.log('External stylesheets:', injectionAnalysis.wordpress.extraStyles);
  console.log('Minified nvx CSS:', injectionAnalysis.siteground.minifiedNvx);

  // Check specific external resources
  console.log('\n1. Checking for SiteGround-specific resources...');
  const sgResources = injectionAnalysis.externalResources.filter(
    (r) => r.src?.includes('siteground') || r.href?.includes('siteground')
  );
  console.log(`   SiteGround resources found: ${sgResources.length}`);
  sgResources.forEach((r) => {
    console.log(`   - ${r.type}: ${r.src || r.href}`);
  });

  console.log('\n2. Checking for WordPress block library resources...');
  const wpBlockResources = injectionAnalysis.externalResources.filter(
    (r) => r.src?.includes('wp-includes') || r.href?.includes('wp-includes')
  );
  console.log(`   WordPress block resources found: ${wpBlockResources.length}`);
  wpBlockResources.forEach((r) => {
    console.log(`   - ${r.type}: ${r.src || r.href}`);
  });

  // Check if WordPress injects inline styles that override design tokens
  console.log('\n3. Checking for inline style overrides...');
  await page.goto('https://staging2.nuvanx.com/');

  const inlineStyleOverrides = await page.evaluate(() => {
    const styleElements = document.querySelectorAll('style');
    let overrides = 0;

    styleElements.forEach((style) => {
      const content = style.textContent;
      // Check if inline styles contain color or background properties
      if (
        content.includes('color:') ||
        content.includes('background:') ||
        content.includes('background-color:')
      ) {
        overrides++;
      }
    });

    return overrides;
  });

  console.log(
    `   Inline styles with color/background properties: ${inlineStyleOverrides}`
  );

  // Check if WordPress core CSS overrides theme CSS
  console.log('\n4. Checking for WordPress core CSS loaded...');
  await page.goto('https://staging2.nuvanx.com/');

  const wpCoreCSS = await page.evaluate(() => {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    const wpCore = [];

    links.forEach((link) => {
      const { href } = link;
      if (href.includes('wp-includes/css') || href.includes('wp-admin/css')) {
        wpCore.push(href);
      }
    });

    return wpCore;
  });

  console.log(`   WordPress core CSS files: ${wpCoreCSS.length}`);
  wpCoreCSS.forEach((css) => {
    console.log(`   - ${css}`);
  });

  await context.close();
  await browser.close();
  console.log('\n=== Injection Detection Complete ===');
}

detectInjections().catch(console.error);
