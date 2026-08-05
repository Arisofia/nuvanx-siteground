# Auditoría de catálogo NUVANX — corte 2026-08-04

> **Alcance y trazabilidad.** Esta entrega reutiliza únicamente evidencia pública obtenida de producción, sitemap, páginas de soluciones/protocolos, Doctoralia, Google Maps y Meta Ad Library. No modifica código, diseño ni producción. Las claves **C01–C22** son claves de conciliación temporales: el material suministrado no contenía el mapeo original **T01–T22**, por lo que no se asignaron ni sustituyeron dichos IDs.

## A. Validation summary

Se reconciliaron **22 entidades de catálogo de producción** con URL individual, sin URL duplicada en esta tabla y con respuesta 200 comprobada. La página marco **Contour Architecture™** (`/remodelacion-corporal-laser-madrid/`) respondió 200, pero se dejó fuera de las 22 entidades porque actúa como página padre/arquitectura y necesita confirmación contra el inventario T01–T22. La URL de portafolio enlazada internamente `https://nuvanx.com/tratamientos/` devolvió 404; es la inconsistencia de navegación principal. [1] [2] [3]

| Indicador | Resultado |
| --- | --- |
| Entidades reconciliadas | 22 |
| URL de las 22 con estado técnico verificado | 200, sin redirección detectada |
| URLs duplicadas dentro de las 22 | 0 |
| ID original T01–T22 disponible | No; pendiente de inventario histórico |
| URL problemática detectada | `/tratamientos/` enlazada desde producción devuelve 404 |
| Precio numérico visible | Endolift® facial y Láser CO₂ fraccionado |
| Fichas Maps de marca verificadas | Chamberí y Salamanca–Goya |
| Ficha Doctoralia de clínica verificada | Sí; 105 opiniones visibles |
| Volúmenes de demanda, GSC y conversiones | N/D |

## B. treatments_master_validated

La tabla completa y normalizada está disponible en `treatments_master_validated.csv`. La vista de control conserva las claves de conciliación, el nombre validado, la categoría, la indexabilidad declarada y la visibilidad de precio.

| Clave | Tratamiento / solución | Categoría | HTTP | Precio | Indexación declarada |
| --- | --- | --- | --- | --- | --- |
| C01 | Endolift® facial | Tecnología · facial | 200 | Visible (alcance de cada importe pendiente de reconciliar) | Declarada: index, follow; indexación efectiva en Google no verificada |
| C02 | Endoláser corporal | Tecnología · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C03 | Láser CO₂ fraccionado | Tecnología · renovación cutánea | 200 | Visible | Declarada: index, follow; indexación efectiva en Google no verificada |
| C04 | EXION® BTL | Tecnología · plataforma | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C05 | EXION® Face | Tecnología · facial | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C06 | EXION® Fractional | Tecnología · radiofrecuencia fraccionada | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C07 | Bioestimuladores de colágeno | Medicina estética facial | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C08 | Ojeras y surco lagrimal | Medicina estética · periocular | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C09 | Rinomodelación sin cirugía | Medicina estética facial | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C10 | Labios con ácido hialurónico | Medicina estética facial | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C11 | BTL EXILITE™ IPL | Tecnología · IPL | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C12 | NUVANX Profile Definition™ | Protocolo Signature · rostro y cuello | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C13 | NUVANX Skin Architecture™ | Protocolo Signature · piel | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C14 | NUVANX Surface Renewal™ | Protocolo Signature · piel | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C15 | NUVANX Tone Correction™ | Protocolo Signature · piel | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C16 | NUVANX Contour · abdomen y flancos | Contour Architecture™ · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C17 | NUVANX Contour · brazos y continuidad axilar | Contour Architecture™ · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C18 | NUVANX Contour · espalda y zona del sujetador | Contour Architecture™ · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C19 | NUVANX Contour · muslos y región subglútea | Contour Architecture™ · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C20 | NUVANX Contour · rodillas | Contour Architecture™ · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C21 | NUVANX Post-Maternity Contour™ | Protocolo Signature · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |
| C22 | NUVANX Male Contour | Planificación específica · corporal | 200 | No se detectó importe explícito en la landing | Declarada: index, follow; indexación efectiva en Google no verificada |

