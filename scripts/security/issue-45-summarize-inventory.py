#!/usr/bin/env python3
"""Summarize issue #45 path-only inventory without exposing secret values."""

from __future__ import annotations

import argparse
from collections import Counter
from pathlib import Path


CANONICAL_THEME_PREFIX = "wp-content/themes/nuvanx-medical/"
DISPOSABLE_CANONICAL_RUNTIME_PATHS = {
    "wp-content/themes/nuvanx-medical/php_errorlog",
}


def read_lines(path: Path) -> list[str]:
    return [
        line.strip()
        for line in path.read_text(encoding="utf-8", errors="replace").splitlines()
        if line.strip()
    ]


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", required=True, type=Path)
    parser.add_argument("--reasons", required=True, type=Path)
    parser.add_argument("--current-tree", required=True, type=Path)
    parser.add_argument("--output-dir", required=True, type=Path)
    args = parser.parse_args()

    output = args.output_dir
    output.mkdir(parents=True, exist_ok=True)

    manifest = set(read_lines(args.manifest))
    current = set(read_lines(args.current_tree))
    intersection = sorted(manifest & current)
    canonical = sorted(path for path in manifest if path.startswith(CANONICAL_THEME_PREFIX))
    canonical_disposable = sorted(path for path in canonical if path in DISPOSABLE_CANONICAL_RUNTIME_PATHS)
    canonical_protected = sorted(path for path in canonical if path not in DISPOSABLE_CANONICAL_RUNTIME_PATHS)

    reason_counts: Counter[str] = Counter()
    malformed = 0
    for line in read_lines(args.reasons):
        if "\t" not in line:
            malformed += 1
            continue
        _path, raw_reasons = line.split("\t", 1)
        for reason in raw_reasons.split(","):
            if reason:
                reason_counts[reason] += 1

    required_checks = {
        "wp-config": any(path.rsplit("/", 1)[-1].lower() == "wp-config.php" for path in manifest),
        "source-site-wpconfig": any("source_site_wpconfig" in path.lower() for path in manifest),
        "duplicator-backup": any(path.startswith("wp-content/backups-dup-lite/") for path in manifest),
        "wordpress-admin": any(path.startswith("wp-admin/") for path in manifest),
        "wordpress-includes": any(path.startswith("wp-includes/") for path in manifest),
        "plugins": any(path.startswith("wp-content/plugins/") for path in manifest),
        "uploads": any(path.startswith("wp-content/uploads/") for path in manifest),
        "legacy-theme": any(path.startswith("wp-content/themes/nuvanx-medical-wpvibe-draft/") for path in manifest),
    }
    optional_observations = {
        "database-dump": any(path.lower().endswith((".sql", ".sql.gz", ".sql.bz2", ".daf")) for path in manifest),
        "archive": any(path.lower().endswith((".zip", ".tar", ".tar.gz", ".tgz")) for path in manifest),
    }

    (output / "current-tree-intersection.txt").write_text(
        "".join(f"{path}\n" for path in intersection), encoding="utf-8"
    )
    (output / "canonical-theme-intersection.txt").write_text(
        "".join(f"{path}\n" for path in canonical), encoding="utf-8"
    )
    (output / "canonical-theme-disposable-runtime.txt").write_text(
        "".join(f"{path}\n" for path in canonical_disposable), encoding="utf-8"
    )
    (output / "canonical-theme-protected-intersection.txt").write_text(
        "".join(f"{path}\n" for path in canonical_protected), encoding="utf-8"
    )
    (output / "category-counts.tsv").write_text(
        "".join(f"{reason}\t{count}\n" for reason, count in sorted(reason_counts.items())),
        encoding="utf-8",
    )
    (output / "mandatory-coverage.tsv").write_text(
        "".join(
            f"{name}\t{'present' if present else 'missing'}\n"
            for name, present in sorted(required_checks.items())
        ),
        encoding="utf-8",
    )
    (output / "optional-observations.tsv").write_text(
        "".join(
            f"{name}\t{'present' if present else 'not-observed'}\n"
            for name, present in sorted(optional_observations.items())
        ),
        encoding="utf-8",
    )

    safe = (
        not intersection
        and not canonical_protected
        and all(required_checks.values())
        and malformed == 0
    )
    summary = [
        f"candidate_paths={len(manifest)}",
        f"current_tree_intersection={len(intersection)}",
        f"canonical_theme_intersection={len(canonical)}",
        f"canonical_theme_disposable_runtime={len(canonical_disposable)}",
        f"canonical_theme_protected_intersection={len(canonical_protected)}",
        f"reason_categories={len(reason_counts)}",
        f"malformed_reason_lines={malformed}",
        f"mandatory_checks_present={sum(required_checks.values())}",
        f"mandatory_checks_total={len(required_checks)}",
        f"optional_observations_present={sum(optional_observations.values())}",
        f"optional_observations_total={len(optional_observations)}",
        f"approval_ready={'true' if safe else 'false'}",
    ]
    (output / "approval-summary.txt").write_text("\n".join(summary) + "\n", encoding="utf-8")

    print("\n".join(summary))
    # Diagnostic publication must complete even when approval_ready=false.
    # The purge workflow performs the actual blocking check before rewriting.
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
