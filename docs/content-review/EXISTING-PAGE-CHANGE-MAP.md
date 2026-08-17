# Mapa de cambios por página existente

## Propósito y alcance

Este documento separa con precisión las **páginas existentes** de los borradores de revisión P1–P5. No sustituye contenido publicado ni autoriza un despliegue: deja trazado qué enlace se solicitó, dónde debía estar y cuál es el estado verificado en la versión publicada.

La comprobación se realizó sobre el contenido WordPress publicado de los IDs `1593`, `3326`, `3316`, `3312` y `3330`. No se cambia el H1, el SEO, el orden de las secciones ni el texto clínico existente.

## Páginas Top 1

| Página existente | ID | Cambio solicitado | Ubicación exacta | Estado verificado | Acción correcta |
|---|---:|---|---|---|---|
| `/endolift-primeras-72-horas-que-esperar/` | 1593 | Bloque relacionado hacia Endolift facial | Al final del contenido, antes del CTA o firma | Existe `div.nvx-related-links` con la URL `/endolift-facial-papada-mandibula/` y el anchor **«guía completa del Endolift® facial en Madrid»**. | **No modificar.** Duplicarlo generaría dos bloques finales equivalentes. |
| `/endolift-vs-hifu-diferencias-reales/` | 3326 | Enlace condicional hacia Endolift facial | Solo si no existía, en conclusiones o CTA | Ya existe el enlace canónico `/endolift-facial-papada-mandibula/` aplicado al primer término **«Endolift®»** dentro de la explicación del tratamiento. | **No modificar.** La condición de inserción ya no se cumple. |
| `/plan-anual-medicina-estetica-sin-sobretratar/` | 3316 | Enlace a Endolift facial, Endoláser corporal y neuromoduladores | Sobre menciones existentes de Endolift/tensado/tercio inferior, grasa localizada/remodelación corporal y neuromoduladores/toxina/botox | El enlace a neuromoduladores canónicos ya aparece en la tabla de intervalos; Endolift facial ya aparece enlazado en el bloque de mantenimiento. No existe una mención fuente de Endoláser corporal, grasa localizada o remodelación corporal apta para enlazar sin añadir texto. | **Mantener los dos enlaces existentes; no añadir Endoláser corporal.** Crear ese enlace exigiría copy nuevo, fuera del alcance de «solo enlaces». |
| `/well-aging-estrategia-medica-global/` | 3312 | Enlace a neuromoduladores y Endoláser corporal | Solo sobre una mención ya existente de relajación muscular/expresión o de composición/grasa corporal | El contenido actual no contiene una mención apta de relajación muscular, expresión, composición corporal ni grasa corporal. | **No modificar.** No se debe forzar un anchor ni añadir copy no solicitado. |
| `/endolaser-corporal-vs-no-invasivos-grasa-localizada/` | 3330 | Bloque relacionado hacia Endoláser corporal | Al final del contenido, antes del CTA | Existe `div.nvx-related-links` con la URL `/endolaser-corporal-grasa-localizada/` y el anchor **«endoláser corporal en NUVANX»**. | **No modificar.** Duplicarlo generaría dos bloques finales equivalentes. |

## Destinos de los enlaces

| Página destino existente | Función en el enlazado | Cambio en la página destino |
|---|---|---|
| `/endolift-facial-papada-mandibula/` | Recibe enlaces desde primeras 72 horas, HIFU y plan anual. | Ninguno en esta tarea. Es una ruta gobernada por `endolift-page.json` y `nvx-endolift-page.php`. |
| `/endolaser-corporal-grasa-localizada/` | Recibe enlaces desde plan anual, well-aging y comparativa corporal cuando existan anchors válidos. | Ninguno en esta tarea. Es una ruta gobernada por `endolaser-page.json` y `nvx-endolaser-page.php`. |
| `/neuromoduladores-faciales-madrid/` | Destino canónico de los anchors de neuromoduladores. | Ninguno en esta tarea. Todas las instrucciones preparadas usan esta URL canónica. |

## Consecuencia para el PR

El cambio verificable de este PR es documental: registra el mapa de inserción, los anchors ya presentes y los tres casos donde **no se debe editar**. No realiza cambios de WordPress ni convierte los borradores P1–P5 en contenido activo.

> Para habilitar los enlaces ausentes de plan anual o well-aging será necesaria una instrucción editorial nueva que autorice añadir texto fuente. Después, cada modificación deberá revisarse y publicarse por separado.
