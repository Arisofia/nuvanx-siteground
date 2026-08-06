import { chromium } from 'playwright';

async function debugGutenbergBlocks() {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log('=== Debugging Gutenberg Blocks Design System Integration ===\n');
  
  // Test known Gutenberg block pages
  const gutenbergPages = [
    '/blog/',  // Blog posts likely use Gutenberg blocks
    '/casos-de-pacientes/',  // Case studies likely use Gutenberg blocks
  ];
  
  for (const route of gutenbergPages) {
    console.log(`\n1. Testing ${route} for Gutenberg blocks...`);
    await page.goto(`https://staging2.nuvanx.com${route}`);
    
    const blockAnalysis = await page.evaluate(() => {
      const results = {
        wpBlockGroup: false,
        wpBlockImage: false,
        wpBlockQuote: false,
        wpBlockButton: false,
        wpBlockTable: false,
        wpBlockSeparator: false,
        nvxSectionClass: false,
        usesDesignTokens: false,
        computedStyles: {}
      };
      
      // Check for Gutenberg blocks
      results.wpBlockGroup = document.querySelectorAll('.wp-block-group').length > 0;
      results.wpBlockImage = document.querySelectorAll('.wp-block-image').length > 0;
      results.wpBlockQuote = document.querySelectorAll('.wp-block-quote').length > 0;
      results.wpBlockButton = document.querySelectorAll('.wp-block-button').length > 0;
      results.wpBlockTable = document.querySelectorAll('.wp-block-table').length > 0;
      results.wpBlockSeparator = document.querySelectorAll('.wp-block-separator').length > 0;
      results.nvxSectionClass = document.querySelectorAll('.nvx-section').length > 0;
      
      // Check if elements use design tokens
      const elementsWithTokens = document.querySelectorAll('[class*="nvx-"]');
      results.usesDesignTokens = elementsWithTokens.length > 0;
      
      // Sample computed styles for a block if exists
      const wpBlockGroup = document.querySelector('.wp-block-group');
      if (wpBlockGroup) {
        const styles = window.getComputedStyle(wpBlockGroup);
        results.computedStyles.wpBlockGroup = {
          padding: styles.padding,
          color: styles.color,
          backgroundColor: styles.backgroundColor,
          fontFamily: styles.fontFamily
        };
      }
      
      const wpBlockButton = document.querySelector('.wp-block-button');
      if (wpBlockButton) {
        const styles = window.getComputedStyle(wpBlockButton);
        results.computedStyles.wpBlockButton = {
          backgroundColor: styles.backgroundColor,
          color: styles.color,
          padding: styles.padding,
          borderRadius: styles.borderRadius
        };
      }
      
      return results;
    });
    
    console.log(`   Block analysis:`, blockAnalysis);
    
    // Check if computed styles use design tokens
    if (blockAnalysis.computedStyles.wpBlockGroup) {
      const styles = blockAnalysis.computedStyles.wpBlockGroup;
      console.log(`   wp-block-group computed styles:`, styles);
      console.log(`   Uses design tokens: ${styles.padding.includes('var') || styles.color.includes('var')}`);
    }
    
    if (blockAnalysis.computedStyles.wpBlockButton) {
      const styles = blockAnalysis.computedStyles.wpBlockButton;
      console.log(`   wp-block-button computed styles:`, styles);
      console.log(`   Uses design tokens: ${styles.backgroundColor.includes('var') || styles.color.includes('var')}`);
    }
  }
  
  // Test for specific design token usage in content
  console.log('\n2. Testing design token usage in page content...');
  await page.goto('https://staging2.nuvanx.com/blog/');
  
  const tokenUsage = await page.evaluate(() => {
    const mainContent = document.querySelector('main, #nvx-main, [role="main"]');
    if (!mainContent) return { found: false };
    
    const allElements = mainContent.querySelectorAll('*');
    let elementsWithCSSVars = 0;
    let elementsWithNvxClasses = 0;
    
    allElements.forEach(el => {
      const computedStyle = window.getComputedStyle(el);
      const hasCSSVar = Object.values(computedStyle).some(val => 
        typeof val === 'string' && val.includes('var(--nvx-')
      );
      if (hasCSSVar) elementsWithCSSVars++;
      
      if (el.className && el.className.includes('nvx-')) {
        elementsWithNvxClasses++;
      }
    });
    
    return {
      found: true,
      totalElements: allElements.length,
      elementsWithCSSVars,
      elementsWithNvxClasses,
      cssVarPercentage: (elementsWithCSSVars / allElements.length * 100).toFixed(2)
    };
  });
  
  console.log(`   Token usage:`, tokenUsage);
  
  // Check for CSS variables loaded in page
  console.log('\n3. Checking loaded CSS variables...');
  await page.goto('https://staging2.nuvanx.com/');
  
  const cssVariables = await page.evaluate(() => {
    const root = document.documentElement;
    const computedStyle = window.getComputedStyle(root);
    
    // Check for key design tokens
    const tokens = [
      '--nvx-surface-base',
      '--nvx-light', 
      '--nvx-ink',
      '--nvx-space-1',
      '--nvx-space-2',
      '--nvx-space-3',
      '--nvx-space-4',
      '--nvx-space-6',
      '--nvx-space-8',
      '--nvx-space-12',
      '--nvx-type-body',
      '--nvx-type-nav',
      '--nvx-color-paper',
      '--nvx-color-line'
    ];
    
    const availableTokens = {};
    tokens.forEach(token => {
      const value = computedStyle.getPropertyValue(token);
      if (value && value !== '') {
        availableTokens[token] = value;
      }
    });
    
    return availableTokens;
  });
  
  console.log(`   Available design tokens:`, Object.keys(cssVariables).length);
  console.log(`   Sample tokens:`, Object.entries(cssVariables).slice(0, 5));
  
  // Test specific Gutenberg block with embedded content
  console.log('\n4. Testing embedded content styling...');
  await page.goto('https://staging2.nuvanx.com/casos-de-pacientes/');
  
  const embeddedContent = await page.evaluate(() => {
    const prose = document.querySelector('.nvx-blog-prose, .nvx-prose, .entry-content');
    if (!prose) return { found: false };
    
    const paragraphs = prose.querySelectorAll('p');
    const headings = prose.querySelectorAll('h1, h2, h3, h4, h5, h6');
    const images = prose.querySelectorAll('img');
    
    // Check if these elements use design tokens
    let paragraphsUseTokens = 0;
    let headingsUseTokens = 0;
    
    paragraphs.forEach(p => {
      const styles = window.getComputedStyle(p);
      if (styles.color.includes('var') || styles.fontSize.includes('var')) {
        paragraphsUseTokens++;
      }
    });
    
    headings.forEach(h => {
      const styles = window.getComputedStyle(h);
      if (styles.color.includes('var') || styles.fontSize.includes('var') || styles.fontFamily.includes('var')) {
        headingsUseTokens++;
      }
    });
    
    return {
      found: true,
      totalParagraphs: paragraphs.length,
      paragraphsUseTokens,
      totalHeadings: headings.length,
      headingsUseTokens,
      totalImages: images.length
    };
  });
  
  console.log(`   Embedded content analysis:`, embeddedContent);
  
  await context.close();
  await browser.close();
  console.log('\n=== Gutenberg Block Debug Complete ===');
}

debugGutenbergBlocks().catch(console.error);