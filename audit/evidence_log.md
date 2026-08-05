# Registro de evidencias — Auditoría NUVANX

## Producción: página principal
- **URL:** https://nuvanx.com/
- **Fecha de comprobación:** 2026-08-04
- La página principal declara dos sedes: **Chamberí** y **Salamanca–Goya**.
- El pie de página publica enlaces a once tratamientos: Endolift® facial, Endoláser corporal, Láser CO₂ fraccionado, EXION® BTL, EXION Face, EXION Fractional, Bioestimuladores de Colágeno, Ojeras y Surco Lagrimal, Rinomodelación sin Cirugía, Labios con Ácido Hialurónico y BTL EXILITE™ IPL.
- La página identifica al Dr. José Javier Rivera Tejeda como dirección médica para Endolift® y láser CO₂; a la Dra. Ivon Yamileth Rivera Deras para medicina y well-aging; y al Dr. Fabio Augusto Quiñónez Bareiro para medicina e investigación en fisiología del envejecimiento.

## Producción: portafolio enlazado
- **URL comprobada:** https://nuvanx.com/tratamientos/
- **Resultado:** HTTP/contenido de página **404 — Página no encontrada**.
- **Observación:** La URL está enlazada internamente desde la página principal y el pie de página como «Ver portafolio completo», por lo que constituye una inconsistencia de navegación/URL de producción a registrar.
- La página 404 conserva en el pie las once URLs individuales de tratamiento; se usarán únicamente como evidencia de catálogo publicado, no como sustituto del inventario T01–T22 aún no localizado.

## Restricción de inventario
- El encargo exige reutilizar un inventario previo de 22 tratamientos con IDs T01–T22. Hasta este punto no se ha localizado ese artefacto en el entorno de trabajo; no se asignarán IDs ni se reconstruirá el inventario sin evidencia adicional.

## Producción: sitemap de páginas
- **URL:** https://nuvanx.com/page-sitemap.xml
- **Fecha de comprobación:** 2026-08-04
- El sitemap XML de Yoast declara **41 URLs** de tipo página y presenta rutas de tratamientos individuales, rutas por indicación/protocolo y páginas de sedes. Entre las rutas de tratamiento visibles se encuentran Endolift facial, Endoláser corporal, Láser CO₂ fraccionado, EXION® BTL, EXION Face, EXION Fractional, Bioestimuladores, Ojeras, Rinomodelación, Labios y BTL EXILITE™ IPL.
- Además figuran rutas de soluciones/indicaciones corporales: remodelación corporal láser, postparto, contorno corporal masculino, rodillas, muslos, espalda, brazos, papada/definición mandibular y abdomen/flancos; también figuran rutas de calidad de piel y manchas/rojeces/photorejuvenecimiento IPL.
- La inclusión en sitemap es evidencia técnica de URL publicada; no prueba por sí sola la indexación en Google ni el rendimiento orgánico.

## Comprobación técnica de URLs de tratamiento
- **Método:** recuperación directa de cada URL declarada en el sitemap de producción, con lectura de estado HTTP, URL final, título, H1, canonical y meta robots.
- **Fecha de comprobación:** 2026-08-04.
- Las 23 URLs comprobadas respondieron con **HTTP 200**, sin redirección detectada; cada una declaró una URL canonical coincidente con la URL solicitada y una directiva robots que incluye **index, follow**. Esta es una validación técnica de indexabilidad declarada, no una confirmación de indexación efectiva en Google.
- Las rutas verificadas abarcan el catálogo de tecnologías y los tratamientos/indicaciones por zona publicados actualmente. El resultado detallado se conserva en `console_outputs/exec_result_2026-08-04_18-54-04_363.txt`.

## Doctoralia
- **URL:** https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser
- **Fecha de comprobación:** 2026-08-04.
- La ficha pública muestra **105 opiniones**, una dirección visible en **Calle Fernández de la Hoz 4, Bajo Derecha, Madrid 28010**, y opciones de **Reservar cita** y **Enviar mensaje**.
- La presencia de Doctoralia está verificada a nivel de clínica. La asignación de servicios, precios y reseñas a cada tratamiento permanece pendiente de comprobar específicamente; no se extrapolarán los datos de la clínica a todos los tratamientos.

