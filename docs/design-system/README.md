# NUVANX Design System — Metal Pulido

**Versión:** 2.1  
**Paleta oficial:** Metal Pulido (Plata Pulida)  
**Código de tokens:** `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`  
**Contrato resumido:** [`../NUVANX-DESIGN-SYSTEM.md`](../NUVANX-DESIGN-SYSTEM.md)

## Índice

| Doc | Contenido |
|-----|-----------|
| [AUDIT.md](./AUDIT.md) | Auditoría de consistencia post-migración |
| [tokens.md](./tokens.md) | Tokens: color, type, spacing, shell, motion |
| [typography.md](./typography.md) | Tipografía y jerarquía editorial |
| [button.md](./button.md) | Botones y aliases |
| [card.md](./card.md) | Cards y superficies |
| [media.md](./media.md) | Roles de imagen y shapes |
| [index.md](./index.md) | Índice / pilares numerados |
| [faq.md](./faq.md) | FAQ accordion |
| [shell-layout.md](./shell-layout.md) | Shell, gutters, secciones |
| [header.md](./header.md) | Header sticky |
| [footer.md](./footer.md) | Footer + pre-footer CTA |
| [home-hero.md](./home-hero.md) | Hero vídeo home |
| [patterns.md](./patterns.md) | Patrones de sección y ownership CSS |

## Principios

1. **Una sola fuente de color/espaciado:** `nvx-tokens.css`.
2. **Sin `!important`** en CSS del tema.
3. **Sin redefinir `:root` de paleta** en brand/page CSS.
4. **Shapes por clase explícita**, nunca `nth-child`.
5. **Metal pulido**, no oro champagne ni cool-green.
6. **Page CSS = composición**, no componentes globales.

## Stack de carga

```
tokens → base → components → site-layout → header → footer → pages → [page CSS]
```

## Nomenclatura

- Prefijo: `nvx-`
- Bloque BEM-like: `nvx-component__element--modifier`
- Aliases legacy permitidos solo si resuelven al canónico (p. ej. `nvx-btn` → `nvx-button`)
