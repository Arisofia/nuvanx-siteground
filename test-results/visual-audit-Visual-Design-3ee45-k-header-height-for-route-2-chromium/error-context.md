# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 2
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
  - banner [ref=e21]:
    - generic [ref=e22]:
      - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e23] [cursor=pointer]:
        - /url: https://staging2.nuvanx.com/
        - img "NUVANX" [ref=e24]
      - navigation "Menú principal" [ref=e25]:
        - list [ref=e26]:
          - listitem [ref=e27]:
            - link "Inicio" [ref=e28] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/
          - listitem [ref=e29]:
            - link "Soluciones médicas" [ref=e30] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/soluciones-medicas/
          - listitem [ref=e31]:
            - link "Protocolos Signature" [ref=e32] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/protocolos-signature/
          - listitem [ref=e33]:
            - link "Tecnología" [ref=e34] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-laser/
          - listitem [ref=e35]:
            - link "Casos clínicos" [ref=e36] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e37]:
            - link "Equipo médico" [ref=e38] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e39]:
            - link "Clínicas" [ref=e40] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e41]:
            - link "Journal" [ref=e42] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e43]:
            - link "Contacto" [ref=e44] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
        - link "Solicitar valoración médica" [ref=e45] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
  - main [ref=e46]:
    - article [ref=e49]:
      - generic [ref=e50]:
        - region [ref=e51]:
          - generic [ref=e53]:
            - paragraph [ref=e54]: Clínicas NUVANX · Madrid
            - 'heading "Clínicas NUVANX en Madrid: Chamberí y Salamanca–Goya" [level=1] [ref=e55]'
            - paragraph [ref=e56]: Consulta direcciones, teléfonos, WhatsApp, horarios y cómo llegar. Para estudiar tu caso, solicita una valoración médica.
            - generic [ref=e57]:
              - link "Solicitar valoración médica" [ref=e58] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/madrid/valoracion/
              - link "Contactar por WhatsApp con NUVANX" [ref=e59] [cursor=pointer]:
                - /url: https://wa.me/34669319836
                - text: Contactar por WhatsApp
            - paragraph [ref=e60]: Chamberí (CS20144) · Salamanca–Goya (CS20073) · Medicina basada en evidencia
        - generic [ref=e61]:
          - region "Sedes y datos de contacto" [ref=e62]:
            - generic [ref=e63]:
              - paragraph [ref=e64]: Sedes autorizadas
              - heading "Datos de contacto y centros sanitarios" [level=2] [ref=e65]
              - paragraph [ref=e66]: Centros de medicina estética autorizados por la Consejería de Sanidad de la Comunidad de Madrid.
              - generic [ref=e67]:
                - article [ref=e68]:
                  - generic [ref=e69]:
                    - heading "Centro Clínico NUVANX Chamberí" [level=3] [ref=e70]
                    - generic [ref=e71]:
                      - text: "Registro sanitario:"
                      - strong [ref=e72]: CS20144
                  - list [ref=e73]:
                    - listitem [ref=e74]: Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid A dos minutos de la Plaza de Olavide
                    - listitem [ref=e78]:
                      - link "669 319 836" [ref=e81] [cursor=pointer]:
                        - /url: tel:+34669319836
                      - text: ·
                      - link "WhatsApp" [ref=e82] [cursor=pointer]:
                        - /url: https://wa.me/34669319836
                    - listitem [ref=e83]: "Horario de clínica: lunes a viernes, 12:00–20:00; sábados, 10:00–18:00"
                    - listitem [ref=e87]: El Dr. Rivera atiende en Chamberí los martes y jueves.
                  - link "Cómo llegar" [ref=e91] [cursor=pointer]:
                    - /url: https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20C%2F%20de%20Fern%C3%A1ndez%20de%20la%20Hoz%204%2028010%20Madrid
                - article [ref=e92]:
                  - generic [ref=e93]:
                    - heading "Centro Clínico NUVANX Salamanca–Goya" [level=3] [ref=e94]
                    - generic [ref=e95]:
                      - text: "Registro sanitario:"
                      - strong [ref=e96]: CS20073
                  - list [ref=e97]:
                    - listitem [ref=e98]: Calle de Fernán González, 26, 28009 Madrid Barrio de Salamanca, Madrid
                    - listitem [ref=e102]:
                      - link "647 505 107" [ref=e105] [cursor=pointer]:
                        - /url: tel:+34647505107
                      - text: ·
                      - link "WhatsApp" [ref=e106] [cursor=pointer]:
                        - /url: https://wa.me/34647505107
                    - listitem [ref=e107]: "Horario de clínica: lunes a viernes, 11:00–20:00"
                    - listitem [ref=e111]: El Dr. Rivera atiende en Salamanca–Goya los miércoles.
                  - link "Cómo llegar" [ref=e115] [cursor=pointer]:
                    - /url: https://www.google.com/maps/search/?api=1&query=NUVANX%20Goya%20C%2F%20de%20Fern%C3%A1n%20Gonz%C3%A1lez%2026%2028009%20Madrid
          - region "Reservar valoración médica" [ref=e116]:
            - generic [ref=e117]:
              - paragraph [ref=e118]: Atención telefónica directa
              - heading "Llama a tu centro NUVANX más cercano" [level=2] [ref=e119]
              - paragraph [ref=e120]: Atención directa para información sobre valoraciones, citas y localización de nuestras sedes.
              - generic [ref=e121]:
                - link "Chamberí · 669 319 836" [ref=e122] [cursor=pointer]:
                  - /url: tel:+34669319836
                - link "Salamanca–Goya · 647 505 107" [ref=e123] [cursor=pointer]:
                  - /url: tel:+34647505107
                - link "Solicitar valoración" [ref=e124] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/madrid/valoracion/
  - region "Solicitar valoración médica" [ref=e125]:
    - generic [ref=e126]:
      - generic [ref=e127]:
        - paragraph [ref=e128]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e129]
        - paragraph [ref=e130]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e131]:
        - link "Iniciar mi valoración médica" [ref=e132] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e133] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e134]:
    - generic [ref=e135]:
      - generic [ref=e136]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e137] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e138]: NUVANX
          - generic [ref=e139]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e140]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e141]:
        - generic "Tratamientos"
        - generic [ref=e143]:
          - list [ref=e144]:
            - listitem [ref=e145]:
              - link "Endolift® facial" [ref=e146] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e147]:
              - link "Endoláser corporal" [ref=e148] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e149]:
              - link "Láser CO₂ fraccionado" [ref=e150] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e151]:
              - link "EXION® BTL" [ref=e152] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e153]:
              - link "EXION Face" [ref=e154] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e155]:
              - link "EXION Body" [ref=e156] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e157]:
              - link "EXION Fractional" [ref=e158] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e159]:
              - link "EMFUSION" [ref=e160] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e161]:
              - link "Bioestimuladores de Colágeno" [ref=e162] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e163]:
              - link "Ojeras y Surco Lagrimal" [ref=e164] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e165]:
              - link "Rinomodelación sin Cirugía" [ref=e166] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e167]:
              - link "Labios con Ácido Hialurónico" [ref=e168] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e169]:
            - link "BTL EXILITE™ IPL" [ref=e170] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e171]:
            - link "Ver todos →" [ref=e172] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e173]:
        - generic "Clínicas"
        - list [ref=e175]:
          - listitem [ref=e176]:
            - link "Nuestras clínicas" [ref=e177] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e178]:
            - link "Chamberí" [ref=e179] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e180]:
            - link "Salamanca–Goya" [ref=e181] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e182]:
            - link "Chamberí · 669 319 836" [ref=e183] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e184]:
            - link "Goya · 647 505 107" [ref=e185] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e186]:
        - generic "NUVANX"
        - list [ref=e188]:
          - listitem [ref=e189]:
            - link "Nosotros" [ref=e190] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e191]:
            - link "Por qué NUVANX" [ref=e192] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e193]:
            - link "Inversión" [ref=e194] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e195]:
            - link "Equipo médico" [ref=e196] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e197]:
            - link "Casos de pacientes" [ref=e198] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e199]:
            - link "Blog" [ref=e200] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e201]:
            - link "Contacto" [ref=e202] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e203]:
            - link "Valoración médica" [ref=e204] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e205]:
      - paragraph [ref=e206]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e207]:
        - list [ref=e208]:
          - listitem [ref=e209]:
            - link "Aviso legal" [ref=e210] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e211]:
            - link "Privacidad" [ref=e212] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e213]:
            - link "Cookies" [ref=e214] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e215]: ·
        - paragraph [ref=e216]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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