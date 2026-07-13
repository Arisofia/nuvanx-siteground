#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1

REPORT="/home/u54-jiiuzkghob55/nuvanx-audits/final-legacy-css-20260711-214625"
ERRORS=0

while IFS= read -r url
do
  [ -n "$url" ] || continue

  status="$(
    curl -Ls \
      -o /dev/null \
      -w "%{http_code}" \
      "${url}?physical_cleanup=$(date +%s)"
  )"

  printf "%s\t%s\n" "$status" "$url"

  if [ "$status" != "200" ]; then
    ERRORS=$((ERRORS + 1))
  fi
done < "$REPORT/urls.txt"

echo
echo "Errores HTTP: $ERRORS"
