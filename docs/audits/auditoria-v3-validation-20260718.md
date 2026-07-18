# NUVANX — Validación de Auditoría v3.0

Fecha de validación: 18 julio 2026  
Fuente auditada: `NUVANX — Auditoría Completa v3.0`  
Entornos comprobados: producción (`nuvanx.com`), staging2 y `master` del repositorio.

## Criterio

Se corrigen únicamente hallazgos reproducibles. No se publican coordenadas aproximadas, horarios de ejemplo, tarifas no aprobadas ni credenciales profesionales sin fuente verificable.

## Resultado consolidado

| Hallazgo de v3.0 | Estado validado | Decisión |
|---|---|---|
| `noindex, nofollow` en las páginas de producción | Resuelto antes de este PR | Producción publica `index, follow`; staging conserva `noindex, nofollow` de forma intencional. |
| `og:url` de producción apuntando a staging2 | Resuelto antes de este PR | Canonical y `og:url` usan `https://nuvanx.com/...`. |
| `og:image` ausente en `/contacto/` | Confirmado | Corregido en este PR con la imagen clínica asociada a la página. |
| Sin `MedicalClinic` en `/contacto/` | Confirmado | Corregido en este PR reutilizando el registro canónico de Chamberí y Goya dentro del grafo de Yoast. |
| Sin horarios visibles en `/contacto/` | Falso positivo / ya resuelto | La página ya publica los horarios reales. El PR los incorpora también al bloque canónico del tema. |
| Horarios sugeridos 10:00–19:00 | No ejecutar | Eran placeholders. Se conservan: Chamberí L–V 12:00–20:00 y S 10:00–18:00; Goya L–V 11:00–20:00. |
| Enlace `/politica-privacidad/` roto | Falso positivo | Esa es la URL canónica publicada. `/politica-de-privacidad/` es la ruta antigua y redirige 301. El footer se corrige para evitar el salto. |
| Label `Contacto privado` | Confirmado | Sustituido por `Clínicas NUVANX · Madrid`. |
| H1 de `/contacto/` poco local | Confirmado | Normalizado a `Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya`. |
| Jerga `directorio NAP` | Pendiente potencial en el generador canónico | Eliminada por el módulo de normalización del PR. |
| Frase `revisará tu interés` | Pendiente en una versión de contenido | Sustituida por orientación centrada en sede, médico y caso. |
| Homepage sin H1 local | Resuelto antes de este PR | H1 renderizado: `Medicina estética láser en Madrid`. |
| Homepage sin `MedicalClinic`, FAQ y tres `Physician` | Resuelto antes de este PR | El grafo renderizado incluye `MedicalClinic`, `FAQPage` y `Physician`. |
| Homepage sin equipo médico | Resuelto antes de este PR | El contenido y el grafo exponen el equipo médico autorizado. |
| Endolift sin `MedicalProcedure` y ofertas | Resuelto antes de este PR | El grafo incluye `MedicalProcedure`, `Service` y `Offer`, con tarifas obtenidas del catálogo canónico. |
| Láser CO₂ sin `MedicalProcedure` y oferta | Resuelto antes de este PR | El grafo incluye `MedicalProcedure`, `Service` y `Offer`, con tarifas obtenidas del catálogo canónico. |
| FAQ de Láser CO₂ | Resuelto antes de este PR | La página renderizada incluye `FAQPage`. |
| FAQ de Endoláser corporal | Resuelto antes de este PR | La página renderizada incluye `FAQPage`. |
| Añadir `MedicalProcedure` de labios a `/medicina-estetica/` | No ejecutar literalmente | Es una página de categoría, no un único procedimiento. Vincular toda la página a una oferta de labios de 290 € sería semánticamente incorrecto y podría desalinear precio visible, catálogo y schema. |
| Precios indicativos EXION® | Pendiente de tarifa aprobada | El schema actual declara presupuesto tras valoración. No se inventa una tarifa fija sin catálogo aprobado y visible. |
| FAQ comparativa EXION® vs. Morpheus8® | Pendiente de aprobación clínica/editorial | No se añade una comparativa médica sin paridad visible y fuente aprobada. El código impide publicar FAQ schema que no exista también en HTML. |
| Credenciales de Cristina Marquez | Pendiente de fuente verificable | No se amplían ni inventan titulaciones, colegiación o alcance profesional. |
| Coordenadas `geo` | Pendiente de verificación exacta | El registro canónico las omite de forma deliberada hasta confirmar latitud/longitud oficiales. La dirección postal completa ya está estructurada. |
| Google Search Console | Fuera de este PR | Requiere acceso y validación de la propiedad de Google; no es un cambio de tema. |
| LCP del hero, alt text global y nuevos artículos | Roadmap separado | No son bloqueantes reproducidos por la auditoría SEO/GEO actual y requieren auditorías específicas de rendimiento, medios y contenido. |

## Cambios de este PR

1. Nuevo módulo `nvx-contacto-audit-fixes.php` limitado a `/contacto/`.
2. Imagen Open Graph y Twitter.
3. Título y descripción canónicos para SERP y social.
4. Dos nodos `MedicalClinic`, sin duplicar JSON-LD.
5. Horarios reales y copy orientado al paciente.
6. Footer enlazado directamente a la política de privacidad canónica.
7. Prueba contractual de contacto.
8. `/contacto/` incorporado a la auditoría renderizada de producción y staging.

## Criterio de cierre

El PR puede considerarse listo cuando PHP Lint, Security Gate, Clinical Claims Gate y SEO GEO Gate concluyan correctamente. La comprobación visual y del grafo actualizado en `/contacto/` debe repetirse después del despliegue del código, porque el gate de un PR consulta los entornos publicados y no ejecuta WordPress desde la rama.
