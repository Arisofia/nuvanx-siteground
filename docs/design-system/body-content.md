# Body content system (text + images)

**Single source of truth.** Page modules (Endolift, Láser, Medicina Estética, home values) must **not** invent private margins or image radii. They inherit from tokens + layout.

## Where it lives

| Layer | File | Owns |
|-------|------|------|
| Tokens | `nvx-tokens.css` | `--nvx-pad-section`, `--nvx-gutter-inner`, `--nvx-measure`, `--nvx-margin-*`, `--nvx-radius-image` |
| Layout | `nvx-site-layout.css` | Shell inners, section pad, body text measure, content figures |
| Components | `nvx-components.css` | `.nvx-media`, `.nvx-media--body`, buttons (pill) |
| PHP | `nvx-content-presentation.php` | `nvx_content_normalize_body_media()` tags body figures/imgs |

## Rules

### Text
- Body paragraphs use `margin-bottom: var(--nvx-margin-body)` (24px).
- Long-form measure: `max-width: var(--nvx-measure)` (68ch) on section body copy.
- Headings: `var(--nvx-margin-h2)` / `h3` tokens.
- Kickers: `var(--nvx-margin-kicker)` + platinum.

### Images (body, not hero)
- Content `<figure>` / `.wp-block-image` get shared rhythm: `margin-block: var(--nvx-space-6)`, `border-radius: var(--nvx-radius-image)`.
- Body `<img>` receive classes `nvx-media nvx-media--body` via the presentation filter.
- **Hero media** (`.nvx-brand-hero__media`, etc.) stays full-bleed — excluded from body rules.

### Sections
- Shared inners list in `nvx-site-layout.css`: `.nvx-brand-section__inner`, `.nvx-endolift-section__inner`, `.nvx-laser-section__inner`, `.nvx-aes-section__inner`, catalog, action shells.
- Section vertical pad: `var(--nvx-pad-section)` (96–120px).

## Do / Don't

| Do | Don't |
|----|--------|
| Change a token once; all pages follow | Hardcode `padding: 96px` or `3rem` in a page block |
| Use `.nvx-button` pills | Square one-off CTA blocks per page |
| Tag body media with `nvx-media--body` | Per-page `img { border-radius: 8px }` |
| Keep hero filters separate | Apply body measure to hero overlay copy without care |

## Connected modules

When you edit this system, smoke-check:

1. Home — values + action banner  
2. `/tratamientos/`  
3. `/endolift-facial-papada-mandibula/`  
4. `/medicina-estetica-laser/`  
5. `/medicina-estetica/`  
6. Any interior page with body figures  

`scripts/staging2/smoke-verify-staging2.sh` + deploy workflow on `master` enforce presence of key markers after merge.
