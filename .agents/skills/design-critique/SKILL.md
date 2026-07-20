---
name: design-critique
description: Instrucciones y directivas para realizar críticas de diseño (Design Critiques) rigurosas sobre NUVANX, garantizando que cumplan el contrato visual estricto del "Quiet Luxury".
---

# Design Critique (NUVANX)

Esta skill define el framework para evaluar y criticar cualquier pantalla, componente o prototipo destinado a NUVANX.

## Framework de Evaluación
Toda crítica debe evaluar los siguientes 5 pilares, leyendo directamente la configuración desde el código base de NUVANX (`nvx-tokens.css` y `docs/design-system/`):

### 1. First Impression & Identidad Visual

- ¿Refleja el tono "Quiet Luxury"?
- ¿Utiliza la combinación tipográfica estricta (Playfair Display para encabezados, Manrope para UI/cuerpo)?
- ¿Se basa en la paleta oficial (Metal Pulido) de `nvx-tokens.css` sin introducir colores "hardcoded" (regla estricta en runtime, ver `nuvanx-theme-factory` para excepciones locales de marketing)?

### 2. Usability & Operabilidad

- Evaluaciones basadas en los guidelines de accesibilidad (touch targets >= 48px, modales operables por teclado).

### 3. Visual Hierarchy

- Uso riguroso de la escala tipográfica validada (clamp values).
- Distribución de espaciado según los tokens `--nvx-space-*` y `--nvx-gap-*`.

### 4. Consistency

- El diseño no debe introducir tamaños privados, ni `!important`, ni variables sueltas. Todo debe mapearse al sistema de variables `--nvx-`.

### 5. Accessibility (WCAG 2.1 AA)

- Verificación de contraste (min 4.5:1 para texto normal, 3:1 para grandes).
- Focus indicators visibles obligatorios. Usar `aria-label` únicamente cuando no exista un nombre accesible semántico o visible.

## Ejecución

Cuando se te pida realizar una crítica de diseño, lee primero `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css` para contrastar el diseño propuesto contra la fuente de la verdad del proyecto. Emite un reporte priorizando violaciones directas del sistema visual.
