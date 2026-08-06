import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

async function debugA11y() {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log('=== Debugging Accessibility Issues ===\n');
  
  // Test skip-link focus
  console.log('1. Testing skip-link focus on home page...');
  await page.goto('https://staging2.nuvanx.com/');
  await page.keyboard.press('Tab');
  const skipLinkFocused = await page.evaluate(() => {
    const active = document.activeElement;
    return active && active.classList.contains('nvx-skip-link');
  });
  console.log(`   Skip-link focused: ${skipLinkFocused ? '✅' : '❌'}`);
  
  if (skipLinkFocused) {
    const computedStyles = await page.evaluate(() => {
      const el = document.activeElement;
      const styles = window.getComputedStyle(el);
      return {
        zIndex: styles.zIndex,
        top: styles.top,
        opacity: styles.opacity,
        visibility: styles.visibility,
        display: styles.display
      };
    });
    console.log('   Skip-link computed styles:', computedStyles);
  }
  
  // Test footer grid on mobile
  console.log('\n2. Testing footer grid on mobile (375px)...');
  await page.setViewportSize({ width: 375, height: 667 });
  await page.goto('https://staging2.nuvanx.com/');
  const footerGrid = await page.evaluate(() => {
    const footerMain = document.querySelector('.nvx-footer__main');
    if (!footerMain) return { found: false };
    const styles = window.getComputedStyle(footerMain);
    return {
      found: true,
      gridTemplateColumns: styles.gridTemplateColumns,
      display: styles.display
    };
  });
  console.log('   Footer grid:', footerGrid);
  
  // Test header background color
  console.log('\n3. Testing header background color...');
  await page.setViewportSize({ width: 1920, height: 1080 });
  await page.goto('https://staging2.nuvanx.com/');
  const headerColor = await page.evaluate(() => {
    const header = document.querySelector('.nvx-header');
    if (!header) return { found: false };
    const styles = window.getComputedStyle(header);
    return {
      found: true,
      backgroundColor: styles.backgroundColor,
      borderColor: styles.borderBottomColor
    };
  });
  console.log('   Header colors:', headerColor);
  
  // Test NAP icons in contacto page
  console.log('\n4. Testing NAP icons in contacto page...');
  await page.goto('https://staging2.nuvanx.com/contacto/');
  const napIcons = await page.evaluate(() => {
    const locationIcon = document.querySelector('use[href="#icon-location"]');
    const phoneIcon = document.querySelector('use[href="#icon-phone"]');
    return {
      locationIcon: !!locationIcon,
      phoneIcon: !!phoneIcon,
      locationIconCount: document.querySelectorAll('use[href="#icon-location"]').length,
      phoneIconCount: document.querySelectorAll('use[href="#icon-phone"]').length
    };
  });
  console.log('   NAP icons:', napIcons);
  
  // Test valoración form
  console.log('\n5. Testing valoración form...');
  await page.goto('https://staging2.nuvanx.com/madrid/valoracion/');
  const formCheck = await page.evaluate(() => {
    const forms = document.querySelectorAll('form');
    const hubspotForms = document.querySelectorAll('[data-hsbspt-form-id]');
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]');
    const submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
    
    return {
      totalForms: forms.length,
      hubspotForms: hubspotForms.length,
      textInputs: inputs.length,
      submitButtons: submitButtons.length,
      hasNameField: !!document.querySelector('input[name*="name"], input[name*="nombre"]'),
      hasEmailField: !!document.querySelector('input[type="email"], input[name*="email"]'),
      hasPhoneField: !!document.querySelector('input[type="tel"], input[name*="phone"], input[name*="telefono"]')
    };
  });
  console.log('   Form analysis:', formCheck);
  
  // Run Axe on a sample page to get specific contrast issues
  console.log('\n6. Running Axe accessibility scan on home page...');
  await page.goto('https://staging2.nuvanx.com/');
  const axeResults = await new AxeBuilder({ page }).analyze();
  
  const contrastViolations = axeResults.violations.filter(v => 
    v.id === 'color-contrast' || v.id === 'color-contrast-enhanced'
  );
  
  console.log(`   Found ${contrastViolations.length} contrast violations`);
  if (contrastViolations.length > 0) {
    contrastViolations.forEach((v, i) => {
      console.log(`   Violation ${i + 1}: ${v.description}`);
      console.log(`   Affected elements: ${v.nodes.length}`);
      if (v.nodes.length > 0) {
        const node = v.nodes[0];
        console.log(`   Target: ${node.target.join(', ')}`);
        console.log(`   HTML: ${node.html.substring(0, 100)}...`);
      }
    });
  }
  
  // Test social media links in footer
  console.log('\n7. Testing social media links in footer...');
  await page.goto('https://staging2.nuvanx.com/');
  const socialLinks = await page.evaluate(() => {
    const footer = document.querySelector('.nvx-footer');
    if (!footer) return { found: false };
    const socialLinks = footer.querySelectorAll('a[href*="instagram"], a[href*="facebook"]');
    return {
      found: true,
      socialLinks: socialLinks.length,
      hasTargetBlank: Array.from(socialLinks).every(a => a.target === '_blank'),
      hasRelNoopener: Array.from(socialLinks).every(a => a.rel.includes('noopener'))
    };
  });
  console.log('   Social links:', socialLinks);
  
  await context.close();
  await browser.close();
  console.log('\n=== Debug Complete ===');
}

debugA11y().catch(console.error);