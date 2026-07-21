# Menú principal definitivo NUVANX

## Fuente de verdad

El menú asignado a la ubicación **Primary** en WordPress es la fuente de verdad.
El tema no inyecta tratamientos ni reordena elementos después de guardarlos.

- Panel: `Apariencia → Menús`.
- Nombre recomendado: `NUVANX Principal`.
- Ubicación: `Primary`.
- Profundidad máxima soportada: tres niveles.
- El CTA `Solicitar valoración médica` permanece fuera del árbol porque ya lo renderiza el header.
- Solo deben añadirse páginas publicadas. Los elementos de página que vuelvan a borrador se retiran automáticamente del render público junto con sus descendientes.

## Árbol definitivo

```text
INICIO

SOLUCIONES                                      [clase opcional: nvx-menu--mega]
├── Rostro y cuello
├── Calidad de piel
├── Contorno corporal
├── Cambios posgestacionales
├── Cicatrices, poros y textura
├── Manchas, rojeces y fotodaño
└── Medicina estética masculina

PROTOCOLOS SIGNATURE                            [clase opcional: nvx-menu--mega]
├── NUVANX Contour Architecture™
├── NUVANX Post-Maternity Contour™
├── NUVANX Profile Definition™
├── NUVANX Skin Architecture™
├── NUVANX Surface Renewal™
└── NUVANX Tone Correction™

TECNOLOGÍA                                      [clase opcional: nvx-menu--mega]
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
```

Los pilares `Soluciones`, `Protocolos Signature` y `Tecnología` reciben automáticamente el tratamiento de mega-menú por su etiqueta. La clase `nvx-menu--mega` permite conservar el mismo tratamiento si una etiqueta cambia.

## Rutas objetivo

| Elemento | Ruta objetivo |
|---|---|
| Soluciones | `/soluciones-medicas/` |
| Protocolos Signature | `/protocolos-signature/` |
| Tecnología | `/medicina-estetica-laser/` |
| Casos clínicos | `/casos-clinicos/` |
| Equipo médico | `/equipo-medico/` |
| Clínicas | `/clinicas-de-medicina-estetica-nuvanx/` |
| Journal | `/blog/` |

### Protocolos Signature

| Elemento | Ruta objetivo |
|---|---|
| NUVANX Contour Architecture™ | `/remodelacion-corporal-laser-madrid/` |
| NUVANX Post-Maternity Contour™ | `/tratamiento-postparto-abdomen-contorno-corporal-madrid/` |
| NUVANX Profile Definition™ | `/papada-definicion-mandibular-madrid/` |
| NUVANX Skin Architecture™ | `/calidad-piel-firmeza-luminosidad-madrid/` |
| NUVANX Surface Renewal™ | `/cicatrices-acne-poros-textura-madrid/` |
| NUVANX Tone Correction™ | `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/` |

No debe crearse un enlace personalizado hacia una ruta futura. Primero se crea la página, se mantiene en borrador durante la revisión médica, jurídica, SEO y visual, y se incorpora al menú únicamente después de publicarla.

## Comportamiento de escritorio

- Ocho pilares compactos y CTA independiente.
- Los tres pilares comerciales abren paneles editoriales de tres columnas.
- Se admite tercer nivel para agrupar subfamilias futuras.
- Apertura mediante `hover` y `focus-within`.
- El breakpoint de navegación cambia al drawer antes de que el header pueda desbordarse.

## Comportamiento móvil

- Los hijos permanecen cerrados al abrir el drawer.
- Cada padre recibe un botón independiente con `aria-expanded` y `aria-controls`.
- Pulsar el nombre navega al hub publicado.
- Pulsar `+ / −` abre o cierra el acordeón.
- Si un padre se configura temporalmente con `#`, pulsar su etiqueta también abre el acordeón.
- Al abrir una familia se cierra su hermana del mismo nivel.
- `Escape`, el botón de cierre y el cambio a escritorio restablecen todos los acordeones.
- Se respeta `prefers-reduced-motion`.

## Reglas de publicación

1. No incorporar LipoSculpt-Air™, V-Lift Awake™ ni ningún protocolo `pending_medical_legal`.
2. No copiar categorías residuales de AirSculpt ni añadir servicios no confirmados.
3. No añadir páginas que respondan 404, borradores o rutas en cuarentena.
4. No duplicar el CTA de valoración dentro del menú.
5. Validar desktop, móvil, teclado, lector de pantalla y ausencia de overflow en Staging2 antes de Producción.
