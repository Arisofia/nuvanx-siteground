# Full CSS audit contract

This repository does not consider the active enqueue stack alone sufficient evidence of design-system cleanliness.

The design-system gate must inspect every non-minified stylesheet under the canonical theme and report:

- active, inactive and unreferenced stylesheets;
- every `:root` block and token definition outside `nvx-tokens.css`;
- undefined token references;
- duplicate selectors across active and inactive CSS;
- non-canonical `font-family` declarations outside `nvx-fonts.css`;
- hardcoded spacing declarations that bypass `--nvx-*` rhythm tokens;
- literal color declarations that bypass the canonical palette;
- icon `color`, `fill` and `stroke` declarations inconsistent with canonical roles;
- inline styles, embedded style tags and `!important` in runtime files;
- high-specificity and positional/relational selectors.

Canonical typography roles are `var(--nvx-serif)` and `var(--nvx-sans)`. Font-face declarations are owned only by `nvx-fonts.css`.

Canonical icon colors must use `currentColor` or a documented `--nvx-*` semantic token. Icons must not introduce independent brand colors.

The gate must fail for unreferenced CSS, extra token roots, undefined tokens, non-canonical font-family declarations, inconsistent icon colors, runtime `!important`, embedded style tags, and forbidden selectors.

Hardcoded spacing, literal colors, duplicate selectors and high specificity are reported as remediation inventory until their current baseline is reviewed and reduced to zero or explicitly documented exceptions.
