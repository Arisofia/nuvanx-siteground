# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 9
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
        - region [ref=e52]:
          - generic [ref=e53]:
            - generic [ref=e54]:
              - paragraph [ref=e55]: NUVANX · Madrid
              - 'heading "Sobre Nosotros: Autoridad Médica, Criterio Clínico y Transparencia" [level=1] [ref=e56]'
              - paragraph [ref=e57]: Medicina estética láser basada en evidencia, ingeniería tisular y well-aging — sin protocolos estandarizados ni inercia comercial.
              - generic [ref=e58]:
                - button "Iniciar mi valoración médica" [ref=e59]
                - link "Contactar por WhatsApp" [ref=e60] [cursor=pointer]:
                  - /url: https://wa.me/34669319836
              - paragraph [ref=e63]: Chamberí · Goya · Registros sanitarios CS20144 y CS20073
            - figure [ref=e64]:
              - img "Nosotros" [ref=e65]
        - generic [ref=e66]:
          - region [ref=e67]:
            - generic [ref=e68]:
              - paragraph [ref=e69]: Posicionamiento
              - heading "Criterio clínico antes que catálogo" [level=2] [ref=e70]
              - paragraph [ref=e71]: En NUVANX Medicina Estética Láser (Madrid) rechazamos la comercialización masiva y los protocolos estandarizados de la estética convencional. Operamos bajo el rigor de la medicina basada en la evidencia, la ingeniería tisular y el well-aging (envejecimiento saludable).
              - paragraph [ref=e72]: "No aplicamos tratamientos por inercia: diagnosticamos cada anatomía de forma individual y precisa. Solo entonces prescribimos las soluciones tecnológicas más indicadas, sustentando cada decisión en mecanismos de acción celular comprobables."
          - region [ref=e73]:
            - generic [ref=e74]:
              - paragraph [ref=e75]: Plataformas clínicas
              - heading "Tecnología con evidencia, nunca por tendencia" [level=2] [ref=e76]
              - paragraph [ref=e77]: Incorporamos dispositivos con marcado CE y documentación técnica disponible. Se indican solo cuando la valoración clínica identifica un objetivo, una alternativa y un seguimiento apropiados.
              - list [ref=e78]:
                - listitem [ref=e79]:
                  - heading "Endolift® — láser intersticial 1470 nm" [level=3] [ref=e80]
                  - paragraph [ref=e81]: Tecnología mínimamente invasiva que, a 1470 nm, actúa de forma selectiva sobre agua y grasa subcutánea. Mediante microfibra óptica estéril de 200–300 micras se induce lipólisis selectiva y se estimula neocolagénesis con retracción estructural, sin incisiones de lifting clásico.
                  - paragraph [ref=e82]:
                    - link "Saber más" [ref=e83] [cursor=pointer]:
                      - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
                - listitem [ref=e84]:
                  - heading "EXION® — radiofrecuencia fraccionada y ultrasonido" [level=3] [ref=e85]
                  - paragraph [ref=e86]: Plataforma que combina radiofrecuencia monopolar y ultrasonido focalizado (TUS), con control de profundidad orientado a dermis profunda. Estudios preclínicos de referencia describen un incremento de la producción endógena de ácido hialurónico del orden del 224% en el modelo evaluado; la indicación y el número de sesiones se definen siempre tras valoración médica.
                  - paragraph [ref=e87]:
                    - link "Saber más" [ref=e88] [cursor=pointer]:
                      - /url: https://staging2.nuvanx.com/exion-btl/
                - listitem [ref=e89]:
                  - heading "EMFUSION® — restauración de barrera DYNAMiQ™" [level=3] [ref=e90]
                  - paragraph [ref=e91]: Sistema de infusión cutánea con tecnología DYNAMiQ™ que convierte energía eléctrica en ondas mecánicas y crea microcanales temporales para favorecer la penetración de activos (p. ej. ceramidas y ectoína). Datos de referencia del fabricante describen una reducción relevante de la pérdida de agua transepidérmica y mejor absorción de nutrientes; se indica solo cuando el tejido lo justifica.
                - listitem [ref=e92]:
                  - heading "Láser CO₂ fraccionado, laserlipólisis y modelado subdérmico" [level=3] [ref=e93]
                  - paragraph [ref=e94]: Sistemas de alta precisión térmica para resurfacing y corrección de cicatrices atróficas (CO₂ fraccionado) y para modelado subdérmico / laserlipólisis corporal en casos seleccionados. Se activan únicamente cuando el diagnóstico anticipa una respuesta clínica real y predecible.
                  - paragraph [ref=e95]:
                    - link "Saber más" [ref=e96] [cursor=pointer]:
                      - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
                - listitem [ref=e97]:
                  - heading "Endoláser corporal" [level=3] [ref=e98]
                  - paragraph [ref=e99]: Protocolo de laserlipólisis corporal para adiposidad localizada con retracción térmica asociada, documentado en página propia.
                  - paragraph [ref=e100]:
                    - link "Ver Endoláser corporal" [ref=e101] [cursor=pointer]:
                      - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
          - region [ref=e102]:
            - generic [ref=e103]:
              - paragraph [ref=e104]: Sedes
              - heading "Instalaciones autorizadas en Madrid" [level=2] [ref=e105]
              - paragraph [ref=e106]: "Nuestras instalaciones cumplen la normativa sanitaria de la Comunidad de Madrid en dos sedes de excelencia:"
              - list [ref=e107]:
                - listitem [ref=e108]:
                  - article [ref=e109]:
                    - heading "Centro Clínico NUVANX Chamberí" [level=3] [ref=e110]
                    - paragraph [ref=e111]:
                      - strong [ref=e112]: Registro sanitario
                      - text: — CS20144
                    - paragraph [ref=e113]: Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010, Madrid
                    - paragraph [ref=e114]:
                      - link "669 319 836" [ref=e115] [cursor=pointer]:
                        - /url: tel:+34669319836
                    - paragraph [ref=e116]: Martes y jueves
                - listitem [ref=e117]:
                  - article [ref=e118]:
                    - heading "Centro Clínico NUVANX Salamanca / Goya" [level=3] [ref=e119]
                    - paragraph [ref=e120]:
                      - strong [ref=e121]: Registro sanitario
                      - text: — CS20073
                    - paragraph [ref=e122]: Calle de Fernán González, 26, 28009, Madrid
                    - paragraph [ref=e123]:
                      - link "647 505 107" [ref=e124] [cursor=pointer]:
                        - /url: tel:+34647505107
                    - paragraph [ref=e125]: Miércoles
              - paragraph [ref=e126]:
                - link "Ver clínicas NUVANX" [ref=e127] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - region [ref=e128]:
            - generic [ref=e129]:
              - paragraph [ref=e130]: Cuadro médico
              - heading "Excelencia hospitalaria e investigadora" [level=2] [ref=e131]
              - paragraph [ref=e132]: El mayor aval de NUVANX no es solo la tecnología, sino la trayectoria académica, investigadora y clínica del equipo. Resumen de autoridad; biografías completas en Equipo médico.
              - list [ref=e133]:
                - listitem [ref=e134]:
                  - article [ref=e135]:
                    - paragraph [ref=e136]: Director médico
                    - heading "Dr. José Javier Rivera Tejeda" [level=3] [ref=e137]
                    - paragraph [ref=e138]:
                      - strong [ref=e139]: ICOMEM
                      - text: "282864786"
                    - paragraph [ref=e140]: Especialista en medicina estética avanzada, láser intervencionista (Endolift®) y tricología. Perfil público con reseñas verificadas en Doctoralia.
                    - paragraph [ref=e141]:
                      - 'link "Ver biografía completa: Dr. José Javier Rivera Tejeda" [ref=e142] [cursor=pointer]':
                        - /url: https://staging2.nuvanx.com/equipo-medico/#physician-rivera-tejeda
                        - text: Ver biografía completa
                    - paragraph [ref=e143]:
                      - 'link "Perfil en Doctoralia: Dr. José Javier Rivera Tejeda" [ref=e144] [cursor=pointer]':
                        - /url: https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid
                        - text: Perfil en Doctoralia
                - listitem [ref=e145]:
                  - article [ref=e146]:
                    - paragraph [ref=e147]: Well-aging y geriatría preventiva
                    - heading "Dra. Ivon Yamileth Rivera Deras" [level=3] [ref=e148]
                    - paragraph [ref=e149]:
                      - strong [ref=e150]: ICOMEM
                      - text: "284621525"
                    - paragraph [ref=e151]: Referente en longevidad y well-aging. FEA en Hospital Universitario La Paz; investigación y sociedades científicas (SEMEG / EuGMS).
                    - paragraph [ref=e152]:
                      - 'link "Ver biografía completa: Dra. Ivon Yamileth Rivera Deras" [ref=e153] [cursor=pointer]':
                        - /url: https://staging2.nuvanx.com/equipo-medico/#physician-rivera-deras
                        - text: Ver biografía completa
                - listitem [ref=e154]:
                  - article [ref=e155]:
                    - paragraph [ref=e156]: Geriatría y paciente complejo
                    - heading "Dr. Fabio Augusto Quiñónez Bareiro" [level=3] [ref=e157]
                    - paragraph [ref=e158]:
                      - strong [ref=e159]: ICOMEM
                      - text: "282877543"
                    - paragraph [ref=e160]: Especialista en geriatría, gerontología y paciente complejo. Trayectoria hospitalaria (SERMAS / SESCAM), CIBERFES y docencia universitaria.
                    - paragraph [ref=e161]:
                      - 'link "Ver biografía completa: Dr. Fabio Augusto Quiñónez Bareiro" [ref=e162] [cursor=pointer]':
                        - /url: https://staging2.nuvanx.com/equipo-medico/#physician-quinonez-bareiro
                        - text: Ver biografía completa
              - paragraph [ref=e163]:
                - link "Conocer al equipo médico completo" [ref=e164] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/equipo-medico/
          - region [ref=e165]:
            - generic [ref=e166]:
              - paragraph [ref=e167]: Principios
              - heading "Principios médicos innegociables" [level=2] [ref=e168]
              - list [ref=e169]:
                - listitem [ref=e170]:
                  - heading "Diagnóstico tisular estricto" [level=3] [ref=e171]
                  - paragraph [ref=e172]: Ningún láser se enciende sin indicación médica. Evaluamos calidad dérmica, grado de ptosis y anatomía facial o corporal. Si el caso requiere cirugía, lo comunicamos con honestidad y derivamos al especialista correspondiente.
                - listitem [ref=e173]:
                  - heading "Transparencia transaccional" [level=3] [ref=e174]
                  - paragraph [ref=e175]: Rangos de inversión claros y presupuestos médicos cerrados tras la valoración, sin costes ocultos. La claridad sustituye al hermetismo tradicional del sector.
                - listitem [ref=e176]:
                  - heading "Tecnología con evidencia" [level=3] [ref=e177]
                  - paragraph [ref=e178]: Solo incorporamos dispositivos con certificación CE y respaldo en estudios clínicos publicados. Nunca por tendencia de mercado.
                - listitem [ref=e179]:
                  - heading "Seguimiento médico reglado" [level=3] [ref=e180]
                  - paragraph [ref=e181]: Monitorizamos la evolución y la respuesta celular a corto, medio y largo plazo. La responsabilidad clínica no termina al salir de la consulta.
  - region "Solicitar valoración médica" [ref=e182]:
    - generic [ref=e183]:
      - generic [ref=e184]:
        - paragraph [ref=e185]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e186]
        - paragraph [ref=e187]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e188]:
        - link "Iniciar mi valoración médica" [ref=e189] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e190] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e191]:
    - generic [ref=e192]:
      - generic [ref=e193]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e194] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e195]: NUVANX
          - generic [ref=e196]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e197]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e198]:
        - generic "Tratamientos"
        - generic [ref=e200]:
          - list [ref=e201]:
            - listitem [ref=e202]:
              - link "Endolift® facial" [ref=e203] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e204]:
              - link "Endoláser corporal" [ref=e205] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e206]:
              - link "Láser CO₂ fraccionado" [ref=e207] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e208]:
              - link "EXION® BTL" [ref=e209] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e210]:
              - link "EXION Face" [ref=e211] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e212]:
              - link "EXION Body" [ref=e213] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e214]:
              - link "EXION Fractional" [ref=e215] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e216]:
              - link "EMFUSION" [ref=e217] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e218]:
              - link "Bioestimuladores de Colágeno" [ref=e219] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e220]:
              - link "Ojeras y Surco Lagrimal" [ref=e221] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e222]:
              - link "Rinomodelación sin Cirugía" [ref=e223] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e224]:
              - link "Labios con Ácido Hialurónico" [ref=e225] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e226]:
            - link "BTL EXILITE™ IPL" [ref=e227] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e228]:
            - link "Ver todos →" [ref=e229] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e230]:
        - generic "Clínicas"
        - list [ref=e232]:
          - listitem [ref=e233]:
            - link "Nuestras clínicas" [ref=e234] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e235]:
            - link "Chamberí" [ref=e236] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e237]:
            - link "Salamanca–Goya" [ref=e238] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e239]:
            - link "Chamberí · 669 319 836" [ref=e240] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e241]:
            - link "Goya · 647 505 107" [ref=e242] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e243]:
        - generic "NUVANX"
        - list [ref=e245]:
          - listitem [ref=e246]:
            - link "Nosotros" [ref=e247] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e248]:
            - link "Por qué NUVANX" [ref=e249] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e250]:
            - link "Inversión" [ref=e251] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e252]:
            - link "Equipo médico" [ref=e253] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e254]:
            - link "Casos de pacientes" [ref=e255] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e256]:
            - link "Blog" [ref=e257] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e258]:
            - link "Contacto" [ref=e259] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e260]:
            - link "Valoración médica" [ref=e261] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e262]:
      - paragraph [ref=e263]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e264]:
        - list [ref=e265]:
          - listitem [ref=e266]:
            - link "Aviso legal" [ref=e267] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e268]:
            - link "Privacidad" [ref=e269] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e270]:
            - link "Cookies" [ref=e271] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e272]: ·
        - paragraph [ref=e273]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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