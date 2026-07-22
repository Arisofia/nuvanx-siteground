# NUVANX Contour Architecture™ — Roadmap y estado de ejecución

## Estado del documento fuente

El documento estratégico se conserva como **roadmap válido**, no como certificación de trabajo completado.

- Roadmap: válido.
- Implementación en repositorio: sujeta a gates y revisión.
- Staging2: no se considera cerrado hasta deployment inmutable, smoke, aceptación renderizada y QA visual real.
- Producción: fuera de alcance hasta promoción explícita del mismo SHA validado.

## Decisiones canónicas

### Nombre corporal público

El único nombre público aprobado es:

**NUVANX Contour Architecture™**

Se retiran de la arquitectura pública y del contenido visible:

- Couture Sculpt™.
- NUVANX Contour Sculpt™.
- Contour Sculpt™.

La exclusividad se atribuye al sistema de diagnóstico, cartografía anatómica, selección tecnológica, documentación y seguimiento; no a una tecnología propia o patentada.

### Eye Frame™

**No se publica como Protocolo Signature.**

La región periocular continúa atendida mediante las páginas y servicios ya publicados de ojeras, surco lagrimal, calidad cutánea y medicina estética. Un protocolo Eye Frame™ solo podrá incorporarse después de revisión médica, jurídica, de capacidad asistencial, SEO y claims.

### Contour Focus y Contour Continuity

**Se retiran del roadmap público.**

No se implementan como productos, paquetes ni niveles de precio. La planificación puede ser focal o incluir zonas contiguas, pero cada unidad debe tener indicación documentada y presupuesto individualizado. Esta terminología no debe utilizarse para inducir venta cruzada o presentar paquetes cerrados.

## Fase 1 — páginas canónicas

1. `/protocolos-signature/`
2. `/remodelacion-corporal-laser-madrid/`
3. `/tratamiento-postparto-abdomen-contorno-corporal-madrid/`
4. `/papada-definicion-mandibular-madrid/`
5. `/calidad-piel-firmeza-luminosidad-madrid/`
6. `/cicatrices-acne-poros-textura-madrid/`
7. `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/`

## Fase 2 — páginas anatómicas

1. `/grasa-localizada-abdomen-flancos-madrid/`
2. `/flacidez-grasa-localizada-brazos-madrid/`
3. `/grasa-espalda-zona-sujetador-madrid/`
4. `/flacidez-muslos-internos-subgluteo-madrid/`
5. `/tratamiento-rodillas-grasa-flacidez-madrid/`
6. `/contorno-corporal-masculino-madrid/`

Las rutas de Fase 2 solo pueden aparecer en Soluciones y navegación después de estar publicadas y responder HTTP 200 con su contrato renderizado.

## Navegación

La ubicación WordPress `Primary` debe reconstruirse desde el blueprint canónico publicado. El menú legado `TRATAMIENTOS` se elimina y `/tratamientos/` redirige de forma directa a `/soluciones-medicas/`.

La navegación pública debe separar:

- Soluciones.
- Protocolos Signature.
- Tecnología.
- Casos clínicos.
- Equipo médico.
- Clínicas.
- Journal.
- Contacto.

El CTA de valoración permanece fuera del árbol del menú.

## Claims y límites

Los gates de contenido deben bloquear, entre otras, estas formulaciones:

- Sin bisturí ni puntos.
- Todo en vigilia.
- Mínima recuperación o recuperación inmediata.
- Sin cicatrices, sin dolor, sin inflamación o sin riesgos.
- Resultado definitivo o resultados garantizados.
- Una sola sesión o número estándar de sesiones.
- Reducción universal de dolor o eritema.
- Control térmico absoluto.

## Criterio de cierre

El roadmap solo puede pasar a **completado** cuando el mismo SHA cumpla:

1. PHP, shell y Node syntax.
2. Theme Hygiene Gate.
3. Deployment Contract Gate.
4. Deployment y migración inmutables en staging2.
5. Menú `Primary` canónico sin rutas 404.
6. Smoke independiente de todas las páginas y redirects.
7. Aceptación renderizada con SEO, H1, canonical, robots, schema y claims.
8. QA visual real desktop y móvil del mega-menú, drawer, jerarquía, overflow, foco y CTAs.
9. Evidencia archivada con `report.json` limpio.

Hasta entonces:

**STAGING2: NO-GO PARA CIERRE**

**PRODUCCIÓN: NO EVALUADA**
