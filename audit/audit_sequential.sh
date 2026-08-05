#!/bin/bash
SLUGS=(
  "" "madrid/" "madrid/valoracion/" "soluciones-medicas/" "protocolos-signature/" 
  "por-que-nuvanx/" "inversion-medicina-estetica/" "nosotros/" "equipo-medico/" 
  "contacto/" "blog/" "clinicas-de-medicina-estetica-nuvanx/" "medicina-estetica-chamberi/" 
  "medicina-estetica-goya-barrio-salamanca/" "clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/" 
  "endolift-facial-papada-mandibula/" "endolaser-corporal-grasa-localizada/" 
  "laser-co2-fraccionado-madrid-textura-cicatrices-poro/" "exion-btl/" "exion-face/" 
  "exion-fractional/" "exion-body/" "emfusion/" "btl-exilite-ipl-madrid/" 
  "medicina-estetica-laser/" "medicina-estetica/" "estetica-avanzada/" 
  "bioestimuladores-colageno-madrid/" "ojeras-surco-lagrimal-madrid/" 
  "rinomodelacion-sin-cirugia-madrid/" "labios-acido-hialuronico-madrid/" 
  "remodelacion-corporal-laser-madrid/" "tratamiento-postparto-abdomen-contorno-corporal-madrid/" 
  "papada-definicion-mandibular-madrid/" "calidad-piel-firmeza-luminosidad-madrid/" 
  "cicatrices-acne-poros-textura-madrid/" "manchas-rojeces-fotorejuvenecimiento-ipl-madrid/" 
  "grasa-localizada-abdomen-flancos-madrid/" "flacidez-grasa-localizada-brazos-madrid/" 
  "grasa-espalda-zona-sujetador-madrid/" "flacidez-muslos-internos-subgluteo-madrid/" 
  "tratamiento-rodillas-grasa-flacidez-madrid/" "contorno-corporal-masculino-madrid/" 
  "gracias/" "politica-de-cookies-ue/" "politica-privacidad/" "aviso-legal/" 
  "politica-de-cookies/" "mas-informacion-sobre-las-cookies/" "casos-de-pacientes/" "tratamientos/"
)

echo "slug,prod_status,stag_status" > /home/ubuntu/nuvanx_audit_2026-08-04/nuvanx_status_sequential.csv

for slug in "${SLUGS[@]}"; do
  echo "Procesando: /$slug"
  
  # Prod
  P_STATUS=$(curl -sI -L --http1.1 -k -m 10 -H "Cache-Control: no-cache" -A "Mozilla/5.0" "https://nuvanx.com/$slug" | grep -E "^HTTP/" | tail -n 1 | awk '{print $2}')
  if [ -z "$P_STATUS" ]; then P_STATUS="Error"; fi
  
  # Stag
  S_STATUS=$(curl -sI -L --http1.1 -k -m 10 -H "Cache-Control: no-cache" -A "Mozilla/5.0" "https://staging2.nuvanx.com/$slug" | grep -E "^HTTP/" | tail -n 1 | awk '{print $2}')
  if [ -z "$S_STATUS" ]; then S_STATUS="Error"; fi
  
  echo "\"$slug\",$P_STATUS,$S_STATUS" >> /home/ubuntu/nuvanx_audit_2026-08-04/nuvanx_status_sequential.csv
  sleep 0.5
done

echo "Completado."
