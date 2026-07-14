# Ticket 43 — V3 Full-Home Structure Diff

Canonical source: `qa/fixtures/ticket-43/home-production-post-content.html` (producción ID 9)

## Secciones reestructuradas

| Sección | Producción | V3 |
|---------|------------|-----|
| Wrapper | `#nvx-home-main.nvx-home.nvx-brand-page` | `#nvx-home-main` + clase `nvx-editorial-home-v3` |
| Hero | `section.nvx-brand-hero` + `section.nvx-home-video-feature` hermanos | Grid 44/56 en `.nvx-brand-hero__inner`; máscara SVG; onda asimétrica |
| CTAs intro | En `.nvx-home-invitation .nvx-brand-cta` | Reubicados a `.nvx-home-hero-ctas` (mismo texto/href) |
| Intro | Párrafos + clínicas + invitación en una sección | `.nvx-v3-intro` con composición abierta (imagen + copy + invitación) |
| Clínicas | Dentro de intro | Reubicadas a `.nvx-home-clinicas-panorama` (franja panorámica) |
| Tratamientos | `.nvx-brand-grid--3` con cards | `.nvx-home-tratamientos-editorial` filas 4+3, divisores finos |
| Método | Grid 3 cards | `.nvx-home-metodo-pilares` pilares abiertos numerados |
| Dirección médica | Grid 2 cards | `.nvx-home-direccion-grid` retrato + copy + registros |
| FAQ | `.nvx-brand-faq-accordion` | Mismo contenido + clase `.nvx-home-faq-editorial` |
| CTA final | `.nvx-home-invitation` blanco | `.nvx-home-cta-final-band` banda negra redondeada |

## Nodos añadidos (sin texto nuevo)

- `.nvx-v3-shell` — contenedor 1360px
- `.nvx-home-video-mask` + SVG `clipPath`
- `.nvx-home-organic-wave--asymmetric`
- `.nvx-home-editorial-open`, `.nvx-home-clinicas-panorama__track`
- `.nvx-home-tratamientos-row--4`, `.nvx-home-tratamientos-row--3`
- `.nvx-home-metodo-pilares`, `.nvx-home-pilar-item`
- `.nvx-home-direccion-grid`, `.nvx-home-direccion-media`
- `.nvx-home-cta-final-band`
- Imagen decorativa intro (`alt=""`) y retrato dirección (`alt=""`)

## Copy lock

Verificado con `verify-copy-integrity.php`: hashes y conteos idénticos (h1=1, h2=6, links=24, faq=13, treatments=7, CS20144=1, CS20073=1).