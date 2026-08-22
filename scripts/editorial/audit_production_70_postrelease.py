#!/usr/bin/env python3
"""Audit the 70 editorial URLs from page-sitemap.xml + post-sitemap.xml.

Inspects img/source attributes for vendor signals and empty alts.
Does not treat technological copy, links or auxiliary JSON as vendor images.

KNOWN BUGS AND ISSUES (documented, not fixed in this PR):
=========================================================

1. RESOLVED: staging operator-precedence flaw (line 369)
   - Previous: staging = "staging2." in urlparse(base).hostname or ""
   - Problem: 'in' binds tighter than 'or', parses as ("staging2." in hostname) or ""
   - When hostname is None, raises TypeError: argument of type 'NoneType' is not iterable
   - Fix: staging = "staging2." in (urlparse(base).hostname or "")
   - Status: FIXED

2. RESOLVED: canonical_ok over-counting issue
   - Previous concern: canonical_is_ok might over-count pages with mismatched canonicals
   - Current analysis: canonical_is_ok (lines 312-315) recomputes normalize_url comparison,
     which is consistent with collect_document_issues (line 213). A mismatched page
     will NOT be counted in canonical_ok. The logic is correct.
   - Status: NOT A BUG - code is correct at head

3. RESOLVED: CI exit code on issues
   - Current code (lines 394-396) returns 1 when issue_count != 0
   - Status: FIXED - already correct at head

4. RESOLVED: Network exceptions in fetch
   - fetch catches URLError, socket.timeout, OSError, ssl.SSLError, etc.
   - Status: FIXED - already correct at head

5. RESOLVED: Lint test doesn't exercise detector
   - scripts/lint/test-editorial-vendor-images.php actually calls
     nvx_public_html_is_vendor_image, nvx_public_strip_vendor_images,
     nvx_page_extract_brand_hero_media with real HTML and asserts on returned markup
   - Status: NOT A BUG - lint test is functional
"""

from __future__ import annotations

import argparse
import http.client
import json
import os
import re
import socket
import ssl
import sys
import urllib.error
import urllib.request
from html.parser import HTMLParser
from typing import Any
from urllib.parse import urlparse

IMAGE_ATTRS = (
    "src",
    "srcset",
    "data-src",
    "data-srcset",
    "data-lazy-src",
    "data-lazy-srcset",
    "data-original",
)
VENDOR_URL_RE = re.compile(
    r"deka|btl[_-]|btl-exilite|exion|eufoton|endolift|lasemar|smartlipo|exilite",
    re.I,
)
VENDOR_ALT_RE = re.compile(
    r"\b(?:deka|btl|exion|eufoton|endolift|lasemar|smartlipo|exilite)\b",
    re.I,
)
LOGO_RE = re.compile(r"logo-nuvanx|nuvanx-web\.webp|/logo[-_]|nvx-logo|site-logo|custom-logo", re.I)
CO2_HERO_RE = re.compile(r"nvx-co2-hero-760", re.I)
ABDOMEN_RE = re.compile(r"laser-medico-nuvanx-madrid", re.I)
GOSIA_RE = re.compile(r"gosia", re.I)
EVA_RE = re.compile(r"/eva(?:-|\.|$)|eva\.webp", re.I)
GALLERY_IMG_RE = re.compile(r"nvx-clinic-gallery__image", re.I)


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def http_error_302(self, req, fp, code, msg, headers):  # noqa: ANN001
        raise urllib.error.HTTPError(req.full_url, code, msg, headers, fp)

    http_error_301 = http_error_303 = http_error_307 = http_error_308 = http_error_302


class ImageParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.nodes: list[dict[str, str]] = []
        self.gallery_imgs = 0

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        data = {k: (v or "") for k, v in attrs}
        classes = data.get("class", "")
        if tag in {"img", "source"}:
            rec = {"tag": tag, **data}
            self.nodes.append(rec)
            if tag == "img" and GALLERY_IMG_RE.search(classes):
                self.gallery_imgs += 1