## Protocolos Signature y señales de conversión
- **Página revisada:** https://nuvanx.com/protocolos-signature/.
- La arquitectura publicada identifica explícitamente los protocolos **NUVANX Contour Architecture™**, **NUVANX Post-Maternity Contour™**, **NUVANX Profile Definition™**, **NUVANX Skin Architecture™**, **NUVANX Surface Renewal™** y **NUVANX Tone Correction™**. Cada uno enlaza a una landing de producción concreta.
- Las 23 landings de tratamiento/indicación revisadas incluyen una ruta de valoración online y un enlace de WhatsApp. Todas presentan terminología médica y enlaces internos hacia las 23 landings de la muestra, por lo que la arquitectura interna entre estas páginas está presente.
- La auditoría de texto no detectó enlaces directos a Doctoralia desde las 23 landings individuales. La ficha de Doctoralia se mantiene como evidencia de clínica, no de página de tratamiento.
- En las 23 landings aparecen referencias textuales a precio, presupuesto o inversión. Sólo se detectaron importes explícitos en las páginas revisadas de **Láser CO₂** (330,00 € y 450,00 €) y **Endolift® facial** (798,60 € y 1.064,80 €). Para las demás, la visibilidad de precio explícito se registra como ausente en la página revisada, sin inferir precio no publicado.
- La auditoría detectó marcado FAQPage en EXION Fractional, Bioestimuladores, Ojeras, Rinomodelación, Labios y EXION Face; no fue detectado en las demás páginas de la muestra. El resultado bruto se conserva en `console_outputs/exec_result_2026-08-04_18-56-05_826.txt`.

## Meta Ad Library
- **Consulta pública revisada:** búsqueda «NUVANX» en España, estado «todos los anuncios», 2026-08-04. La interfaz mostró aproximadamente 190 resultados por coincidencia de palabra clave; se atribuyeron a la página Nuvanx los anuncios cuyo anunciante visible era «Nuvanx».
- Se verificó una creatividad de **Láser CO₂**, Library ID **1541359397100607**, marcada como inactiva y con periodo **8 de enero de 2026 a 17 de abril de 2026**. El mensaje describe mejora de luminosidad, textura, manchas, arrugas y cicatrices, con CTA de valoración gratuita.
- Se verificó una creatividad de **Endolift** para rostro y grasa localizada corporal, Library ID **1183939050396915**, inactiva y con periodo **8 de enero de 2026 a 17 de abril de 2026**. El mensaje destaca firmeza facial, grasa localizada y mínima recuperación.
- Se verificó otra agrupación de creatividad vinculada a Endolift, Library ID **1642609980281525**, con periodo **29 de abril de 2026 a 26 de mayo de 2026**, marcada como inactiva; la interfaz indicaba 12 anuncios que usaron esa creatividad y texto.
- El resultado también mostró un anuncio de **Endolift abdominal** para las clínicas de Goya y Chamberí y un anuncio de Endolaser en glúteos, pero los detalles completos no se consolidaron en esta pasada. Se registran como señales comerciales, no como prueba de demanda ni de rendimiento.
- Se identificó asimismo una entrada asociada a una cuenta o página deshabilitada por incumplimiento de las normas publicitarias. No se atribuye a NUVANX como evidencia comercial útil sin confirmación adicional.

## Google Maps — comprobación por sede
- **Chamberí:** la consulta «NUVANX Chamberí Madrid» mostró la ficha **NUVANX Medicina Estética Láser Chamberí**, con valoración visible **5,0/5 (14 reseñas)**, dirección **Calle de Fernández de la Hoz 4, Bajo derecha, Chamberí, 28010 Madrid**, teléfono **+34 669 31 98 36**, enlace a NUVANX y opción de reserva online dirigida a Doctoralia.
- **Salamanca–Goya:** la consulta «NUVANX Goya Madrid» mostró la ficha **NUVANX Medicina Estética Láser Salamanca - Goya**, con valoración visible **5,0/5 (5 reseñas)**, dirección **C/ de Fernán González 26, Salamanca, 28009 Madrid**, teléfono **+34 647 50 51 07**, enlace a NUVANX, WhatsApp y opción de reserva online.
- En ambas comprobaciones, las fichas se recuperaron mediante una búsqueda directa de marca y sede. Por tanto, prueban presencia de ficha local por sede, pero **no** permiten evaluar la posición competitiva de NUVANX para cada búsqueda de tratamiento genérica. Maps por tratamiento se mantiene como **N/D/no testado** cuando no se haya ejecutado una consulta específica y reproducible.

## SERP — evidencia puntual y limitación de acceso
- **Consulta «Endolift facial Madrid»** en Google España, realizada el 2026-08-04: se observaron resultados orgánicos visibles de Eleca Clinic, Templa y Clínica Eguren, además de un bloque de «Sitios de lugares» con Doctoralia, Top Doctors, Facebook y Atrápalo. Se mostró un bloque local con VenusMed, Clínica Holivine y Endolifter · Dr. Daniel Quirós; NUVANX no figuraba entre los tres negocios locales visibles en la captura. Esta es una observación puntual, geolocalizada y no una medición histórica de ranking.
- La página de resultados también mostró un precio de 1.400,00 € asociado a un resultado de Templa; se registra únicamente como señal competitiva visible, no como referencia de precio de NUVANX.
- La consulta equivalente para «Endoláser corporal Madrid» quedó limitada por un CAPTCHA antes de recuperar resultados utilizables. En consecuencia, no se asigna posición orgánica ni de Maps para esa intención.
- El bloqueo de CAPTCHA impide una auditoría exhaustiva y reproducible de SERP para todas las 22 rutas desde esta sesión. Los campos sin comprobación directa se mantienen como **N/D** o **no_testado**, según corresponda.
