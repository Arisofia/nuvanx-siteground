# Tokens visuales NUVANX

**Fuente ejecutable:** `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`  
**Regla:** este documento describe el archivo; nunca define una alternativa.

## Paleta canónica

| Token | Valor | Uso |
|---|---:|---|
| `--nvx-light` | `#FCFBF8` | Blanco cálido y texto sobre fondos oscuros |
| `--nvx-surface-base` | `#F8F7F4` | Fondo principal |
| `--nvx-surface-soft` | `#ECEAE6` | Alternancia y superficies secundarias |
| `--nvx-border-soft` | `#D4D1CC` | Bordes suaves |
| `--nvx-ink` | `#1A1A1A` | Títulos y texto principal |
| `--nvx-charcoal` | `#2B2926` | Fondos oscuros y hover |
| `--nvx-text-body` | `#1A1A1A` | Texto de lectura |
| `--nvx-text-muted` | `#66615C` | Texto secundario |
| `--nvx-accent-muted` | `#756F69` | Kicker, índice e iconografía en fondo claro |
| `--nvx-color-line` | `rgba(26,26,26,.16)` | Líneas y bordes semánticos |

En fondos oscuros se utilizan los tokens `--nvx-text-on-dark-*` y `--nvx-border-on-dark-*`.

## Tipografía oficial única

No hay opciones intercambiables en runtime.

```css
--nvx-serif: "Playfair Display", Georgia, "Times New Roman", serif;
--nvx-sans: "Manrope", "Helvetica Neue", Arial, sans-serif;
```

| Rol | Token / valor |
|---|---|
| Display / Hero | `clamp(2.8rem, 5vw, 4.2rem)` |
| H1 | `clamp(2.2rem, 4vw, 3.2rem)` |
| H2 | `clamp(1.7rem, 3vw, 2.4rem)` |
| H3 | `1.4rem` |
| Body | `1.0625rem` · 17px |
| Small | `0.875rem` |
| Caption | `0.75rem` · tracking `0.04em` · uppercase |

- Display, H1, H2 y H3: Playfair Display, peso 500, tracking `-0.02em`, interlineado `1.15`.
- Body y UI: Manrope, peso 400, interlineado `1.6`.
- Manrope cargada en 300, 400, 500, 600 y 700.
- Playfair Display cargada en 400, 500, 600 y 700, más italic 400.

### Fuentes prohibidas en runtime

- Bodoni Moda.
- Cormorant Garamond.
- Playfair, Manrope u otras familias escritas directamente fuera de `--nvx-serif` y `--nvx-sans`.
- Variables paralelas `--nvx-serif-2`, `--nvx-serif-3`, `--nvx-sans-2` o `--nvx-sans-3`.

## Iconos

| Token | Valor |
|---|---:|
| `--nvx-icon-xs` | `16px` |
| `--nvx-icon-sm` | `24px` |
| `--nvx-icon-md` | `32px` |
| `--nvx-icon-lg` | `40px` |
| `--nvx-icon-frame` | `48px` |
| `--nvx-icon-stroke` | `1.5` |

Los iconos lineales utilizan `currentColor`; su color pertenece al contenedor, no al SVG.

## Espaciado y shell

- Escala base: `--nvx-space-1` a `--nvx-space-12`, múltiplos de 8 px.
- Secciones: `--nvx-pad-section` y `--nvx-pad-section-tight`.
- Contenido: `--nvx-shell`, `--nvx-gutter`, `--nvx-gutter-inner`.
- Lectura: `--nvx-measure` y `--nvx-measure-lead`.

## Mantenimiento

1. Cambiar un valor en `nvx-tokens.css`, no en una página.
2. No incluir familias, hex, RGB o tamaños tipográficos privados en markup PHP.
3. No crear un segundo bloque `:root`.
4. Todo nuevo token debe tener un consumidor real y una prueba de contrato.
