import { test, expect } from '@playwright/test';
import { AxeBuilder } from '@axe-core/playwright';

const routes = ['/', '/contacto/', '/blog/', '/404-expected-slug/'];

for (const path of routes) {
  test(`axe-core accessibility on ${path}`, async ({ page }) => {
    await page.goto(path);
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa'])
      .analyze();

    const serious = results.violations.filter(
      (v) => ['serious', 'critical'].includes(v.impact ?? '')
    );
    expect(serious).toEqual([]);
  });
}
