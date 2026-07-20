# NUVANX Accessibility Audit (WCAG 2.1 AA)

**Date**: 2026-07-20
**Scope**: NUVANX Medical Editorial Theme (Staging2 / Master)
**Methodology**: Static Code Analysis & POUR Framework Evaluation

---

## 1. Perceivable
*Information and user interface components must be presentable to users in ways they can perceive.*

### 1.1 Text Alternatives & ARIA
- **Status**: `PASS / PARTIAL`
- **Findings**:
  - ARIA labels (`aria-label`) están aplicados en el CTA Global (`#nvx-site-closing-cta`) y en los modales de valoración.
  - El SVG de Joinchat / WhatsApp se inyecta correctamente utilizando el ayudante canónico (`nvx_visual_whatsapp_icon_svg()`), asegurando que la semántica gráfica sea controlada centralmente.
- **Action Items**: Asegurar que las imágenes insertadas por editores en HubSpot o en los posts médicos tengan el atributo `alt` correcto en la capa de contenido.

### 1.2 Contrast (Minimum)
- **Status**: `PASS`
- **Findings**:
  - El design system utiliza pares de alto contraste de manera estricta: `--nvx-ink` (`#1a1a1a`) sobre `--nvx-light` (`#fcfbf8`).
  - El ratio de contraste excede holgadamente el 4.5:1 exigido para textos normales y el 3:1 para textos grandes.
  - La auditoría en `scripts/accessibility/test-contrast-contract.mjs` certifica la ausencia de violaciones de contraste en el uso de los tokens principales.

---

## 2. Operable
*User interface components and navigation must be operable.*

### 2.1 Keyboard Accessible & Focus Indicators
- **Status**: `PASS / PARTIAL`
- **Findings**:
  - El menú de navegación (Header Sticky) es operable vía teclado.
  - Los formularios embebidos de HubSpot y los modales de valoración capturan el foco.
  - Los indicadores de foco (`outline`, `outline-offset`) están definidos en los tokens visuales (`--nvx-color-primary` o anillos de contraste).
- **Action Items**:
  - Validar que el botón flotante de Joinchat (`.joinchat__button`) no atrape el foco (focus trap) impidiendo volver al cuerpo de la página.

### 2.2 Navigable (Touch Targets)
- **Status**: `PASS`
- **Findings**:
  - Todos los botones interactivos e íconos en marcos (ej. Joinchat frame) tienen un área mínima de interacción de `48px` (`--nvx-icon-frame`), cumpliendo el estándar avanzado de accesibilidad táctil.

---

## 3. Understandable
*Information and the operation of user interface must be understandable.*

### 3.1 Predictability & Consistency
- **Status**: `PASS`
- **Findings**:
  - La navegación es altamente predecible. La limitación tipográfica a **Playfair Display** (Títulos) y **Manrope** (UI) reduce la fatiga cognitiva.
  - Los componentes repetitivos (CTA clusters, footers) mantienen exactamente el mismo orden y marcado a través del ecosistema, verificado por `audit-css.mjs`.

---

## 4. Robust
*Content must be robust enough that it can be interpreted by a wide variety of user agents, including assistive technologies.*

### 4.1 Parsing & ARIA Roles
- **Status**: `PASS`
- **Findings**:
  - Roles estructurales implementados correctamente.
  - Las herramientas de auditoría (`audit-visual-system.mjs`) rechazan el HTML mal formado o la fuga de estilos "inline", garantizando un árbol DOM limpio para los Screen Readers.

---

## Priority Fixes & Next Steps
1. **Focus Trap Testing**: Realizar una prueba manual con lector de pantalla (VoiceOver/NVDA) sobre el flujo: `Abrir modal de valoración` -> `Completar formulario` -> `Cerrar modal`.
2. **Alt Text Enforcement**: Validar la higiene del contenido médico para asegurar alternativas textuales en todos los diagramas anatómicos o resultados "Antes y Después".
