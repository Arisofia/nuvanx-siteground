#!/bin/bash
set -euo pipefail

# PageSpeed Insights Audit Script
# Usage: bash pagespeed-audit.sh --url <url> --strategy <mobile|desktop> --api-key <key>

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

if [[ -z "$URL" || -z "$STRATEGY" || -z "$API_KEY" ]]; then
  echo "Error: --url, --strategy, and --api-key are required"
  exit 1
fi

echo "Running PageSpeed audit for: $URL (strategy: $STRATEGY)"

# Run PageSpeed Insights API
API_URL="https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${URL}&strategy=${STRATEGY}&key=${API_KEY}"

RESPONSE=$(curl -s "$API_URL")

# Extract key metrics
PERFORMANCE_SCORE=$(echo "$RESPONSE" | jq -r '.lighthouseResult.categories.performance.score // null')
if [[ "$PERFORMANCE_SCORE" != "null" && "$PERFORMANCE_SCORE" != "" ]]; then
  PERFORMANCE_SCORE_INT=$(echo "$PERFORMANCE_SCORE * 100" | bc 2>/dev/null || echo "0")
  PERFORMANCE_SCORE_INT=${PERFORMANCE_SCORE_INT%.*}  # Remove decimal
else
  PERFORMANCE_SCORE_INT=0
fi

FCP=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["first-contentful-paint"].displayValue // null')
LCP=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["largest-contentful-paint"].displayValue // null')
CLS=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["cumulative-layout-shift"].displayValue // null')
TTI=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["interactive"].displayValue // null')
SPEED_INDEX=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["speed-index"].displayValue // null')
TBT=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["total-blocking-time"].displayValue // null')
TTFB=$(echo "$RESPONSE" | jq -r '.lighthouseResult.audits["server-response-time"].displayValue // null')

# Output results
echo "PERFORMANCE_SCORE=${PERFORMANCE_SCORE_INT}"
echo "FCP=${FCP}"
echo "LCP=${LCP}"
echo "CLS=${CLS}"
echo "TTI=${TTI}"
echo "SPEED_INDEX=${SPEED_INDEX}"
echo "TOTAL_BLOCKING_TIME=${TBT}"
echo "TTFB=${TTFB}"

# Save full response
mkdir -p artifacts
echo "$RESPONSE" > artifacts/pagespeed-${STRATEGY}-$(date +%s).json

echo "Audit completed. Full response saved to artifacts/"

# Determine pass/fail thresholds
if [[ "$STRATEGY" == "mobile" ]]; then
  # Mobile thresholds (stricter)
  if [[ "$PERFORMANCE_SCORE_INT" -ge 50 ]]; then
    echo "STATUS=PASS"
  else
    echo "STATUS=FAIL"
  fi
else
  # Desktop thresholds
  if [[ "$PERFORMANCE_SCORE_INT" -ge 70 ]]; then
    echo "STATUS=PASS"
  else
    echo "STATUS=FAIL"
  fi
fi

echo "AUDIT_COMPLETED=true"
