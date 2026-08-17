#!/usr/bin/env python3
"""Read-only redacted scanner for private forensic source exports.

The scanner never writes source fragments or matched values. It emits only path,
line, classification, match byte length and SHA-256 of the matched text.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import re
from collections import Counter
from pathlib import Path

POSSIBLE_SECRET = re.compile(
    r"(?i)(?:password|secret|token|access_token|refresh_token|client_secret|api_key|authorization|bearer|private_key)"
)
SECRET_LITERAL = re.compile(
    r"(?i)(?:password|secret|token|access_token|refresh_token|client_secret|api_key|private_key)"
    r"\s*(?:=>|:|=|,)\s*['\"][^'\"]{8,}['\"]"
)
AUTH_LITERAL = re.compile(r"(?i)(?:authorization\s*[:=]\s*['\"]?bearer\s+|bearer\s+)[A-Za-z0-9._~+\-/=]{12,}")
ENVIRONMENT = re.compile(r"(?:staging2\.nuvanx\.com|nuvanx\.com|/home/customer/|/home/ubuntu/)", re.I)
STABLE = re.compile(r"(?:\bGTM-[A-Z0-9-]+\b|\bG-[A-Z0-9]{6,}\b|\bAW-[0-9]+(?:/[A-Za-z0-9_-]+)?\b|\bact_[0-9]+\b|\bportal(?:[_-]?id)?\s*[:=>]+\s*['\"]?[0-9]+)", re.I)
CONTENT = re.compile(r"(?i)(?:\b(?:page|post)[_-]?id\s*(?:=>|:|=)\s*\d+|\bis_page\s*\(\s*\d+|\bget_post\s*\(\s*\d+|\bID\s*(?:=>|:|=)\s*\d+)")
BUSINESS = re.compile(r"(?i)(?:hubspot|klaviyo|complianz|joinchat|google\s*(?:ads|analytics|tag|site kit)|meta\s*(?:pixel|event|ads)?|consent)")
ACCIDENTAL = re.compile(r"(?i)(?:\bTODO\b|\bFIXME\b|\bHACK\b|\bTEMP(?:ORARY)?\b|\bREMOVE\s+ME\b)")


def add(rows: list[dict], path: str, line: int, category: str, value: str) -> None:
    rows.append({
        "file": path,
        "line": line,
        "category": category,
        "match_length": len(value.encode("utf-8")),
        "sha256_match": hashlib.sha256(value.encode("utf-8")).hexdigest(),
    })


def scan(root: Path) -> dict:
    rows: list[dict] = []
    files = []
    for p in sorted(root.rglob("*")):
        if not p.is_file():
            continue
        try:
            content = p.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue
        rel = p.relative_to(root).as_posix()
        files.append(rel)
        for line_no, source_line in enumerate(content.splitlines(), 1):
            secret_matches = list(SECRET_LITERAL.finditer(source_line))
            auth_matches = list(AUTH_LITERAL.finditer(source_line))
            for match in secret_matches:
                add(rows, rel, line_no, "SECRET", match.group(0))
            for match in auth_matches:
                add(rows, rel, line_no, "SECRET", match.group(0))
            # Skip keywords already covered by a structured secret/auth literal on this line.
            secret_spans = [(item.start(), item.end()) for item in (*secret_matches, *auth_matches)]
            for match in POSSIBLE_SECRET.finditer(source_line):
                if any(start <= match.start() and match.end() <= end for start, end in secret_spans):
                    continue
                add(rows, rel, line_no, "BUSINESS_CONFIG", match.group(0))
            for category, pattern in (
                ("ENVIRONMENT_SPECIFIC", ENVIRONMENT),
                ("STABLE_PUBLIC_IDENTIFIER", STABLE),
                ("CONTENT_IDENTIFIER", CONTENT),
                ("BUSINESS_CONFIG", BUSINESS),
                ("ACCIDENTAL_HARDCODE", ACCIDENTAL),
            ):
                for match in pattern.finditer(source_line):
                    add(rows, rel, line_no, category, match.group(0))
    unique = {(r["file"], r["line"], r["category"], r["sha256_match"]): r for r in rows}
    ordered = sorted(unique.values(), key=lambda r: (r["file"], r["line"], r["category"], r["sha256_match"]))
    return {
        "schema": "nuvanx-forensic-redacted-source-scan/v1",
        "redaction": "Matched values and source fragments are never emitted.",
        "files_scanned": files,
        "category_counts": dict(sorted(Counter(r["category"] for r in ordered).items())),
        "findings": ordered,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("source_dir", type=Path)
    parser.add_argument("output_json", type=Path)
    args = parser.parse_args()
    report = scan(args.source_dir.resolve())
    args.output_json.parent.mkdir(parents=True, exist_ok=True)
    args.output_json.write_text(json.dumps(report, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(json.dumps({"files": len(report["files_scanned"]), "category_counts": report["category_counts"]}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
