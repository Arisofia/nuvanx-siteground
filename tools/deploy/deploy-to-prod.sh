#!/usr/bin/env bash
# MUTATING: deploy an already-accepted nuvanx-medical candidate to production.
# Requires --confirm or NUVANX_CONFIRM=yes.
#
# Safety model:
# - exact production identity and exact 40-char candidate SHA
# - source is staged and validated away from the live theme
# - mandatory SQL + theme backup before mutation
# - no production MU-plugin mutation; legacy ownership must already be clean
# - directory cutover avoids rsyncing partial files into the live theme
# - exact .nvx-deploy-sha marker is part of the staged release
# - shared content migration + divergence audit run inside the same transaction
# - any post-cutover failure restores the previous live theme AND database
# - SiteGround dynamic-cache purge restores the original Speed Optimizer state
set -Eeuo pipefail

PROD_ROOT=""
SOURCE_THEME=""
SHA=""
CONFIRM=0

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-to-prod.sh \
    --prod-root /home/customer/www/nuvanx.com/public_html \
    --source-theme /absolute/path/to/accepted/theme \
    --sha <full-lowercase-40-char-commit-sha> \
    --confirm
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
    --source-theme) SOURCE_THEME="$2"; shift 2 ;;
    --sha) SHA="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) usage; echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$PROD_ROOT" && -n "$SOURCE_THEME" && -n "$SHA" ]] || { usage; exit 2; }
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "ERROR: SHA must be a full lowercase 40-character commit SHA" >&2; exit 2; }
[[ "$PROD_ROOT" == '/home/customer/www/nuvanx.com/public_html' ]] || {
  echo "ERROR: refusing unexpected production root: $PROD_ROOT" >&2
  exit 1
}

command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }
command -v rsync >/dev/null 2>&1 || { echo "rsync required" >&2; exit 2; }
command -v php >/dev/null 2>&1 || { echo "php required" >&2; exit 2; }
command -v tar >/dev/null 2>&1 || { echo "tar required" >&2; exit 2; }
require_confirm

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd -P)"
# Resolve the migration tooling for both the flattened CI payload layout
# ($SCRIPT_DIR/tools/migrations, script at the release root) and a repository
# checkout ($SCRIPT_DIR/../migrations, script at tools/deploy/).
MIGRATION_SCRIPT=""
AUDIT_SCRIPT=""
for candidate_dir in "$SCRIPT_DIR/tools/migrations" "$SCRIPT_DIR/../migrations"; do
  if [[ -f "$candidate_dir/content-hygiene-shared.php" && -f "$candidate_dir/audit-content-divergence.php" ]]; then
    MIGRATION_SCRIPT="$candidate_dir/content-hygiene-shared.php"
    AUDIT_SCRIPT="$candidate_dir/audit-content-divergence.php"
    break
  fi
done
[[ -f "$MIGRATION_SCRIPT" ]] || { echo "ERROR: shared content migration missing under $SCRIPT_DIR/tools/migrations or $SCRIPT_DIR/../migrations" >&2; exit 1; }
[[ -f "$AUDIT_SCRIPT" ]] || { echo "ERROR: content divergence audit missing under $SCRIPT_DIR/tools/migrations or $SCRIPT_DIR/../migrations" >&2; exit 1; }

# Production site URL constants
PROD_URL='https://nuvanx.com'

THEMES_ROOT="$PROD_ROOT/wp-content/themes"
LIVE_THEME="$THEMES_ROOT/nuvanx-medical"
[[ -d "$LIVE_THEME" ]] || { echo "ERROR: production theme missing at $LIVE_THEME" >&2; exit 1; }
[[ -d "$SOURCE_THEME" ]] || { echo "ERROR: source theme missing at $SOURCE_THEME" >&2; exit 1; }

live_real="$(cd "$LIVE_THEME" && pwd -P)"
source_real="$(cd "$SOURCE_THEME" && pwd -P)"
[[ "$live_real" != "$source_real" ]] || { echo "ERROR: source theme is the live production theme" >&2; exit 1; }

