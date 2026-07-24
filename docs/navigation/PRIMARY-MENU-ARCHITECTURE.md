# Menú principal definitivo NUVANX

## Fuente de verdad

El menú asignado a la ubicación **Primary** en WordPress es la fuente de verdad. La migración gobernada reconstruye ese menú a partir del blueprint publicado y bloquea la deriva en la auditoría posterior.

- Nombre recomendado: `NUVANX Principal`.
- Ubicación: `Primary`.
- Profundidad máxima soportada: tres niveles.
- El CTA `Solicitar valoración médica` permanece fuera del árbol porque ya lo renderiza el header.
- Solo se resuelven páginas publicadas. Una ruta ausente, en borrador o en cuarentena se omite junto con sus descendientes.
- `/tratamientos/` no forma parte del menú y redirige a `/soluciones-medicas/`.

## Árbol definitivo

```text
INICIO

SOLUCIONES                                      [mega-menú]
├── Rostro y cuello
├── Calidad de piel
├── Contorno corporal
├── Cambios posgestacionales
├── Cicatrices, poros y textura
├── Manchas, rojeces y fotodaño
└── Medicina estética masculina

PROTOCOLOS SIGNATURE                            [mega-menú]
├── NUVANX Contour Architecture™
│   ├── Abdomen y flancos
│   ├── Brazos y axila
│   ├── Espalda y zona del sujetador
│   ├── Muslos y región subglútea
│   ├── Rodillas
│   └── Contorno masculino
├── NUVANX Post-Maternity Contour™
├── NUVANX Profile Definition™
├── NUVANX Skin Architecture™
├── NUVANX Surface Renewal™
└── NUVANX Tone Correction™

TECNOLOGÍA                                      [mega-menú]
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

Los pilares `Soluciones`, `Protocolos Signature` y `Tecnología` reciben automáticamente el tratamiento de mega-menú. La clase `nvx-menu--mega` conserva ese tratamiento si una etiqueta cambia.

## Rutas objetivo

| Elemento | Ruta objetivo |
|---|---|
| Soluciones | `/soluciones-medicas/` |
| Protocolos Signature | `/protocolos-signature/` |
| Tecnología | `/medicina-estetica-laser/` |
| Casos clínicos | `/casos-de-pacientes/` |
| Equipo médico | `/equipo-medico/` |
| Clínicas | `/clinicas-de-medicina-estetica-nuvanx/` |
| Journal | `/blog/` |
| Contacto | `/contacto/` |

### Protocolos Signature

| Elemento | Ruta objetivo |
|---|---|
| NUVANX Contour Architecture™ | `/remodelacion-corporal-laser-madrid/` |
| NUVANX Post-Maternity Contour™ | `/tratamiento-postparto-abdomen-contorno-corporal-madrid/` |
| NUVANX Profile Definition™ | `/papada-definicion-mandibular-madrid/` |
| NUVANX Skin Architecture™ | `/calidad-piel-firmeza-luminosidad-madrid/` |
| NUVANX Surface Renewal™ | `/cicatrices-acne-poros-textura-madrid/` |
| NUVANX Tone Correction™ | `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/` |

### Fase 2 — Contour Architecture

| Elemento | Ruta objetivo |
|---|---|
| Abdomen y flancos | `/grasa-localizada-abdomen-flancos-madrid/` |
| Brazos y axila | `/flacidez-grasa-localizada-brazos-madrid/` |
| Espalda y zona del sujetador | `/grasa-espalda-zona-sujetador-madrid/` |
| Muslos y región subglútea | `/flacidez-muslos-internos-subgluteo-madrid/` |
| Rodillas | `/tratamiento-rodillas-grasa-flacidez-madrid/` |
| Contorno masculino | `/contorno-corporal-masculino-madrid/` |

No debe crearse un enlace personalizado hacia una ruta futura. Primero se publica mediante la migración gobernada, se valida el contrato renderizado y después aparece en el menú resuelto.

## Decisiones excluidas

- `NUVANX Eye Frame™` no se incorpora hasta completar una revisión médica, jurídica, asistencial, SEO y de claims independiente.
- `Contour Focus` y `Contour Continuity` no se publican como productos, paquetes ni niveles de precio.
- `Couture Sculpt™` y `Contour Sculpt™` se consideran nombres retirados. El único nombre corporal público es `NUVANX Contour Architecture™`.

## Comportamiento de escritorio

- Ocho pilares compactos y CTA independiente.
- Los tres pilares comerciales abren paneles editoriales.
- Se admite tercer nivel únicamente para rutas publicadas.
- Apertura mediante `hover` y `focus-within`.
- El breakpoint cambia al drawer antes de que el header pueda desbordarse.

## Comportamiento móvil

- Los hijos permanecen cerrados al abrir el drawer.
- Cada padre recibe un botón independiente con `aria-expanded` y `aria-controls`.
- Pulsar el nombre navega al hub publicado.
- Pulsar `+ / −` abre o cierra el acordeón.
- Al abrir una familia se cierra su hermana del mismo nivel.
- `Escape`, el botón de cierre y el cambio a escritorio restablecen los acordeones.
- Se respeta `prefers-reduced-motion`.

## Reglas de publicación

1. No incorporar LipoSculpt-Air™, V-Lift Awake™ ni protocolos `pending_medical_legal`.
2. No copiar categorías residuales de AirSculpt ni añadir servicios no confirmados.
3. No añadir páginas que respondan 404, borradores o rutas en cuarentena.
4. No duplicar el CTA de valoración dentro del menú.
5. Validar escritorio, móvil, teclado, lector de pantalla y ausencia de overflow en staging2 antes de producción.

## Contrato de enrutamiento de renderers

- El renderer de Equipo Médico solo puede ejecutarse en la ruta canónica `/equipo-medico/`.
- Frases editoriales como `equipo especialista`, nombres de médicos o enlaces al equipo no constituyen evidencia suficiente para sustituir el contenido de otra página.
- Cualquier renderer que reemplace `the_content` debe quedar limitado por ruta o identificador gobernado antes de evaluar marcadores textuales.
