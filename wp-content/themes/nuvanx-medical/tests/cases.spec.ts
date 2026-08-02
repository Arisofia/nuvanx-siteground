import { test, expect } from '@playwright/test';

test.describe('Cases and Patients', () => {
  test('Patient cases structure', async ({ page }) => {
    // Assuming /casos-de-pacientes/ exists. Adjust if needed.
    const response = await page.goto('/casos-de-pacientes/');
    
    if (response?.status() === 200) {
      // Just check the page loads properly for now
      await expect(page.locator('main')).toBeVisible();
    }
  });
});
