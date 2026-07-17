#!/usr/bin/env python3
"""Contract tests for the issue #45 inventory summarizer."""

from __future__ import annotations

import subprocess
import tempfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "scripts/security/issue-45-summarize-inventory.py"


def run_case(manifest: list[str], reasons: list[str], current: list[str]) -> dict[str, str]:
    with tempfile.TemporaryDirectory() as raw_tmp:
        tmp = Path(raw_tmp)
        manifest_path = tmp / "manifest.txt"
        reasons_path = tmp / "reasons.tsv"
        current_path = tmp / "current.txt"
        output = tmp / "out"
        manifest_path.write_text("".join(f"{item}\n" for item in manifest), encoding="utf-8")
        reasons_path.write_text("".join(f"{item}\n" for item in reasons), encoding="utf-8")
        current_path.write_text("".join(f"{item}\n" for item in current), encoding="utf-8")
        subprocess.run(
            [
                "python3",
                str(SCRIPT),
                "--manifest",
                str(manifest_path),
                "--reasons",
                str(reasons_path),
                "--current-tree",
                str(current_path),
                "--output-dir",
                str(output),
            ],
            check=True,
            cwd=ROOT,
        )
        summary: dict[str, str] = {}
        for line in (output / "approval-summary.txt").read_text(encoding="utf-8").splitlines():
            key, value = line.split("=", 1)
            summary[key] = value
        return summary


BASE_MANIFEST = [
    "wp-config.php",
    "source_site_wpconfig.txt",
    "wp-content/backups-dup-lite/package/installer.php",
    "wp-admin/admin.php",
    "wp-includes/version.php",
    "wp-content/plugins/example/plugin.php",
    "wp-content/uploads/example.jpg",
    "wp-content/themes/nuvanx-medical-wpvibe-draft/style.css",
]
BASE_REASONS = [f"{path}\ttest" for path in BASE_MANIFEST]

safe = run_case(
    BASE_MANIFEST + ["wp-content/themes/nuvanx-medical/php_errorlog"],
    BASE_REASONS + ["wp-content/themes/nuvanx-medical/php_errorlog\tgitleaks-redacted-finding"],
    ["wp-content/themes/nuvanx-medical/functions.php"],
)
assert safe["approval_ready"] == "true", safe
assert safe["canonical_theme_disposable_runtime"] == "1", safe
assert safe["canonical_theme_protected_intersection"] == "0", safe
assert safe["optional_observations_present"] == "0", safe

protected = run_case(
    BASE_MANIFEST + ["wp-content/themes/nuvanx-medical/functions.php"],
    BASE_REASONS + ["wp-content/themes/nuvanx-medical/functions.php\tgitleaks-redacted-finding"],
    [],
)
assert protected["approval_ready"] == "false", protected
assert protected["canonical_theme_protected_intersection"] == "1", protected

current_intersection = run_case(BASE_MANIFEST, BASE_REASONS, ["wp-config.php"])
assert current_intersection["approval_ready"] == "false", current_intersection
assert current_intersection["current_tree_intersection"] == "1", current_intersection

missing_required = run_case(
    [path for path in BASE_MANIFEST if not path.startswith("wp-content/uploads/")],
    [line for line in BASE_REASONS if not line.startswith("wp-content/uploads/")],
    [],
)
assert missing_required["approval_ready"] == "false", missing_required
assert missing_required["mandatory_checks_present"] == "7", missing_required

print("Issue 45 inventory summarizer tests passed.")
