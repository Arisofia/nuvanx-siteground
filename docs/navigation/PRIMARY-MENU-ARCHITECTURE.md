# Menú principal definitivo NUVANX

## Fuente de verdad

El menú asignado a la ubicación **Primary** en WordPress es la fuente de verdad. La migración gobernada reconstruye ese menú desde el blueprint publicado y bloquea la deriva en la auditoría posterior.

- Nombre recomendado: `NUVANX Principal`.
- Ubicación: `Primary`.
- Profundidad máxima: tres niveles.
- El CTA `Solicitar valoración médica` permanece fuera del árbol porque ya lo renderiza el header.
- Solo se resuelven páginas publicadas. Una ruta ausente, en borrador o en cuarentena se omite junto con sus descendientes.
- `/tratamientos/` no forma parte del menú y redirige a `/soluciones-medicas/`.

## Árbol definitivo

```text
INICIO

SOLUCIONES
├── Rostro y cuello
├── Calidad de piel
├── Contorno corporal
├── Cambios posgestacionales
├── Cicatrices, poros y textura
├── Manchas, rojeces y fotodaño
└── Medicina estética masculina

PROTOCOLOS SIGNATURE
├── NUVANX Contour Architecture™
│   ├── Abdomen y flancos
│   ├── Brazos y axila
│   ├── Espalda y zona del sujetador
│   ├── Muslos y región subglútea
│   ├── Rodillas
│   └── Contorno masculino
├── NUVANX Post-Maternity Contour™
├── NUVANX Profile Definition™
├── NUVANX Eye Frame™
├── NUVANX Skin Architecture™
├── NUVANX Surface Renewal™
└── NUVANX Tone Correction™

TECNOLOGÍA
├── Endolift® facial
├── Endoláser corporal
├── EXION® Face
├── EXION® Body
├── EXION® Fractional RF
├── Láser CO₂ fraccionado
├── BTL EXILITE™ IPL
├── EMFUSION®
└── Medicina inyectable

CASOS CLÍNICOS
EQUIPO MÉDICO
CLÍNICAS
├── Chamberí
└── Salamanca–Goya
JOURNAL
CONTACTO
```

Los pilares `Soluciones`, `Protocolos Signature` y `Tecnología` reciben tratamiento de mega-menú. El tercer nivel solo se renderiza para rutas publicadas.

## Rutas Signature

| Elemento | Ruta objetivo |
|---|---|
| NUVANX Contour Architecture™ | `/remodelacion-corporal-laser-madrid/` |
| NUVANX Post-Maternity Contour™ | `/tratamiento-postparto-abdomen-contorno-corporal-madrid/` |
| NUVANX Profile Definition™ | `/papada-definicion-mandibular-madrid/` |
| NUVANX Eye Frame™ | `/tratamiento-ojeras-bolsas-mirada-madrid/` |
| NUVANX Skin Architecture™ | `/calidad-piel-firmeza-luminosidad-madrid/` |
| NUVANX Surface Renewal™ | `/cicatrices-acne-poros-textura-madrid/` |
| NUVANX Tone Correction™ | `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/` |

## Rutas Fase 2 — Contour Architecture

| Elemento | Ruta objetivo |
|---|---|
| Abdomen y flancos | `/grasa-localizada-abdomen-flancos-madrid/` |
| Brazos y axila | `/flacidez-grasa-localizada-brazos-madrid/` |
| Espalda y zona del sujetador | `/grasa-espalda-zona-sujetador-madrid/` |
| Muslos y región subglútea | `/flacidez-muslos-internos-subgluteo-madrid/` |
| Rodillas | `/tratamiento-rodillas-grasa-flacidez-madrid/` |
| Contorno masculino | `/contorno-corporal-masculino-madrid/` |

No se crea un enlace hacia una ruta futura. Primero se publica mediante la migración gobernada, se valida el contrato renderizado y después aparece en el menú resuelto.

## Decisiones de producto

- `NUVANX Contour Architecture™` es el único nombre corporal público.
- `Couture Sculpt™` y `Contour Sculpt™` quedan retirados de navegación y copy visible.
- `Contour Focus` y `Contour Continuity` no se publican como productos, paquetes ni niveles de precio.
- `NUVANX Eye Frame™` se conserva como protocolo diagnóstico periocular ya implementado; no promete una técnica única y explicita cuándo derivar.

## Comportamiento y QA

- Escritorio: mega-menú por `hover` y `focus-within`, sin overflow.
- Móvil: drawer con acordeones, `aria-expanded`, cierre por `Escape` y restauración de foco.
- El QA de staging2 debe generar capturas reales desktop/móvil, bloquear pantallas 403 y comprobar las interacciones antes de producción.
