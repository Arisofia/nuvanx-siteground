#!/usr/bin/env bash
set -euo pipefail
SRC=/tmp/nvx-theme-mu
ROOT_STG=/home/customer/www/staging2.nuvanx.com/public_html
ROOT_PROD=/home/customer/www/nuvanx.com/public_html
SHA=$(cat /tmp/nvx-theme-mu.sha)

php -l "$SRC/inc/nvx-hero-and-forms.php"
php -l "$SRC/inc/nvx-integrations.php"
php -l "$SRC/inc/nvx-document-governance.php"
php -l "$SRC/inc/nvx-contacto-valoracion-page.php"
php -l "$SRC/inc/nvx-page-hygiene.php"

# CRITICAL: retire MU plugins BEFORE theme rsync/wp-cli so function names do not collide.
for root in "$ROOT_STG" "$ROOT_PROD"; do
  for mu in \
    nuvanx-valoracion-native-hubspot-form.php \
    nuvanx-contacto-hubspot-form.php \
    nvx-disable-public-facebook-pixel.php \
    nuvanx-google-attribution.php
  do
    rm -f "$root/wp-content/mu-plugins/$mu"
  done
  rm -rf "$root/wp-content/mu-plugins/nuvanx-google-attribution"
done

rsync -a --delete --exclude='.git' --exclude='node_modules' "$SRC/" "$ROOT_STG/wp-content/themes/nuvanx-medical/"
echo "$SHA" > "$ROOT_STG/wp-content/themes/nuvanx-medical/.nvx-deploy-sha"
wp cache flush --path="$ROOT_STG" 2>/dev/null || true

NUVANX_CONFIRM=yes bash /tmp/deploy-to-prod.sh \
  --prod-root "$ROOT_PROD" \
  --staging-root "$ROOT_STG" \
  --confirm

wp cache flush --path="$ROOT_PROD" 2>/dev/null || true

echo "STG=$(cat $ROOT_STG/wp-content/themes/nuvanx-medical/.nvx-deploy-sha)"
echo "PROD=$(cat $ROOT_PROD/wp-content/themes/nuvanx-medical/.nvx-deploy-sha)"

for host in https://staging2.nuvanx.com https://nuvanx.com; do
  for path in / /contacto/ /madrid/valoracion/ /soluciones-medicas/ /medicina-estetica-laser/; do
    code=$(curl -sk -o /dev/null -w '%{http_code}' --max-time 25 "$host$path" || echo ERR)
    echo "$code  $host$path"
  done
done

# Prove retired MUs gone
for root in "$ROOT_STG" "$ROOT_PROD"; do
  for mu in \
    nuvanx-valoracion-native-hubspot-form.php \
    nuvanx-contacto-hubspot-form.php \
    nvx-disable-public-facebook-pixel.php \
    nuvanx-google-attribution.php
  do
    if [[ -f "$root/wp-content/mu-plugins/$mu" ]]; then
      echo "FAIL still present: $root/$mu" >&2
      exit 1
    fi
  done
done
echo MU_ABSORB_DEPLOY_OK
