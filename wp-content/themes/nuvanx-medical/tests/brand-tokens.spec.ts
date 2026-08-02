import { test } from '@playwright/test';

test('Brand tokens for CTAs and icons', async ({ page }) => {
  await page.goto('/contacto/');

  const allowedFonts = ['Manrope'];
  const allowedColors = ['rgb(0, 0, 0)', 'rgb(255, 255, 255)'];

  const ctas = await page.locator('a.nvx-button, a.nvx-btn').elementHandles();
  for (const el of ctas) {
    const styles = await el.evaluate((node) => {
      const cs = getComputedStyle(node);
      return { fontFamily: cs.fontFamily, color: cs.color };
    });

    if (!allowedFonts.some((f) => styles.fontFamily.includes(f))) {
      throw new Error(`Unexpected CTA font: ${styles.fontFamily}`);
    }
    if (!allowedColors.includes(styles.color)) {
      throw new Error(`Unexpected CTA color: ${styles.color}`);
    }
  }

  const uses = await page.locator('svg use[href^="#"]').elementHandles();
  for (const use of uses) {
    const href = await use.evaluate(
      (el) => el.getAttribute('href') || el.getAttribute('xlink:href')
    );
    if (!href) continue;
    const id = href.replace('#', '');
    const ok = await page.evaluate((id) => {
      const symbol = document.getElementById(id);
      if (!symbol) return false;
      const rect = symbol.getBoundingClientRect();
      return rect.width > 0;
    }, id);
    if (!ok) {
      throw new Error(`Icon "${id}" not resolved or zero width`);
    }
  }
});