## C. treatments_opportunity

No se inventaron demanda, ticket, rendimiento de conversión ni rankings. Por ello, los **22 opportunity scores permanecen N/D**, conforme a la regla de no calcular si falta cualquiera de los inputs críticos. El archivo íntegro está disponible en `treatments_opportunity.csv`.

| Clave | Prioridad | SERP | Maps | Competencia | Gap | Score |
| --- | --- | --- | --- | --- | --- | --- |
| C01 | P1 | mixta | baja | alta | competencia domina SERP | N/D |
| C02 | P1 | organic | N/D | alta | precio ausente/confuso | N/D |
| C03 | P2 | N/D | N/D | N/D | N/D | N/D |
| C04 | P3 | N/D | N/D | N/D | N/D | N/D |
| C05 | P3 | N/D | N/D | N/D | N/D | N/D |
| C06 | P3 | N/D | N/D | N/D | N/D | N/D |
| C07 | P2 | N/D | N/D | N/D | N/D | N/D |
| C08 | P2 | N/D | N/D | N/D | N/D | N/D |
| C09 | P3 | N/D | N/D | N/D | N/D | N/D |
| C10 | P3 | N/D | N/D | N/D | N/D | N/D |
| C11 | P3 | N/D | N/D | N/D | N/D | N/D |
| C12 | P1 | organic | N/D | alta | competencia domina SERP | N/D |
| C13 | P3 | N/D | N/D | N/D | N/D | N/D |
| C14 | P2 | N/D | N/D | N/D | N/D | N/D |
| C15 | P3 | N/D | N/D | N/D | N/D | N/D |
| C16 | P2 | N/D | N/D | N/D | N/D | N/D |
| C17 | P2 | N/D | N/D | N/D | N/D | N/D |
| C18 | P2 | N/D | N/D | N/D | N/D | N/D |
| C19 | P2 | N/D | N/D | N/D | N/D | N/D |
| C20 | P2 | N/D | N/D | N/D | N/D | N/D |
| C21 | P2 | N/D | N/D | N/D | N/D | N/D |
| C22 | P2 | N/D | N/D | N/D | N/D | N/D |

## D. Priority list

La clasificación es **operativa y provisional**, no una estimación de mercado. Los P1 preservan las tres prioridades estratégicas del encargo, con confianza media y tareas explícitas para completar la evidencia que falta. P2 agrupa landings con señal comercial/publicada pero sin datos críticos completos. P3 significa «no invertir ahora hasta validar», no ausencia definitiva de potencial.

