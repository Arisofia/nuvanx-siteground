import { test, expect } from '@playwright/test';

const CRITICAL_ROUTES = [
  'https://staging2.nuvanx.com/',
  'https://staging2.nuvanx.com/contacto/',
  'https://staging2.nuvanx.com/blog/',
  'https://staging2.nuvanx.com/tratamientos/',
  'https://staging2.nuvanx.com/soluciones-medicas/',
  'https://staging2.nuvanx.com/clinicas/',
  'https://staging2.nuvanx.com/madrid/valoracion/',
  'https://staging2.nuvanx.com/equipo-medico/',
  'https://staging2.nuvanx.com/nosotros/',
];

test.describe('Visual Design Audit', () => {
  test.describe('Screenshot Capture', () => {
    CRITICAL_ROUTES.forEach((route, index) => {
      test(`capture screenshot for route ${index + 1}`, async ({ page }) => {
        await page.goto(route);
        await page.waitForLoadState('networkidle');
        
        const routeName = route.replace('https://staging2.nuvanx.com', '').replace(/\//g, '-') || 'home';
        
        // Full page screenshot
        await page.screenshot({
          path: `tests/screenshots/${routeName}-full.png`,
          fullPage: true
        });
        
        // Viewport screenshot
        await page.screenshot({
          path: `tests/screenshots/${routeName}-viewport.png`,
          fullPage: false
        });
      });
    });
  });

  test.describe('Design Measurements', () => {
    CRITICAL_ROUTES.forEach((route, index) => {
      test(`measure design metrics for route ${index + 1}`, async ({ page }) => {
        await page.goto(route);
        await page.waitForLoadState('networkidle');
        
        const routeName = route.replace('https://staging2.nuvanx.com', '').replace(/\//g, '-') || 'home';
        
        const metrics = await page.evaluate(() => {
          const results: {
            headerHeight?: number;
            firstSectionPaddingTop?: number;
            firstSectionPaddingBottom?: number;
            h1FontSize?: number;
            h1LineHeight?: number;
            hasTokens: {
              nvxInk: boolean;
              nvxSpace2: boolean;
              nvxTypeH1: boolean;
              nvxHeaderHeight: boolean;
            };
          } = {};
          
          // Header measurement - try multiple selectors
          const header = document.querySelector('header') ||
                         document.querySelector('.nvx-header') ||
                         document.querySelector('[class*="header"]') ||
                         document.querySelector('.site-header') ||
                         document.querySelector('#masthead') ||
                         document.querySelector('.header-main');
          if (header) {
            const rect = header.getBoundingClientRect();
            results.headerHeight = Math.round(rect.height);
          }
          
          // First section padding
          const firstSection = document.querySelector('section, [class*="section"]');
          if (firstSection) {
            const styles = window.getComputedStyle(firstSection);
            results.firstSectionPaddingTop = parseInt(styles.paddingTop);
            results.firstSectionPaddingBottom = parseInt(styles.paddingBottom);
          }
          
          // H1 measurement
          const h1 = document.querySelector('h1');
          if (h1) {
            const styles = window.getComputedStyle(h1);
            results.h1FontSize = parseInt(styles.fontSize);
            results.h1LineHeight = parseFloat(styles.lineHeight);
          }
          
          // CSS tokens check
          const root = document.documentElement;
          const computed = getComputedStyle(root);
          results.hasTokens = {
            nvxInk: computed.getPropertyValue('--nvx-ink') !== '',
            nvxSpace2: computed.getPropertyValue('--nvx-space-2') !== '',
            nvxTypeH1: computed.getPropertyValue('--nvx-type-h1') !== '',
            nvxHeaderHeight: computed.getPropertyValue('--nvx-header-height') !== ''
          };
          
          return results;
        });
        
        console.log(`📊 ${routeName} metrics:`, JSON.stringify(metrics, null, 2));
      });
    });
  });

  test.describe('Header Consistency Check', () => {
    const headerHeights: number[] = [];
    
    CRITICAL_ROUTES.forEach((route, index) => {
      test(`check header height for route ${index + 1}`, async ({ page }) => {
        await page.goto(route);
        await page.waitForLoadState('networkidle');
        
        const header = page.locator('header, .nvx-header, [class*="header"], .site-header, #masthead').first();
        if (await header.count() > 0) {
          const height = await header.evaluate(el => {
            const rect = el.getBoundingClientRect();
            return Math.round(rect.height);
          });
          
          headerHeights.push(height);
          console.log(`📏 Header height for route ${index + 1}: ${height}px`);
          
          // Header should be close to 80px (±10px tolerance)
          expect(height).toBeLessThanOrEqual(90);
          expect(height).toBeGreaterThanOrEqual(70);
        }
      });
    });
    
    test('header heights are consistent across pages', async () => {
      if (headerHeights.length > 1) {
        const maxHeight = Math.max(...headerHeights);
        const minHeight = Math.min(...headerHeights);
        const variance = maxHeight - minHeight;
        
        console.log(`📊 Header height variance: ${variance}px`);
        
        // Variance should be minimal (≤20px)
        expect(variance).toBeLessThanOrEqual(20);
      }
    });
  });
});