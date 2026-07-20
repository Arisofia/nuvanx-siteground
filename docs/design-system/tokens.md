# Tokens visuales NUVANX

**Fuente ejecutable:** `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`  
**Regla:** este documento describe el archivo; nunca define una paleta alternativa.

## Paleta canónica

| Token | Valor | Uso |
|---|---:|---|
| `--nvx-light` | `#FCFBF8` | Blanco cálido, superficies claras y texto sobre fondos oscuros |
| `--nvx-surface-base` | `#F8F7F4` | Fondo principal |
| `--nvx-surface-soft` | `#ECEAE6` | Alternancia y superficies secundarias |
| `--nvx-border-soft` | `#D4D1CC` | Bordes visibles suaves |
| `--nvx-ink` | `#171717` | Títulos y contraste máximo |
| `--nvx-charcoal` | `#2B2926` | Fondos oscuros y hover |
| `--nvx-text-body` | `#3D3A36` | Texto de lectura |
| `--nvx-text-muted` | `#66615C` | Texto secundario |
| `--nvx-accent-muted` | `#756F69` | Kicker, índice e iconografía en fondo claro |
| `--nvx-color-line` | `rgba(23,23,23,.16)` | Líneas y bordes semánticos |

En fondos oscuros deben utilizarse exclusivamente los tokens `--nvx-text-on-dark-*` y `--nvx-border-on-dark-*`; `--nvx-accent-muted` no es un color de texto pequeño sobre negro.

### Colores retirados

No deben reaparecer en runtime:

- `#9A8A78`, `#B89A5B` o `#C5A880` como dorado/champagne.
- La antigua familia fría `#14161A`, `#2A2D33`, `#F6F7F8`, `#9BA3AD`.
- Aliases de paletas históricas como `--nvx-champagne`, `--nvx-platinum` o `--nvx-color-primary`.

## Tipografía

| Rol | Familia / token |
|---|---|
| Display, H1, H2, H3 y métricas | `--nvx-serif` · Bodoni Moda |
| Body, lead, caption, kicker, navegación, botones e índices | `--nvx-sans` · Manrope |
| Secuencia `01`, `02`, `03` | `--nvx-index-number-*` |

Los pesos Manrope cargados son 300, 400, 500, 600 y 700. No se permite solicitar un peso no cargado.

## Iconos

| Token | Valor |
|---|---:|
| `--nvx-icon-xs` | `16px` |
| `--nvx-icon-sm` | `24px` |
| `--nvx-icon-md` | `32px` |
| `--nvx-icon-lg` | `40px` |
| `--nvx-icon-frame` | `48px` |
| `--nvx-icon-stroke` | `1.5` |

Los iconos lineales deben usar `currentColor`; su color pertenece al contenedor, no al archivo SVG. Véase `icons.md`.

## Espaciado y shell

- Escala base: `--nvx-space-1` a `--nvx-space-12`, múltiplos de 8 px.
- Secciones: `--nvx-pad-section` y `--nvx-pad-section-tight`.
- Contenido: `--nvx-shell`, `--nvx-gutter`, `--nvx-gutter-inner`.
- Lectura: `--nvx-measure` y `--nvx-measure-lead`.

## Reglas de mantenimiento

1. Cambiar un valor en `nvx-tokens.css`, no en una página.
2. No incluir hex, RGB o tamaños privados en markup PHP.
3. No crear un segundo bloque `:root`.
4. Cualquier nuevo token debe tener un consumidor real y una prueba de contrato.
