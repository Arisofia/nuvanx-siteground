# Design System Audit — Metal Pulido

**Fecha:** 2026-07-15  
**HEAD tema:** stack post-`ecb4402` + docs 2.1  
**Paleta:** Metal Pulido

### Summary

| Métrica | Valor |
|---------|--------|
| Components documentados | 12 packs |
| Issues críticos de paleta | 0 |
| `!important` en tema | 0 |
| Capas fluid/visual/typography | Eliminadas |
| Score | **90/100** |

### Naming Consistency

| Issue | Components | Recommendation | Estado |
|-------|------------|----------------|--------|
| Triple alias botones | `nvx-button`, `nvx-brand-btn`, `nvx-btn` | Mantener aliases; canónico = `nvx-button` | OK |
| Kicker dual | `nvx-eyebrow`, `nvx-brand-kicker` | Canónico = `nvx-eyebrow` | OK |
| champagne alias | tokens | Alias → `--nvx-platinum` | OK |
| site-footer legacy PHP | eliminado | — | OK |

### Token Coverage

| Category | Defined | Hardcoded legacy (oro/cool) |
|----------|---------|------------------------------|
| Colors Metal Pulido | Sí | **0** |
| Spacing / shell | Sí | 0 |
| Typography | Sí | 0 |
| Radii / media | Sí | 0 |
| Motion | Parcial (`--nvx-ease`) | — |

### Component Completeness

| Component | States | Variants | Docs | Score |
|-----------|--------|----------|------|-------|
| Tokens | — | — | ✅ | 10/10 |
| Typography | — | display→copy | ✅ | 9/10 |
| Button | hover/focus | 4 variants | ✅ | 9/10 |
| Card | hover | surface | ✅ | 8/10 |
| Media + shape | — | roles + shapes | ✅ | 9/10 |
| Index | — | 3-col | ✅ | 8/10 |
| FAQ | open | details | ✅ | 8/10 |
| Shell | — | gutters | ✅ | 9/10 |
| Header | sticky/mobile | CTA | ✅ | 8/10 |
| Footer | — | dark band | ✅ | 8/10 |
| Home hero | video | full-bleed | ✅ | 8/10 |
| Forms | — | page CSS | ⚠️ | 6/10 |

### Gitignore / tracking findings

| Finding | Severity | Fix |
|---------|----------|-----|
| `index.php` y `wp-*.php` sin `/` ignoraban **cualquier** path | Alta | Scoped a **root** con `/index.php`, `/wp-*.php` |
| Tema sin `index.php` en disco | Media | Creado `nuvanx-medical/index.php` (requisito WP) |
| `!nuvanx-medical/**` | — | Asegura que el tema no quede engullido por reglas amplias |
| CSS/min del tema | — | Tracked (no ignorados) |
| `artifacts/staging2-*` | Info | Solo evidencia local; no es source |

### Priority Actions

1. ✅ Documentar sistema completo (este directorio).  
2. ✅ Corregir gitignore root-scoped.  
3. ✅ Añadir `index.php` del tema.  
4. QA visual staging post-delete de fluid (manual).  
5. Opcional: migrar hardcodes `#fff` de hero a tokens semánticos.