def fetch(url: str, timeout: int = 25) -> tuple[int, dict[str, str], str]:
    parsed = urlparse(url)
    if parsed.scheme not in {"http", "https"}:
        raise ValueError(f"URL scheme must be http or https, got: {parsed.scheme}")
    ctx = ssl.create_default_context()
    ctx.minimum_version = ssl.TLSVersion.TLSv1_2
    req = urllib.request.Request(url, headers={"User-Agent": "nvx-editorial-70-audit"})
    opener = urllib.request.build_opener(
        urllib.request.HTTPSHandler(context=ctx),
        _NoRedirect(),
    )
    try:
        with opener.open(req, timeout=timeout) as resp:
            body = resp.read().decode("utf-8", "replace")
            headers = {k.lower(): v for k, v in resp.headers.items()}
            return int(resp.status), headers, body
    except urllib.error.HTTPError as exc:
        body = exc.read().decode("utf-8", "replace") if exc.fp else ""
        headers = {k.lower(): v for k, v in (exc.headers.items() if exc.headers else [])}
        return int(exc.code), headers, body
    except (
        urllib.error.URLError,
        TimeoutError,
        socket.timeout,
        OSError,
        ssl.SSLError,
        http.client.IncompleteRead,
    ) as exc:
        return 0, {"x-nvx-fetch-error": str(exc)[:300]}, ""


def sitemap_locs(xml: str, base_host: str) -> list[str]:
    locs: list[str] = []
    for raw in re.findall(r"<loc>\s*([^<]+)\s*</loc>", xml, flags=re.I):
        url = raw.replace("&amp;", "&").strip()
        parsed = urlparse(url)
        if parsed.hostname != base_host:
            continue
        if "/wp-content/uploads/" in parsed.path:
            continue
        if parsed.path.endswith(".xml"):
            continue
        locs.append(url)
    return locs


def collect_urls(base_url: str) -> list[str]:
    origin = base_url.rstrip("/")
    host = urlparse(origin).hostname or ""
    seen: list[str] = []
    for name in ("page-sitemap.xml", "post-sitemap.xml"):
        status, headers, xml = fetch(f"{origin}/{name}")
        if status != 200:
            reason = headers.get("x-nvx-fetch-error") or f"HTTP {status}"
            raise RuntimeError(f"sitemap {name} {reason}")
        for url in sitemap_locs(xml, host):
            if url not in seen:
                seen.append(url)
    return seen


def image_vendor_hits(node: dict[str, str]) -> list[str]:
    hits: list[str] = []
    alt = node.get("alt", "")
    if alt and VENDOR_ALT_RE.search(alt):
        hits.append(f"alt:{alt[:120]}")
    for attr in IMAGE_ATTRS:
        value = node.get(attr, "")
        if value and VENDOR_URL_RE.search(value):
            hits.append(f"{attr}:{value[:180]}")
    return hits


def is_logo(node: dict[str, str]) -> bool:
    blob = " ".join(node.values())
    return bool(LOGO_RE.search(blob))


def meta_content(html: str, name: str) -> str:
    match = re.search(
        rf'<meta[^>]+(?:name|property)=["\']{re.escape(name)}["\'][^>]*content=["\']([^"\']*)["\']',
        html,
        flags=re.I,
    )
    if match:
        return match.group(1)
    match = re.search(
        rf'<meta[^>]+content=["\']([^"\']*)["\'][^>]*(?:name|property)=["\']{re.escape(name)}["\']',
        html,
        flags=re.I,
    )
    return match.group(1) if match else ""


def canonical(html: str) -> str:
    match = re.search(r'rel=["\']canonical["\'][^>]*href=["\']([^"\']+)["\']', html, flags=re.I)
    if match:
        return match.group(1)
    match = re.search(r'href=["\']([^"\']+)["\'][^>]*rel=["\']canonical["\']', html, flags=re.I)
    return match.group(1) if match else ""


def path_of(url: str) -> str:
    path = urlparse(url).path or "/"
    return path if path.endswith("/") else path + "/"


