# NUVANX Plata Pulida 2.0

## Runtime contract

The active theme loads one direct, non-nested visual stack:

1. `nvx-fonts.css`
2. `nvx-tokens.css`
3. `nvx-base.css`
4. `nvx-site-layout.css`
5. `nvx-components.css`
6. `nvx-patterns-editorial.css`
7. `nvx-header.css`
8. `nvx-footer.css`
9. `nvx-brand-home.css` only on the front page

No minified duplicates, compatibility stylesheets, V3/V4 layers, runtime class remapping, content regular-expression rewrites, page-specific token declarations, backup copies or legacy archives are part of the canonical system.

## Typography

- Display, H1, H2, H3 and editorial numerals: Bodoni Moda 400.
- Body, lead, kicker, caption, navigation and buttons: Manrope.
- Pinyon Script is permitted only for an explicit signature component.

## Composition

- Twelve-column editorial grid with controlled asymmetry.
- Pill buttons.
- Contextual radii; not every block is a card.
- Explicit media roles and shape modifiers.
- No `nth-child` composition.
- No `:has()` dependencies.
- No glassmorphism or decorative gradient system.
- Text overlays are selective and require accessible contrast.

## Source of truth

Changes replace the corresponding canonical source file. Do not retain `.old`, `.bak`, `legacy`, versioned design forks or historical CSS copies in the active repository tree.

Git history is not a runtime backup mechanism and is managed separately from the deployed theme.