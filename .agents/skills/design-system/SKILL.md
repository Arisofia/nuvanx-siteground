---
name: design-system
description: Instrucciones operativas para entender, extender y generar Handoff Specs basados en el Design System "Metal Pulido" de NUVANX.
---

# Design System Handoff (NUVANX)

Esta skill gobierna la generación automática de "Handoff Specs" desde diseños hacia desarrollo, asegurando que cualquier nuevo componente cumpla con las directrices del repositorio principal de NUVANX.

## 1. Reglas Generales de Extensión
Cuando se te solicite implementar o diseñar un componente nuevo:
- **Zero New Tokens**: No debes crear nuevos `--nvx-` a menos que sea estructuralmente imprescindible y se documente en `nvx-tokens.css`.
- **Sin `!important`**: El sistema resuelve la especificidad mediante la carga secuencial (`ACTIVE_STACK` en `scripts/design-system/audit-css.mjs`) y el peso natural de BEM.

## 2. Generación de Handoff Spec
Un "Design Handoff Spec" generado por esta skill DEBE incluir:
- **Tokens Utilizados**: Una lista exhaustiva de las variables `--nvx-` mapeadas desde el diseño (colores, sombras, espaciado).
- **Tipografía**: La jerarquía mapeada a `--nvx-type-*` y `--nvx-lh-*`.
- **Accesibilidad**: Indicadores de foco previstos y atributos ARIA (ej. `aria-label`, `aria-expanded`).
- **Estados Activos**: Hover, focus, active mapping.

## 3. Integración Directa
Antes de emitir el spec, el agente debe usar `view_file` sobre `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css` y `docs/design-system/` para garantizar que la propuesta de Handoff es sintácticamente idéntica a los tokens vivos del proyecto.
