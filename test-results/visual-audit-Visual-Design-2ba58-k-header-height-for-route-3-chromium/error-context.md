# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 3
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
    - generic [ref=e49]:
      - region [ref=e50]:
        - generic [ref=e52]:
          - paragraph [ref=e53]: NUVANX Journal
          - heading "Medicina estética con criterio" [level=1] [ref=e54]
          - paragraph [ref=e55]: Análisis médicos sobre tecnología láser, calidad de piel, well-aging, seguridad y decisiones terapéuticas en Madrid.
      - generic [ref=e57]:
        - generic [ref=e58]:
          - article [ref=e59]:
            - generic [ref=e60]:
              - generic [ref=e61]:
                - link "Medicina estética láser" [ref=e63] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e64]: ·15/07/2026
                - generic [ref=e65]: ·2 min
              - heading [level=2] [ref=e66]:
                - link "Cómo se combinan EXION®, Endolift® y EMFUSION® en un plan médico" [ref=e67] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/combinar-exion-endolift-emfusion-plan-medico/
              - paragraph [ref=e69]: Las tecnologías no forman un paquete cerrado. La combinación y la secuencia dependen de si el problema principal está en estructura, grasa localizada, calidad cutánea o barrera de la piel.
              - 'link "Leer artículo: Cómo se combinan EXION®, Endolift® y EMFUSION® en un plan médico" [ref=e70] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/combinar-exion-endolift-emfusion-plan-medico/
                - text: Leer artículo
                - generic [ref=e71]: →
          - article [ref=e72]:
            - generic [ref=e73]:
              - generic [ref=e74]:
                - link "Medicina estética láser" [ref=e76] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e77]: ·15/07/2026
                - generic [ref=e78]: ·2 min
              - heading [level=2] [ref=e79]:
                - 'link "EMFUSION®, limpieza profunda y microneedling: objetivos distintos para la piel" [ref=e80] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/emfusion-limpieza-profunda-microneedling-diferencias/
              - paragraph [ref=e82]: EMFUSION®, los tratamientos de limpieza e hidratación y el microneedling actúan con enfoques diferentes. La elección depende de barrera cutánea, textura, inflamación y objetivo clínico.
              - 'link "Leer artículo: EMFUSION®, limpieza profunda y microneedling: objetivos distintos para la piel" [ref=e83] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/emfusion-limpieza-profunda-microneedling-diferencias/
                - text: Leer artículo
                - generic [ref=e84]: →
          - article [ref=e85]:
            - generic [ref=e86]:
              - generic [ref=e87]:
                - link "Medicina estética láser" [ref=e89] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e90]: ·15/07/2026
                - generic [ref=e91]: ·2 min
              - heading [level=2] [ref=e92]:
                - 'link "EXION® Fractional RF y otras radiofrecuencias con microagujas: qué cambia" [ref=e93] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/exion-fractional-rf-radiofrecuencia-microagujas-diferencias/
              - paragraph [ref=e95]: EXION® Fractional RF, Morpheus8 y Potenza pertenecen a la radiofrecuencia con microagujas, pero no son equivalentes directos. Profundidad, control de energía y protocolo deben individualizarse.
              - 'link "Leer artículo: EXION® Fractional RF y otras radiofrecuencias con microagujas: qué cambia" [ref=e96] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/exion-fractional-rf-radiofrecuencia-microagujas-diferencias/
                - text: Leer artículo
                - generic [ref=e97]: →
          - article [ref=e98]:
            - generic [ref=e99]:
              - generic [ref=e100]:
                - link "Medicina estética láser" [ref=e102] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e103]: ·15/07/2026
                - generic [ref=e104]: ·2 min
              - heading [level=2] [ref=e105]:
                - 'link "EXION® Body, criolipólisis y radiofrecuencia corporal: diferencias de indicación" [ref=e106] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/exion-body-criolipolisis-radiofrecuencia-corporal-diferencias/
              - paragraph [ref=e108]: Grasa localizada, laxitud y exceso de piel son diagnósticos distintos. EXION® Body, criolipólisis y radiofrecuencia corporal no deben elegirse sin valorar qué componente predomina.
              - 'link "Leer artículo: EXION® Body, criolipólisis y radiofrecuencia corporal: diferencias de indicación" [ref=e109] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/exion-body-criolipolisis-radiofrecuencia-corporal-diferencias/
                - text: Leer artículo
                - generic [ref=e110]: →
          - article [ref=e111]:
            - generic [ref=e112]:
              - generic [ref=e113]:
                - link "Medicina estética láser" [ref=e115] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e116]: ·15/07/2026
                - generic [ref=e117]: ·2 min
              - heading [level=2] [ref=e118]:
                - 'link "EXION® Face, HIFU y radiofrecuencia facial: diferencias de indicación" [ref=e119] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/exion-face-hifu-radiofrecuencia-diferencias-indicacion/
              - paragraph [ref=e121]: EXION® Face, ultrasonido focalizado y radiofrecuencia facial no son equivalentes. La elección depende del tejido, la profundidad, la reserva de volumen y el objetivo clínico.
              - 'link "Leer artículo: EXION® Face, HIFU y radiofrecuencia facial: diferencias de indicación" [ref=e122] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/exion-face-hifu-radiofrecuencia-diferencias-indicacion/
                - text: Leer artículo
                - generic [ref=e123]: →
          - article [ref=e124]:
            - generic [ref=e125]:
              - generic [ref=e126]:
                - link "Tecnología médica" [ref=e128] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/tecnologia-medica/
                - time [ref=e129]: ·10/07/2026
                - generic [ref=e130]: ·2 min
              - heading [level=2] [ref=e131]:
                - 'link "IPL médica: manchas, rojeces, acné y fotorejuvenecimiento con BTL EXILITE™" [ref=e132] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento/
              - paragraph [ref=e134]: ¿Qué es la IPL médica BTL EXILITE? La luz pulsada intensa (IPL) es una tecnología que emite pulsos de luz en un espectro amplio, permitiendo actuar sobre distintos cromóforos de…
              - 'link "Leer artículo: IPL médica: manchas, rojeces, acné y fotorejuvenecimiento con BTL EXILITE™" [ref=e135] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento/
                - text: Leer artículo
                - generic [ref=e136]: →
          - article [ref=e137]:
            - generic [ref=e138]:
              - generic [ref=e139]:
                - link "Medicina estética láser" [ref=e141] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
                - time [ref=e142]: ·09/07/2026
                - generic [ref=e143]: ·4 min
              - heading [level=2] [ref=e144]:
                - 'link "EXION® BTL: qué diferencia hay entre Fractional RF, Face y Body" [ref=e145] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/exion-btl-fractional-rf-face-body/
              - paragraph [ref=e147]: Guía sobre EXION® BTL, sus aplicadores Fractional RF, Face y Body, y sus posibles indicaciones médicas.
              - 'link "Leer artículo: EXION® BTL: qué diferencia hay entre Fractional RF, Face y Body" [ref=e148] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/exion-btl-fractional-rf-face-body/
                - text: Leer artículo
                - generic [ref=e149]: →
          - article [ref=e150]:
            - generic [ref=e151]:
              - generic [ref=e152]:
                - link "Medicina estética" [ref=e154] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica/
                - time [ref=e155]: ·07/07/2026
                - generic [ref=e156]: ·4 min
              - heading [level=2] [ref=e157]:
                - link "Endolift o cirugía — Cómo elegir entre lifting quirúrgico y láser sin incisiones" [ref=e158] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/endolift-vs-lifting-quirurgico-cuando-operarse/
              - paragraph [ref=e160]: La laxitud cutánea y la pérdida de definición en el óvalo facial y la zona submentoniana son motivos de consulta frecuentes en medicina estética, presentándose de forma habitual a partir…
              - 'link "Leer artículo: Endolift o cirugía — Cómo elegir entre lifting quirúrgico y láser sin incisiones" [ref=e161] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/endolift-vs-lifting-quirurgico-cuando-operarse/
                - text: Leer artículo
                - generic [ref=e162]: →
          - article [ref=e163]:
            - generic [ref=e164]:
              - generic [ref=e165]:
                - link "Seguridad médica" [ref=e167] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/seguridad-medica/
                - time [ref=e168]: ·07/07/2026
                - generic [ref=e169]: ·6 min
              - heading [level=2] [ref=e170]:
                - 'link "Riesgos del intrusismo en tratamientos inyectables: la importancia del criterio médico" [ref=e171] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/intrusismo-tratamientos-inyectables-riesgos/
              - paragraph [ref=e173]: "Recibes un mensaje de WhatsApp: «Hola, soy esteticista, hago Botox y rellenos en casa, cita mañana, 200€ Botox, 150€ relleno.» O escuchas en el bar: «Mi peluquera hace inyecciones en…"
              - 'link "Leer artículo: Riesgos del intrusismo en tratamientos inyectables: la importancia del criterio médico" [ref=e174] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/intrusismo-tratamientos-inyectables-riesgos/
                - text: Leer artículo
                - generic [ref=e175]: →
          - article [ref=e176]:
            - generic [ref=e177]:
              - generic [ref=e178]:
                - link "Medicina estética" [ref=e180] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica/
                - time [ref=e181]: ·16/06/2026
                - generic [ref=e182]: ·4 min
              - heading [level=2] [ref=e183]:
                - link "Lo que debes saber las primeras 72 horas después de Endolift" [ref=e184] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/endolift-primeras-72-horas-que-esperar/
              - paragraph [ref=e186]: "Guía de recuperación tras Endolift: primeras 72 horas, cuidados, señales de consulta y seguimiento médico en NUVANX Madrid."
              - 'link "Leer artículo: Lo que debes saber las primeras 72 horas después de Endolift" [ref=e187] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/endolift-primeras-72-horas-que-esperar/
                - text: Leer artículo
                - generic [ref=e188]: →
          - article [ref=e189]:
            - generic [ref=e190]:
              - generic [ref=e191]:
                - link "Well-aging" [ref=e193] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/well-aging/
                - time [ref=e194]: ·16/06/2026
                - generic [ref=e195]: ·3 min
              - heading [level=2] [ref=e196]:
                - link "A los 48 años, tu piel habla otro idioma" [ref=e197] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/well-aging-48-cambios-hormonales-piel/
              - paragraph [ref=e199]: Well-aging médico en Madrid para calidad de piel, firmeza y luminosidad en etapa de cambios hormonales. Valoración en NUVANX.
              - 'link "Leer artículo: A los 48 años, tu piel habla otro idioma" [ref=e200] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/well-aging-48-cambios-hormonales-piel/
                - text: Leer artículo
                - generic [ref=e201]: →
          - article [ref=e202]:
            - generic [ref=e203]:
              - generic [ref=e204]:
                - link "Medicina estética" [ref=e206] [cursor=pointer]:
                  - /url: https://staging2.nuvanx.com/category/medicina-estetica/
                - time [ref=e207]: ·16/06/2026
                - generic [ref=e208]: ·3 min
              - heading [level=2] [ref=e209]:
                - 'link "Cómo funciona Endolift®: láser subdérmico, indicaciones y límites" [ref=e210] [cursor=pointer]':
                  - /url: https://staging2.nuvanx.com/endolift-ciencia-laser-subdermico/
              - paragraph [ref=e212]: Qué ocurre bajo la piel durante un tratamiento Endolift®, qué tejidos puede abordar y por qué la indicación depende de la anatomía y del diagnóstico médico.
              - 'link "Leer artículo: Cómo funciona Endolift®: láser subdérmico, indicaciones y límites" [ref=e213] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/endolift-ciencia-laser-subdermico/
                - text: Leer artículo
                - generic [ref=e214]: →
        - navigation "Paginación del Journal" [ref=e215]
        - navigation [ref=e216]:
          - heading "Explorar por tema" [level=2] [ref=e217]
          - list [ref=e218]:
            - listitem [ref=e219]:
              - link "Medicina estética láser" [ref=e220] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/category/medicina-estetica-laser/
            - listitem [ref=e221]:
              - link "Medicina estética" [ref=e222] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/category/medicina-estetica/
            - listitem [ref=e223]:
              - link "Seguridad médica" [ref=e224] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/category/seguridad-medica/
            - listitem [ref=e225]:
              - link "Well-aging" [ref=e226] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/category/well-aging/
            - listitem [ref=e227]:
              - link "Tecnología médica" [ref=e228] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/category/tecnologia-medica/
  - region "Solicitar valoración médica" [ref=e229]:
    - generic [ref=e230]:
      - generic [ref=e231]:
        - paragraph [ref=e232]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e233]
        - paragraph [ref=e234]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e235]:
        - link "Iniciar mi valoración médica" [ref=e236] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e237] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e238]:
    - generic [ref=e239]:
      - generic [ref=e240]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e241] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e242]: NUVANX
          - generic [ref=e243]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e244]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e245]:
        - generic "Tratamientos"
        - generic [ref=e247]:
          - list [ref=e248]:
            - listitem [ref=e249]:
              - link "Endolift® facial" [ref=e250] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e251]:
              - link "Endoláser corporal" [ref=e252] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e253]:
              - link "Láser CO₂ fraccionado" [ref=e254] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e255]:
              - link "EXION® BTL" [ref=e256] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e257]:
              - link "EXION Face" [ref=e258] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e259]:
              - link "EXION Body" [ref=e260] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e261]:
              - link "EXION Fractional" [ref=e262] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e263]:
              - link "EMFUSION" [ref=e264] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e265]:
              - link "Bioestimuladores de Colágeno" [ref=e266] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e267]:
              - link "Ojeras y Surco Lagrimal" [ref=e268] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e269]:
              - link "Rinomodelación sin Cirugía" [ref=e270] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e271]:
              - link "Labios con Ácido Hialurónico" [ref=e272] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e273]:
            - link "BTL EXILITE™ IPL" [ref=e274] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e275]:
            - link "Ver todos →" [ref=e276] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e277]:
        - generic "Clínicas"
        - list [ref=e279]:
          - listitem [ref=e280]:
            - link "Nuestras clínicas" [ref=e281] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e282]:
            - link "Chamberí" [ref=e283] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e284]:
            - link "Salamanca–Goya" [ref=e285] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e286]:
            - link "Chamberí · 669 319 836" [ref=e287] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e288]:
            - link "Goya · 647 505 107" [ref=e289] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e290]:
        - generic "NUVANX"
        - list [ref=e292]:
          - listitem [ref=e293]:
            - link "Nosotros" [ref=e294] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e295]:
            - link "Por qué NUVANX" [ref=e296] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e297]:
            - link "Inversión" [ref=e298] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e299]:
            - link "Equipo médico" [ref=e300] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e301]:
            - link "Casos de pacientes" [ref=e302] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e303]:
            - link "Blog" [ref=e304] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e305]:
            - link "Contacto" [ref=e306] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e307]:
            - link "Valoración médica" [ref=e308] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e309]:
      - paragraph [ref=e310]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e311]:
        - list [ref=e312]:
          - listitem [ref=e313]:
            - link "Aviso legal" [ref=e314] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e315]:
            - link "Privacidad" [ref=e316] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e317]:
            - link "Cookies" [ref=e318] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e319]: ·
        - paragraph [ref=e320]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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