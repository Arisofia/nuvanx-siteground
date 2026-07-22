# Menú principal definitivo NUVANX

## Fuente de verdad

El menú `NUVANX Principal` asignado a la ubicación **Primary** en WordPress es la fuente de verdad. La migración de production readiness lo crea o reconstruye de forma idempotente y conserva el menú anterior sin asignarlo, como rollback operativo.

- Ubicación: `Primary`.
- Profundidad máxima: tres niveles.
- Solo se incorporan páginas publicadas.
- El CTA `Solicitar valoración médica` permanece fuera del árbol porque ya lo renderiza el header.
- Los tres pilares comerciales reciben la clase `nvx-menu--mega`.
- `Contacto` usa `nvx-menu-mobile-only` para estar disponible en el drawer móvil sin sobrecargar el header desktop.

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
├── NUVANX Post-Maternity Contour™
├── NUVANX Profile Definition™
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
CONTACTO [solo móvil]
```

## Rutas canónicas

| Elemento | Ruta |
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

| Protocolo | Ruta |
|---|---|
| NUVANX Contour Architecture™ | `/remodelacion-corporal-laser-madrid/` |
| NUVANX Post-Maternity Contour™ | `/tratamiento-postparto-abdomen-contorno-corporal-madrid/` |
| NUVANX Profile Definition™ | `/papada-definicion-mandibular-madrid/` |
| NUVANX Skin Architecture™ | `/calidad-piel-firmeza-luminosidad-madrid/` |
| NUVANX Surface Renewal™ | `/cicatrices-acne-poros-textura-madrid/` |
| NUVANX Tone Correction™ | `/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/` |

## Comportamiento desktop

- Ocho pilares compactos y CTA independiente.
- Soluciones, Protocolos Signature y Tecnología abren paneles editoriales.
- Apertura mediante `hover` y `focus-within`.
- Los hijos no publicados se omiten.
- El breakpoint cambia al drawer antes de que el header pueda desbordarse.

## Comportamiento móvil

- Los hijos permanecen cerrados al abrir el drawer.
- Cada padre recibe un botón independiente con `aria-expanded` y `aria-controls`.
- Pulsar el nombre navega al hub publicado.
- Pulsar `+ / −` abre o cierra el acordeón.
- Al abrir una familia se cierra su hermana del mismo nivel.
- `Escape`, cierre y cambio a escritorio restablecen los acordeones.
- Se respeta `prefers-reduced-motion`.

## Reglas de publicación

1. No incorporar LipoSculpt-Air™, V-Lift Awake™, Eye Frame™ ni ningún protocolo no aprobado.
2. No añadir páginas 404, borradores o rutas en cuarentena.
3. No duplicar el CTA de valoración dentro del menú.
4. No exponer zonas de Fase 3 hasta confirmar servicio, profesional, autorización y claims.
5. Validar desktop, móvil, teclado, lector de pantalla y ausencia de overflow en staging2 antes de producción.