| Prioridad | Clave | Entidad | Justificación de una línea |
| --- | --- | --- | --- |
| P1 | C01 | Endolift® facial | P1 preliminar: prioridad estratégica indicada en el encargo; landing con importe y activos Meta; competencia validada como alta. |
| P1 | C02 | Endoláser corporal | P1 preliminar: prioridad estratégica indicada en el encargo; landing y activos Meta; sin volumen, precio o ranking reproducible. |
| P1 | C12 | NUVANX Profile Definition™ | P1 preliminar: prioridad estratégica indicada en el encargo y presencia orgánica candidata; la SERP muestreada presenta competencia alta. |
| P2 | C03 | Láser CO₂ fraccionado | P2: landing con precios y promoción Meta verificada, pero sin demanda, competencia ni conversión comparables. |
| P2 | C07 | Bioestimuladores de colágeno | P2: tratamiento individual con landing y FAQ; sin volumen, ticket ni conversión verificables. |
| P2 | C08 | Ojeras y surco lagrimal | P2: tratamiento individual con landing y FAQ; sin volumen, ticket ni conversión verificables. |
| P2 | C14 | NUVANX Surface Renewal™ | P2: potencial indicado en el encargo; landing publicada, pero sin volumen, ticket ni conversión verificables. |
| P2 | C16 | NUVANX Contour · abdomen y flancos | P2: potencial indicado en el encargo; landing por zona y señal publicitaria de Endolift abdominal, sin datos críticos completos. |
| P2 | C17 | NUVANX Contour · brazos y continuidad axilar | P2: solución Contour publicada; sin datos críticos completos. |
| P2 | C18 | NUVANX Contour · espalda y zona del sujetador | P2: solución Contour publicada; sin datos críticos completos. |
| P2 | C19 | NUVANX Contour · muslos y región subglútea | P2: solución Contour publicada; sin datos críticos completos. |
| P2 | C20 | NUVANX Contour · rodillas | P2: solución Contour publicada; sin datos críticos completos. |
| P2 | C21 | NUVANX Post-Maternity Contour™ | P2: protocolo Signature publicado; sin datos críticos completos. |
| P2 | C22 | NUVANX Male Contour | P2: solución específica publicada; sin datos críticos completos. |
| P3 | C04 | EXION® BTL | P3: plataforma publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C05 | EXION® Face | P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C06 | EXION® Fractional | P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C09 | Rinomodelación sin cirugía | P3: landing publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C10 | Labios con ácido hialurónico | P3: landing publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C11 | BTL EXILITE™ IPL | P3: tecnología publicada; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C13 | NUVANX Skin Architecture™ | P3: protocolo publicado; sin evidencia suficiente de demanda, ticket o conversión. |
| P3 | C15 | NUVANX Tone Correction™ | P3: protocolo publicado; sin evidencia suficiente de demanda, ticket o conversión. |

## E. Action Tracker

Sólo se crearon acciones relacionadas con un gap documentado de P1. No se han ejecutado cambios en ninguna propiedad de producción.

| Action ID | Clave | Tipo | Problema | Acción | Estado |
| --- | --- | --- | --- | --- | --- |
| SEO-TRT-ENDO-01 | C01 | SEO | Competencia domina SERP; en la consulta puntual no se observó NUVANX entre los resultados locales visibles. | Comparar la landing con los competidores orgánicos observados y cerrar únicamente las brechas documentadas de entidad médica, contenido de intención y evidencia clínica, con revisión facultativa previa. | Pendiente |
| LOCAL-TRT-ENDO-01 | C01 | LOCAL | La ficha de marca existe en ambas sedes, pero NUVANX no figuró entre los tres negocios locales visibles para «Endolift facial Madrid». | Revisar categorías, servicios, URL de destino y datos de las dos fichas locales para Endolift; activar una captura de reseñas verificadas y no incentivadas cuando proceda clínicamente. | Pendiente |
| CONV-TRT-ENDO-01 | C01 | CONVERSION | La landing publica dos importes; el alcance, la zona, las inclusiones y la vigencia de cada uno requieren una comprobación operativa. | Verificar precio, zona, IVA, inclusiones y vigencia con el tarifario operativo; explicar el alcance de cada importe antes de modificar cualquier canal público. | Pendiente |
| CONV-TRT-ENDOLASER-01 | C02 | CONVERSION | La landing contiene referencia a inversión/planificación, pero no se detectó importe explícito. | Decidir y documentar si conviene publicar un rango o explicar explícitamente por qué el presupuesto es individual; medir por separado clic a valoración y WhatsApp antes de juzgar rendimiento. | Pendiente |
| SEO-TRT-PROFILE-01 | C12 | SEO | Competencia domina SERP en la muestra de papada/definición mandibular; la posición de NUVANX no fue verificable. | Ejecutar una comparación local, fechada y reproducible de las variantes de intención papada, mandíbula y perfil; actualizar la página sólo contra brechas comprobadas. | Pendiente |
| GEO-TRT-PROFILE-01 | C12 | GEO | Presencia GEO/AI no testada. | Probar y conservar evidencia de las consultas «tratamiento + Madrid», «+ Chamberí», «+ Goya» y «mejor clínica» para Profile Definition antes de abrir tareas de optimización GEO. | Pendiente |

