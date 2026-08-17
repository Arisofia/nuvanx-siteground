# Preflight de publicación — P1, P2 y enlaces internos

## URL canónica normalizada

La URL canónica aplicable a neuromoduladores en todo el material preparado es:

```text
/neuromoduladores-faciales-madrid/
```

No quedan referencias a la ruta anterior de neuromoduladores en los documentos Markdown preparados.

## Enlaces internos Top 1

Las cinco instrucciones de enlazado se conservaron en `p6-enlazado-interno-top1.md`, ya normalizadas con la URL canónica actual. Las comprobaciones del contenido publicado indican que los bloques finales de primeras 72 horas y Endoláser vs no invasivos, además del enlace condicional de Endolift vs HIFU, ya están presentes. La instrucción de plan anual conserva las referencias canónicas de Endolift y neuromoduladores; no se fuerza un enlace corporal si no existe una mención fuente apta. El mismo criterio se aplica a well-aging para no introducir copy ajeno a la orden de enlazado.

## Compatibilidad P2

La URL de despliegue de Endoláser Corporal P2 es:

```text
/endolaser-corporal-grasa-localizada/
```

No se debe reemplazar el contenido de esta superficie mediante HTML completo en WordPress. El tema usa el contrato siguiente:

| Componente | Ubicación |
|---|---|
| Contenido canónico P2 | `wp-content/themes/nuvanx-medical/inc/data/endolaser-page.json` |
| Renderizador | `wp-content/themes/nuvanx-medical/inc/nvx-endolaser-page.php` |
| Identidad de ruta | `seo_id=endolaser`, `schema_id=endolaser_corporal` |

Este es el equivalente de compatibilidad de P1: la plantilla construye hero, cuerpo editorial, FAQ y CTA a partir del JSON de datos, no de un reemplazo de HTML almacenado en WordPress.

## Bloqueos previos a cualquier publicación P1/P2

> El material fuente se ha preparado como borrador editorial. No está autorizado para publicación clínica hasta que se corrijan los siguientes puntos y se obtenga la aprobación correspondiente.

| Hallazgo | Impacto |
|---|---|
| P2 usa seis claves `endolaser.*`, pero el catálogo vigente solo contiene namespaces `endolift` y `endolift_combo`. | Las tarifas no se pueden renderizar ni validar todavía. |
| P1 usa `endolift.full_face`, mientras que la clave disponible está en `endolift_combo.full_face`. | Requiere corrección técnica antes de publicar. |
| P1 y P2 incluyen `recognizingAuthority` y `performer` en JSON-LD suministrado. | No son compatibles con la gobernanza de schema ya implantada. |
| P2 atribuye equipo, fabricante, técnica, precios y claims clínicos concretos. | La aprobación Endoláser sigue en `PENDING`; no se debe publicar hasta tener evidencia real y aprobación completa. |

Por tanto, el paquete queda preparado en cuanto a estructura de contenidos y URL canónicas, pero el despliegue de P2 debe pasar por el flujo gobernado del tema y la aprobación clínica pendiente.
