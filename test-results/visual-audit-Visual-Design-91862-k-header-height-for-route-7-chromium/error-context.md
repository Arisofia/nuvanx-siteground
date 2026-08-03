# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 7
- Location: wp-content/themes/nuvanx-medical/tests/visual-audit.spec.ts:107:7

# Error details

```
Error: expect(received).toBeGreaterThanOrEqual(expected)

Expected: >= 70
Received:    24
```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - dialog "Gestionar el consentimiento de las cookies" [ref=e2]:
    - paragraph [ref=e7]: Utilizamos cookies para optimizar nuestro sitio web y nuestro servicio. Visite nuestra página sobre política de cookies o haga clic en el enlace al pie para obtener más información y cambiar sus preferencias.
    - generic [ref=e8]:
      - button "Aceptar cookies" [ref=e9] [cursor=pointer]
      - button "Denegar" [ref=e10] [cursor=pointer]
      - button "Ver preferencias" [ref=e11] [cursor=pointer]
    - list [ref=e13]:
      - listitem [ref=e14]:
        - link "Política de cookies" [ref=e15] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
      - listitem [ref=e16]:
        - link "Política de privacidad" [ref=e17] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/politica-privacidad/
      - listitem [ref=e18]:
        - link "Aviso legal" [ref=e19] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/aviso-legal/
  - link "Saltar al contenido principal" [ref=e20] [cursor=pointer]:
    - /url: "#nvx-main"
  - main [ref=e21]:
    - article [ref=e24]:
      - region [ref=e25]:
        - generic [ref=e26]:
          - figure [ref=e27]:
            - img "Consulta médica personalizada en Madrid" [ref=e28]
          - generic [ref=e29]:
            - paragraph [ref=e30]: NUVANX
            - heading "Consulta médica personalizada en Madrid" [level=1] [ref=e31]
      - generic [ref=e33]:
        - region [ref=e34]:
          - generic [ref=e35]:
            - generic [ref=e36]:
              - paragraph [ref=e37]: VALORACIÓN MÉDICA · MADRID
              - heading "Valoración médica estética en Madrid" [level=1] [ref=e38]
              - paragraph [ref=e39]: Revisamos anatomía, calidad de piel, antecedentes y expectativas antes de indicar un tratamiento facial o corporal.
              - generic [ref=e40]:
                - button "Iniciar mi valoración médica" [ref=e41]
                - link "Contactar por WhatsApp" [ref=e42] [cursor=pointer]:
                  - /url: https://wa.me/34669319836
            - figure [ref=e45]:
              - img "Consulta médica personalizada en Madrid" [ref=e46]
        - region [ref=e47]:
          - generic [ref=e48]:
            - paragraph [ref=e49]: SOLICITUD DE VALORACIÓN
            - heading "Cuéntanos qué quieres valorar" [level=2] [ref=e50]
            - paragraph [ref=e51]: Completa tus datos, indica la zona o tratamiento de interés y selecciona tu sede preferida. El equipo de NUVANX te contactará para coordinar la cita.
            - generic "Formulario de valoración médica NUVANX" [ref=e52]:
              - paragraph [ref=e54]: La información enviada se utiliza para gestionar tu solicitud. La indicación final depende de valoración médica y los resultados pueden variar según cada paciente.
        - region [ref=e55]:
          - generic [ref=e56]:
            - paragraph [ref=e57]: Primer paso
            - heading "Una consulta médica para orientar tu caso" [level=2] [ref=e58]
            - paragraph [ref=e59]: Antes de proponer un láser o un protocolo, hay que confirmar si existe indicación. La consulta médica estética se realiza de forma presencial en Chamberí o Salamanca–Goya.
            - paragraph [ref=e60]: "Saldrás con un criterio claro. El equipo, bajo la dirección del Dr. Rivera Tejeda, sigue tres pasos:"
            - list [ref=e61]:
              - listitem [ref=e62]:
                - heading "Motivo y expectativas" [level=3] [ref=e63]
                - paragraph [ref=e64]: Historial, cirugías previas y lo que quieres mejorar — con realismo, sin presión comercial.
              - listitem [ref=e65]:
                - heading "Exploración y seguridad" [level=3] [ref=e66]
                - paragraph [ref=e67]: Calidad de piel, flacidez, grasa localizada y criterios de seguridad para indicar o descartar un protocolo.
              - listitem [ref=e68]:
                - heading "Plan A/B y presupuesto" [level=3] [ref=e69]
                - paragraph [ref=e70]: "Si hay indicación: plan, tiempos de recuperación y presupuesto orientativo. Puedes decidir con calma."
            - paragraph [ref=e71]:
              - emphasis [ref=e72]: "Privacidad: si adjunta material fotográfico para una orientación preliminar, se trata bajo protocolos de confidencialidad clínica (GDPR). Ningún diagnóstico definitivo se emite solo a partir de una evaluación fotográfica; la indicación se confirma en valoración presencial."
        - region [ref=e73]:
          - generic [ref=e74]:
            - paragraph [ref=e75]: Sedes
            - heading "Ubicaciones autorizadas por Sanidad" [level=2] [ref=e76]
            - generic [ref=e77]:
              - article [ref=e78]:
                - heading "Centro Clínico NUVANX Chamberí" [level=3] [ref=e79]
                - paragraph [ref=e80]:
                  - strong [ref=e81]: "Registro sanitario:"
                  - text: CS20144
                - paragraph [ref=e82]: Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010, Madrid
                - paragraph [ref=e85]:
                  - strong [ref=e88]: "Teléfono / WhatsApp:"
                  - link "669 319 836" [ref=e89] [cursor=pointer]:
                    - /url: tel:+34669319836
                - paragraph [ref=e90]:
                  - strong [ref=e91]: "Consulta médica directa:"
                  - text: Martes y jueves
              - article [ref=e92]:
                - heading "Centro Clínico NUVANX Salamanca / Goya" [level=3] [ref=e93]
                - paragraph [ref=e94]:
                  - strong [ref=e95]: "Registro sanitario:"
                  - text: CS20073
                - paragraph [ref=e96]: Calle de Fernán González, 26, 28009, Madrid
                - paragraph [ref=e99]:
                  - strong [ref=e102]: "Teléfono / WhatsApp:"
                  - link "647 505 107" [ref=e103] [cursor=pointer]:
                    - /url: tel:+34647505107
                - paragraph [ref=e104]:
                  - strong [ref=e105]: "Consulta médica directa:"
                  - text: Miércoles
  - region "Solicitar valoración médica" [ref=e106]:
    - generic [ref=e107]:
      - generic [ref=e108]:
        - paragraph [ref=e109]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e110]
        - paragraph [ref=e111]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e112]:
        - link "Iniciar mi valoración médica" [ref=e113] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/#nvx-hubspot-form
        - link "Contactar por WhatsApp" [ref=e114] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e115]:
    - navigation "Información legal" [ref=e117]:
      - list [ref=e118]:
        - listitem [ref=e119]:
          - link "Política de privacidad" [ref=e120] [cursor=pointer]:
            - /url: https://staging2.nuvanx.com/politica-privacidad/
