#!/usr/bin/env bash
# MUTATING: promote nuvanx-medical theme from staging2 disk to production.
# Requires --confirm or NUVANX_CONFIRM=yes. Prefer running on the SiteGround host with wp-cli.
#
# Does NOT rsync the entire mu-plugins tree (would delete prod-only plugins).
# Disables SiteGround CSS minify and removes stale nvx-*.min.css so the canonical
# source CSS stack is what the public HTML enqueues (same policy as staging2 deploy).
set -Eeuo pipefail

PROD_ROOT=""
STAGING_ROOT=""
CONFIRM=0

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-to-prod.sh \
    --prod-root /absolute/prod/wp-root \
    --staging-root /absolute/staging2/wp-root \
    --confirm

Promotes the nuvanx-medical theme from staging2 to production without touching
prod-only MU plugins, and resets SiteGround CSS/JS minify so public HTML enqueues
canonical theme assets.
EOF
}

require_confirm() {
  [[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
    echo "Refusing to run without --confirm or NUVANX_CONFIRM=yes" >&2
    exit 1
  }
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --prod-root) PROD_ROOT="$2"; shift 2 ;;
    --staging-root) STAGING_ROOT="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *)
      usage
      echo "Unknown arg: $1" >&2
      exit 2
      ;;
  esac
done

[[ -n "$PROD_ROOT" && -n "$STAGING_ROOT" ]] || {
  usage
  exit 2
}

command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }
command -v rsync >/dev/null 2>&1 || { echo "rsync required" >&2; exit 2; }
require_confirm

[[ -d "$PROD_ROOT/wp-content/themes/nuvanx-medical" ]] || {
  echo "ERROR: prod theme missing at $PROD_ROOT" >&2
  exit 1
}
[[ -d "$STAGING_ROOT/wp-content/themes/nuvanx-medical" ]] || {
  echo "ERROR: staging theme missing at $STAGING_ROOT" >&2
  exit 1
}

echo "== Guard: prod siteurl/home/theme =="
(
  cd "$PROD_ROOT"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  theme="$(wp theme list --status=active --field=name)"
  echo "prod siteurl=$siteurl home=$home theme=$theme"
  [[ "$siteurl" == 'https://nuvanx.com' ]] || { echo "ERROR: unexpected prod siteurl=$siteurl" >&2; exit 1; }
  [[ "$home" == 'https://nuvanx.com' ]] || { echo "ERROR: unexpected prod home=$home" >&2; exit 1; }
  [[ "$theme" == 'nuvanx-medical' ]] || { echo "ERROR: active theme is $theme" >&2; exit 1; }
)

echo "== Guard: staging2 siteurl/theme and canonical assets =="
(
  cd "$STAGING_ROOT"
  siteurl="$(wp option get siteurl)"
  theme="$(wp theme list --status=active --field=name)"
  echo "staging siteurl=$siteurl theme=$theme"
  [[ "$siteurl" == 'https://staging2.nuvanx.com' ]] || { echo "ERROR: unexpected staging siteurl=$siteurl" >&2; exit 1; }
  [[ "$theme" == 'nuvanx-medical' ]] || { echo "ERROR: staging active theme is $theme" >&2; exit 1; }
  [[ -f wp-content/themes/nuvanx-medical/assets/css/nvx-patterns-editorial.css ]] || {
    echo "ERROR: missing nvx-patterns-editorial.css on staging2" >&2
    exit 1
  }
  [[ -f wp-content/themes/nuvanx-medical/inc/nvx-blog-system.php ]] || {
    echo "ERROR: missing nvx-blog-system.php on staging2" >&2
    exit 1
  }
)

DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$PROD_ROOT/wp-content/backups-nuvanx/pre-sync-$DATE"

echo "== Backup production → $BACKUP_DIR =="
mkdir -p "$BACKUP_DIR"
(cd "$PROD_ROOT" && wp db export "$BACKUP_DIR/db.sql" --quiet) || {
  echo "WARN: db export failed — continuing with theme/mu-plugin backup only" >&2
}
tar -czf "$BACKUP_DIR/theme.tgz" -C "$PROD_ROOT" wp-content/themes/nuvanx-medical
if [[ -d "$PROD_ROOT/wp-content/mu-plugins" ]]; then
  tar -czf "$BACKUP_DIR/mu-plugins.tgz" -C "$PROD_ROOT" wp-content/mu-plugins
fi

echo "== Disable SG CSS/JS minify/combine on prod =="
(cd "$PROD_ROOT" && wp sg optimize css disable || true)
(cd "$PROD_ROOT" && wp sg optimize combine-css disable || true)
(cd "$PROD_ROOT" && wp sg optimize combine-js disable || true)

echo "== Rsync theme (delete obsolete theme files) =="
rsync -a --delete \
  --exclude='.git' --exclude='php_errorlog' --exclude='*.log' \
  --exclude='backups-nuvanx' --exclude='quarantine' \
  --exclude='_archive*' --exclude='_disabled*' --exclude='*.bak*' \
  "$STAGING_ROOT/wp-content/themes/nuvanx-medical/" \
  "$PROD_ROOT/wp-content/themes/nuvanx-medical/"

echo "== Retire absorbed MU plugins (logic now lives in the theme) =="
mkdir -p "$PROD_ROOT/wp-content/mu-plugins"
for mu in \
  nuvanx-valoracion-native-hubspot-form.php \
  nuvanx-contacto-hubspot-form.php \
  nvx-disable-public-facebook-pixel.php \
  nuvanx-google-attribution.php
do
  rm -f "$PROD_ROOT/wp-content/mu-plugins/$mu"
  rm -f "$STAGING_ROOT/wp-content/mu-plugins/$mu"
done
# Drop empty attribution package if present.
rm -rf "$PROD_ROOT/wp-content/mu-plugins/nuvanx-google-attribution" \
  "$STAGING_ROOT/wp-content/mu-plugins/nuvanx-google-attribution"

echo "== Remove stale theme min.css siblings on prod =="
find "$PROD_ROOT/wp-content/themes/nuvanx-medical/assets/css" \
  -maxdepth 1 -type f -name 'nvx-*.min.css' -delete 2>/dev/null || true

echo "== Verify canonical CSS on prod disk =="
CSS="$PROD_ROOT/wp-content/themes/nuvanx-medical/assets/css"
for css_file in \
  nvx-fonts.css \
  nvx-tokens.css \
  nvx-base.css \
  nvx-site-layout.css \
  nvx-components.css \
  nvx-patterns-editorial.css \
  nvx-header.css \
  nvx-footer.css \
  nvx-posts.css
do
  [[ -f "$CSS/$css_file" ]] || { echo "ERROR: missing $css_file after rsync" >&2; exit 1; }
done
grep -Fq 'nvx-patterns-editorial.css' "$PROD_ROOT/wp-content/themes/nuvanx-medical/functions.php"
[[ -f "$PROD_ROOT/wp-content/themes/nuvanx-medical/inc/nvx-blog-system.php" ]]

echo "== Purge prod cache =="
(
  cd "$PROD_ROOT"
  wp cache flush || true
  wp sg purge || true
  rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
  rm -rf wp-content/cache/sgo-cache/* 2>/dev/null || true
  rm -rf wp-content/cache/* 2>/dev/null || true
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
)

echo "== DONE backup=$BACKUP_DIR =="
echo "PROMOTE_PROD_OK"
