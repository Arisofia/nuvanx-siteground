import { test, expect } from '@playwright/test';

const routes = ['/', '/contacto/', '/404-expected-slug/'];

for (const path of routes) {
  test(`structure on ${path}`, async ({ page }) => {
    await page.goto(path);

    await expect(page.locator('main')).toHaveCount(1);
    await expect(page.locator('header[role="banner"]')).toHaveCount(1);
    await expect(page.locator('footer[role="contentinfo"]')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveCount(1);

    const headings = await page.locator('h1, h2, h3, h4, h5, h6').elementHandles();
    let lastLevel = 0;

    for (const h of headings) {
      const tag = await h.evaluate((el) => el.tagName.toLowerCase());
      const level = parseInt(tag.replace('h', ''), 10);
      if (lastLevel && level - lastLevel > 1) {
        throw new Error(`Heading jump from h${lastLevel} to h${level}`);
      }
      lastLevel = level;
    }
  });
}