```

# Test source

```ts
  23  |         
  24  |         // Full page screenshot
  25  |         await page.screenshot({
  26  |           path: `tests/screenshots/${routeName}-full.png`,
  27  |           fullPage: true
  28  |         });
  29  |         
  30  |         // Viewport screenshot
  31  |         await page.screenshot({
  32  |           path: `tests/screenshots/${routeName}-viewport.png`,
  33  |           fullPage: false
  34  |         });
  35  |       });
  36  |     });
  37  |   });
  38  | 
  39  |   test.describe('Design Measurements', () => {
  40  |     CRITICAL_ROUTES.forEach((route, index) => {
  41  |       test(`measure design metrics for route ${index + 1}`, async ({ page }) => {
  42  |         await page.goto(route);
  43  |         await page.waitForLoadState('networkidle');
  44  |         
  45  |         const routeName = route.replace('https://staging2.nuvanx.com', '').replace(/\//g, '-') || 'home';
  46  |         
  47  |         const metrics = await page.evaluate(() => {
  48  |           const results: {
  49  |             headerHeight?: number;
  50  |             firstSectionPaddingTop?: number;
  51  |             firstSectionPaddingBottom?: number;
  52  |             h1FontSize?: number;
  53  |             h1LineHeight?: number;
  54  |             hasTokens: {
  55  |               nvxInk: boolean;
  56  |               nvxSpace2: boolean;
  57  |               nvxTypeH1: boolean;
  58  |               nvxHeaderHeight: boolean;
  59  |             };
  60  |           } = {};
  61  |           
  62  |           // Header measurement
  63  |           const header = document.querySelector('header, .nvx-header, [class*="header"]');
  64  |           if (header) {
  65  |             const rect = header.getBoundingClientRect();
  66  |             results.headerHeight = Math.round(rect.height);
  67  |           }
  68  |           
  69  |           // First section padding
  70  |           const firstSection = document.querySelector('section, [class*="section"]');
  71  |           if (firstSection) {
  72  |             const styles = window.getComputedStyle(firstSection);
  73  |             results.firstSectionPaddingTop = parseInt(styles.paddingTop);
  74  |             results.firstSectionPaddingBottom = parseInt(styles.paddingBottom);
  75  |           }
  76  |           
  77  |           // H1 measurement
  78  |           const h1 = document.querySelector('h1');
  79  |           if (h1) {
  80  |             const styles = window.getComputedStyle(h1);
  81  |             results.h1FontSize = parseInt(styles.fontSize);
  82  |             results.h1LineHeight = parseFloat(styles.lineHeight);
  83  |           }
  84  |           
  85  |           // CSS tokens check
  86  |           const root = document.documentElement;
  87  |           const computed = getComputedStyle(root);
  88  |           results.hasTokens = {
  89  |             nvxInk: computed.getPropertyValue('--nvx-ink') !== '',
  90  |             nvxSpace2: computed.getPropertyValue('--nvx-space-2') !== '',
  91  |             nvxTypeH1: computed.getPropertyValue('--nvx-type-h1') !== '',
  92  |             nvxHeaderHeight: computed.getPropertyValue('--nvx-header-height') !== ''
  93  |           };
  94  |           
  95  |           return results;
  96  |         });
  97  |         
  98  |         console.log(`📊 ${routeName} metrics:`, JSON.stringify(metrics, null, 2));
  99  |       });
  100 |     });
  101 |   });
  102 | 
  103 |   test.describe('Header Consistency Check', () => {
  104 |     const headerHeights: number[] = [];
  105 |     
  106 |     CRITICAL_ROUTES.forEach((route, index) => {
  107 |       test(`check header height for route ${index + 1}`, async ({ page }) => {
  108 |         await page.goto(route);
  109 |         await page.waitForLoadState('networkidle');
  110 |         
  111 |         const header = page.locator('header, .nvx-header, [class*="header"]').first();
  112 |         if (await header.count() > 0) {
  113 |           const height = await header.evaluate(el => {
  114 |             const rect = el.getBoundingClientRect();
  115 |             return Math.round(rect.height);
  116 |           });
  117 |           
  118 |           headerHeights.push(height);
  119 |           console.log(`📏 Header height for route ${index + 1}: ${height}px`);
  120 |           
  121 |           // Header should be close to 80px (±10px tolerance)
  122 |           expect(height).toBeLessThanOrEqual(90);
> 123 |           expect(height).toBeGreaterThanOrEqual(70);
      |                          ^ Error: expect(received).toBeGreaterThanOrEqual(expected)
  124 |         }
  125 |       });
  126 |     });
  127 |     
  128 |     test('header heights are consistent across pages', async () => {
  129 |       if (headerHeights.length > 1) {
  130 |         const maxHeight = Math.max(...headerHeights);
  131 |         const minHeight = Math.min(...headerHeights);
  132 |         const variance = maxHeight - minHeight;
  133 |         
  134 |         console.log(`📊 Header height variance: ${variance}px`);
  135 |         
  136 |         // Variance should be minimal (≤20px)
  137 |         expect(variance).toBeLessThanOrEqual(20);
  138 |       }
  139 |     });
  140 |   });
  141 | });
```