## F. Control Tower schema

La estructura propuesta usa una relación 1:1 entre master y opportunity mediante `treatment_id` cuando se cargue el inventario histórico, y una relación N:1 desde `action_tracker` hacia cada tratamiento. `evidence_sources` se conserva como relación 1:N para no perder trazabilidad de cada validación. Los esquemas y filtros importables están en `control_tower_schema.csv` y `control_tower_filters.csv`.

| Filtro | Valores |
| --- | --- |
| Prioridad | P1 / P2 / P3 / N/D |
| Sede | Chamberí / Salamanca–Goya / Ambas / N/D |
| Categoría | Tecnología / Protocolo Signature / Contour / Medicina estética |
| Situación | no_aparece / aparece_no_convierte / aparece_convierte_bien / N/D |
| Demanda | alta / media / baja / N/D |
| Ticket | alto / medio / bajo / N/D |
| Competencia | alta / media / baja / N/D |
| GEO/AI | menciona_nuvanx / menciona_competidores / no_menciona_nadie / no_testado |
| Estado de validación | verificado / parcial / N/D |

## G. Data quality

La validación técnica de URL, canonical, directiva `index, follow`, rutas de reserva/WhatsApp, precios visibles puntuales, fichas de marca Maps, ficha de clínica Doctoralia y creatividades Meta se apoya en evidencia pública fechada. [1] [4] [5] [6] [7] [8] [9]

La **indexación efectiva**, los volúmenes de demanda, la posición estable en SERP, la visibilidad Maps por cada tratamiento, los resultados GEO/AI, la disponibilidad por sede, las reseñas específicas de tratamiento, el ticket categorizado, las tasas de conversión y la correspondencia histórica T01–T22 requieren validación posterior. Google bloqueó consultas adicionales por CAPTCHA, por lo que no se debe inferir ranking más allá de la observación puntual de Endolift. [10]

| Estado | Campo / tema | Próximo dato necesario |
| --- | --- | --- |
| Verificado | Producción, 22 URLs, estado 200, canonical, robots declarados | Mantener comprobación periódica |
| Verificado | Fichas Maps de marca por sede | Exportar/validar ficha y atributos desde cuenta de negocio |
| Verificado | Doctoralia a nivel de clínica | Exportar servicios, precios y disponibilidad por tratamiento |
| Parcial | Precio | Confirmar alcance de importes, IVA, zona y vigencia |
| N/D | Demanda, ticket, conversión, score | GSC, Keyword Planner, CRM/analítica y regla de ticket aprobada |
| No testado | GEO/AI y Maps por intención | Protocolo de consultas fechado por tratamiento/sede |
| Bloqueante | Mapeo T01–T22 | Adjuntar master histórico original para realizar la unión sin cambiar IDs |

## References

[1]: https://nuvanx.com/page-sitemap.xml "NUVANX — page sitemap"
[2]: https://nuvanx.com/soluciones-medicas/ "NUVANX — Soluciones médicas"
[3]: https://nuvanx.com/protocolos-signature/ "NUVANX — Protocolos Signature"
[4]: https://nuvanx.com/ "NUVANX — Inicio"
[5]: https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser "Doctoralia — NUVANX Medicina Estética Láser"
[6]: https://www.google.com/maps/search/NUVANX%20Chamber%C3%AD%20Madrid "Google Maps — NUVANX Chamberí"
[7]: https://www.google.com/maps/search/NUVANX%20Goya%20Madrid "Google Maps — NUVANX Salamanca–Goya"
[8]: https://www.facebook.com/ads/library/?active_status=all&ad_type=all&country=ES&q=NUVANX&search_type=keyword_unordered "Meta Ad Library — búsqueda NUVANX"
[9]: https://nuvanx.com/endolift-facial-papada-mandibula/ "NUVANX — Endolift® facial"
[10]: https://www.google.com/search?q=Endolift+facial+Madrid&hl=es&gl=es "Google — Endolift facial Madrid"
