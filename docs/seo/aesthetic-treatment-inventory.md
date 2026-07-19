# NUVANX — Inventario de tratamientos faciales

Fecha de control: 2026-07-19

## Contrato de publicación

Las cuatro páginas comparten una única fuente versionada:

- Contenido visible: `inc/nvx-aesthetic-treatment-pages.php`.
- Metadata: filtros Yoast del mismo módulo.
- FAQ visible: catálogo `faqs` del mismo módulo.
- FAQPage y MedicalProcedure: `inc/nvx-aesthetic-treatment-schema.php` dentro del grafo único de Yoast.
- Producción: páginas en estado `draft` hasta aprobación médica.
- Staging2: páginas sembradas como `publish` únicamente en el entorno globalmente `noindex`.

No se publican precios, número fijo de sesiones, duración garantizada ni comparativas de superioridad.

## Matriz canónica

| Clave | URL | ID producción | H1 | Schema | Estado |
|---|---|---:|---|---|---|
| `lips_ha` | `/labios-acido-hialuronico-madrid/` | 3318 | Ácido hialurónico en labios en Madrid | MedicalProcedure + Service + FAQPage | Draft · revisión médica pendiente |
| `rhinomodeling_ha` | `/rinomodelacion-sin-cirugia-madrid/` | 3319 | Rinomodelación con ácido hialurónico en Madrid | MedicalProcedure + Service + FAQPage | Draft · revisión médica pendiente |
| `tear_trough_ha` | `/ojeras-surco-lagrimal-madrid/` | 3320 | Tratamiento de ojeras y surco lagrimal en Madrid | MedicalProcedure + Service + FAQPage | Draft · revisión médica pendiente |
| `biostimulators` | `/bioestimuladores-colageno-madrid/` | 3321 | Bioestimuladores de colágeno en Madrid | MedicalProcedure + Service + FAQPage | Draft · revisión médica pendiente |

## Arquitectura visible obligatoria

Cada página contiene:

1. Hero y H1 único.
2. Introducción clínica y límites del tratamiento.
3. Diagnóstico diferencial.
4. Indicaciones seleccionadas.
5. Precauciones y supuestos de no indicación.
6. Mecanismo y proceso.
7. Evolución y recuperación.
8. Riesgos explicados.
9. Combinaciones posibles sin automatismos comerciales.
10. FAQ visible.
11. CTA de valoración médica.
12. Enlaces a Chamberí y Salamanca–Goya.
13. Aviso visible de revisión médica pendiente mientras no exista aprobación.

## Correcciones clínicas contractuales

- Radiesse® se identifica como hidroxiapatita cálcica (CaHA), no como ácido hialurónico.
- Sculptra® se identifica como ácido poli-L-láctico (PLLA), no como ácido hialurónico.
- CaHA y PLLA no se presentan como reversibles mediante hialuronidasa.
- La rinomodelación no se describe como reducción de tamaño ni corrección funcional.
- El surco lagrimal se diferencia de bolsas, festones, edema, pigmentación y componente vascular.
- No se prescribe una pausa obligatoria de anticoagulantes o antiagregantes.
- Se incluyen señales de alarma vascular y necesidad de valoración inmediata.
- Se prohíben resultados garantizados, calendarios rígidos y promesas de permanencia.

## Enlazado interno

Entradas:

- Hub `/medicina-estetica-madrid/`.
- Catálogo `/tratamientos/` cuando el estado pase a aprobado.
- Guía diagnóstica facial en borrador editorial.

Salidas:

- `/madrid/valoracion/`.
- `/medicina-estetica-chamberi/`.
- `/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/`.
- Tratamientos complementarios solo cuando exista una relación clínica explícita.

## Gate médico previo a producción

Para cambiar una página de `draft` a `publish` deben constar:

- Nombre y número de colegiado del médico revisor.
- Fecha de revisión.
- Versión o SHA revisado.
- Validación de indicaciones, contraindicaciones, riesgos y cuidados.
- Validación de productos realmente disponibles y autorizados en el centro.
- Aprobación de cualquier precio, fotografía o caso clínico.
- Confirmación de que FAQ visible y FAQPage son idénticos.
- Audit renderizado sin duplicación JSON-LD, canonical incorrecto ni enlaces rotos.
