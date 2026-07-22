#!/usr/bin/env python3
"""Rebuild the editorial-coherence diff from master without line-ending churn."""
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
THEME = ROOT / "wp-content/themes/nuvanx-medical"
WORKFLOWS = {
    ".github/workflows/apply-editorial-coherence-once.yml",
}
NEW_FILES = {
    "wp-content/themes/nuvanx-medical/assets/css/nvx-editorial-coherence.css",
    "scripts/design-system/rebuild-editorial-diff.py",
}


def restore_existing_changed_files() -> None:
    changed = subprocess.check_output(
        ["git", "diff", "--name-only", "--diff-filter=ACM", "origin/master...HEAD"],
        text=True,
    ).splitlines()
    for relative in changed:
        if relative in WORKFLOWS or relative in NEW_FILES:
            continue
        path = ROOT / relative
        try:
            base = subprocess.check_output(["git", "show", f"origin/master:{relative}"])
        except subprocess.CalledProcessError:
            continue
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(base)
        print(f"restored={relative}")


def replace_shared_primitives() -> None:
    replacements = [
        (b"nvx-endolift-diagnosis__panel", b"nvx-editorial-panel"),
        (b"nvx-endolift-diagnosis__copy", b"nvx-editorial-split__copy"),
        (b"nvx-endolift-diagnosis__grid", b"nvx-editorial-split"),
        (b"nvx-endolift-section__inner", b"nvx-editorial-section__inner"),
        (b"nvx-endolift-body--measure", b"nvx-editorial-body--measure"),
        (b"nvx-endolift-panel-label", b"nvx-editorial-panel__label"),
        (b"nvx-endolift-panel-list", b"nvx-editorial-fact-list"),
        (b"nvx-endolaser-zone__title", b"nvx-editorial-grid-item__title"),
        (b"nvx-endolaser-zone-list", b"nvx-editorial-grid-list"),
        (b"nvx-endolaser-zone", b"nvx-editorial-grid-item"),
        (b"nvx-endolift-editorial", b"nvx-editorial-page"),
        (b"nvx-endolift-section", b"nvx-editorial-section"),
        (b"nvx-endolift-kicker", b"nvx-editorial-kicker"),
        (b"nvx-endolift-heading", b"nvx-editorial-heading"),
        (b"nvx-endolift-body", b"nvx-editorial-body"),
        (b"nvx-endolift-hero", b"nvx-editorial-hero"),
    ]
    for path in sorted((THEME / "inc").glob("*.php")):
        if path.name == "nvx-endolift-page.php":
            continue
        data = path.read_bytes()
        updated = data
        for old, new in replacements:
            updated = updated.replace(old, new)
        if updated != data:
            path.write_bytes(updated)
            print(f"migrated={path.relative_to(ROOT).as_posix()}")


def update_functions() -> None:
    path = THEME / "functions.php"
    data = path.read_bytes()
    pattern = b"\twp_enqueue_style( 'nvx-patterns', $css . 'nvx-patterns-editorial.css', array( 'nvx-components' ), nvx_asset_version( 'assets/css/nvx-patterns-editorial.css' ) );"
    coherence = b"\twp_enqueue_style( 'nvx-editorial-coherence', $css . 'nvx-editorial-coherence.css', array( 'nvx-patterns' ), nvx_asset_version( 'assets/css/nvx-editorial-coherence.css' ) );"
    newline = b"\r\n" if pattern + b"\r\n" in data else b"\n"
    if coherence not in data:
        if pattern not in data:
            raise RuntimeError("functions.php canonical patterns enqueue not found")
        data = data.replace(pattern + newline, pattern + newline + coherence + newline, 1)
    data = data.replace(
        b"wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-patterns' )",
        b"wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-editorial-coherence' )",
        1,
    )
    path.write_bytes(data)


def comment_start(source: bytes, token: bytes, offset: int = 0) -> int:
    token_at = source.find(token, offset)
    if token_at < 0:
        raise RuntimeError(f"CSS cleanup token missing: {token.decode()}")
    start = source.rfind(b"/*", offset, token_at + 1)
    if start < 0:
        raise RuntimeError(f"CSS comment start missing for: {token.decode()}")
    return start


def remove_css_block(source: bytes, start_token: bytes, end_token: bytes, newline: bytes) -> bytes:
    start = comment_start(source, start_token)
    end = comment_start(source, end_token, start + 2)
    if end <= start:
        raise RuntimeError(f"Invalid CSS cleanup range: {start_token.decode()}")
    return source[:start].rstrip(b"\r\n") + newline + newline + source[end:]


def clean_patterns() -> None:
    path = THEME / "assets/css/nvx-patterns-editorial.css"
    data = path.read_bytes()
    newline = b"\r\n" if data.count(b"\r\n") > data.count(b"\n") / 2 else b"\n"
    data = remove_css_block(data, b"Equipo page structure", b"recovery timeline", newline)
    data = remove_css_block(
        data,
        b"zone list (reuses endolift section rhythm)",
        b"Comparative table Endolift vs surgery",
        newline,
    )
    data = data.replace(
        b".nvx-nosotros-team-card .nvx-endolift-kicker",
        b".nvx-nosotros-team-card .nvx-editorial-kicker",
    )
    old_selector = (
        b".nvx-nosotros-team-card .nvx-endolaser-zone__title,"
        + newline
        + b".nvx-nosotros-team-card .nvx-endolift-body"
    )
    new_selector = (
        b".nvx-nosotros-team-card .nvx-editorial-grid-item__title,"
        + newline
        + b".nvx-nosotros-team-card .nvx-editorial-body"
    )
    data = data.replace(old_selector, new_selector)
    path.write_bytes(data)


def extend_hygiene() -> None:
    path = ROOT / "scripts/theme-hygiene/test-theme-hygiene.mjs"
    data = path.read_bytes()
    newline = b"\r\n" if data.count(b"\r\n") > data.count(b"\n") / 2 else b"\n"
    marker = b"const report = errors.length"
    block_text = """const crossedPrimitives = [
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
for (const file of runtime.filter((candidate) => /\\/inc\\/[^/]+\\.php$/i.test(candidate) && !candidate.endsWith('/inc/nvx-endolift-page.php'))) {
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
if ((functions.match(/wp_enqueue_style\\(\\s*'nvx-editorial-coherence'/g) || []).length !== 1) fail('functions.php: editorial coherence must be enqueued once');
if (!functions.includes("array( 'nvx-editorial-coherence' )")) fail('functions.php: header must depend on editorial coherence');

"""
    block = block_text.replace("\n", newline.decode()).encode()
    if block not in data:
        if marker not in data:
            raise RuntimeError("theme hygiene report marker not found")
        data = data.replace(marker, block + marker, 1)
    path.write_bytes(data)


if __name__ == "__main__":
    restore_existing_changed_files()
    replace_shared_primitives()
    update_functions()
    clean_patterns()
    extend_hygiene()
