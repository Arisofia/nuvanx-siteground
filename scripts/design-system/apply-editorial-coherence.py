#!/usr/bin/env python3
"""One-time migration from treatment-prefixed layout primitives to neutral editorial primitives."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
THEME = ROOT / "wp-content/themes/nuvanx-medical"

REPLACEMENTS = [
    ("nvx-endolift-diagnosis__panel", "nvx-editorial-panel"),
    ("nvx-endolift-diagnosis__copy", "nvx-editorial-split__copy"),
    ("nvx-endolift-diagnosis__grid", "nvx-editorial-split"),
    ("nvx-endolift-section__inner", "nvx-editorial-section__inner"),
    ("nvx-endolift-body--measure", "nvx-editorial-body--measure"),
    ("nvx-endolift-panel-label", "nvx-editorial-panel__label"),
    ("nvx-endolift-panel-list", "nvx-editorial-fact-list"),
    ("nvx-endolaser-zone__title", "nvx-editorial-grid-item__title"),
    ("nvx-endolaser-zone-list", "nvx-editorial-grid-list"),
    ("nvx-endolaser-zone", "nvx-editorial-grid-item"),
    ("nvx-endolift-editorial", "nvx-editorial-page"),
    ("nvx-endolift-section", "nvx-editorial-section"),
    ("nvx-endolift-kicker", "nvx-editorial-kicker"),
    ("nvx-endolift-heading", "nvx-editorial-heading"),
    ("nvx-endolift-body", "nvx-editorial-body"),
    ("nvx-endolift-hero", "nvx-editorial-hero"),
]


def replace_shared_primitives() -> None:
    for path in sorted((THEME / "inc").glob("*.php")):
        if path.name == "nvx-endolift-page.php":
            continue
        text = path.read_text(encoding="utf-8")
        updated = text
        for old, new in REPLACEMENTS:
            updated = updated.replace(old, new)
        if updated != text:
            path.write_text(updated, encoding="utf-8", newline="\n")


def write_editorial_css() -> None:
    css = r'''/* NUVANX shared editorial primitives.
   Institutional and non-Endolift pages use this neutral layer.
   Page modules own content; this file owns rhythm, grids and responsive lists. */

.nvx-editorial-page {
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: var(--nvx-light);
}

.nvx-editorial-section {
  padding-block: clamp(var(--nvx-space-5), 7vw, var(--nvx-space-8));
  border-bottom: 1px solid var(--nvx-color-line);
}

.nvx-editorial-section:last-child {
  border-bottom: 0;
}

.nvx-editorial-section__inner {
  width: min(calc(100% - (var(--nvx-space-3) * 2)), var(--nvx-measure-wide, 72rem));
  margin-inline: auto;
}

.nvx-editorial-kicker {
  margin: 0 0 var(--nvx-margin-kicker);
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-kicker);
  font-weight: var(--nvx-fw-semibold, 600);
  line-height: var(--nvx-lh-kicker);
  letter-spacing: var(--nvx-track-kicker);
  text-transform: uppercase;
  color: var(--nvx-accent-muted);
}

.nvx-editorial-heading {
  max-width: 24ch;
  margin: 0 0 var(--nvx-margin-h2);
  font-family: var(--nvx-serif);
  font-size: var(--nvx-type-h2);
  font-weight: 400;
  line-height: var(--nvx-lh-h2);
  letter-spacing: var(--nvx-track-h2);
  color: var(--nvx-ink);
  text-wrap: balance;
}

.nvx-editorial-body {
  max-width: var(--nvx-measure, 68ch);
  margin: 0 0 var(--nvx-margin-body);
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-body);
  font-weight: 400;
  line-height: var(--nvx-lh-body);
  color: var(--nvx-text-body);
}

.nvx-editorial-body:last-child {
  margin-bottom: 0;
}

.nvx-editorial-body--measure {
  max-width: var(--nvx-measure, 68ch);
}

.nvx-editorial-split {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: clamp(var(--nvx-space-4), 5vw, var(--nvx-space-7));
  align-items: start;
}

.nvx-editorial-split__copy {
  min-width: 0;
}

.nvx-editorial-panel {
  padding: clamp(var(--nvx-space-3), 3vw, var(--nvx-space-5));
  border: 1px solid var(--nvx-color-line);
  border-radius: var(--nvx-radius-card);
  background: var(--nvx-surface-base);
}

.nvx-editorial-panel__label {
  margin: 0 0 var(--nvx-space-3);
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-kicker);
  font-weight: var(--nvx-fw-semibold, 600);
  line-height: var(--nvx-lh-kicker);
  letter-spacing: var(--nvx-track-kicker);
  text-transform: uppercase;
  color: var(--nvx-accent-muted);
}

.nvx-editorial-fact-list,
.nvx-editorial-grid-list {
  list-style: none;
  margin: var(--nvx-space-4) 0 0;
  padding: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: var(--nvx-space-3);
}

.nvx-editorial-fact-list li,
.nvx-editorial-grid-item {
  min-width: 0;
  margin: 0;
  padding: var(--nvx-space-3) 0 0;
  border-top: 1px solid var(--nvx-color-line);
}

.nvx-editorial-fact-list li {
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-body);
  line-height: var(--nvx-lh-body);
  color: var(--nvx-text-body);
}

.nvx-editorial-fact-list strong {
  color: var(--nvx-ink);
  font-weight: var(--nvx-fw-semibold, 600);
}

.nvx-editorial-grid-item__title {
  margin: 0 0 var(--nvx-space-2);
  font-family: var(--nvx-serif);
  font-size: var(--nvx-type-h3);
  font-weight: 400;
  line-height: var(--nvx-lh-h3);
  letter-spacing: var(--nvx-track-h3);
  color: var(--nvx-ink);
  text-wrap: balance;
}

.nvx-editorial-grid-item .nvx-editorial-body {
  margin-bottom: 0;
}

.nvx-equipo-editorial > .nvx-equipo-director,
.nvx-equipo-editorial > .nvx-equipo-ivon,
.nvx-equipo-editorial > .nvx-equipo-fabio {
  border-bottom: 1px solid var(--nvx-color-line);
}

.nvx-equipo-director,
.nvx-equipo-ivon,
.nvx-equipo-fabio {
  display: flex;
  flex-direction: column;
}

.nvx-equipo-ivon {
  background: var(--nvx-surface-base);
}

.nvx-equipo-profile-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: clamp(var(--nvx-space-4), 5vw, var(--nvx-space-7));
  align-items: start;
}

.nvx-equipo-profile-layout__copy {
  min-width: 0;
  max-width: var(--nvx-measure, 68ch);
}

.nvx-equipo-portrait {
  width: min(100%, 20rem);
  margin: 0;
  aspect-ratio: 5 / 6;
  overflow: hidden;
  border-radius: var(--nvx-radius-image);
  background: var(--nvx-surface-base);
}

.nvx-equipo-portrait img,
.nvx-equipo-portrait .nvx-media--doctor {
  display: block;
  width: 100%;
  height: 100%;
  max-width: none;
  max-height: none;
  object-fit: cover;
  object-position: center top;
  border-radius: 0;
}

.nvx-equipo-staff-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 16rem), 1fr));
  gap: clamp(var(--nvx-space-3), 3vw, var(--nvx-space-5));
  margin-top: var(--nvx-space-5);
}

.nvx-equipo-staff-grid .nvx-brand-card,
.nvx-equipo-staff-grid .nvx-brand-card--team {
  grid-column: auto;
  min-width: 0;
  height: 100%;
}

.nvx-equipo-blockquote {
  max-width: var(--nvx-measure, 68ch);
  margin: 0;
  padding: clamp(var(--nvx-space-3), 4vw, var(--nvx-space-5));
  border-left: calc(var(--nvx-border-hairline) * 3) solid var(--nvx-accent-muted);
  background: var(--nvx-surface-base);
}

.nvx-equipo-blockquote p {
  margin: 0 0 var(--nvx-space-3);
  font-family: var(--nvx-serif);
  font-size: var(--nvx-type-h3);
  font-weight: 400;
  line-height: var(--nvx-lh-h3);
  color: var(--nvx-ink);
}

.nvx-equipo-blockquote footer {
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-small, 0.875rem);
  color: var(--nvx-text-muted);
}

.screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: calc(var(--nvx-border-hairline) * -1);
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

@media (min-width: 48rem) {
  .nvx-editorial-grid-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: clamp(var(--nvx-space-4), 5vw, var(--nvx-space-7));
    row-gap: var(--nvx-space-5);
  }

  .nvx-editorial-fact-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: var(--nvx-space-4);
  }

  .nvx-editorial-split {
    grid-template-columns: minmax(0, 1.45fr) minmax(16rem, 0.75fr);
  }

  .nvx-equipo-profile-layout {
    grid-template-columns: minmax(14rem, 20rem) minmax(0, 1fr);
  }
}
'''
    (THEME / "assets/css/nvx-editorial-coherence.css").write_text(css, encoding="utf-8", newline="\n")


def update_functions() -> None:
    path = THEME / "functions.php"
    text = path.read_text(encoding="utf-8")
    pattern_line = "\twp_enqueue_style( 'nvx-patterns', $css . 'nvx-patterns-editorial.css', array( 'nvx-components' ), nvx_asset_version( 'assets/css/nvx-patterns-editorial.css' ) );"
    coherence_line = "\twp_enqueue_style( 'nvx-editorial-coherence', $css . 'nvx-editorial-coherence.css', array( 'nvx-patterns' ), nvx_asset_version( 'assets/css/nvx-editorial-coherence.css' ) );"
    if coherence_line not in text:
        if pattern_line not in text:
            raise SystemExit("functions.php canonical patterns enqueue not found")
        text = text.replace(pattern_line, pattern_line + "\n" + coherence_line, 1)
    text = text.replace(
        "wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-patterns' )",
        "wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-editorial-coherence' )",
        1,
    )
    path.write_text(text, encoding="utf-8", newline="\n")


def clean_patterns() -> None:
    path = THEME / "assets/css/nvx-patterns-editorial.css"
    text = path.read_text(encoding="utf-8")
    blocks = [
        ("/* Equipo page structure — aligned profiles + staff */", "/* CO₂ recovery timeline */"),
        ("/* Endoláser corporal — zone list (reuses endolift section rhythm) */", "/* Comparative table Endolift vs surgery */"),
    ]
    for start, end in blocks:
        if start not in text or end not in text:
            raise SystemExit(f"CSS cleanup marker missing: {start}")
        before, remainder = text.split(start, 1)
        _, after = remainder.split(end, 1)
        text = before.rstrip() + "\n\n" + end + after
    text = text.replace(
        ".nvx-nosotros-team-card .nvx-endolift-kicker",
        ".nvx-nosotros-team-card .nvx-editorial-kicker",
    )
    text = text.replace(
        ".nvx-nosotros-team-card .nvx-endolaser-zone__title,\n.nvx-nosotros-team-card .nvx-endolift-body",
        ".nvx-nosotros-team-card .nvx-editorial-grid-item__title,\n.nvx-nosotros-team-card .nvx-editorial-body",
    )
    path.write_text(text, encoding="utf-8", newline="\n")


def extend_hygiene_gate() -> None:
    path = ROOT / "scripts/theme-hygiene/test-theme-hygiene.mjs"
    text = path.read_text(encoding="utf-8")
    marker = "const report = errors.length"
    block = r'''const crossedPrimitives = [
  'nvx-endolift-section',
  'nvx-endolift-kicker',
  'nvx-endolift-heading',
  'nvx-endolift-body',
  'nvx-endolift-diagnosis__',
  'nvx-endolift-panel-',
  'nvx-endolift-editorial',
  'nvx-endolift-hero',
  'nvx-endolaser-zone',
];
for (const file of runtime.filter((candidate) => /\/inc\/[^/]+\.php$/i.test(candidate) && !candidate.endsWith('/inc/nvx-endolift-page.php'))) {
  const content = fs.readFileSync(file, 'utf8');
  for (const primitive of crossedPrimitives) {
    if (content.includes(primitive)) fail(`${rel(file)}: crossed treatment design primitive ${primitive}`);
  }
}
const editorialCoherence = read('assets/css/nvx-editorial-coherence.css');
for (const required of [
  '.nvx-editorial-section',
  '.nvx-editorial-grid-list',
  '.nvx-editorial-fact-list',
  '.nvx-equipo-profile-layout',
  '@media (min-width: 48rem)',
]) if (!editorialCoherence.includes(required)) fail(`editorial coherence: missing ${required}`);
if ((functions.match(/wp_enqueue_style\(\s*'nvx-editorial-coherence'/g) || []).length !== 1) fail('functions.php: editorial coherence must be enqueued once');
if (!functions.includes("array( 'nvx-editorial-coherence' )")) fail('functions.php: header must depend on editorial coherence');

'''
    if block not in text:
        if marker not in text:
            raise SystemExit("theme hygiene report marker not found")
        text = text.replace(marker, block + marker, 1)
    path.write_text(text, encoding="utf-8", newline="\n")


if __name__ == "__main__":
    replace_shared_primitives()
    write_editorial_css()
    update_functions()
    clean_patterns()
    extend_hygiene_gate()