def normalize_url(url: str) -> str:
    parsed = urlparse(url.strip())
    scheme = (parsed.scheme or "https").lower()
    host = (parsed.hostname or "").lower()
    port = parsed.port
    path = path_of(url)
    netloc = host
    if port and port not in (80, 443):
        netloc = f"{host}:{port}"
    normalized = f"{scheme}://{netloc}{path}"
    if parsed.query:
        normalized += f"?{parsed.query}"
    return normalized


def robots_has(token: str, robots_meta: str, x_robots: str) -> bool:
    return token in robots_meta or token in x_robots


def collect_document_issues(
    url: str,
    status: int,
    html: str,
    robots_meta: str,
    x_robots: str,
    staging: bool,
) -> tuple[str, list[str]]:
    issues: list[str] = []
    if status != 200:
        issues.append(f"http_{status}")

    canon = canonical(html)
    if not canon:
        issues.append("canonical_missing")
    elif normalize_url(canon) != normalize_url(url):
        issues.append(f"canonical_mismatch:{canon}")

    if staging:
        if not robots_has("noindex", robots_meta, x_robots):
            issues.append("robots_expected_noindex")
        if not robots_has("nofollow", robots_meta, x_robots):
            issues.append("robots_expected_nofollow")
    elif robots_has("noindex", robots_meta, x_robots):
        issues.append("robots_expected_index")

    return canon, issues


def collect_shell_and_co2_issues(path: str, html: str) -> list[str]:
    issues: list[str] = []
    if re.search(r'class=["\'][^"\']*\bnvx-page-hero\b', html):
        issues.append("nvx-page-hero_class")
        if re.search(r'class=["\'][^"\']*\bnvx-brand-hero\b', html):
            issues.append("mixed_hero_shell")

    if not path.startswith("/laser-co2-fraccionado"):
        return issues
    if not CO2_HERO_RE.search(html):
        issues.append("co2_hero_760_missing")
    if ABDOMEN_RE.search(html):
        issues.append("co2_uses_laser_medico_abdomen")
    if re.search(r"150x150", html) and CO2_HERO_RE.search(html) is None:
        issues.append("co2_thumbnail_src")
    return issues


def collect_image_issues(path: str, parser: ImageParser) -> tuple[list[dict[str, Any]], list[dict[str, str]], list[dict[str, Any]], list[str]]:
    vendor: list[dict[str, Any]] = []
    empty_alt: list[dict[str, str]] = []
    images: list[dict[str, Any]] = []
    issues: list[str] = []
    goya = "goya" in path or "salamanca" in path

    for node in parser.nodes:
        blob = " ".join(f"{k}={v}" for k, v in node.items() if v)
        src = node.get("src") or node.get("data-src") or node.get("srcset") or ""
        rec = {"tag": node.get("tag", ""), "src": src[:220], "alt": node.get("alt", "")}
        images.append(rec)
        hits = image_vendor_hits(node)
        if hits:
            vendor.append({"src": src[:220], "alt": node.get("alt", ""), "hits": hits})
            issues.append("vendor_image")
        if node.get("tag") == "img" and not is_logo(node) and node.get("alt", "") == "":
            empty_alt.append(rec)
            if path == "/blog/":
                issues.append("blog_empty_alt")
        if not goya and (GOSIA_RE.search(blob) or EVA_RE.search(blob)):
            issues.append("gosia_eva_outside_goya")

    if parser.gallery_imgs > 4 and ("chamberi" in path or "goya" in path or "salamanca" in path):
        issues.append(f"gallery_over_4:{parser.gallery_imgs}")

    return vendor, empty_alt, images, issues


def audit_page(url: str, staging: bool) -> dict[str, Any]:
    status, headers, html = fetch(url)
    parser = ImageParser()
    try:
        parser.feed(html)
    except Exception:
        pass
    path = path_of(url)
    robots_meta = meta_content(html, "robots").lower()
    x_robots = headers.get("x-robots-tag", "").lower()

    canon, issues = collect_document_issues(url, status, html, robots_meta, x_robots, staging)
    issues.extend(collect_shell_and_co2_issues(path, html))
    vendor, empty_alt, images, image_issues = collect_image_issues(path, parser)
    issues.extend(image_issues)

    return {
        "url": url,
        "path": path,
        "http": status,
        "canonical": canon,
        "robots_meta": robots_meta,
        "x_robots": x_robots,
        "deploy_sha": meta_content(html, "nvx-deploy-sha"),
        "img_count": sum(1 for n in parser.nodes if n.get("tag") == "img"),
        "source_count": sum(1 for n in parser.nodes if n.get("tag") == "source"),
        "gallery_imgs": parser.gallery_imgs,
        "vendor_images": vendor,
        "empty_alt_non_logo": empty_alt,
        "issues": sorted(set(issues)),
        "images": images,
    }


