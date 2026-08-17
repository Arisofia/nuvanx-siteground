# Matriz completa de contenidos fuente y despliegue

## Estado general

El paquete contiene una versión preparada de **todos los entregables del ZIP**. Las versiones `review` o `pending` son borradores editoriales conservadores: preservan la ruta y la estructura técnica, pero eliminan datos de clínica, equipo, técnica, protocolo, resultados, tiempos, tarifas y schema que todavía no estén aprobados.

| Fuente del ZIP | Ruta canónica | Borrador preparado | Superficie de despliegue | Estado |
|---|---|---|---|---|
| P1 Endolift facial | `/endolift-facial-papada-mandibula/` | `p1-endolift-page-review.json` | `inc/data/endolift-page.json` + `inc/nvx-endolift-page.php` | Revisión segura; no sustituir el JSON gobernado sin aprobación. |
| P2 Endoláser corporal | `/endolaser-corporal-grasa-localizada/` | `p2-endolaser-page-pending.json` | `inc/data/endolaser-page.json` + `inc/nvx-endolaser-page.php` | PENDING seguro; mantiene FAQ mínima para evitar fallback. |
| P3 Neuromoduladores | `/neuromoduladores-faciales-madrid/` | `p3-neuromoduladores-faciales-review.html` | Contenido administrado de la página `3508`, tras revisar su propietario efectivo. | Revisión segura. |
| P4 Chamberí | `/medicina-estetica-chamberi/` | `p4-medicina-estetica-chamberi-review.html` | Contenido administrado de la página `1543`, tras confirmar datos de sede y recursos. | Revisión segura. |
| P5 Valoración | `/madrid/valoracion/` | `p5-valoracion-review.html` | Página administrada `2636`; verificar `nvx-valoracion-managed-page.php` antes de editar contenido. | Revisión segura. |
| P6 Enlazado Top 1 | Cinco rutas indicadas en P6 | `p6-enlazado-interno-top1.md` normalizado | Cambios individuales únicamente donde exista el ancla de origen. | Preparado con URL canónica actual. |

## URL de neuromoduladores normalizada

Todos los borradores usan:

```text
/neuromoduladores-faciales-madrid/
```

## Validaciones realizadas

| Control | Resultado |
|---|---|
| Estructura de los borradores preparados | `ALL_REVIEW_DRAFTS_STRUCTURE=PASS` |
| URL canónicas y ausencia de la ruta anterior de neuromoduladores | `ALL_REVIEW_DRAFTS_CANONICAL_URLS=PASS` |
| Ausencia de claves tarifarias, schema no gobernado, marcas y referencias no aprobadas | `ALL_REVIEW_DRAFTS_GOVERNANCE=PASS` |
| Estructura y fallback seguro de P2 PENDING | `P2_PENDING_STRUCTURE=PASS`, `P2_PENDING_FAQ=PASS`, `P2_PENDING_PRICING=PASS` |

> Ningún borrador ha sido publicado. El paso a producción requiere la validación clínica, documental y operativa que corresponda a cada superficie, además de la confirmación explícita antes de modificar el sitio.
