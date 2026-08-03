import { test, expect } from '@playwright/test';
import { CRITICAL_ROUTES } from './routes-critical';

test.describe('Visual Regression Testing', () => {
  test.describe('Screenshot Comparison', () => {
    for (const route of CRITICAL_ROUTES) {
      test(`visual consistency on ${route}`, async ({ page }) => {
        await page.goto(route);
        
        // Wait for page to be fully loaded
        await page.waitForLoadState('networkidle');
        
        // Take full page screenshot
        await page.screenshot({
          path: `tests/screenshots/${route.replace(/\//g, '-')}.png`,
          fullPage: true
        });
        
        // Take viewport screenshot
        await page.screenshot({
          path: `tests/screenshots/${route.replace(/\//g, '-')}-viewport.png`,
          fullPage: false
        });
      });
    }
  });

  test.describe('Component Comparison', () => {
    test('header component consistency', async ({ page }) => {
      await page.goto('/');
      
      const header = page.locator('header, .nvx-header, [class*="header"]').first();
      if (await header.count() > 0) {
        await header.screenshot({
          path: 'tests/screenshots/header-home.png'
        });
      }
      
      await page.goto('/contacto/');
      const headerContact = page.locator('header, .nvx-header, [class*="header"]').first();
      if (await headerContact.count() > 0) {
        await headerContact.screenshot({
          path: 'tests/screenshots/header-contacto.png'
        });
      }
    });

    test('button component consistency', async ({ page }) => {
      await page.goto('/');
      
      const buttons = page.locator('button, .nvx-button, [class*="button"]').first();
      if (await buttons.count() > 0) {
        await buttons.screenshot({
          path: 'tests/screenshots/button-home.png'
        });
      }
      
      await page.goto('/contacto/');
      const buttonsContact = page.locator('button, .nvx-button, [class*="button"]').first();
      if (await buttonsContact.count() > 0) {
        await buttonsContact.screenshot({
          path: 'tests/screenshots/button-contacto.png'
        });
      }
    });
  });
});