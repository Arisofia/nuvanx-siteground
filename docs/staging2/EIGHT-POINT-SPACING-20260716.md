# Staging2 — NUVANX 8px spacing system

This candidate applies the NUVANX Plata Pulida color and typography system across the active theme while standardizing layout spacing on an 8px scale.

## Global contract

- section padding: 96px to 120px desktop;
- tight sections: 64px to 80px;
- desktop grid gutter: 32px to 48px;
- title-to-copy distance: 16px;
- icon-to-title distance: 16px to 24px;
- mobile value-item separation: 48px;
- reading measure: maximum 68 characters;
- Bodoni Moda for headings;
- Manrope for body, labels and actions;
- icons: outline SVG, 32px maximum, 1.5px stroke, Plata Pulida platinum or ink;
- no alert colors or generic corporate blue/green/red iconography.

## Staging-only deployment

The branch includes a one-time workflow that validates the exact staging2 URL, active theme and source tokens before deployment. It copies the current three CSS files to a protected directory outside the web root, deploys the theme to staging2, purges staging caches and requires HTTP 200.

Production is not contacted or modified.
