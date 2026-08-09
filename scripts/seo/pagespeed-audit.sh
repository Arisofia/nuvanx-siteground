#!/bin/bash
set -euo pipefail

# PageSpeed Insights API audit script
# Usage: ./pagespeed-audit.sh --url <URL> --strategy <mobile|desktop> --api-key <API_KEY>

URL=""
STRATEGY="mobile"
API_KEY=""

while [[ $# -gt 0 ]]; do
  case $1 in
    --url)
      URL="$2"
      shift 2
      ;;
    --strategy)
      STRATEGY="$2"
      shift 2
      ;;
    --api-key)
      API_KEY="$2"
      shift 2
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

if [[ -z "$URL" || -z "$API_KEY" ]]; then
  echo "Error: URL and API_KEY are required"
  exit 1
fi

echo "Running PageSpeed audit for $URL (strategy: $STRATEGY)"

# Call PageSpeed Insights API
API_URL="https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${URL}&strategy=${STRATEGY}&key=${API_KEY}"

response=$(curl -s "$API_URL")

# Extract key metrics
performance_score=$(echo "$response" | jq -r '.lighthouseResult.categories.performance.score // "N/A"')
fcp=$(echo "$response" | jq -r '.lighthouseResult.audits["first-contentful-paint"].displayValue // "N/A"')
lcp=$(echo "$response" | jq -r '.lighthouseResult.audits["largest-contentful-paint"].displayValue // "N/A"')
cls=$(echo "$response" | jq -r '.lighthouseResult.audits["cumulative-layout-shift"].displayValue // "N/A"')
tti=$(echo "$response" | jq -r '.lighthouseResult.audits["interactive"].displayValue // "N/A"')
speed_index=$(echo "$response" | jq -r '.lighthouseResult.audits["speed-index"].displayValue // "N/A"')
tbt=$(echo "$response" | jq -r '.lighthouseResult.audits["total-blocking-time"].displayValue // "N/A"')

# Convert score to percentage
if [[ "$performance_score" != "N/A" ]]; then
  performance_score=$(awk "BEGIN {printf \"%.0f\", $performance_score * 100}")
fi

echo "PERFORMANCE_SCORE=${performance_score}"
echo "FCP=${fcp}"
echo "LCP=${lcp}"
echo "CLS=${cls}"
echo "TTI=${tti}"
echo "SPEED_INDEX=${speed_index}"
echo "TOTAL_BLOCKING_TIME=${tbt}"

# Determine pass/fail thresholds
if [[ "$STRATEGY" == "mobile" ]]; then
  # Mobile thresholds (stricter)
  if (( $(echo "$performance_score >= 50" | bc -l) )); then
    echo "STATUS=PASS"
  else
    echo "STATUS=FAIL"
  fi
else
  # Desktop thresholds
  if (( $(echo "$performance_score >= 70" | bc -l) )); then
    echo "STATUS=PASS"
  else
    echo "STATUS=FAIL"
  fi
fi

echo "AUDIT_COMPLETED=true"
