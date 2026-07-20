# NUVANX Design System — Web canónico

**Versión:** 3.0  
**Tipografía validada:** julio de 2026  
**Docs de componentes:** [`design-system/`](./design-system/README.md)

## Principio

**Un solo diseño en todo el sitio.**

Blog, valoración, contacto, sedes, tratamientos, gracias y 404 comparten paleta, tipografía, escalas, iconos, numeraciones, márgenes, botones y media. El vídeo del Home es una diferencia de contenido, no un segundo sistema visual.

## Paleta

| Token | Valor |
|---|---:|
| `--nvx-ink` | `#1A1A1A` |
| `--nvx-charcoal` | `#2B2926` |
| `--nvx-light` | `#FCFBF8` |
| `--nvx-surface-base` | `#F8F7F4` |
| `--nvx-surface-soft` | `#ECEAE6` |
| `--nvx-border-soft` | `#D4D1CC` |
| `--nvx-accent-muted` | `#756F69` |

La única fuente ejecutable es `assets/css/nvx-tokens.css`.

## Tipografía oficial

- Display, H1, H2 y H3: **Playfair Display**, peso 500.
- Body y UI: **Manrope**.
- Display: `clamp(2.8rem, 5vw, 4.2rem)`.
- H1: `clamp(2.2rem, 4vw, 3.2rem)`.
- H2: `clamp(1.7rem, 3vw, 2.4rem)`.
- H3: `1.4rem`.
- Body: `1.0625rem`, interlineado `1.6`.
- Caption: `0.75rem`, tracking `0.04em`, uppercase.

No se utilizan Bodoni Moda ni Cormorant Garamond.

## Iconos

- Una escala: 16, 24, 32 y 40 px.
- Frame editorial: 48 px.
- Trazo lineal: 1.5.
- Color: `currentColor`.
- Registro y cierre runtime: `inc/nvx-visual-system.php`.

## Numeraciones

- Secuencias: `01`, `02`, `03`, en Manrope.
- El número está separado del título.
- Las métricas usan Playfair Display y pertenecen a otro rol.
- Las listas ordenadas editoriales conservan sus marcadores.

## Stack CSS

```text
fonts → tokens → base → site-layout → components → patterns → header → footer → home
                                           ↘ cierre canónico runtime
```

## No reintroducir

- Segunda familia serif.
- Escalas tipográficas por página.
- SVG con colores o trazos fijos.
- Números dentro de kickers o H3.
- Segundo bloque `:root`.
- Oro, cool-green o paletas históricas.
- Estilos inline para componentes reutilizables.

---

*v3.0 — Playfair Display + Manrope como única combinación web.*
