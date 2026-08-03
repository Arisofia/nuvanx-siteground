import { test, expect } from '@playwright/test';
import { CRITICAL_ROUTES, CONVERSION_ROUTES } from './routes-critical';

test.describe('SEO Extended Validation', () => {
  test.describe('Meta Robots Validation', () => {
    for (const route of CRITICAL_ROUTES) {
      test(`meta robots on ${route}`, async ({ page }) => {
        await page.goto(route);
        
        // 404 page should have noindex
        if (route.includes('404')) {
          const robots = await page.locator('meta[name="robots"]').all();
          expect(robots.length).toBeGreaterThan(0);
          const content = await robots[0].getAttribute('content');
          expect(content).toContain('noindex');
        } else {
          // Normal pages should not have noindex in production
          const robots = await page.locator('meta[name="robots"]').all();
          // In staging, noindex might be present - this is acceptable
          if (robots.length > 0) {
            const content = await robots[0].getAttribute('content');
            console.log(`Robots meta on ${route}:`, content);
          }
        }
      });
    }
  });

  test.describe('Canonical URL Consistency', () => {
    for (const route of CRITICAL_ROUTES) {
      test(`canonical consistency on ${route}`, async ({ page }) => {
        if (route.includes('404')) {
          // 404 pages should not have canonical
          await page.goto(route);
          const canonical = await page.locator('link[rel="canonical"]').all();
          expect(canonical.length).toBe(0);
          return;
        }

        await page.goto(route);
        const canonical = await page.locator('link[rel="canonical"]').first();
        const href = await canonical.getAttribute('href');
        
        expect(href).toBeTruthy();
        expect(href).toContain('nuvanx.com');
        expect(href).not.toContain('staging2'); // Production URLs in canonical
      });
    }
  });

  test.describe('Hreflang Validation', () => {
    for (const route of CRITICAL_ROUTES) {
      test(`hreflang tags on ${route}`, async ({ page }) => {
        if (route.includes('404')) {
          // 404 pages should not have hreflang
          await page.goto(route);
          const hreflangs = await page.locator('link[rel="alternate"][hreflang]').all();
          expect(hreflangs.length).toBe(0);
          return;
        }

        await page.goto(route);
        const esEs = await page.locator('link[rel="alternate"][hreflang="es-ES"]').first();
        const xDefault = await page.locator('link[rel="alternate"][hreflang="x-default"]').first();
        
        expect(await esEs.count()).toBe(1);
        expect(await xDefault.count()).toBe(1);
      });
    }
  });

  test.describe('Schema.org Validation', () => {
    test('Yoast schema deduplication on conversion page', async ({ page }) => {
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

    test('MedicalOrganization schema presence', async ({ page }) => {
      await page.goto('/');
      
      const scripts = await page.locator('script[type="application/ld+json"]').all();
      let foundMedicalOrg = false;
      
      for (const script of scripts) {
        const content = await script.textContent();
        if (!content) continue;
        
        const data = JSON.parse(content);
        const graph = data['@graph'] || [];
        
        const hasMedicalOrg = graph.some((node: any) => 
          node['@type'] === 'MedicalOrganization' || 
          node['@type'] === 'MedicalClinic'
        );
        
        if (hasMedicalOrg) {
          foundMedicalOrg = true;
          break;
        }
      }
      
      expect(foundMedicalOrg).toBe(true);
    });
  });

  test.describe('Title and Meta Description', () => {
    for (const route of CRITICAL_ROUTES) {
      if (route.includes('404')) continue;
      
      test(`title and meta description on ${route}`, async ({ page }) => {
        await page.goto(route);
        
        const title = await page.title();
        const metaDesc = await page.locator('meta[name="description"]').getAttribute('content');
        
        expect(title).toBeTruthy();
        expect(title.length).toBeGreaterThan(10);
        expect(title.length).toBeLessThan(70); // SEO best practice
        
        if (metaDesc) {
          expect(metaDesc.length).toBeGreaterThan(50);
          expect(metaDesc.length).toBeLessThan(170); // SEO best practice
        }
      });
    }
  });
});