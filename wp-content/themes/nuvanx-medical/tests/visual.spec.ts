import { test, expect } from '@playwright/test';

const routes = [
  { name: 'home', path: '/' },
  { name: 'contact', path: '/contacto/' },
];

for (const route of routes) {
  test(`visual desktop - ${route.name}`, async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(route.path);
    await expect(page).toHaveScreenshot(`${route.name}-desktop.png`, {
      maxDiffPixelRatio: 0.01,
    });
  });

  test(`visual mobile - ${route.name}`, async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(route.path);
    await expect(page).toHaveScreenshot(`${route.name}-mobile.png`, {
      maxDiffPixelRatio: 0.01,
    });
  });
}
