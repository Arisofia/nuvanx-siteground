#!/bin/bash
urls=(
  "https://nuvanx.com/"
  "https://nuvanx.com/medicina-estetica-laser/"
  "https://nuvanx.com/madrid/valoracion/"
  "https://nuvanx.com/equipo-medico/"
  "https://nuvanx.com/medicina-estetica-chamberi/"
  "https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/"
)

echo "=== PUBLIC VALIDATION PROD ==="
for url in "${urls[@]}"; do
  echo "Testing $url ..."
  curl -sL $url | grep -Ei "Thermage|NVX_|et_pb_|tmp-|brand-manual|preg_replace|zzzz|nvx-phase3c"
done
