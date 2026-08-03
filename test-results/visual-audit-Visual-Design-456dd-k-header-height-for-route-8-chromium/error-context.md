# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: visual-audit.spec.ts >> Visual Design Audit >> Header Consistency Check >> check header height for route 8
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
          - generic [ref=e54]:
            - paragraph [ref=e55]: NUVANX · Equipo médico
            - 'heading "Equipo médico NUVANX: quién te valora y quién trata" [level=1] [ref=e56]'
            - paragraph [ref=e57]: Médicos con práctica hospitalaria y consulta estética en Madrid. Dirección médica, well-aging y valoración clínica antes de cualquier protocolo láser.
            - paragraph [ref=e58]: Dr. José Javier Rivera Tejeda (ICOMEM 282864786), director médico; Dra. Ivon Yamileth Rivera Deras (ICOMEM 284621525), well-aging y geriatría preventiva; y Dr. Fabio Augusto Quiñónez Bareiro (ICOMEM 282877543), geriatría y paciente complejo — junto al resto del equipo clínico NUVANX.
            - generic [ref=e59]:
              - button "Iniciar mi valoración médica" [ref=e60]
              - link "Contactar por WhatsApp" [ref=e61] [cursor=pointer]:
                - /url: https://wa.me/34669319836
            - paragraph [ref=e64]: Chamberí · Goya · Medicina basada en evidencia
        - generic [ref=e65]:
          - generic [ref=e66]:
            - region [ref=e67]:
              - generic [ref=e68]:
                - figure [ref=e69]:
                  - img "Dr. José Javier Rivera" [ref=e70]
                - generic [ref=e71]:
                  - paragraph [ref=e72]: Director médico
                  - 'heading "Dr. José Javier Rivera Tejeda: Director Médico e Investigador Clínico" [level=2] [ref=e73]'
                  - paragraph [ref=e74]: Con número de colegiación ICOMEM 282864786, el Dr. José Javier Rivera Tejeda ostenta la Dirección Médica de las clínicas NUVANX en Madrid. Médico estético hiper-especializado en la aplicación avanzada de tecnologías láser intervencionistas y medicina regenerativa tisular.
                  - paragraph [ref=e75]:
                    - text: Su perfil público en
                    - link "Doctoralia" [ref=e76] [cursor=pointer]:
                      - /url: https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid
                    - text: "concentra reseñas certificadas de pacientes (consultables en el directorio). Es el responsable del diseño de los protocolos de tratamiento en NUVANX: la aparatología se subordina al diagnóstico, no al revés."
            - region [ref=e77]:
              - generic [ref=e78]:
                - paragraph [ref=e79]: Ámbito clínico
                - heading "Subespecialización y experiencia" [level=2] [ref=e80]
                - list [ref=e81]:
                  - listitem [ref=e82]:
                    - heading "Láser intersticial avanzado" [level=3] [ref=e83]
                    - paragraph [ref=e84]: Endolift® y laserlipólisis para modificación estructural de grasa submentoniana y corporal en casos seleccionados.
                  - listitem [ref=e85]:
                    - heading "Dermatología láser ablativa" [level=3] [ref=e86]
                    - paragraph [ref=e87]: Láser CO₂ fraccionado orientado a secuelas de acné, textura y fotodaño, con planificación de downtime.
                  - listitem [ref=e88]:
                    - heading "Arquitectura y geometría facial" [level=3] [ref=e89]
                    - paragraph [ref=e90]: Restauración volumétrica con inductores de colágeno (p. ej. Radiesse®, Ellansé®) y neuromoduladores cuando el diagnóstico lo indica — tras tensar, no al revés.
                  - listitem [ref=e91]:
                    - heading "Tricología médica" [level=3] [ref=e92]
                    - paragraph [ref=e93]: Abordaje médico del cabello y cuero cabelludo dentro del alcance de la consulta especializada.
            - region [ref=e94]:
              - generic [ref=e95]:
                - generic [ref=e96]:
                  - paragraph [ref=e97]: Formación
                  - heading "Formación académica y trayectoria" [level=2] [ref=e98]
                  - paragraph [ref=e99]: Máster Universitario en Medicina Estética por la Universidad Complutense de Madrid (UCM). Máster especializado en Tricología y Cirugía Capilar (AMIR).
                  - paragraph [ref=e100]: Trayectoria como director de cirugía cosmética láser en cadenas hospitalarias de referencia (Clínicas Londres, Clínicas Dr. Esquivel), aplicada hoy al modelo de doble sede NUVANX.
                - complementary "Identidad profesional" [ref=e101]:
                  - paragraph [ref=e102]: Identidad
                  - list [ref=e103]:
                    - listitem [ref=e104]:
                      - strong [ref=e105]: Colegiado
                      - text: — ICOMEM 282864786
                    - listitem [ref=e106]:
                      - strong [ref=e107]: Cargo
                      - text: — Director médico NUVANX Madrid
                    - listitem [ref=e108]:
                      - strong [ref=e109]: Sedes
                      - text: — Chamberí y Goya · Barrio Salamanca
                    - listitem [ref=e110]:
                      - strong [ref=e111]: Agenda
                      - text: — Mar/Jue Chamberí · Mié Goya
            - region "Visión clínica de Dr. J.J. Rivera Tejeda" [ref=e112]:
              - blockquote [ref=e114]:
                - paragraph [ref=e115]: Mi visión clínica rechaza la transformación anatómica artificial. La tecnología láser más sofisticada debe emplearse para desencadenar la regeneración celular propia del paciente, logrando una firmeza biológica real, no un aspecto quirúrgico evidente.
                - generic [ref=e116]: — Dr. J.J. Rivera Tejeda
          - generic [ref=e117]:
            - region [ref=e118]:
              - generic [ref=e119]:
                - figure [ref=e120]:
                  - img "Ivon Rivera - NUVANX"
                - generic [ref=e121]:
                  - paragraph [ref=e122]: Well-aging y geriatría preventiva
                  - 'heading "Dra. Ivon Yamileth Rivera Deras: Referente Científico en Well-Aging y Geriatría Preventiva" [level=2] [ref=e123]'
                  - paragraph [ref=e124]: Colegiada ICOMEM 284621525. La Dra. Rivera Deras aporta experiencia en medicina funcional, longevidad y well-aging. Su actividad asistencial e investigadora contribuye a que los protocolos se revisen con criterio clínico y evidencia aplicable.
            - region [ref=e125]:
              - generic [ref=e126]:
                - paragraph [ref=e127]: Asistencia pública
                - heading "Actividad asistencial hospitalaria" [level=2] [ref=e128]
                - paragraph [ref=e129]: Médico Especialista (FEA) por concurso selectivo en el Hospital Universitario La Paz, en Unidad de Recuperación Funcional y Hospital de Día Geriátrico. Forma parte del cuadro médico del Hospital Central de la Cruz Roja San José y Santa Adela, centro de referencia en neurorrehabilitación y atención al adulto mayor.
            - region [ref=e130]:
              - generic [ref=e131]:
                - generic [ref=e132]:
                  - paragraph [ref=e133]: Investigación
                  - heading "Investigación, sociedades y academia" [level=2] [ref=e134]
                  - list [ref=e135]:
                    - listitem [ref=e136]:
                      - heading "Real-World Evidence" [level=3] [ref=e137]
                      - paragraph [ref=e138]: Investigadora clínica externa y consultora médica para OXON Epidemiology.
                    - listitem [ref=e139]:
                      - heading "SEMEG y EuGMS" [level=3] [ref=e140]
                      - paragraph [ref=e141]: Coordinadora científica de las Jornadas de Deterioro Cognitivo de la Sociedad Española de Medicina Geriátrica (SEMEG) y colaboración activa con la European Geriatric Medicine Society (EuGMS).
                    - listitem [ref=e142]:
                      - heading "Universidad Europea de Madrid" [level=3] [ref=e143]
                      - paragraph [ref=e144]: Profesora e investigadora en la UEM, vinculada al Hospital Vithas Madrid Arturo Soria. Formación continuada de facultativos, enfermería y TCAE en hospitales del SERMAS.
                    - listitem [ref=e145]:
                      - heading "Obra escrita y publicaciones" [level=3] [ref=e146]
                      - paragraph [ref=e147]: Coautora de obras bioéticas y clínicas como «El tormento de la inmortalidad sin juventud» y del «Manual de manejo de personas mayores que sufren caídas» (SEMEG), además de trabajos sobre cribado cognitivo temprano.
                - complementary "Identidad profesional" [ref=e148]:
                  - paragraph [ref=e149]: Identidad
                  - list [ref=e150]:
                    - listitem [ref=e151]:
                      - strong [ref=e152]: Colegiada
                      - text: — ICOMEM 284621525
                    - listitem [ref=e153]:
                      - strong [ref=e154]: Ámbito
                      - text: — Well-aging · Geriatría preventiva · Longevidad
                    - listitem [ref=e155]:
                      - strong [ref=e156]: Asistencia
                      - text: — La Paz · Cruz Roja
                    - listitem [ref=e157]:
                      - strong [ref=e158]: Sociedades
                      - text: — SEMEG · EuGMS
          - generic [ref=e159]:
            - region [ref=e160]:
              - generic [ref=e161]:
                - figure [ref=e162]:
                  - img "Fabio Quiñonez - NUVANX"
                - generic [ref=e163]:
                  - paragraph [ref=e164]: Geriatría, gerontología y paciente complejo
                  - 'heading "Dr. Fabio Augusto Quiñónez Bareiro: Especialista en Geriatría, Gerontología y Paciente Complejo" [level=2] [ref=e165]'
                  - paragraph [ref=e166]: Colegiado ICOMEM 282877543. El Dr. Quiñónez Bareiro refuerza la unidad de medicina regenerativa y longevidad de NUVANX con experiencia en fisiología del envejecimiento y abordaje clínico del paciente complejo.
            - region [ref=e167]:
              - generic [ref=e168]:
                - paragraph [ref=e169]: Asistencia
                - heading "Experiencia clínica y asistencial" [level=2] [ref=e170]
                - paragraph [ref=e171]: Facultativo Especialista de Área (FEA) en el Servicio de Geriatría del Hospital Virgen del Valle (Toledo). Trayectoria en SESCAM y Madrid con etapa clave en el Complejo Hospitalario Universitario de Toledo. Experiencia previa en pacientes críticos en Urgencias del Hospital Virgen de la Salud, y labor asistencial en el Hospital de Emergencias Enfermera Isabel Zendal y el Hospital Quirónsalud Tres Culturas.
            - region [ref=e172]:
              - generic [ref=e173]:
                - paragraph [ref=e174]: Investigación
                - heading "Investigación, congresos y casos clínicos" [level=2] [ref=e175]
                - list [ref=e176]:
                  - listitem [ref=e177]:
                    - heading "CIBERFES y SEMEG" [level=3] [ref=e178]
                    - paragraph [ref=e179]: Investigador activo asociado al CIBER de Fragilidad y Envejecimiento Saludable (CIBERFES) y colaborador de la Sociedad Española de Medicina Geriátrica (SEMEG).
                  - listitem [ref=e180]:
                    - heading "Estudio Toledo · Envejecimiento saludable" [level=3] [ref=e181]
                    - paragraph [ref=e182]: Trabajos que proponen el uso de la velocidad de onda de pulso (cf-PWV) para la detección temprana del deterioro cognitivo en el marco del Estudio Toledo para el Envejecimiento Saludable.
                  - listitem [ref=e183]:
                    - heading "Casos y diagnóstico diferencial" [level=3] [ref=e184]
                    - paragraph [ref=e185]: Coautoría en «¿Será una infección del tracto urinario?» (diagnósticos diferenciales entre delírium e infección en el anciano) e investigaciones sobre riesgo cardiovascular mal controlado, síncopes y fracturas de cadera.
            - region [ref=e186]:
              - generic [ref=e187]:
                - generic [ref=e188]:
                  - paragraph [ref=e189]: Docencia
                  - heading "Labor docente y formación académica" [level=2] [ref=e190]
                  - paragraph [ref=e191]: "Profesor Colaborador en TECH Universidad: dirige el Curso Universitario en Paciente Anciano Crónico Complejo (pluripatología: diabetes, insuficiencia cardíaca y demencia) y diseña contenidos del Experto en Patología Osteoarticular (artrosis, osteoporosis y dolor avanzado)."
                  - paragraph [ref=e192]: Doctor (Ph.D.) por la Universidad Autónoma de Madrid (UAM) con la tesis «Disfunción vascular sub-clínica, declinar cognitivo y fragilidad». Máster en Psicogeriatría (UAB). Licenciado en Medicina por la ELAM.
                - complementary "Identidad profesional" [ref=e193]:
                  - paragraph [ref=e194]: Identidad
                  - list [ref=e195]:
                    - listitem [ref=e196]:
                      - strong [ref=e197]: Colegiado
                      - text: — ICOMEM 282877543
                    - listitem [ref=e198]:
                      - strong [ref=e199]: Ámbito
                      - text: — Geriatría · Paciente complejo · Longevidad
                    - listitem [ref=e200]:
                      - strong [ref=e201]: Doctorado
                      - text: — UAM
                    - listitem [ref=e202]:
                      - strong [ref=e203]: Redes
                      - text: — CIBERFES · SEMEG
          - generic [ref=e204]:
            - region [ref=e205]:
              - generic [ref=e206]:
                - figure [ref=e207]:
                  - img "Cristina Marquez - NUVANX"
                - generic [ref=e208]:
                  - paragraph [ref=e209]: Radiología mamaria y medicina estética
                  - heading "Dra. Cristina Márquez González" [level=2] [ref=e210]
                  - paragraph [ref=e211]:
                    - strong [ref=e212]: Colegiada ICOMEM 282858861.
                    - text: Radióloga y médica estética, especialista en radiología mamaria y diagnóstico mamario avanzado, con práctica como facultativa especialista en HM Hospitales.
                  - paragraph [ref=e213]:
                    - strong [ref=e214]: "Formación:"
                    - text: Licenciatura en Medicina · Especialización en Senología y Patología Mamaria · Máster en Medicina Estética.
                  - paragraph [ref=e215]:
                    - text: Su
                    - link "perfil profesional y opiniones en Doctoralia" [ref=e216] [cursor=pointer]:
                      - /url: https://www.doctoralia.es/cristina-marquez-gonzalez-2/radiologo-medico-estetico/madrid
                    - text: permiten consultar públicamente su especialidad, colegiación, formación y actividad asistencial.
            - region "Identidad profesional" [ref=e217]:
              - complementary "Identidad profesional" [ref=e219]:
                - paragraph [ref=e220]: Identidad
                - list [ref=e221]:
                  - listitem [ref=e222]:
                    - strong [ref=e223]: Colegiada
                    - text: — ICOMEM 282858861
                  - listitem [ref=e224]:
                    - strong [ref=e225]: Especialidades
                    - text: — Radiología · Medicina estética
                  - listitem [ref=e226]:
                    - strong [ref=e227]: Área clínica
                    - text: — Radiología mamaria · Senología
                  - listitem [ref=e228]:
                    - strong [ref=e229]: Sede NUVANX
                    - text: — Goya · Barrio Salamanca
          - region [ref=e230]:
            - generic [ref=e231]:
              - paragraph [ref=e232]: Equipo clínico
              - heading "Resto del equipo médico NUVANX" [level=2] [ref=e233]
              - paragraph [ref=e234]: Profesionales que atienden valoración, seguimiento y protocolos en Chamberí y Goya, junto a la dirección médica y al criterio científico de la clínica.
              - generic [ref=e235]:
                - article [ref=e236]:
                  - figure [ref=e237]:
                    - img "Francisco Geraldo" [ref=e238]
                  - paragraph [ref=e239]: Dirección clínica · CEO
                  - heading "Francisco Geraldo Lorenzo" [level=3] [ref=e240]
                  - paragraph [ref=e241]: CEO · Dirección Clínica · Experiencia del paciente.
                  - paragraph [ref=e242]: Enfermero colegiado. Coordina la experiencia del paciente, la preparación dermocosmética y los cuidados post-láser (EXION®, Endolift®).
                  - paragraph [ref=e243]: Enfermero colegiado nº 97969.
                - article [ref=e244]:
                  - figure [ref=e245]:
                    - img "Yolanda Piñero" [ref=e246]
                  - paragraph [ref=e247]: Dirección de centro · Goya
                  - heading "Yolanda Piñero Berral" [level=3] [ref=e248]
                  - paragraph [ref=e249]: Directora · NUVANX Goya.
                  - paragraph [ref=e250]: Coordina la atención en clínica, la experiencia del paciente y el estándar operativo de la sede Goya.
                  - paragraph [ref=e251]: Asegura una experiencia premium integral.
  - region "Solicitar valoración médica" [ref=e252]:
    - generic [ref=e253]:
      - generic [ref=e254]:
        - paragraph [ref=e255]: Medicina estética con criterio clínico
        - heading "Da el siguiente paso con una valoración médica personalizada." [level=2] [ref=e256]
        - paragraph [ref=e257]: Plan individualizado • Precisión clínica • Recuperación según tu caso
      - generic [ref=e258]:
        - link "Iniciar mi valoración médica" [ref=e259] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/madrid/valoracion/
        - link "Contactar por WhatsApp" [ref=e260] [cursor=pointer]:
          - /url: https://wa.me/34669319836
  - contentinfo [ref=e261]:
    - generic [ref=e262]:
      - generic [ref=e263]:
        - link "NUVANX MEDICINA ESTÉTICA LÁSER — Inicio" [ref=e264] [cursor=pointer]:
          - /url: https://staging2.nuvanx.com/
          - generic [ref=e265]: NUVANX
          - generic [ref=e266]: MEDICINA ESTÉTICA LÁSER
        - paragraph [ref=e267]: Madrid · ChamberíMadrid · Salamanca
      - group [ref=e268]:
        - generic "Tratamientos"
        - generic [ref=e270]:
          - list [ref=e271]:
            - listitem [ref=e272]:
              - link "Endolift® facial" [ref=e273] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolift-facial-papada-mandibula/
            - listitem [ref=e274]:
              - link "Endoláser corporal" [ref=e275] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/endolaser-corporal-grasa-localizada/
            - listitem [ref=e276]:
              - link "Láser CO₂ fraccionado" [ref=e277] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/laser-co2-fraccionado-madrid-textura-cicatrices-poro/
            - listitem [ref=e278]:
              - link "EXION® BTL" [ref=e279] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-btl/
            - listitem [ref=e280]:
              - link "EXION Face" [ref=e281] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-face/
            - listitem [ref=e282]:
              - link "EXION Body" [ref=e283] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-body/
            - listitem [ref=e284]:
              - link "EXION Fractional" [ref=e285] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/exion-fractional/
            - listitem [ref=e286]:
              - link "EMFUSION" [ref=e287] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/emfusion/
            - listitem [ref=e288]:
              - link "Bioestimuladores de Colágeno" [ref=e289] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/bioestimuladores-colageno-madrid/
            - listitem [ref=e290]:
              - link "Ojeras y Surco Lagrimal" [ref=e291] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/ojeras-surco-lagrimal-madrid/
            - listitem [ref=e292]:
              - link "Rinomodelación sin Cirugía" [ref=e293] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/rinomodelacion-sin-cirugia-madrid/
            - listitem [ref=e294]:
              - link "Labios con Ácido Hialurónico" [ref=e295] [cursor=pointer]:
                - /url: https://staging2.nuvanx.com/labios-acido-hialuronico-madrid/
          - text: "?>"
          - listitem [ref=e296]:
            - link "BTL EXILITE™ IPL" [ref=e297] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/btl-exilite-ipl-madrid/
          - listitem [ref=e298]:
            - link "Ver todos →" [ref=e299] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/tratamientos/
      - group [ref=e300]:
        - generic "Clínicas"
        - list [ref=e302]:
          - listitem [ref=e303]:
            - link "Nuestras clínicas" [ref=e304] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/
          - listitem [ref=e305]:
            - link "Chamberí" [ref=e306] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/medicina-estetica-chamberi/
          - listitem [ref=e307]:
            - link "Salamanca–Goya" [ref=e308] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/
          - listitem [ref=e309]:
            - link "Chamberí · 669 319 836" [ref=e310] [cursor=pointer]:
              - /url: tel:+34669319836
          - listitem [ref=e311]:
            - link "Goya · 647 505 107" [ref=e312] [cursor=pointer]:
              - /url: tel:+34647505107
      - group [ref=e313]:
        - generic "NUVANX"
        - list [ref=e315]:
          - listitem [ref=e316]:
            - link "Nosotros" [ref=e317] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/nosotros/
          - listitem [ref=e318]:
            - link "Por qué NUVANX" [ref=e319] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/por-que-nuvanx/
          - listitem [ref=e320]:
            - link "Inversión" [ref=e321] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/inversion-medicina-estetica/
          - listitem [ref=e322]:
            - link "Equipo médico" [ref=e323] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/equipo-medico/
          - listitem [ref=e324]:
            - link "Casos de pacientes" [ref=e325] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/casos-de-pacientes/
          - listitem [ref=e326]:
            - link "Blog" [ref=e327] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/blog/
          - listitem [ref=e328]:
            - link "Contacto" [ref=e329] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/contacto/
          - listitem [ref=e330]:
            - link "Valoración médica" [ref=e331] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/madrid/valoracion/
    - generic [ref=e332]:
      - paragraph [ref=e333]: © 2026 NUVANX. Todos los derechos reservados.
      - navigation "Información legal" [ref=e334]:
        - list [ref=e335]:
          - listitem [ref=e336]:
            - link "Aviso legal" [ref=e337] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/aviso-legal/
          - listitem [ref=e338]:
            - link "Privacidad" [ref=e339] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-privacidad/
          - listitem [ref=e340]:
            - link "Cookies" [ref=e341] [cursor=pointer]:
              - /url: https://staging2.nuvanx.com/politica-de-cookies-ue/
        - generic [ref=e342]: ·
        - paragraph [ref=e343]: Chamberí · Centro sanitario autorizado CS20144 · Salamanca · Centro sanitario autorizado CS20073
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