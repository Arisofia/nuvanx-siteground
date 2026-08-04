import { test, expect, Request, Response } from '@playwright/test';

// Rutas críticas a verificar tras la conversión desde Divi
const TARGET_ROUTES = [
  '/',
  '/servicios',
  '/contacto',
  '/politica-privacidad',
];

test.describe('NUVANX Production Gate: Auditoría de Layout e Integridad', () => {

  TARGET_ROUTES.forEach((route) => {
    test.describe(`Ruta: ${route}`, () => {

      /**
       * TEST 1: Cero Errores 404 / 500 en Recursos y Assets
       */
      test('No debe generar errores 404/500 en imágenes, CSS, JS ni APIs', async ({ page }) => {
        const failedRequests: { url: string; status: number; type: string }[] = [];

        // Interceptación de respuestas de red
        page.on('response', (response: Response) => {
          const status = response.status();
          const request: Request = response.request();
          const resourceType = request.resourceType();

          // Registrar cualquier recurso que devuelva status >= 400
          if (status >= 400) {
            failedRequests.push({
              url: response.url(),
              status,
              type: resourceType,
            });
          }
        });

        await page.goto(route, { waitUntil: 'networkidle' });

        // Assert: La lista de peticiones fallidas debe estar vacía
        expect(
          failedRequests,
          `Se encontraron ${failedRequests.length} recursos con errores HTTP en ${route}:\n` +
          JSON.stringify(failedRequests, null, 2)
        ).toEqual([]);
      });

      /**
       * TEST 2: Cero Imágenes Rotas (Renderizado Físico en DOM)
       */
      test('Todas las imágenes deben cargarse y tener dimensiones mayores a 0px', async ({ page }) => {
        await page.goto(route, { waitUntil: 'domcontentloaded' });

        // Esperar a que las imágenes visibles terminen de decodificarse
        await page.waitForLoadState('networkidle');

        const brokenImages = await page.evaluate(async () => {
          const images = Array.from(document.querySelectorAll('img'));
          const failures: string[] = [];

          for (const img of images) {
            // Verificar si la imagen falló en carga o tiene tamaño cero
            const isLoaded = img.complete && img.naturalWidth > 0 && img.naturalHeight > 0;
            if (!isLoaded) {
              failures.push(img.src || img.getAttribute('data-src') || 'Imagen sin atributo src');
            }
          }
          return failures;
        });

        expect(
          brokenImages,
          `Se detectaron imágen(es) rotas o no renderizadas en ${route}:\n` + brokenImages.join('\n')
        ).toEqual([]);
      });

      /**
       * TEST 3: Cero Fugas de Shortcodes Legacy de Divi
       */
      test('El contenido procesado no debe contener shortcodes [et_pb_]', async ({ page }) => {
        await page.goto(route, { waitUntil: 'domcontentloaded' });

        const bodyText = await page.locator('body').innerText();
        const rawHTML = await page.content();

        // Verificar que no existan cadenas residuales del constructor antiguo
        expect(bodyText).not.toContain('[et_pb_');
        expect(rawHTML).not.toContain('et_pb_section');
        expect(rawHTML).not.toContain('et_pb_row');
      });

      /**
       * TEST 4: Estructura del Shell & Sistema de Tokens
       */
      test('Debe renderizar la envolvente nvx-page-shell y el contenedor máster', async ({ page }) => {
        await page.goto(route, { waitUntil: 'domcontentloaded' });

        // Verificar existencia de la etiqueta principal del nuevo sistema
        const mainContent = page.locator('#main-content, .nvx-page-shell, main');
        await expect(mainContent.first()).toBeVisible();

        // Verificar que el header y footer centrales estén presentes
        await expect(page.locator('header').first()).toBeVisible();
        await expect(page.locator('footer').first()).toBeVisible();
      });

      /**
       * TEST 5: Estabilidad de Layout (Cumulative Layout Shift - CLS)
       */
      test('El desplazamiento de diseño (CLS) debe ser inferior a 0.1', async ({ page }) => {
        await page.goto(route);

        // Medir el indicador CLS mediante la API de PerformanceObserver en el navegador
        const clsValue = await page.evaluate(() => {
          return new Promise<number>((resolve) => {
            let cls = 0;
            const observer = new PerformanceObserver((list) => {
              for (const entry of list.getEntries() as any[]) {
                if (!entry.hadRecentInput) {
                  cls += entry.value;
                }
              }
            });

            observer.observe({ type: 'layout-shift', buffered: true });

            // Resolver medición tras 2 segundos de estabilización
            setTimeout(() => {
              observer.disconnect();
              resolve(cls);
            }, 2000);
          });
        });

        // Criterio de aceptación estándar de Web Vitals (CLS < 0.1)
        expect(clsValue, `El valor CLS en ${route} fue de ${clsValue}, superando el umbral de 0.1`).toBeLessThan(0.1);
      });

    });
  });

});
