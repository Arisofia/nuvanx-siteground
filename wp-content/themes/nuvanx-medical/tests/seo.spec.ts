import { test, expect } from '@playwright/test';

test.describe('SEO Governance', () => {
  test('404 page has no canonical or hreflang', async ({ page }) => {
    const response = await page.goto('/ruta-que-no-existe-123/');
    expect(response?.status()).toBe(404);
    
    await expect(page.locator('link[rel="canonical"]')).toHaveCount(0);
    await expect(page.locator('link[rel="alternate"][hreflang]')).toHaveCount(0);
  });

  test('Valid page has exactly one canonical and hreflang', async ({ page }) => {
    await page.goto('/madrid/valoracion/');
    
    await expect(page.locator('link[rel="canonical"]')).toHaveCount(1);
    await expect(page.locator('link[rel="alternate"][hreflang="es-ES"]')).toHaveCount(1);
    await expect(page.locator('link[rel="alternate"][hreflang="x-default"]')).toHaveCount(1);
  });

  test('Yoast schema deduplicates @id', async ({ page }) => {
    await page.goto('/madrid/valoracion/');
    
    const scripts = await page.locator('script[type="application/ld+json"][class="yoast-schema-graph"]').all();
    expect(scripts.length).toBeGreaterThan(0);
    
    for (const script of scripts) {
      const content = await script.textContent();
      if (!content) continue;
      
      const data = JSON.parse(content);
      const graph = data['@graph'] || [];
      const ids = graph.map((node: any) => node['@id']).filter(Boolean);
      const uniqueIds = new Set(ids);
      
      expect(ids.length).toBe(uniqueIds.size);
    }
  });
});
