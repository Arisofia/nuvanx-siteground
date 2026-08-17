# Paquete preparado de enlaces internos y contenido P1/P2

## Normalización de URL

Todas las referencias editables de neuromoduladores del paquete preparado utilizan la ruta canónica publicada:

```text
/neuromoduladores-faciales-madrid/
```

Se han actualizado las instrucciones de enlazado Top 1, las instrucciones de P1 y las referencias internas del documento P3/P4/P5. Los archivos originales descomprimidos se conservan sin modificación en `/home/ubuntu/endolaser-link-files/`.

## Estado de las cinco acciones Top 1

| Superficie | Estado verificado | Acción preparada |
|---|---|---|
| `/endolift-primeras-72-horas-que-esperar/` | El bloque final solicitado ya está publicado con la URL correcta. | No duplicar. |
| `/endolift-vs-hifu-diferencias-reales/` | Ya enlaza a Endolift facial. | No duplicar, según la regla condicional. |
| `/plan-anual-medicina-estetica-sin-sobretratar/` | Ya enlaza a neuromoduladores canónicos y a Endolift facial; no incluye una mención corporal que pueda enlazarse sin añadir copy. | Mantener y no forzar enlace corporal. |
| `/well-aging-estrategia-medica-global/` | No contiene una mención apta de relajación muscular/expresión ni de composición o grasa corporal. | No añadir copy fuera de la instrucción. |
| `/endolaser-corporal-vs-no-invasivos-grasa-localizada/` | El bloque final solicitado ya está publicado con la URL correcta. | No duplicar. |

## Mapa verificable de las páginas existentes

`EXISTING-PAGE-CHANGE-MAP.md` identifica, para cada una de las cinco páginas Top 1 ya publicadas, el ID de WordPress, el anchor o bloque solicitado, la ubicación exacta, el estado detectado y la acción correcta. Distingue expresamente entre el enlace ya presente, el enlace condicional que no debe duplicarse y los casos que requerirían una instrucción editorial nueva porque no existe texto fuente apto.

## Compatibilidad de plantilla para P1 y P2

P1 y P2 no deben pegarse como HTML íntegro en el contenido administrado de WordPress. Ambas rutas son superficies gobernadas por el tema, donde el contenido visible se renderiza desde sus archivos de datos y su controlador de plantilla.

| Página | URL de despliegue | Datos canónicos | Renderizador de plantilla | Identidad estructurada |
|---|---|---|---|---|
| P1, Endolift facial | `/endolift-facial-papada-mandibula/` | `inc/data/endolift-page.json` | `inc/nvx-endolift-page.php` | `seo_id=endolift`, `schema_id=endolift_facial` |
| P2, Endoláser corporal | `/endolaser-corporal-grasa-localizada/` | `inc/data/endolaser-page.json` | `inc/nvx-endolaser-page.php` | `seo_id=endolaser`, `schema_id=endolaser_corporal` |

Por tanto, la URL que debe emplear el agente para el despliegue de **Endoláser Corporal P2** es:

```text
/endolaser-corporal-grasa-localizada/
```

La compatibilidad equivalente a P1 consiste en adaptar el contenido a `endolaser-page.json` y dejar que `nvx-endolaser-page.php` componga el hero, cuerpo editorial, FAQ y CTA. No debe sustituirse la plantilla mediante HTML completo en WordPress.

> El contenido P2 del ZIP incluye menciones de equipo, técnica, tarifas y claims clínicos. La aprobación Endoláser registrada en el repositorio continúa en estado `PENDING`; por ello, este paquete deja la estructura lista, pero no autoriza publicar esas afirmaciones ni completar su registro de aprobación.

## Borrador P2 PENDING

El archivo `p2-endolaser-page-pending.json` es un borrador saneado que mantiene la estructura exigida por `nvx-endolaser-page.php` y evita tarifas, marcas, equipos, protocolos, plazos, resultados, claims y atributos schema no aprobados. La utilidad `validate-p2-pending.mjs` comprueba su estructura y confirma que no activa el fallback de FAQ ni una referencia de catálogo tarifario.

Este borrador es una **preparación editorial**, no un reemplazo autorizado del archivo gobernado del repositorio. El gate Endoláser protege cualquier cambio de `endolaser-page.json`; por tanto, la sustitución efectiva sigue requiriendo evidencia real y el registro de aprobación completo.
