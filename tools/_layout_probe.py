#!/usr/bin/env python3
from __future__ import annotations
import re
import urllib.request

UA = {"User-Agent": "nvx-layout-probe", "Cache-Control": "no-cache"}


def fetch(url: str) -> str:
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=45) as r:
        return r.read().decode("utf-8", "replace")


def snippet_around(html: str, needle: str, before: int = 200, after: int = 500) -> list[str]:
    out = []
    start = 0
    while True:
        i = html.find(needle, start)
        if i < 0:
            break
        out.append(html[max(0, i - before) : i + after])
        start = i + len(needle)
        if len(out) >= 6:
            break
    return out


for url in (
    "https://staging2.nuvanx.com/clinicas-de-medicina-estetica-nuvanx/",
    "https://staging2.nuvanx.com/tratamientos/",
):
    html = fetch(url)
    print("=" * 72)
    print(url, "chars", len(html))
    print("actions count", len(re.findall(r"nvx-brand-actions", html)))
    print("button count", len(re.findall(r"nvx-button", html)))
    print("grid--logos", html.count("nvx-brand-grid--logos"))
    print("collaborators", html.count("nvx-brand-collaborators"))
    print("brand-card__media", html.count("nvx-brand-card__media"))
    print("brand-readable", html.count("nvx-brand-readable"))
    print("section--cta", html.count("nvx-brand-section--cta"))

    print("\n-- actions blocks --")
    for m in re.finditer(
        r'<div[^>]*class="[^"]*nvx-brand-actions[^"]*"[^>]*>[\s\S]{0,800}?</div>',
        html,
        re.I,
    ):
        block = re.sub(r"\s+", " ", m.group(0))
        print(block[:500])
        print("---")

    print("\n-- logo-ish cards --")
    for m in re.finditer(
        r'<article[^>]*class="[^"]*nvx-brand-card[^"]*"[^>]*>[\s\S]{0,900}?</article>',
        html,
        re.I,
    ):
        block = m.group(0)
        if "img" in block.lower() and (
            "logo" in block.lower()
            or "collabor" in block.lower()
            or re.search(r"nvx-brand-card__media", block)
        ):
            # only print if looks like logo (short titles / brand names)
            text = re.sub(r"<[^>]+>", " ", block)
            text = re.sub(r"\s+", " ", text).strip()[:120]
            classes = re.search(r'class="([^"]+)"', block)
            print("CARD", classes.group(1) if classes else "?", "|", text)
            imgs = re.findall(r'<img[^>]+>', block, re.I)
            for im in imgs[:2]:
                print("  ", im[:220])

    # parent of logos section
    for s in snippet_around(html, "nvx-brand-grid--logos", 120, 400):
        print("\nLOGOS CONTEXT:", re.sub(r"\s+", " ", s)[:450])
    for s in snippet_around(html, "nvx-brand-collaborators", 120, 400):
        print("\nCOLLAB CONTEXT:", re.sub(r"\s+", " ", s)[:450])
