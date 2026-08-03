# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 5
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
    - generic [ref=e48]:
      - region [ref=e49]:
        - generic [ref=e51]:
          - paragraph [ref=e52]: SOLUCIONES MÉDICAS · NUVANX MADRID
          - heading "Soluciones médicas para rostro, piel y contorno corporal." [level=1] [ref=e53]
          - paragraph [ref=e54]: La preocupación orienta la consulta. El diagnóstico define el tratamiento. Organizamos las soluciones por anatomía y por causa clínica, no por catálogo de máquinas. Antes de recomendar una tecnología diferenciamos grasa, laxitud, soporte, textura, pigmentación y otros componentes que pueden producir signos similares.
          - generic [ref=e55]:
            - link "Solicitar valoración médica" [ref=e56] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
            - link "Explorar soluciones" [ref=e57] [cursor=pointer]:
              - /url: "#mapa-soluciones"
          - paragraph [ref=e58]: Diagnóstico individual · Indicación proporcionada · Seguimiento médico
      - navigation "Mapa de soluciones médicas" [ref=e59]:
        - generic [ref=e60]:
          - link "01 ROSTRO Y CUELLO" [ref=e61] [cursor=pointer]:
            - /url: "#rostro-cuello"
            - generic [ref=e62]: "01"
            - text: ROSTRO Y CUELLO
          - link "02 PIEL Y SUPERFICIE" [ref=e63] [cursor=pointer]:
            - /url: "#piel-superficie"
            - generic [ref=e64]: "02"
            - text: PIEL Y SUPERFICIE
          - link "03 CONTORNO CORPORAL" [ref=e65] [cursor=pointer]:
            - /url: "#contorno-corporal"
            - generic [ref=e66]: "03"
            - text: CONTORNO CORPORAL
          - link "04 PLANIFICACIÓN ESPECÍFICA" [ref=e67] [cursor=pointer]:
            - /url: "#planes-especificos"
            - generic [ref=e68]: "04"
            - text: PLANIFICACIÓN ESPECÍFICA
      - region [ref=e69]:
        - generic [ref=e70]:
          - generic [ref=e71]:
            - paragraph [ref=e72]: ANTES DE LA TECNOLOGÍA
            - heading "Una misma preocupación puede tener causas distintas." [level=2] [ref=e73]
          - generic [ref=e74]:
            - paragraph [ref=e75]: Dos personas pueden consultar por la misma zona y necesitar planes distintos. La anatomía, la calidad del tejido, los antecedentes y los límites clínicos cambian la indicación.
            - paragraph [ref=e76]: La valoración también puede concluir que conviene esperar, derivar o no tratar. Esa decisión forma parte del criterio médico.
      - region [ref=e77]:
        - generic [ref=e78]:
          - generic [ref=e79]:
            - generic [ref=e80]: "01"
            - generic [ref=e81]:
              - paragraph [ref=e82]: ROSTRO Y CUELLO
              - 'heading "Rostro y cuello: definición, soporte y calidad cutánea" [level=2] [ref=e83]'
            - paragraph [ref=e84]: La misma preocupación visible puede proceder de grasa, laxitud, pérdida de soporte, pigmentación o una combinación. La exploración determina qué componente debe tratarse y cuál no.
          - generic [ref=e85]:
            - article [ref=e86]:
              - generic [ref=e87]:
                - paragraph [ref=e88]: Profile Definition™
                - heading "Papada y línea mandibular" [level=3] [ref=e89]
                - generic [ref=e90]:
                  - term [ref=e91]: Qué se valora
                  - definition [ref=e92]: Grasa localizada, laxitud, soporte del mentón y continuidad entre rostro y cuello.
                  - term [ref=e93]: Límites
                  - definition [ref=e94]: Un exceso importante de piel o una alteración estructural puede requerir una alternativa quirúrgica.
              - 'link "Explorar solución: Papada y línea mandibular" [ref=e95] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/papada-definicion-mandibular-madrid/
                - text: Explorar solución
                - generic [ref=e96]: →
            - article [ref=e97]:
              - generic [ref=e98]:
                - paragraph [ref=e99]: Valoración periocular
                - heading "Región periocular y mirada" [level=3] [ref=e100]
                - generic [ref=e101]:
                  - term [ref=e102]: Qué se valora
                  - definition [ref=e103]: Surco, pigmentación, vascularización, calidad de piel, laxitud y bolsas reales o aparentes.
                  - term [ref=e104]: Límites
                  - definition [ref=e105]: Las bolsas grasas verdaderas o las alteraciones funcionales requieren valoración específica.
              - 'link "Explorar solución: Región periocular y mirada" [ref=e106] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
                - text: Explorar solución
                - generic [ref=e107]: →
            - article [ref=e108]:
              - generic [ref=e109]:
                - paragraph [ref=e110]: Skin Architecture™
                - heading "Firmeza y densidad facial" [level=3] [ref=e111]
                - generic [ref=e112]:
                  - term [ref=e113]: Qué se valora
                  - definition [ref=e114]: Calidad dérmica, pérdida de firmeza, textura, poros y luminosidad.
                  - term [ref=e115]: Límites
                  - definition [ref=e116]: La modalidad depende del fototipo, la profundidad del problema y el tiempo de recuperación disponible.
              - 'link "Explorar solución: Firmeza y densidad facial" [ref=e117] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/calidad-piel-firmeza-luminosidad-madrid/
                - text: Explorar solución
                - generic [ref=e118]: →
      - region [ref=e119]:
        - generic [ref=e120]:
          - generic [ref=e121]:
            - generic [ref=e122]: "02"
            - generic [ref=e123]:
              - paragraph [ref=e124]: PIEL Y SUPERFICIE
              - heading "Textura, cicatrices, manchas y rojeces" [level=2] [ref=e125]
            - paragraph [ref=e126]: Las alteraciones de superficie requieren diagnóstico diferencial. El fototipo, la profundidad, la inflamación y el riesgo de pigmentación condicionan la energía y la secuencia de tratamiento.
          - generic [ref=e127]:
            - article [ref=e128]:
              - generic [ref=e129]:
                - paragraph [ref=e130]: Surface Renewal™
                - heading "Cicatrices de acné, poros y textura" [level=3] [ref=e131]
                - generic [ref=e132]:
                  - term [ref=e133]: Qué se valora
                  - definition [ref=e134]: Tipo y profundidad de cicatriz, irregularidad de superficie, poros y calidad dérmica.
                  - term [ref=e135]: Límites
                  - definition [ref=e136]: Las cicatrices profundas o mixtas pueden requerir varias técnicas y fases.
              - 'link "Explorar solución: Cicatrices de acné, poros y textura" [ref=e137] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/cicatrices-acne-poros-textura-madrid/
                - text: Explorar solución
                - generic [ref=e138]: →
            - article [ref=e139]:
              - generic [ref=e140]:
                - paragraph [ref=e141]: Tone Correction™
                - heading "Manchas, rojeces y fotodaño" [level=3] [ref=e142]
                - generic [ref=e143]:
                  - term [ref=e144]: Qué se valora
                  - definition [ref=e145]: Léntigos, eritema, telangiectasias, melasma y pigmentación postinflamatoria.
                  - term [ref=e146]: Límites
                  - definition [ref=e147]: Las lesiones pigmentadas sospechosas deben evaluarse antes de aplicar luz o láser.
              - 'link "Explorar solución: Manchas, rojeces y fotodaño" [ref=e148] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/
                - text: Explorar solución
                - generic [ref=e149]: →
            - article [ref=e150]:
              - generic [ref=e151]:
                - paragraph [ref=e152]: Láser CO₂ fraccionado
                - heading "Renovación cutánea con láser CO₂" [level=3] [ref=e153]
                - generic [ref=e154]:
                  - term [ref=e155]: Qué se valora
                  - definition [ref=e156]: Textura, arrugas finas, cicatrices y alteraciones seleccionadas de superficie.
                  - term [ref=e157]: Límites
                  - definition [ref=e158]: La intensidad y la recuperación se individualizan según la indicación y el fototipo.
              - 'link "Explorar solución: Renovación cutánea con láser CO₂" [ref=e159] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
                - text: Explorar solución
                - generic [ref=e160]: →
      - region [ref=e161]:
        - generic [ref=e162]:
          - generic [ref=e163]:
            - generic [ref=e164]: "03"
            - generic [ref=e165]:
              - paragraph [ref=e166]: CONTORNO CORPORAL
              - 'heading "Contorno corporal: grasa localizada, laxitud y continuidad anatómica" [level=2] [ref=e167]'
            - paragraph [ref=e168]: No tratamos zonas como compartimentos aislados. Analizamos la relación entre abdomen, flancos, espalda, brazos, muslos y rodillas para preservar proporción y continuidad.
          - generic [ref=e169]:
            - article [ref=e170]:
              - generic [ref=e171]:
                - paragraph [ref=e172]: Contour Architecture™
                - heading "Abdomen y flancos" [level=3] [ref=e173]
                - generic [ref=e174]:
                  - term [ref=e175]: Qué se valora
                  - definition [ref=e176]: Grasa subcutánea, laxitud, estrías, estabilidad de peso y pared abdominal.
                  - term [ref=e177]: Límites
                  - definition [ref=e178]: La grasa visceral, una diástasis relevante o un exceso importante de piel no se resuelven con un tratamiento focal.
              - 'link "Explorar solución: Abdomen y flancos" [ref=e179] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/grasa-localizada-abdomen-flancos-madrid/
                - text: Explorar solución
                - generic [ref=e180]: →
            - article [ref=e181]:
              - generic [ref=e182]:
                - paragraph [ref=e183]: Contour Architecture™
                - heading "Brazos y continuidad axilar" [level=3] [ref=e184]
                - generic [ref=e185]:
                  - term [ref=e186]: Qué se valora
                  - definition [ref=e187]: Grasa localizada, laxitud posterior y relación con axila, espalda y torso.
                  - term [ref=e188]: Límites
                  - definition [ref=e189]: La reserva de piel condiciona cuánto puede mejorar el contorno sin cirugía.
              - 'link "Explorar solución: Brazos y continuidad axilar" [ref=e190] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/flacidez-grasa-localizada-brazos-madrid/
                - text: Explorar solución
                - generic [ref=e191]: →
            - article [ref=e192]:
              - generic [ref=e193]:
                - paragraph [ref=e194]: Contour Architecture™
                - heading "Espalda y zona del sujetador" [level=3] [ref=e195]
                - generic [ref=e196]:
                  - term [ref=e197]: Qué se valora
                  - definition [ref=e198]: Pliegues por grasa, laxitud, presión de la prenda y continuidad con brazos y flancos.
                  - term [ref=e199]: Límites
                  - definition [ref=e200]: Cada zona debe tener una indicación documentada; una combinación no se prescribe por defecto.
              - 'link "Explorar solución: Espalda y zona del sujetador" [ref=e201] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/grasa-espalda-zona-sujetador-madrid/
                - text: Explorar solución
                - generic [ref=e202]: →
            - article [ref=e203]:
              - generic [ref=e204]:
                - paragraph [ref=e205]: Contour Architecture™
                - heading "Muslos y región subglútea" [level=3] [ref=e206]
                - generic [ref=e207]:
                  - term [ref=e208]: Qué se valora
                  - definition [ref=e209]: Laxitud, grasa localizada, celulitis estructural y continuidad del tren inferior.
                  - term [ref=e210]: Límites
                  - definition [ref=e211]: La grasa, la laxitud y la celulitis responden a mecanismos distintos.
              - 'link "Explorar solución: Muslos y región subglútea" [ref=e212] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/flacidez-muslos-internos-subgluteo-madrid/
                - text: Explorar solución
                - generic [ref=e213]: →
            - article [ref=e214]:
              - generic [ref=e215]:
                - paragraph [ref=e216]: Contour Architecture™
                - heading "Rodillas" [level=3] [ref=e217]
                - generic [ref=e218]:
                  - term [ref=e219]: Qué se valora
                  - definition [ref=e220]: Grasa localizada, laxitud y relación con muslo interno y pierna.
                  - term [ref=e221]: Límites
                  - definition [ref=e222]: La anatomía de la zona y la calidad de piel determinan la indicación.
              - 'link "Explorar solución: Rodillas" [ref=e223] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/tratamiento-rodillas-grasa-flacidez-madrid/
                - text: Explorar solución
                - generic [ref=e224]: →
      - region [ref=e225]:
        - generic [ref=e226]:
          - generic [ref=e227]:
            - generic [ref=e228]: "04"
            - generic [ref=e229]:
              - paragraph [ref=e230]: PLANIFICACIÓN ESPECÍFICA
              - heading "Contextos que requieren una lectura propia" [level=2] [ref=e231]
            - paragraph [ref=e232]: Algunos cambios no deben abordarse como una zona aislada. La historia clínica, la etapa vital, el patrón anatómico y los procedimientos previos modifican la planificación.
          - generic [ref=e233]:
            - article [ref=e234]:
              - generic [ref=e235]:
                - paragraph [ref=e236]: Post-Maternity Contour™
                - heading "Cambios posgestacionales" [level=3] [ref=e237]
                - generic [ref=e238]:
                  - term [ref=e239]: Qué se valora
                  - definition [ref=e240]: Grasa localizada, laxitud, estrías, cicatriz de cesárea, diástasis y cambio de proporción.
                  - term [ref=e241]: Límites
                  - definition [ref=e242]: La diástasis, la hernia o el exceso importante de piel pueden requerir valoración especializada.
              - 'link "Explorar solución: Cambios posgestacionales" [ref=e243] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/tratamiento-postparto-abdomen-contorno-corporal-madrid/
                - text: Explorar solución
                - generic [ref=e244]: →
            - article [ref=e245]:
              - generic [ref=e246]:
                - paragraph [ref=e247]: Male Contour
                - heading "Contorno masculino" [level=3] [ref=e248]
                - generic [ref=e249]:
                  - term [ref=e250]: Qué se valora
                  - definition [ref=e251]: Perfil mandibular, grasa localizada, calidad de piel y proporciones del patrón anatómico masculino.
                  - term [ref=e252]: Límites
                  - definition [ref=e253]: La planificación preserva la anatomía individual y no impone una forma estándar.
              - 'link "Explorar solución: Contorno masculino" [ref=e254] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/contorno-corporal-masculino-madrid/
                - text: Explorar solución
                - generic [ref=e255]: →
            - article [ref=e256]:
              - generic [ref=e257]:
                - paragraph [ref=e258]: Segunda valoración médica
                - heading "Valoración de procedimientos previos" [level=3] [ref=e259]
                - generic [ref=e260]:
                  - term [ref=e261]: Qué se valora
                  - definition [ref=e262]: Evolución, materiales utilizados, tiempos biológicos y posibilidad real de corregir o esperar.
                  - term [ref=e263]: Límites
                  - definition [ref=e264]: Una segunda valoración puede concluir que lo indicado es observar, derivar o no intervenir.
              - 'link "Explorar solución: Valoración de procedimientos previos" [ref=e265] [cursor=pointer]':
                - /url: https://staging2.nuvanx.com/madrid/valoracion/
                - text: Explorar solución
                - generic [ref=e266]: →
      - region [ref=e267]:
        - generic [ref=e268]:
          - paragraph [ref=e269]: CÓMO SE CONSTRUYE EL PLAN
          - heading "De la preocupación visible a una indicación documentada." [level=2] [ref=e270]
          - list [ref=e271]:
            - listitem [ref=e272]:
              - text: "01"
              - heading "Escuchar el motivo de consulta" [level=3] [ref=e273]
              - paragraph [ref=e274]: Definimos qué cambio buscas y qué resultado consideras proporcionado.
            - listitem [ref=e275]:
              - text: "02"
              - heading "Explorar anatomía y tejido" [level=3] [ref=e276]
              - paragraph [ref=e277]: Revisamos estructura, grasa, laxitud, superficie, fototipo y antecedentes.
            - listitem [ref=e278]:
              - text: "03"
              - heading "Separar causas y límites" [level=3] [ref=e279]
              - paragraph [ref=e280]: Diferenciamos qué componente puede tratarse y qué requiere otra alternativa.
            - listitem [ref=e281]:
              - text: "04"
              - heading "Documentar el plan" [level=3] [ref=e282]
              - paragraph [ref=e283]: Explicamos técnica, fases, cuidados, seguimiento y presupuesto individualizado.
      - region [ref=e284]:
        - generic [ref=e285]:
          - paragraph [ref=e286]: TU PRIMERA VALORACIÓN
          - heading "No necesitas elegir un tratamiento antes de consultar." [level=2] [ref=e287]
          - paragraph [ref=e288]: Cuéntanos qué zona o cambio quieres valorar. El equipo médico estudiará la causa, las alternativas razonables y los límites antes de proponer cualquier procedimiento.
          - generic [ref=e289]:
            - link "Solicitar valoración médica" [ref=e290] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
            - link "Conocer al equipo médico" [ref=e291] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
  - region "Solicitar valoración médica" [ref=e292]:
    - generic [ref=e293]:
      - generic [ref=e294]:
        - paragraph [ref=e295]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e296]
        - paragraph [ref=e297]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e298]:
        - link "Iniciar mi valoración médica" [ref=e299] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e300] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e301]:
    - generic [ref=e302]:
      - generic [ref=e303]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e304] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e305]: NUVANX
          - generic [ref=e306]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e307]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e308]:
        - generic "Tratamientos"
        - generic [ref=e310]:
          - list [ref=e311]:
            - listitem [ref=e312]:
              - link "Endolift® facial" [ref=e313] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e314]:
              - link "Endoláser corporal" [ref=e315] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e316]:
              - link "Láser CO₂ fraccionado" [ref=e317] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e318]:
              - link "EXION® BTL" [ref=e319] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e320]:
              - link "EXION Face" [ref=e321] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e322]:
              - link "EXION Body" [ref=e323] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e324]:
              - link "EXION Fractional" [ref=e325] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e326]:
              - link "EMFUSION" [ref=e327] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e328]:
              - link "Bioestimuladores de Colágeno" [ref=e329] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e330]:
              - link "Ojeras y Surco Lagrimal" [ref=e331] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e332]:
              - link "Rinomodelación sin Cirugía" [ref=e333] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e334]:
              - link "Labios con Ácido Hialurónico" [ref=e335] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e336]:
            - link "BTL EXILITE™ IPL" [ref=e337] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e338]:
            - link "Ver todos →" [ref=e339] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e340]:
        - generic "Clínicas"
        - list [ref=e342]:
          - listitem [ref=e343]:
            - link "Nuestras clínicas" [ref=e344] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e345]:
            - link "Chamberí" [ref=e346] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e347]:
            - link "Salamanca–Goya" [ref=e348] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e349]:
            - link "Chamberí · 669 319 836" [ref=e350] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e351]:
            - link "Goya · 647 505 107" [ref=e352] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e353]:
        - generic "NUVANX"
        - list [ref=e355]:
          - listitem [ref=e356]:
            - link "Nosotros" [ref=e357] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e358]:
            - link "Por qué NUVANX" [ref=e359] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e360]:
            - link "Inversión" [ref=e361] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e362]:
            - link "Equipo médico" [ref=e363] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e364]:
            - link "Casos de pacientes" [ref=e365] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e366]:
            - link "Blog" [ref=e367] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e368]:
            - link "Contacto" [ref=e369] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e370]:
            - link "Valoración médica" [ref=e371] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e372]:
      - paragraph [ref=e373]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e374]:
        - list [ref=e375]:
          - listitem [ref=e376]:
            - link "Aviso legal" [ref=e377] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e378]:
            - link "Privacidad" [ref=e379] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e380]:
            - link "Cookies" [ref=e381] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e382]: ·
        - paragraph [ref=e383]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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