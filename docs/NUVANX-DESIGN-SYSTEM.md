# NUVANX Design System — Metal Pulido

**Versión:** 2.2  
**Docs de componentes:** [`design-system/`](./design-system/README.md)

## Principio

**Un solo diseño en todo el sitio.**  
La única diferencia es el **vídeo del home** (`nvx-brand-home.css`).

Blog, consulta, contacto, sedes, tratamientos, gracias y 404 comparten tokens, tipografía, márgenes, botones y media.

## Paleta (tokens)

| Token | Hex |
|-------|-----|
| ink | `#14161A` |
| charcoal | `#2A2D33` |
| pearl | `#F6F7F8` |
| mist | `#E8EAED` |
| silver | `#C4C8CE` |
| platinum | `#9BA3AD` |
| white | `#FFFFFF` |

Aliases de compat: champagne→platinum, ivory→pearl, sand→mist.

## Tipografía

- Display / H1–H3: **Bodoni Moda**  
- Body / UI: **Manrope**  
- Clases: `nvx-eyebrow`, `nvx-heading`, `nvx-lead`, `nvx-copy`, `nvx-button`

## Shell

- Gutter: `clamp(48px, 6vw, 120px)`  
- Section Y: `clamp(80px, 9vw, 140px)`  
- Measure texto: `62ch`  
- Una columna en listados y grids

## Stack CSS

```
tokens → base → site-layout → header → footer → components
(+ brand-home solo en home / vídeo)
```

## No reintroducir

- CSS por tipo de página  
- Segundo sistema en `base` (botones/header/tipo)  
- `!important`  
- Oro / cool-green  
- Columnas multi-col como layout de página  
- Plantillas “journal / single-hero” paralelas  

---

*v2.2 — un diseño; solo el vídeo del home es especial.*
