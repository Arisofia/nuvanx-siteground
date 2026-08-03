# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 6
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
      - region [ref=e50]:
        - generic [ref=e51]:
          - figure [ref=e52]:
            - img "Clínicas NUVANX Medicina Estética Láser en Madrid" [ref=e53]
          - generic [ref=e54]:
            - paragraph [ref=e55]: NUVANX
            - heading "Clínicas NUVANX Medicina Estética Láser en Madrid" [level=1] [ref=e56]
      - generic [ref=e58]:
        - region [ref=e59]:
          - generic [ref=e60]:
            - generic [ref=e61]:
              - paragraph [ref=e62]: Clínicas NUVANX · Madrid
              - heading "Clínicas NUVANX Medicina Estética Láser en Madrid" [level=1] [ref=e63]
              - paragraph [ref=e64]: Dos centros sanitarios autorizados, una sola dirección médica. Chamberí y Salamanca–Goya con el mismo criterio clínico, protocolos láser y valoración presencial antes de cualquier tratamiento.
              - generic [ref=e65]:
                - button "Iniciar mi valoración médica" [ref=e66]
                - link "Contactar por WhatsApp" [ref=e67] [cursor=pointer]:
                  - /url: https://wa.me/34669319836
              - paragraph [ref=e70]: Chamberí CS20144 · Salamanca–Goya CS20073 · Medicina basada en evidencia
            - figure [ref=e71]:
              - img "Clínicas NUVANX Medicina Estética Láser en Madrid" [ref=e72]
        - navigation "Sedes NUVANX" [ref=e73]:
          - generic [ref=e74]:
            - button "Iniciar mi valoración médica" [ref=e75]
            - link "Contactar por WhatsApp" [ref=e76] [cursor=pointer]:
              - /url: https://wa.me/34669319836
        - region [ref=e79]:
          - generic [ref=e80]:
            - paragraph [ref=e81]: Registro sanitario CS20144
            - heading "Centro Clínico NUVANX Chamberí" [level=2] [ref=e82]
            - paragraph [ref=e83]: A dos minutos de la Plaza de Olavide. Valoración, Endolift®, láser CO₂ y seguimiento en un centro autorizado por la Comunidad de Madrid.
            - list [ref=e84]:
              - listitem [ref=e85]: — Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid
              - listitem [ref=e86]:
                - text: —
                - link "669 319 836" [ref=e87] [cursor=pointer]:
                  - /url: tel:+34669319836
                - text: ·
                - link "WhatsApp" [ref=e88] [cursor=pointer]:
                  - /url: https://wa.me/34669319836
              - listitem [ref=e89]: "— Horario: lunes a viernes, 12:00–20:00; sábados, 10:00–18:00"
              - listitem [ref=e90]: — El Dr. Rivera atiende en Chamberí los martes y jueves.
            - generic [ref=e91]:
              - button "Iniciar mi valoración médica" [ref=e92]
              - link "Contactar por WhatsApp" [ref=e93] [cursor=pointer]:
                - /url: https://wa.me/34669319836
        - region [ref=e96]:
          - generic [ref=e97]:
            - paragraph [ref=e98]: Registro sanitario CS20073
            - heading "Centro Clínico NUVANX Salamanca–Goya" [level=2] [ref=e99]
            - paragraph [ref=e100]: En el Barrio de Salamanca. Misma dirección médica y protocolos que Chamberí, con atención y valoración en sede propia.
            - list [ref=e101]:
              - listitem [ref=e102]: — Calle de Fernán González, 26, 28009 Madrid
              - listitem [ref=e103]:
                - text: —
                - link "647 505 107" [ref=e104] [cursor=pointer]:
                  - /url: tel:+34647505107
                - text: ·
                - link "WhatsApp" [ref=e105] [cursor=pointer]:
                  - /url: https://wa.me/34647505107
              - listitem [ref=e106]: "— Horario: lunes a viernes, 11:00–20:00"
              - listitem [ref=e107]: — El Dr. Rivera atiende en Salamanca–Goya los miércoles.
            - generic [ref=e108]:
              - button "Iniciar mi valoración médica" [ref=e109]
              - link "Contactar por WhatsApp" [ref=e110] [cursor=pointer]:
                - /url: https://wa.me/34669319836
        - region [ref=e113]:
          - generic [ref=e114]:
            - paragraph [ref=e115]: Siguiente paso
            - heading "Valoración médica en la sede que elijas" [level=2] [ref=e116]
            - paragraph [ref=e117]: La indicación y el presupuesto se definen en consulta presencial. Elige sede al solicitar la valoración o llama directamente a tu centro.
            - generic [ref=e118]:
              - button "Iniciar mi valoración médica" [ref=e119]
              - link "Contactar por WhatsApp" [ref=e120] [cursor=pointer]:
                - /url: https://wa.me/34669319836
  - region "Solicitar valoración médica" [ref=e123]:
    - generic [ref=e124]:
      - generic [ref=e125]:
        - paragraph [ref=e126]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e127]
        - paragraph [ref=e128]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e129]:
        - link "Iniciar mi valoración médica" [ref=e130] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e131] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e132]:
    - generic [ref=e133]:
      - generic [ref=e134]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e135] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e136]: NUVANX
          - generic [ref=e137]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e138]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e139]:
        - generic "Tratamientos"
        - generic [ref=e141]:
          - list [ref=e142]:
            - listitem [ref=e143]:
              - link "Endolift® facial" [ref=e144] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e145]:
              - link "Endoláser corporal" [ref=e146] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e147]:
              - link "Láser CO₂ fraccionado" [ref=e148] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e149]:
              - link "EXION® BTL" [ref=e150] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e151]:
              - link "EXION Face" [ref=e152] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e153]:
              - link "EXION Body" [ref=e154] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e155]:
              - link "EXION Fractional" [ref=e156] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e157]:
              - link "EMFUSION" [ref=e158] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e159]:
              - link "Bioestimuladores de Colágeno" [ref=e160] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e161]:
              - link "Ojeras y Surco Lagrimal" [ref=e162] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e163]:
              - link "Rinomodelación sin Cirugía" [ref=e164] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e165]:
              - link "Labios con Ácido Hialurónico" [ref=e166] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e167]:
            - link "BTL EXILITE™ IPL" [ref=e168] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e169]:
            - link "Ver todos →" [ref=e170] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e171]:
        - generic "Clínicas"
        - list [ref=e173]:
          - listitem [ref=e174]:
            - link "Nuestras clínicas" [ref=e175] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e176]:
            - link "Chamberí" [ref=e177] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e178]:
            - link "Salamanca–Goya" [ref=e179] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e180]:
            - link "Chamberí · 669 319 836" [ref=e181] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e182]:
            - link "Goya · 647 505 107" [ref=e183] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e184]:
        - generic "NUVANX"
        - list [ref=e186]:
          - listitem [ref=e187]:
            - link "Nosotros" [ref=e188] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e189]:
            - link "Por qué NUVANX" [ref=e190] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e191]:
            - link "Inversión" [ref=e192] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e193]:
            - link "Equipo médico" [ref=e194] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e195]:
            - link "Casos de pacientes" [ref=e196] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e197]:
            - link "Blog" [ref=e198] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e199]:
            - link "Contacto" [ref=e200] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e201]:
            - link "Valoración médica" [ref=e202] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e203]:
      - paragraph [ref=e204]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e205]:
        - list [ref=e206]:
          - listitem [ref=e207]:
            - link "Aviso legal" [ref=e208] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e209]:
            - link "Privacidad" [ref=e210] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e211]:
            - link "Cookies" [ref=e212] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e213]: ·
        - paragraph [ref=e214]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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