#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://nuvanx.com}"
BASE_URL="${BASE_URL%/}"
ua='NUVANX-Document-Head-Title-Audit/1.1'
tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

matrix_url="$BASE_URL/matriz-diagnostico-facial-estructura-piel-musculo-grasa/"
expected_runtime_contract='20260815-db-authoritative-single-entry-v1'

fetch() {
  curl -fsSL --max-time 30 -A "$ua" "$1"
}

index="$tmpdir/index.xml"
fetch "$BASE_URL/sitemap_index.xml" > "$index"
children="$tmpdir/children.txt"
grep -oE '<loc>[^<]+</loc>' "$index" | sed -E 's#</?loc>##g' | sort -u > "$children"
urls="$tmpdir/urls.txt"
: > "$urls"
while IFS= read -r sitemap; do
  [[ -n "$sitemap" ]] || continue
  fetch "$sitemap" | grep -oE '<loc>[^<]+</loc>' | sed -E 's#</?loc>##g' >> "$urls"
done < "$children"
sort -u "$urls" -o "$urls"

pass=0
fail=0
runtime_sentinel_pass=0
runtime_sentinel_fail=0
index=0
while IFS= read -r url; do
  [[ -n "$url" ]] || continue
  index=$((index + 1))
  html="$tmpdir/page-$index.html"
  fetch "$url" > "$html"
  set +e
  result="$(php -r '
    $html=file_get_contents($argv[1]);
    libxml_use_internal_errors(true);
    $dom=new DOMDocument();
    $dom->loadHTML($html, LIBXML_NOWARNING|LIBXML_NOERROR);
    $xp=new DOMXPath($dom);
    $nodes=$xp->query("/html/head/title");
    $count=$nodes ? $nodes->length : 0;
    $title=$count ? trim(preg_replace("/\\s+/u"," ",$nodes->item(0)->textContent)) : "";
    echo json_encode(["head_title_count"=>$count,"title"=>$title], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
    exit($count===1 && $title!=="" ? 0 : 1);
  ' "$html" 2>&1)"
  rc=$?
  set -e
  if [[ "$rc" -eq 0 ]]; then
    echo "PASS DOCUMENT_HEAD_TITLE url=$url detail=$result"
    pass=$((pass + 1))
  else
    echo "FAIL DOCUMENT_HEAD_TITLE url=$url detail=$result" >&2
    fail=$((fail + 1))
  fi

  if [[ "$url" == "$matrix_url" ]]; then
    set +e
    runtime_contract="$(php -r '
      $html=file_get_contents($argv[1]);
      libxml_use_internal_errors(true);
      $dom=new DOMDocument();
      $dom->loadHTML($html, LIBXML_NOWARNING|LIBXML_NOERROR);
      $xp=new DOMXPath($dom);
      $nodes=$xp->query("//meta[translate(@name,\"ABCDEFGHIJKLMNOPQRSTUVWXYZ\",\"abcdefghijklmnopqrstuvwxyz\")=\"nvx-governed-blog-runtime-contract\"]");
      if (!$nodes || $nodes->length !== 1) exit(1);
      echo trim($nodes->item(0)->getAttribute("content"));
    ' "$html")"
    runtime_rc=$?
    set -e
    if [[ "$runtime_rc" -eq 0 && "$runtime_contract" == "$expected_runtime_contract" ]]; then
      echo "PASS GOVERNED_BLOG_RUNTIME_SENTINEL url=$url contract=$runtime_contract"
      runtime_sentinel_pass=$((runtime_sentinel_pass + 1))
    else
      echo "FAIL GOVERNED_BLOG_RUNTIME_SENTINEL url=$url expected=$expected_runtime_contract actual=${runtime_contract:-missing}" >&2
      runtime_sentinel_fail=$((runtime_sentinel_fail + 1))
    fi
  fi
done < "$urls"

printf 'DOCUMENT_HEAD_TITLE_URLS=%s\n' "$((pass + fail))"
printf 'DOCUMENT_HEAD_TITLE_PASS=%s\n' "$pass"
printf 'DOCUMENT_HEAD_TITLE_FAIL=%s\n' "$fail"
printf 'GOVERNED_BLOG_RUNTIME_SENTINEL_PASS=%s\n' "$runtime_sentinel_pass"
printf 'GOVERNED_BLOG_RUNTIME_SENTINEL_FAIL=%s\n' "$runtime_sentinel_fail"
if [[ "$fail" -ne 0 || "$runtime_sentinel_fail" -ne 0 || "$runtime_sentinel_pass" -ne 1 ]]; then
  echo 'DOCUMENT_HEAD_TITLE_AUDIT=FAIL' >&2
  exit 1
fi
echo 'DOCUMENT_HEAD_TITLE_AUDIT=PASS'
