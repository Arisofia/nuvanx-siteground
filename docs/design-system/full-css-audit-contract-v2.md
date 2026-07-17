# Repository-wide CSS audit

The CSS gate must inspect every non-minified stylesheet in the canonical theme, not only the enqueue stack. It must identify inactive and unreferenced CSS, duplicate selectors, extra token roots, undefined tokens, non-canonical typography, hardcoded spacing, literal colors, inconsistent icon colors, inline styles, embedded style tags, `!important`, and unsafe/high-specificity selectors.

Canonical typography roles are `var(--nvx-serif)` and `var(--nvx-sans)`. Font-face declarations are owned only by `nvx-fonts.css`. Icons must inherit `currentColor` or use a documented semantic `--nvx-*` token.

The gate fails for orphan stylesheets, extra token roots, undefined tokens, non-canonical font families, inconsistent icon colors, runtime `!important`, embedded style tags, and forbidden positional or relational selectors. Remaining spacing, color, specificity and duplication findings are emitted as a remediation inventory until reduced to zero or explicitly approved.
