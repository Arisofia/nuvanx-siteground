# Auditoría maestra staging2 — 24 páginas

Generado desde la auditoría editorial/clínica (2026-07-16).

## Entregables

| Archivo | Uso |
|---------|-----|
| `nuvanx_staging2_auditoria_maestra_24_paginas.xml` | Informe maestro: 24 `page_audit`, exclusiones 1380/2635/18, P0–P2, **NO GO producción** |
| `nuvanx_staging2_modulos_paginas_06_24.html` | Módulos HTML de transformación (páginas 06–24) |
| `nuvanx_staging2_componentes_canonicos_24_paginas.css` | CSS de módulos de auditoría (tokens 8px); integrar solo tras revisión |
| `nuvanx_staging2_higiene_canonica_paginas.php` | Copia de referencia; **canónico en tema**: `inc/nvx-page-hygiene.php` |

## Higiene en tema (`nvx-page-hygiene.php`)

- Redirect 301 de cookies antiguas **18** y **31** → **577**
- `noindex` página **78** (transaccional)
- `noindex` casos **2645** hasta meta `_nvx_cases_publication_ready=1`
- Exclusión sitemap Yoast de 78 y 2645 (si no ready)

## Veredicto

- **Producción:** NO GO  
- **Staging2:** GO para corrección controlada  

P0: legal, Endolift≠RF, EXILITE JSON-LD, casos vacíos, conversión valoración.
