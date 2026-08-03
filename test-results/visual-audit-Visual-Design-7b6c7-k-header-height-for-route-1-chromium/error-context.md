# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 1
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
          - generic "Experiencia NUVANX Medicina Estética Láser en Madrid" [ref=e52]
          - generic [ref=e53]:
            - heading "Medicina estética con criterio. Madrid." [level=1] [ref=e54]
            - paragraph [ref=e55]: Antes de recomendar nada, escuchamos qué te preocupa y entendemos qué tendría sentido mejorar en tu caso.
            - link "Iniciar valoración" [ref=e56] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - region [ref=e57]:
          - generic [ref=e58]:
            - paragraph [ref=e59]: No tratamos una imagen aislada. Tratamos a una persona, con su historia y sus prioridades.
            - paragraph [ref=e60]: Cada protocolo comienza con una valoración médica individual. Si no está indicado para ti, te lo diremos con la misma claridad.
        - region [ref=e61]:
          - generic [ref=e62]:
            - heading "Intervención mínima. Planificación estructural." [level=2] [ref=e63]
            - paragraph [ref=e64]: Protocolos médicos definidos según anatomía, calidad del tejido y objetivos clínicos realistas.
          - generic [ref=e65]:
            - generic [ref=e66]:
              - generic [ref=e67]: I
              - heading "Abordajes sin incisiones quirúrgicas amplias" [level=3] [ref=e68]
              - paragraph [ref=e69]: Determinadas indicaciones pueden abordarse mediante microcánulas o fibra óptica, siempre tras exploración médica.
            - generic [ref=e70]:
              - generic [ref=e71]: II
              - heading "Recuperación según el procedimiento" [level=3] [ref=e72]
              - paragraph [ref=e73]: El tiempo de reincorporación depende del tratamiento, la zona, los parámetros utilizados y la respuesta individual.
            - generic [ref=e74]:
              - generic [ref=e75]: III
              - heading "Anestesia adaptada a la indicación" [level=3] [ref=e76]
              - paragraph [ref=e77]: Cuando procede, los tratamientos se realizan con anestesia local y seguimiento médico personalizado.
            - generic [ref=e78]:
              - generic [ref=e79]: IV
              - heading "Tratamiento combinado del contorno" [level=3] [ref=e80]
              - paragraph [ref=e81]: La reducción adiposa y la mejora de la firmeza pueden integrarse en un mismo plan cuando existe indicación.
            - generic [ref=e82]:
              - generic [ref=e83]: V
              - heading "Evolución progresiva y seguimiento" [level=3] [ref=e84]
              - paragraph [ref=e85]: La evolución se revisa en consulta y varía según el tratamiento, el tejido y los hábitos de cada paciente.
        - region [ref=e86]:
          - paragraph [ref=e88]: Arquitectura anatómica
          - generic [ref=e89]:
            - article [ref=e90]:
              - generic [ref=e91]: "01"
              - heading "Endolift® Facial" [level=3] [ref=e92]
              - paragraph [ref=e93]: Retracción tisular y definición del contorno mandibular mediante láser subdérmico.
            - article [ref=e94]:
              - generic [ref=e95]: "02"
              - heading "Endoláser Corporal" [level=3] [ref=e96]
              - paragraph [ref=e97]: Lipólisis láser focalizada para depósitos adiposos y flacidez.
            - article [ref=e98]:
              - generic [ref=e99]: "03"
              - heading "Láser CO₂ Fraccionado" [level=3] [ref=e100]
              - paragraph [ref=e101]: Renovación fraccionada para abordar fotodaño, cicatrices y textura según parámetros médicos.
            - article [ref=e102]:
              - generic [ref=e103]: "04"
              - heading "Medicina Estética Facial" [level=3] [ref=e104]
              - paragraph [ref=e105]: Planificación conservadora que respeta la identidad y las proporciones naturales.
            - article [ref=e106]:
              - generic [ref=e107]: "05"
              - heading "EXION® y tecnologías BTL" [level=3] [ref=e108]
              - paragraph [ref=e109]: Protocolos para calidad cutánea, firmeza y tratamiento facial o corporal tras diagnóstico.
          - link "Ver portafolio completo" [ref=e111] [cursor=pointer]:
            - /url: https://staging2.nuvanx.com/tratamientos/
        - region [ref=e112]:
          - generic [ref=e113]:
            - img "Valoración médica personalizada en NUVANX Madrid" [ref=e115]
            - generic [ref=e116]:
              - heading "La evolución necesita contexto, no promesas rápidas." [level=2] [ref=e117]
              - paragraph [ref=e118]: "Un resultado sólo tiene sentido si se entiende de dónde partimos. Por eso documentamos nuestros casos con criterio médico: sin filtros, en la misma postura y bajo la misma luz."
              - link "Explorar casos clínicos" [ref=e119] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/casos-de-pacientes/
        - region [ref=e120]:
          - generic [ref=e121]:
            - generic [ref=e122]:
              - heading "Dirección y criterio médico" [level=2] [ref=e123]
              - link "Conocer al equipo médico" [ref=e124] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/equipo-medico/
            - generic [ref=e125]:
              - paragraph [ref=e126]: El equipo integra experiencia clínica, valoración individual y seguimiento para seleccionar la tecnología adecuada en cada caso.
              - list [ref=e127]:
                - listitem [ref=e128]:
                  - strong [ref=e129]: Dr. José Javier Rivera Tejeda
                  - text: Dirección médica. Endolift® y láser CO₂.
                - listitem [ref=e130]:
                  - strong [ref=e131]: Dra. Ivon Yamileth Rivera Deras
                  - text: Medicina y well-aging.
                - listitem [ref=e132]:
                  - strong [ref=e133]: Dr. Fabio Augusto Quiñónez Bareiro
                  - text: Medicina e investigación en fisiología del envejecimiento.
        - region [ref=e134]:
          - generic [ref=e135]:
            - paragraph [ref=e136]: Áreas de valoración y tratamiento
            - generic [ref=e137]:
              - generic [ref=e138]:
                - heading "Corporal" [level=3] [ref=e139]
                - list [ref=e140]:
                  - listitem [ref=e141]:
                    - strong [ref=e142]: "Abdomen y flancos:"
                    - text: valoración de grasa localizada y firmeza.
                  - listitem [ref=e143]:
                    - strong [ref=e144]: "Caderas y muslos:"
                    - text: planificación del contorno según anatomía.
                  - listitem [ref=e145]:
                    - strong [ref=e146]: "Brazos, rodillas y espalda:"
                    - text: protocolos ajustados al tejido.
                  - listitem [ref=e147]:
                    - strong [ref=e148]: "Calidad cutánea corporal:"
                    - text: selección de tecnología según diagnóstico.
              - generic [ref=e149]:
                - heading "Facial" [level=3] [ref=e150]
                - list [ref=e151]:
                  - listitem [ref=e152]:
                    - strong [ref=e153]: "Tercio inferior:"
                    - text: mandíbula, cuello y papada.
                  - listitem [ref=e154]:
                    - strong [ref=e155]: "Armonización:"
                    - text: planificación conservadora de proporciones y soporte.
                  - listitem [ref=e156]:
                    - strong [ref=e157]: "Calidad de piel:"
                    - text: textura, poros, cicatrices y fotodaño.
        - region [ref=e158]:
          - heading "Madrid. Dos sedes. Un único criterio médico." [level=2] [ref=e159]
          - generic [ref=e160]:
            - generic [ref=e161]:
              - heading "Chamberí" [level=3] [ref=e162]
              - paragraph [ref=e163]: Serenidad y discreción.
              - generic [ref=e164]: CS20144
            - generic [ref=e165]:
              - heading "Salamanca–Goya" [level=3] [ref=e166]
              - paragraph [ref=e167]: Accesibilidad y sofisticación.
              - generic [ref=e168]: CS20073
        - region [ref=e169]:
          - heading "Medicina estética con criterio clínico." [level=2] [ref=e170]
          - paragraph [ref=e171]: Plan individualizado. Precisión médica. Seguimiento según tu caso.
          - generic [ref=e172]:
            - link "Definir mi plan clínico" [ref=e173] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
            - link "Contactar por WhatsApp" [ref=e174] [cursor=pointer]:
              - /url: https://wa.me/34669319836
  - contentinfo [ref=e175]:
    - generic [ref=e176]:
      - generic [ref=e177]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e178] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e179]: NUVANX
          - generic [ref=e180]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e181]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e182]:
        - generic "Tratamientos"
        - generic [ref=e184]:
          - list [ref=e185]:
            - listitem [ref=e186]:
              - link "Endolift® facial" [ref=e187] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e188]:
              - link "Endoláser corporal" [ref=e189] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e190]:
              - link "Láser CO₂ fraccionado" [ref=e191] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e192]:
              - link "EXION® BTL" [ref=e193] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e194]:
              - link "EXION Face" [ref=e195] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e196]:
              - link "EXION Body" [ref=e197] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e198]:
              - link "EXION Fractional" [ref=e199] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e200]:
              - link "EMFUSION" [ref=e201] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e202]:
              - link "Bioestimuladores de Colágeno" [ref=e203] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e204]:
              - link "Ojeras y Surco Lagrimal" [ref=e205] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e206]:
              - link "Rinomodelación sin Cirugía" [ref=e207] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e208]:
              - link "Labios con Ácido Hialurónico" [ref=e209] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e210]:
            - link "BTL EXILITE™ IPL" [ref=e211] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e212]:
            - link "Ver todos →" [ref=e213] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e214]:
        - generic "Clínicas"
        - list [ref=e216]:
          - listitem [ref=e217]:
            - link "Nuestras clínicas" [ref=e218] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e219]:
            - link "Chamberí" [ref=e220] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e221]:
            - link "Salamanca–Goya" [ref=e222] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e223]:
            - link "Chamberí · 669 319 836" [ref=e224] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e225]:
            - link "Goya · 647 505 107" [ref=e226] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e227]:
        - generic "NUVANX"
        - list [ref=e229]:
          - listitem [ref=e230]:
            - link "Nosotros" [ref=e231] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e232]:
            - link "Por qué NUVANX" [ref=e233] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e234]:
            - link "Inversión" [ref=e235] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e236]:
            - link "Equipo médico" [ref=e237] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e238]:
            - link "Casos de pacientes" [ref=e239] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e240]:
            - link "Blog" [ref=e241] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e242]:
            - link "Contacto" [ref=e243] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e244]:
            - link "Valoración médica" [ref=e245] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e246]:
      - paragraph [ref=e247]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e248]:
        - list [ref=e249]:
          - listitem [ref=e250]:
            - link "Aviso legal" [ref=e251] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e252]:
            - link "Privacidad" [ref=e253] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e254]:
            - link "Cookies" [ref=e255] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e256]: ·
        - paragraph [ref=e257]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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