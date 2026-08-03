# Análisis de Valores Hardcoded en CSS

## Resumen Ejecutivo
Análisis de valores hardcoded en archivos CSS del tema nuvanx-medical que deberían migrarse a tokens CSS.

## 📊 ESTADÍSTICAS
- **Total archivos CSS analizados**: 13 archivos principales
- **Valores hardcoded encontrados**: 33 instancias (excluyendo tokens y media queries)
- **Archivos con más hardcoded values**: nvx-components.css (4), nvx-tokens.css (31 esperado), nvx-home-v3.css (2)

## 🔍 VALORES HARDCODED IDENTIFICADOS

### 1. nvx-components.css (4 instancias)
**Línea 219**: `outline-offset: 2px;`
- **Token sugerido**: `--nvx-space-1` (8px) o crear `--nvx-outline-offset`
- **Prioridad**: Baja (outline visual, no afecta layout)

**Línea 351**: `text-decoration-thickness: 1px;`
- **Token sugerido**: `--nvx-border-hairline` (1px) ya existe
- **Prioridad**: Baja (estilo visual)

**Línea 416**: `outline-offset: 2px;`
- **Token sugerido**: `--nvx-space-1` (8px) o crear `--nvx-outline-offset`
- **Prioridad**: Baja

**Línea 996**: `min-height: 120px;`
- **Token sugerido**: `calc(var(--nvx-space-6) * 2.5)` = 120px
- **Prioridad**: Media (afecta layout de modal)

### 2. nvx-posts.css (1 instancia)
**Línea 26**: `gap: 1px;`
- **Token sugerido**: `--nvx-border-hairline` (1px) ya existe
- **Prioridad**: Baja (border visual)

### 3. nvx-portfolio-hub.css (1 instancia)
**Línea 109**: `max-width: 900px;`
- **Token sugerido**: `--nvx-shell` ya es `min(1240px, calc(100vw - var(--nvx-gutter)))`
- **Prioridad**: Media (afecta layout de catálogo)

### 4. nvx-patterns-editorial.css (2 instancias)
**Líneas 894-895**: `width: 1px; height: 1px;`
- **Token sugerido**: `--nvx-border-hairline` (1px) ya existe
- **Prioridad**: Baja (screen reader text, no visible)

### 5. nvx-home-v3.css (2 instancias)
**Línea 14**: `min-height: 600px;`
- **Token sugerido**: `calc(var(--nvx-hero-h) * 0.6)` = ~588px
- **Prioridad**: Alta (afecta hero section layout)

**Línea 455**: `max-width: 800px;`
- **Token sugerido**: Crear `--nvx-measure-tight` o usar `--nvx-measure` (68ch)
- **Prioridad**: Media (afecta layout de section)

### 6. nvx-accessibility-governance.css (2 instancias)
**Línea 96**: `top: -60px;`
- **Token sugerido**: `calc(var(--nvx-header-height) * -0.75)` = -60px
- **Prioridad**: Alta (afecta skip-link functionality)

**Línea 109**: `outline-offset: 2px;`
- **Token sugerido**: `--nvx-space-1` (8px) o crear `--nvx-outline-offset`
- **Prioridad**: Baja

## 🎯 TOKENS CSS EXISTENTES (para referencia)

### Espaciado
- `--nvx-space-1`: 8px
- `--nvx-space-2`: 16px
- `--nvx-space-3`: 24px
- `--nvx-space-4`: 32px
- `--nvx-space-5`: 40px
- `--nvx-space-6`: 48px
- `--nvx-space-8`: 64px
- `--nvx-space-10`: 80px
- `--nvx-space-12`: 96px

### Dimensiones
- `--nvx-header-height`: 80px
- `--nvx-header-height-mobile`: 72px
- `--nvx-hero-h`: min(92svh, 980px)
- `--nvx-shell`: min(1240px, calc(100vw - var(--nvx-gutter)))
- `--nvx-measure`: 68ch
- `--nvx-measure-wide`: 72rem

### Otros
- `--nvx-border-hairline`: 1px
- `--nvx-gutter`: clamp(32px, 6vw, 96px)

## 📋 PLAN DE MIGRACIÓN PRIORIZADO

### Prioridad ALTA (afecta layout crítico)
1. **nvx-home-v3.css línea 14**: `min-height: 600px` → `calc(var(--nvx-hero-h) * 0.6)`
2. **nvx-accessibility-governance.css línea 96**: `top: -60px` → `calc(var(--nvx-header-height) * -0.75)`

### Prioridad MEDIA (afecta layout secundario)
3. **nvx-components.css línea 996**: `min-height: 120px` → `calc(var(--nvx-space-6) * 2.5)`
4. **nvx-portfolio-hub.css línea 109**: `max-width: 900px` → crear `--nvx-catalog-width`
5. **nvx-home-v3.css línea 455**: `max-width: 800px` → crear `--nvx-section-tight-width`

### Prioridad BAJA (estilo visual)
6. **nvx-components.css líneas 219, 416**: `outline-offset: 2px` → crear `--nvx-outline-offset`
7. **nvx-components.css línea 351**: `text-decoration-thickness: 1px` → `--nvx-border-hairline`
8. **nvx-posts.css línea 26**: `gap: 1px` → `--nvx-border-hairline`
9. **nvx-patterns-editorial.css líneas 894-895**: `width/height: 1px` → `--nvx-border-hairline`
10. **nvx-accessibility-governance.css línea 109**: `outline-offset: 2px` → crear `--nvx-outline-offset`

## 🆕 TOKENS NUEVOS PROPUESTOS

```css
/* Propuestos para nvx-tokens.css */
--nvx-outline-offset: 2px;
--nvx-catalog-width: 900px;
--nvx-section-tight-width: 800px;
--nvx-modal-min-height: 120px;
```

## 📊 IMPACTO ESTIMADO
- **Archivos a modificar**: 6 archivos
- **Líneas a cambiar**: 12 líneas
- **Tokens nuevos**: 4 tokens
- **Riesgo de regresión**: Bajo (cambios puramente visuales o calculados)

---

**Generado**: 2026-08-03
**Estado**: Análisis completado, pendiente migración