def has_issue(issues: list[str], code: str) -> bool:
    return any(item == code or item.startswith(code + ":") for item in issues)


def canonical_is_ok(page: dict[str, Any]) -> bool:
    canon = str(page.get("canonical") or "")
    url = str(page.get("url") or "")
    return bool(canon) and bool(url) and normalize_url(canon) == normalize_url(url)


def summarize(pages: list[dict[str, Any]]) -> dict[str, Any]:
    issue_pages = [p for p in pages if p["issues"]]
    vendor_pages = [p["path"] for p in pages if p["vendor_images"]]
    return {
        "url_count": len(pages),
        "http_200": sum(1 for p in pages if p["http"] == 200),
        "canonical_ok": sum(1 for p in pages if canonical_is_ok(p)),
        "vendor_image_pages": vendor_pages,
        "blog_empty_alt": any(p["path"] == "/blog/" and has_issue(p["issues"], "blog_empty_alt") for p in pages),
        "issue_count": sum(len(p["issues"]) for p in pages),
        "failing_paths": [p["path"] for p in issue_pages],
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", required=True)
    parser.add_argument("--output", required=True)
    args = parser.parse_args()
    base = args.base_url.rstrip("/")
    staging = "staging2." in (urlparse(base).hostname or "")
    try:
        urls = collect_urls(base)
    except RuntimeError as exc:
        report = {
            "schema": "nvx-editorial-70/v1",
            "base_url": base,
            "expected_count": 70,
            "summary": {
                "url_count": 0,
                "http_200": 0,
                "canonical_ok": 0,
                "vendor_image_pages": [],
                "blog_empty_alt": False,
                "issue_count": 1,
                "failing_paths": [],
                "count_is_70": False,
                "sitemap_error": str(exc),
            },
            "pages": [],
        }
        output_path = os.path.abspath(args.output)
        if not output_path.startswith(os.getcwd()):
            raise ValueError(f"Output path must be within current directory: {args.output}")
        with open(output_path, "w", encoding="utf-8") as handle:
            json.dump(report, handle, ensure_ascii=False, indent=2)
            handle.write("\n")
        print(f"SITEMAP_FAIL {exc}", file=sys.stderr)
        print(f"OUTPUT={args.output}")
        return 1

    pages = [audit_page(url, staging=bool(staging)) for url in urls]
    report = {
        "schema": "nvx-editorial-70/v1",
        "base_url": base,
        "expected_count": 70,
        "summary": summarize(pages),
        "pages": pages,
    }
    report["summary"]["count_is_70"] = len(urls) == 70
    output_path = os.path.abspath(args.output)
    if not output_path.startswith(os.getcwd()):
        raise ValueError(f"Output path must be within current directory: {args.output}")
    with open(output_path, "w", encoding="utf-8") as handle:
        json.dump(report, handle, ensure_ascii=False, indent=2)
        handle.write("\n")
    summary = report["summary"]
    print(f"URLS={len(urls)}")
    print(f"HTTP_200={summary['http_200']}")
    print(f"CANONICAL_OK={summary['canonical_ok']}")
    print(f"VENDOR_PAGES={len(summary['vendor_image_pages'])}")
    print(f"ISSUES={summary['issue_count']}")
    print(f"OUTPUT={args.output}")
    if len(urls) != 70:
        print(f"COUNT_MISMATCH expected=70 got={len(urls)}", file=sys.stderr)
        return 2
    if summary["issue_count"] != 0:
        print("FAILING_PATHS=" + ",".join(summary["failing_paths"]), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
