# NUVANX Contour Architecture™ — Roadmap y estado

## Clasificación

El documento estratégico original se conserva como **roadmap válido**, no como prueba de ejecución completada. fileciteturn767file0

- Repositorio: sujeto a gates y revisión.
- Staging2: NO-GO hasta completar deployment inmutable y evidencia.
- Producción: no evaluada y fuera de alcance.

## Decisiones canónicas

### Producto corporal

El único nombre público aprobado es **NUVANX Contour Architecture™**.

Se retiran del copy y la navegación públicos:

- Couture Sculpt™.
- NUVANX Contour Sculpt™.
- Contour Sculpt™.

La diferenciación reside en el diagnóstico, cartografía anatómica, selección tecnológica, documentación y seguimiento; no en atribuir a NUVANX una máquina propia.

### Eye Frame™

**NUVANX Eye Frame™ se conserva.** La página ya implementada organiza el diagnóstico periocular por pigmentación, vascularización, surco, bolsas, edema y laxitud, y declara cuándo la medicina estética no es suficiente.

### Contour Focus y Contour Continuity

**No se publican como productos, paquetes ni niveles de precio.** Una planificación puede ser focal o incluir zonas contiguas, pero cada unidad necesita indicación documentada y presupuesto individualizado.

## Páginas gobernadas

### Fase 1 y Eye Frame

- `/protocolos-signature/`
- `/remodelacion-corporal-laser-madrid/`
- `/tratamiento-postparto-abdomen-contorno-corporal-madrid/`
- `/papada-definicion-mandibular-madrid/`
- `/calidad-piel-firmeza-luminosidad-madrid/`
- `/cicatrices-acne-poros-textura-madrid/`
- `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/`
- `/tratamiento-ojeras-bolsas-mirada-madrid/`

### Fase 2 canónica

- `/grasa-localizada-abdomen-flancos-madrid/`
- `/flacidez-grasa-localizada-brazos-madrid/`
- `/grasa-espalda-zona-sujetador-madrid/`
- `/flacidez-muslos-internos-subgluteo-madrid/`
- `/tratamiento-rodillas-grasa-flacidez-madrid/`
- `/contorno-corporal-masculino-madrid/`

Las rutas solo aparecen en navegación después de publicarse y resolver HTTP 200.

## Navegación

La migración reconstruye la ubicación WordPress `Primary` desde el blueprint publicado. El menú legacy `TRATAMIENTOS` se elimina y `/tratamientos/` redirige directamente a `/soluciones-medicas/`.

## Claims bloqueados

Los gates controlan, entre otras, estas formulaciones:

- Sin bisturí ni puntos.
- Todo en vigilia.
- Mínima recuperación o recuperación inmediata.
- Sin cicatrices, dolor, inflamación o riesgos.
- Resultado definitivo o resultados garantizados.
- Una sola sesión o número estándar de sesiones.
- Reducción universal de dolor o eritema.
- Control térmico absoluto.

## Criterio de cierre

El roadmap solo pasa a **completado** cuando el mismo SHA cumple:

1. PHP, shell y Node syntax.
2. Theme Hygiene, Navigation Architecture y Deployment Contract.
3. Deployment y migración inmutables en staging2.
4. Menú `Primary` canónico sin rutas 404.
5. Smoke independiente de las 17 páginas y tres redirects.
6. Aceptación renderizada con SEO, H1, canonical, robots, schema, claims y marker SHA.
7. QA real en Chrome desktop/móvil, con mega-menú, drawer, acordeones, foco, Escape y ausencia de overflow.
8. Artifact con `report.json` sin findings y capturas válidas, no pantallas 403.

Hasta entonces:

**DOCUMENTO: ROADMAP VÁLIDO / NO COMPLETADO**

**STAGING2: NO-GO PARA CIERRE**

**PRODUCCIÓN: NO EVALUADA**
