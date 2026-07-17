#!/usr/bin/env python3
"""Build an exact, path-only purge manifest for security incident #45.

The script never writes secret values. It classifies paths from
`git rev-list --objects --all` and optionally adds paths reported by a redacted
Gitleaks JSON report. The resulting manifest contains exact Git paths, one per
line, and must be reviewed before any history rewrite.
"""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Iterable

ROOT_WORDPRESS_FILES = {
    "index.php",
    "xmlrpc.php",
    "license.txt",
    "readme.html",
    ".htaccess",
    "php.ini",
    ".user.ini",
}

PREFIX_RULES: tuple[tuple[str, str], ...] = (
    ("wp-admin/", "wordpress-core"),
    ("wp-includes/", "wordpress-core"),
    ("wp-content/uploads/", "uploads"),
    ("wp-content/cache/", "runtime-cache"),
    ("wp-content/upgrade/", "runtime-upgrade"),
    ("wp-content/backup-db/", "database-backup"),
    ("wp-content/ai1wm-backups/", "site-backup"),
    ("wp-content/updraft/", "site-backup"),
    ("wp-content/backups-dup-lite/", "duplicator-backup"),
    ("wp-content/backups-nuvanx/", "site-backup"),
    ("wp-content/plugins/", "redistributed-plugin"),
    ("qa/screenshots/", "qa-capture"),
    ("qa/results/", "qa-result"),
    ("artifacts/audit-results/", "audit-artifact"),
    ("artifacts/content/", "content-artifact"),
    ("artifacts/visual-validation-", "visual-artifact"),
    ("artifacts/staging2-", "staging-artifact"),
    (".tmp", "temporary-artifact"),
    (".well-known/", "environment-verification"),
    ("autoconfig/", "environment-configuration"),
)

SENSITIVE_BASENAME = re.compile(
    r"^(?:wp-config\.php|source_site_wpconfig.*|\.env(?:\..*)?|"
    r".*installer.*\.php|.*installer.*\.log|.*installer-log.*|"
    r".*bootlog.*|php_errorlog|debug\.log)$",
    re.IGNORECASE,
)

ARCHIVE_OR_DUMP = re.compile(
    r"\.(?:daf|sql|sql\.gz|sql\.bz2|zip|tar|tar\.gz|tgz)$",
    re.IGNORECASE,
)

CREDENTIAL_EXPORT = re.compile(
    r"(?:credential|secret|password|access[-_]?key|database[-_]?export|db[-_]?export)",
    re.IGNORECASE,
)

TEXT_EXPORT = re.compile(r"\.(?:txt|json|csv|ya?ml|ini|conf|config)$", re.IGNORECASE)


def iter_object_paths(objects_file: Path) -> Iterable[str]:
    for raw_line in objects_file.read_text(encoding="utf-8", errors="replace").splitlines():
        if " " not in raw_line:
            continue
        _object_id, path = raw_line.split(" ", 1)
        path = path.strip()
        if path:
            yield path


def classify(path: str) -> str | None:
    if path in ROOT_WORDPRESS_FILES or re.fullmatch(r"wp-[^/]+\.php", path):
        return "wordpress-root"

    for prefix, reason in PREFIX_RULES:
        if path.startswith(prefix):
            return reason

    if path.startswith("wp-content/backups-"):
        return "site-backup"

    if path.startswith("wp-content/themes/"):
        parts = path.split("/", 3)
        if len(parts) >= 3 and parts[2] != "nuvanx-medical":
            return "non-canonical-theme"

    basename = path.rsplit("/", 1)[-1]
    if SENSITIVE_BASENAME.match(basename):
        return "secret-or-installer"

    if ARCHIVE_OR_DUMP.search(path):
        return "archive-or-database-dump"

    if CREDENTIAL_EXPORT.search(path) and TEXT_EXPORT.search(path):
        return "possible-credential-export"

    return None


def gitleaks_paths(report_path: Path | None) -> set[str]:
    if report_path is None or not report_path.exists() or report_path.stat().st_size == 0:
        return set()
    try:
        data = json.loads(report_path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return set()

    if not isinstance(data, list):
        return set()

    paths: set[str] = set()
    for finding in data:
        if not isinstance(finding, dict):
            continue
        candidate = finding.get("File") or finding.get("file")
        if isinstance(candidate, str) and candidate.strip():
            paths.add(candidate.strip().lstrip("./"))
    return paths


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--objects", required=True, type=Path)
    parser.add_argument("--manifest", required=True, type=Path)
    parser.add_argument("--reasons", required=True, type=Path)
    parser.add_argument("--gitleaks-report", type=Path)
    args = parser.parse_args()

    reasons: dict[str, set[str]] = {}
    for path in iter_object_paths(args.objects):
        reason = classify(path)
        if reason:
            reasons.setdefault(path, set()).add(reason)

    for path in gitleaks_paths(args.gitleaks_report):
        reasons.setdefault(path, set()).add("gitleaks-redacted-finding")

    args.manifest.parent.mkdir(parents=True, exist_ok=True)
    args.reasons.parent.mkdir(parents=True, exist_ok=True)

    sorted_paths = sorted(reasons)
    args.manifest.write_text(
        "".join(f"{path}\n" for path in sorted_paths),
        encoding="utf-8",
    )
    args.reasons.write_text(
        "".join(f"{path}\t{','.join(sorted(reasons[path]))}\n" for path in sorted_paths),
        encoding="utf-8",
    )

    print(f"candidate_paths={len(sorted_paths)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
