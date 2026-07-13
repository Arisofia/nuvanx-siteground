#!/bin/bash
echo "== VALIDANDO STAGING PÚBLICO =="
echo "Buscando Thermage (debe dar 0 lineas):"
curl -sL -H "Cache-Control: no-cache" "https://staging2.nuvanx.com/?nocache=$(date +%s)" | grep -Ei 'Thermage|thermage|nvx-thermage|nvx-phase3c|et_pb_|brand-manual|zzzz' | wc -l

echo "Buscando Video (debe dar >0 lineas):"
curl -sL -H "Cache-Control: no-cache" "https://staging2.nuvanx.com/?nocache=$(date +%s)" | grep -Ei 'nvx-home-video-feature|<video|nvx-home-hero-video' | wc -l
