# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 4
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
      - generic [ref=e51]:
        - region "Soluciones médico-estéticas NUVANX" [ref=e52]:
          - generic [ref=e53]:
            - generic [ref=e54]:
              - paragraph [ref=e55]: NUVANX · Soluciones · Madrid
              - heading "Soluciones médico-estéticas según lo que quieres mejorar" [level=1] [ref=e56]
              - paragraph [ref=e57]: Organizamos la oferta por necesidad clínica y objetivo estético, no por una lista de aparatos. La indicación final se confirma tras una valoración médica personalizada.
              - generic [ref=e58]:
                - button "Iniciar mi valoración médica" [ref=e59]
                - link "Contactar por WhatsApp" [ref=e60] [cursor=pointer]:
                  - /url: https://wa.me/34669319836
              - paragraph [ref=e63]: Chamberí · Salamanca–Goya · Consulta presencial o virtual
            - figure "Diagnóstico, indicación, tratamiento y seguimiento." [ref=e64]:
              - img "Consulta y tratamiento médico-estético en NUVANX Madrid" [ref=e65]
        - region "Soluciones NUVANX por objetivo" [ref=e66]:
          - generic [ref=e67]:
            - paragraph [ref=e68]: Arquitectura de soluciones
            - heading "Primero el objetivo. Después, la técnica." [level=2] [ref=e69]
            - paragraph [ref=e70]: Una misma preocupación puede requerir enfoques distintos según anatomía, piel, tejido, antecedentes y recuperación disponible.
            - generic [ref=e71]:
              - article [ref=e72]:
                - paragraph [ref=e73]: Rostro y cuello
                - heading "Perfil, papada y definición mandibular" [level=3] [ref=e74]
                - paragraph [ref=e75]: Valoración del óvalo facial, cuello, línea mandibular, soporte y calidad del tejido.
                - paragraph [ref=e76]:
                  - link "Explorar rostro y cuello" [ref=e77] [cursor=pointer]:
                    - /url: /endolift-facial-papada-mandibula/
              - article [ref=e78]:
                - paragraph [ref=e79]: Calidad de piel
                - heading "Firmeza, hidratación y luminosidad" [level=3] [ref=e80]
                - paragraph [ref=e81]: Planes progresivos mediante bioestimulación, EXION® Face, EMFUSION® o cuidado profesional.
                - paragraph [ref=e82]:
                  - link "Explorar calidad de piel" [ref=e83] [cursor=pointer]:
                    - /url: /bioestimuladores-colageno-madrid/
              - article [ref=e84]:
                - paragraph [ref=e85]: Contorno corporal
                - heading "Grasa localizada, firmeza y proporción" [level=3] [ref=e86]
                - paragraph [ref=e87]: Abordaje de abdomen, flancos, brazos, muslos o espalda con diagnóstico del tejido.
                - paragraph [ref=e88]:
                  - link "Explorar contorno corporal" [ref=e89] [cursor=pointer]:
                    - /url: /endolaser-corporal-grasa-localizada/
              - article [ref=e90]:
                - paragraph [ref=e91]: Cambios posgestacionales
                - heading "Abdomen y contorno después del embarazo" [level=3] [ref=e92]
                - paragraph [ref=e93]: Evaluación respetuosa de piel, tejido y proporción corporal, según anatomía y objetivo realista.
                - paragraph [ref=e94]:
                  - link "Consultar abordaje posgestacional" [ref=e95] [cursor=pointer]:
                    - /url: /endolaser-corporal-grasa-localizada/
              - article [ref=e96]:
                - paragraph [ref=e97]: Cicatrices, poros y textura
                - heading "Renovación cutánea controlada" [level=3] [ref=e98]
                - paragraph [ref=e99]: Láser CO₂ fraccionado o EXION® Fractional RF según fototipo, profundidad y recuperación disponible.
                - paragraph [ref=e100]:
                  - link "Explorar textura y cicatrices" [ref=e101] [cursor=pointer]:
                    - /url: /laser-co2-fraccionado-madrid-textura-cicatrices-poro/
              - article [ref=e102]:
                - paragraph [ref=e103]: Manchas, rojeces y fotodaño
                - heading "Tono más uniforme con diagnóstico previo" [level=3] [ref=e104]
                - paragraph [ref=e105]: Luz pulsada médica para indicaciones seleccionadas tras revisar fototipo y antecedentes.
                - paragraph [ref=e106]:
                  - link "Explorar manchas y rojeces" [ref=e107] [cursor=pointer]:
                    - /url: /btl-exilite-ipl-madrid/
              - article [ref=e108]:
                - paragraph [ref=e109]: Medicina estética masculina
                - heading "Definición discreta y proporción natural" [level=3] [ref=e110]
                - paragraph [ref=e111]: Perfil, papada, mandíbula, calidad de piel o contorno corporal sin alterar la identidad.
                - paragraph [ref=e112]:
                  - link "Explorar medicina estética masculina" [ref=e113] [cursor=pointer]:
                    - /url: /medicina-estetica/
              - article [ref=e114]:
                - paragraph [ref=e115]: Medicina inyectable
                - heading "Labios, perfil nasal, mirada y bioestimulación" [level=3] [ref=e116]
                - paragraph [ref=e117]: Procedimientos médicos para armonizar, hidratar o mejorar calidad del tejido manteniendo expresión.
                - paragraph [ref=e118]:
                  - link "Explorar medicina inyectable" [ref=e119] [cursor=pointer]:
                    - /url: /medicina-estetica/
              - article [ref=e120]:
                - paragraph [ref=e121]: Cuidado y mantenimiento
                - heading "Preparación, apoyo y continuidad" [level=3] [ref=e122]
                - paragraph [ref=e123]: Limpieza, hidratación, drenaje, preparación de la piel o apoyo después de tratamientos médicos.
                - paragraph [ref=e124]:
                  - link "Explorar cuidado y mantenimiento" [ref=e125] [cursor=pointer]:
                    - /url: /estetica-avanzada/
        - region "Método clínico NUVANX" [ref=e126]:
          - generic [ref=e127]:
            - paragraph [ref=e128]: Método clínico
            - heading "Una solución no es un tratamiento único" [level=2] [ref=e129]
            - generic [ref=e130]:
              - article [ref=e131]:
                - paragraph [ref=e132]: "01"
                - heading "Diagnóstico" [level=3] [ref=e133]
                - paragraph [ref=e134]: Revisamos anatomía, piel, tejido, tratamientos previos y motivo de consulta.
              - article [ref=e135]:
                - paragraph [ref=e136]: "02"
                - heading "Indicación" [level=3] [ref=e137]
                - paragraph [ref=e138]: Definimos qué conviene tratar, qué preservar y qué procedimiento tiene sentido.
              - article [ref=e139]:
                - paragraph [ref=e140]: "03"
                - heading "Seguimiento" [level=3] [ref=e141]
                - paragraph [ref=e142]: Explicamos evolución, cuidados, límites y revisiones necesarias.
  - contentinfo [ref=e143]:
    - generic [ref=e144]:
      - generic [ref=e145]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e146] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e147]: NUVANX
          - generic [ref=e148]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e149]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e150]:
        - generic "Tratamientos"
        - generic [ref=e152]:
          - list [ref=e153]:
            - listitem [ref=e154]:
              - link "Endolift® facial" [ref=e155] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e156]:
              - link "Endoláser corporal" [ref=e157] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e158]:
              - link "Láser CO₂ fraccionado" [ref=e159] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e160]:
              - link "EXION® BTL" [ref=e161] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e162]:
              - link "EXION Face" [ref=e163] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e164]:
              - link "EXION Body" [ref=e165] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e166]:
              - link "EXION Fractional" [ref=e167] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e168]:
              - link "EMFUSION" [ref=e169] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e170]:
              - link "Bioestimuladores de Colágeno" [ref=e171] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e172]:
              - link "Ojeras y Surco Lagrimal" [ref=e173] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e174]:
              - link "Rinomodelación sin Cirugía" [ref=e175] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e176]:
              - link "Labios con Ácido Hialurónico" [ref=e177] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e178]:
            - link "BTL EXILITE™ IPL" [ref=e179] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e180]:
            - link "Ver todos →" [ref=e181] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e182]:
        - generic "Clínicas"
        - list [ref=e184]:
          - listitem [ref=e185]:
            - link "Nuestras clínicas" [ref=e186] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e187]:
            - link "Chamberí" [ref=e188] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e189]:
            - link "Salamanca–Goya" [ref=e190] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e191]:
            - link "Chamberí · 669 319 836" [ref=e192] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e193]:
            - link "Goya · 647 505 107" [ref=e194] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e195]:
        - generic "NUVANX"
        - list [ref=e197]:
          - listitem [ref=e198]:
            - link "Nosotros" [ref=e199] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e200]:
            - link "Por qué NUVANX" [ref=e201] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e202]:
            - link "Inversión" [ref=e203] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e204]:
            - link "Equipo médico" [ref=e205] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e206]:
            - link "Casos de pacientes" [ref=e207] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e208]:
            - link "Blog" [ref=e209] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e210]:
            - link "Contacto" [ref=e211] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e212]:
            - link "Valoración médica" [ref=e213] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e214]:
      - paragraph [ref=e215]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e216]:
        - list [ref=e217]:
          - listitem [ref=e218]:
            - link "Aviso legal" [ref=e219] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e220]:
            - link "Privacidad" [ref=e221] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e222]:
            - link "Cookies" [ref=e223] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e224]: ·
        - paragraph [ref=e225]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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