RUN_TOKEN="${NVX_RUN_TOKEN:-$(date +%Y%m%d-%H%M%S)-$$}"
[[ "$RUN_TOKEN" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "ERROR: invalid NVX_RUN_TOKEN" >&2; exit 2; }
RELEASE_ROOT="$THEMES_ROOT/.nvx-prod-release-${SHA}-${RUN_TOKEN}"
STAGED_THEME="$RELEASE_ROOT/nuvanx-medical"
PREVIOUS_THEME="$THEMES_ROOT/.nvx-prod-previous-${RUN_TOKEN}"
FAILED_THEME="$THEMES_ROOT/.nvx-prod-failed-${RUN_TOKEN}"
BACKUP_DIR="$PROD_ROOT/wp-content/backups-nuvanx/pre-prod-${RUN_TOKEN}-${SHA:0:12}"
# Keep the migration/audit evidence beside the run-token-scoped snapshot so a
# rollback preserves the forensic trail. $SCRIPT_DIR is the ephemeral CI payload
# that the workflow deletes after the run.
# SECURITY: BACKUP_DIR is under PROD_ROOT/wp-content/backups-nuvanx/ (document root).
# The webserver configuration must deny access to wp-content/backups-nuvanx/
# to prevent exposure of these logs and the database snapshot.
MIGRATION_LOG="$BACKUP_DIR/migration-production.log"
AUDIT_LOG="$BACKUP_DIR/migration-audit-production.log"
MIGRATION_WRITE_MARKER="$BACKUP_DIR/.migration-write-marker"
SWAPPED=0
PREVIOUS_MOVED=0
ROLLBACK_OK=1

cleanup_uncommitted_release() {
  if [[ "$SWAPPED" -eq 0 ]]; then
    rm -rf "$RELEASE_ROOT" 2>/dev/null || true
    # If previous theme was moved but not swapped, restore it
    if [[ "$PREVIOUS_MOVED" -eq 1 && ! -d "$LIVE_THEME" && -d "$PREVIOUS_THEME" ]]; then
      mv "$PREVIOUS_THEME" "$LIVE_THEME" 2>/dev/null || true
    fi
  else
    # After rollback, clean up empty release directories
    rm -rf "$RELEASE_ROOT" 2>/dev/null || true
    # Keep FAILED_THEME for forensic investigation if rollback failed
    # Only delete if rollback was successful
    if [[ "$ROLLBACK_OK" -eq 1 ]]; then
      rm -rf "$FAILED_THEME" 2>/dev/null || true
    else
      echo "INFO: Keeping failed theme at $FAILED_THEME for investigation" >&2
    fi
  fi
}
trap cleanup_uncommitted_release EXIT

# SiteGround exposes `wp sg purge` only while Speed Optimizer is active on some
# installations. Temporarily activate it only when needed, purge, and always
# restore the original plugin state before returning.
purge_siteground_dynamic_cache() {
  local plugin='sg-cachepress'
  local activated_temporarily=0
  local purge_rc=0
  local restore_rc=0

  cd "$PROD_ROOT"

  if wp help sg >/dev/null 2>&1; then
    wp sg purge
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=existing-command'
    return 0
  fi

  if ! wp plugin is-installed "$plugin" >/dev/null 2>&1; then
    echo 'SITEGROUND_DYNAMIC_PURGE=SKIPPED reason=sg-command-and-plugin-unavailable'
    return 0
  fi

  if ! wp plugin is-active "$plugin" >/dev/null 2>&1; then
    wp plugin activate "$plugin" --quiet
    activated_temporarily=1
  fi

  if wp help sg >/dev/null 2>&1; then
    wp sg purge || purge_rc=$?
  else
    echo "ERROR: SiteGround command unavailable after transient Speed Optimizer activation" >&2
    purge_rc=1
  fi

  if [[ "$activated_temporarily" -eq 1 ]]; then
    wp plugin deactivate "$plugin" --quiet || restore_rc=$?
    if wp plugin is-active "$plugin" >/dev/null 2>&1; then
      echo "ERROR: Speed Optimizer remained active after transient cache purge" >&2
      restore_rc=10  # Distinct code for plugin restoration failure
    fi
  fi

  if [[ "$restore_rc" -ne 0 ]]; then
    return "$restore_rc"
  fi
  if [[ "$purge_rc" -ne 0 ]]; then
    return "$purge_rc"
  fi

  if [[ "$activated_temporarily" -eq 1 ]]; then
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=transient-plugin-activation restored=inactive'
  else
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=plugin-already-active'
  fi
}

echo "== Guard production identity =="
(
  cd "$PROD_ROOT"
  db="$(wp config get DB_NAME)"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  blog_public="$(wp option get blog_public)"
  theme="$(wp theme list --status=active --field=name)"
  echo "prod db=$db siteurl=$siteurl home=$home blog_public=$blog_public theme=$theme"
  [[ "$db" == 'db0ecrycwv2tgb' ]] || { echo "ERROR: unexpected production DB=$db" >&2; exit 1; }
  [[ "$siteurl" == "$PROD_URL" ]] || { echo "ERROR: unexpected prod siteurl=$siteurl" >&2; exit 1; }
  [[ "$home" == "$PROD_URL" ]] || { echo "ERROR: unexpected prod home=$home" >&2; exit 1; }
  [[ "$blog_public" == '1' ]] || { echo "ERROR: production blog_public=$blog_public" >&2; exit 1; }
  [[ "$theme" == 'nuvanx-medical' ]] || { echo "ERROR: active theme is $theme" >&2; exit 1; }
)

for legacy_mu in \
  nuvanx-valoracion-native-hubspot-form.php \
  nuvanx-contacto-hubspot-form.php \
  nvx-disable-public-facebook-pixel.php \
  nuvanx-google-attribution.php
do
  [[ ! -e "$PROD_ROOT/wp-content/mu-plugins/$legacy_mu" ]] || {
    echo "ERROR: legacy production MU plugin still present: $legacy_mu" >&2
    exit 1
  }
done
[[ ! -d "$PROD_ROOT/wp-content/mu-plugins/nuvanx-google-attribution" ]] || {
  echo "ERROR: legacy production attribution MU package still present" >&2
  exit 1
}

echo "== Stage accepted theme away from live production =="
[[ ! -e "$RELEASE_ROOT" ]]
[[ ! -e "$PREVIOUS_THEME" ]]
[[ ! -e "$FAILED_THEME" ]]
mkdir -p "$STAGED_THEME"
rsync -a --delete \
  --exclude='.git' --exclude='php_errorlog' --exclude='*.log' \
  --exclude='backups-nuvanx' --exclude='quarantine' \
  --exclude='_archive*' --exclude='_disabled*' --exclude='*.bak*' \
  "$SOURCE_THEME/" "$STAGED_THEME/"
printf '%s\n' "$SHA" > "$STAGED_THEME/.nvx-deploy-sha"
[[ "$(tr -d '\r\n' < "$STAGED_THEME/.nvx-deploy-sha")" == "$SHA" ]]

for required in \
  assets/css/nvx-fonts.css \
  assets/css/nvx-tokens.css \
  assets/css/nvx-base.css \
  assets/css/nvx-site-layout.css \
  assets/css/nvx-components.css \
  assets/css/nvx-patterns-editorial.css \
  assets/css/nvx-header.css \
  assets/css/nvx-footer.css \
  assets/css/nvx-posts.css \
  inc/nvx-blog-system.php \
  functions.php
do
  [[ -f "$STAGED_THEME/$required" ]] || { echo "ERROR: staged release missing $required" >&2; exit 1; }
done
grep -Fq 'nvx-patterns-editorial.css' "$STAGED_THEME/functions.php"
find "$STAGED_THEME" -path '*/vendor' -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l >/dev/null
find "$STAGED_THEME/assets/css" -maxdepth 1 -type f -name 'nvx-*.min.css' -delete 2>/dev/null || true

echo "== Mandatory pre-deploy rollback snapshot =="
umask 077
mkdir -p "$BACKUP_DIR"
# Deny HTTP access to the snapshot directory: it holds a full production DB dump
# plus the migration/audit logs, and lives under the document root. umask 077
# only protects the filesystem, not the webserver, which reads as its own user.
# SECURITY WARNING: This .htaccess protection only works on Apache servers with
# AllowOverride enabled. For nginx/LiteSpeed or Apache with AllowOverride None,
# these files may be HTTP-reachable. Consider moving BACKUP_DIR outside the
# document root for maximum security in non-Apache environments.
cat > "$BACKUP_DIR/.htaccess" <<'HTACCESS'
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  <IfModule mod_access_compat.c>
    Deny from all
  </IfModule>
</IfModule>
HTACCESS
# Remove any stale migration write marker from previous runs
rm -f "$MIGRATION_WRITE_MARKER" 2>/dev/null || true
(
  cd "$PROD_ROOT"
  wp db export "$BACKUP_DIR/db.sql" --quiet
)
tar -czf "$BACKUP_DIR/theme.tgz" -C "$PROD_ROOT" wp-content/themes/nuvanx-medical
if [[ -d "$PROD_ROOT/wp-content/mu-plugins" ]]; then
  tar -czf "$BACKUP_DIR/mu-plugins.tgz" -C "$PROD_ROOT" wp-content/mu-plugins
fi
[[ -s "$BACKUP_DIR/db.sql" ]]
[[ -s "$BACKUP_DIR/theme.tgz" ]]
if [[ -f "$LIVE_THEME/.nvx-deploy-sha" ]]; then
  tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha" > "$BACKUP_DIR/previous-sha.txt"
else
  : > "$BACKUP_DIR/previous-sha.txt"
fi

echo "ROLLBACK_SNAPSHOT=PASS path=$BACKUP_DIR"

ROLLBACK_IN_PROGRESS=0

# Signal handlers that pass appropriate exit codes to rollback_after_swap
# Standard Unix signal codes: 128+signal number
rollback_on_int() {
  rollback_after_swap 130  # SIGINT = 2, 128+2 = 130
}
rollback_on_term() {
  rollback_after_swap 143  # SIGTERM = 15, 128+15 = 143
}
rollback_on_hup() {
  rollback_after_swap 129  # SIGHUP = 1, 128+1 = 129
}
rollback_after_swap() {
  local rc="${1:-$?}"  # Use passed signal code or fall back to $?
  local rollback_ok=1
  ROLLBACK_OK=0
  # Disarm every trigger and guard against re-entry: a second signal (e.g. the
  # CI runner escalating from SIGTERM) must not re-run this handler and move the
  # already-restored good theme aside, which would leave production with no theme.
  trap - ERR INT TERM HUP
  if [[ "$ROLLBACK_IN_PROGRESS" -eq 1 ]]; then
    return
  fi
  ROLLBACK_IN_PROGRESS=1
  set +e

  # Handle the critical no-theme window: if previous was moved but swap
  # didn't complete, restore previous theme immediately
  if [[ "$PREVIOUS_MOVED" -eq 1 && "$SWAPPED" -eq 0 ]]; then
    echo "ROLLBACK_TRIGGERED rc=$rc recovering from incomplete cutover" >&2
    if [[ -d "$LIVE_THEME" && ! -d "$PREVIOUS_THEME" ]]; then
      # The first move never happened: the live theme is still intact.
      PREVIOUS_MOVED=0
    elif [[ -d "$PREVIOUS_THEME" ]] && mv "$PREVIOUS_THEME" "$LIVE_THEME"; then
      PREVIOUS_MOVED=0
    else
      echo "ERROR: failed to restore previous theme during incomplete cutover" >&2
      rollback_ok=0
    fi
  fi

  if [[ "$SWAPPED" -eq 1 ]]; then
    echo "ROLLBACK_TRIGGERED rc=$rc previous=$PREVIOUS_THEME backup=$BACKUP_DIR" >&2
    rm -rf "$FAILED_THEME"

    if [[ -d "$LIVE_THEME" ]]; then
      mv "$LIVE_THEME" "$FAILED_THEME" || rollback_ok=0
    fi
    if [[ -d "$PREVIOUS_THEME" ]]; then
      mv "$PREVIOUS_THEME" "$LIVE_THEME" || rollback_ok=0
    else
      echo "ERROR: previous production theme is unavailable during rollback" >&2
      rollback_ok=0
    fi

    # Check durable write marker created by migration script after first DB write
    # This is the authoritative source for whether the DB was actually modified.
    if [[ -f "$MIGRATION_WRITE_MARKER" ]]; then
      echo "ROLLBACK_DB=RESTORED reason=write-marker-detected-db-was-modified" >&2
      if [[ -s "$BACKUP_DIR/db.sql" ]]; then
        (
          cd "$PROD_ROOT" || return
          wp db import "$BACKUP_DIR/db.sql" --allow-root
        ) || rollback_ok=0
      else
        echo "ERROR: production DB backup is unavailable during rollback" >&2
        rollback_ok=0
      fi
    else
      echo "ROLLBACK_DB=SKIPPED reason=no-write-marker-db-not-modified-or-migration-not-started" >&2
    fi

    (
      cd "$PROD_ROOT" || return
      wp cache flush || true
      purge_siteground_dynamic_cache || true
      rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
      rm -rf wp-content/cache/sgo-cache/* wp-content/cache/* 2>/dev/null || true
      wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); }' || true
    )

    restored="$(cat "$BACKUP_DIR/previous-sha.txt" 2>/dev/null || true)"
    if [[ -z "$restored" ]]; then
      echo "INFO: No previous SHA marker recorded (first deploy or hand-placed theme)" >&2
    elif [[ ! -f "$LIVE_THEME/.nvx-deploy-sha" ]]; then
      echo "ERROR: previous SHA recorded but restored theme has no marker" >&2
      rollback_ok=0
    else
      actual="$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")"
      if [[ "$actual" != "$restored" ]]; then
        echo "ERROR: rollback marker $actual != expected $restored" >&2
        rollback_ok=0
      fi
    fi

    if [[ "$rollback_ok" -eq 1 ]]; then
      echo "ROLLBACK_PRODUCTION=PASS scope=theme+db" >&2
      ROLLBACK_OK=1
    else
      echo "ROLLBACK_PRODUCTION=FAIL scope=theme+db" >&2
      ROLLBACK_OK=0
    fi
  fi

  if [[ "$rc" -eq 0 ]]; then
    rc=1
  fi
  if [[ "$rollback_ok" -ne 1 ]]; then
    rc=1
  fi
  # Reset SWAPPED=0 after rollback so EXIT trap cleans up RELEASE_ROOT
  SWAPPED=0
  exit "$rc"
}
# Arm on ERR and on interruption signals so a dropped SSH session or SIGTERM/
# SIGHUP during the (now longer) post-cutover window still restores the theme
# and, if the migration had started, the database.
trap rollback_after_swap ERR
trap rollback_on_int INT
trap rollback_on_term TERM
trap rollback_on_hup HUP

echo "== Pre-cutover content audit (read-only) =="
# Run the audit in read-only mode before cutover to fail fast on content issues
# that would otherwise trigger a rollback after the theme swap. This prevents
# editorial changes (e.g. H1 text) from causing full rollbacks.
(
  trap - ERR
  cd "$PROD_ROOT"
  wp eval-file "$AUDIT_SCRIPT" --allow-root 2>&1 | tee "$BACKUP_DIR/pre-cutover-audit.log"
  grep -Fq 'Status: AUDIT_CLEAN' "$BACKUP_DIR/pre-cutover-audit.log"
)
echo 'PRE_CUTOVER_AUDIT=PASS'

echo "== Directory cutover =="
# Track intermediate state to handle the critical no-theme window. Set
# PREVIOUS_MOVED=1 BEFORE the first mv so an interruption landing between the
# move and the bookkeeping still lets the handler restore the previous theme.
PREVIOUS_MOVED=1
mv "$LIVE_THEME" "$PREVIOUS_THEME"
mv "$STAGED_THEME" "$LIVE_THEME"
SWAPPED=1
PREVIOUS_MOVED=0

echo "== Verify exact production release on disk =="
(
  trap - ERR
  cd "$PROD_ROOT"
  [[ "$(tr -d '\r\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)" == "$SHA" ]]
  [[ "$(wp config get DB_NAME)" == 'db0ecrycwv2tgb' ]]
  [[ "$(wp option get home)" == "$PROD_URL" ]]
  [[ "$(wp option get siteurl)" == "$PROD_URL" ]]
  [[ "$(wp option get blog_public)" == '1' ]]
  [[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]]
)

echo "== Run shared production content migration and divergence audit =="
# Pass MIGRATION_WRITE_MARKER to the migration script so it can emit a durable
# marker after the first successful DB write. This allows rollback to distinguish
# between pre-write failures (no DB restore needed) and post-write failures.
(
  trap - ERR
  cd "$PROD_ROOT"
  MIGRATION_WRITE_MARKER="$MIGRATION_WRITE_MARKER" wp eval-file "$MIGRATION_SCRIPT" --allow-root 2>&1 | tee "$MIGRATION_LOG"
  grep -Fq 'Status: MIGRATION_OK' "$MIGRATION_LOG"
  wp eval-file "$AUDIT_SCRIPT" --allow-root 2>&1 | tee "$AUDIT_LOG"
  grep -Fq 'Status: AUDIT_CLEAN' "$AUDIT_LOG"
)
echo 'PRODUCTION_CONTENT_MIGRATION=PASS audit=clean'
echo 'SHARED_MIGRATION=PASS audit=clean'

echo "== Purge production caches =="
# Cache purge is cosmetic and runs after the DB migration + audit have already
# passed. It must never trigger a database rollback, so keep it non-fatal.
# However, Speed Optimizer restoration failures are serious - they change the
# production plugin state and must be treated as a deployment failure.
purge_rc=0
(
  trap - ERR
  cd "$PROD_ROOT"
  inner_rc=0
  wp cache flush || inner_rc=$?
  # purge_siteground_dynamic_cache returns distinct codes:
  # - 0: success
  # - 10: plugin restoration failure (plugin remained active) - fatal
  # - other non-zero: purge failure - non-fatal
  purge_siteground_dynamic_cache || inner_rc=$?
  rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
  rm -rf wp-content/cache/sgo-cache/* wp-content/cache/* 2>/dev/null || true
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
  exit "$inner_rc"
) || purge_rc=$?

# Check for plugin restoration failure (exit code 10)
if [[ "$purge_rc" -eq 10 ]]; then
  echo "ERROR: Speed Optimizer plugin restoration failed - this changes production state" >&2
  exit 1
fi
[[ "$purge_rc" -eq 0 ]] || echo "WARN: production cache purge reported a non-fatal error rc=$purge_rc" >&2

# Re-verify SHA after migration/audit to catch any on-disk mutations
[[ "$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")" == "$SHA" ]]

trap - ERR INT TERM HUP
# All checks passed. Remove the migration write marker to finalize the deployment.
# From this point, the DB changes are committed and no rollback should restore them.
rm -f "$MIGRATION_WRITE_MARKER" 2>/dev/null || true
rm -rf "$PREVIOUS_THEME" "$RELEASE_ROOT"
SWAPPED=0
trap - EXIT

echo "DEPLOY_PRODUCTION_OK sha=$SHA backup=$BACKUP_DIR"