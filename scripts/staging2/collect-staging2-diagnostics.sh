#!/usr/bin/env bash
# READ-ONLY: collect staging2 deployment preflight diagnostics.
set -Eeuo pipefail

EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'
EXPECTED_URL='https://staging2.nuvanx.com'
WP_ROOT=''

usage() {
  cat >&2 <<'EOF'
Usage:
  collect-staging2-diagnostics.sh \
    --wp-root /home/customer/www/staging2.nuvanx.com/public_html
EOF
}

fail() {
  echo "DIAGNOSTIC_ERROR: $*" >&2
  return 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="${2:-}"; shift 2 ;;
    *) usage; fail "unknown argument: $1" ;;
  esac
done

[[ "$WP_ROOT" == "$EXPECTED_ROOT" ]] || fail "refusing unexpected WordPress root: $WP_ROOT"

for command_name in bash curl df php rsync tar wp; do
  if command -v "$command_name" >/dev/null 2>&1; then
    echo "command.$command_name=available"
  else
    echo "command.$command_name=missing"
  fi
done

[[ -d "$WP_ROOT" ]] || fail "WordPress root does not exist: $WP_ROOT"
[[ -f "$WP_ROOT/wp-config.php" ]] || fail "wp-config.php is missing: $WP_ROOT/wp-config.php"

BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'
BACKUP_PARENT='/home/customer/backups-nuvanx'
DEPLOYMENT_ROOT="$WP_ROOT/wp-content/.nuvanx-deployments"
DEPLOYMENT_PARENT="$WP_ROOT/wp-content"
THEME_ROOT="$WP_ROOT/wp-content/themes/nuvanx-medical"

echo "diagnostic.timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "diagnostic.hostname=$(hostname)"
echo "diagnostic.user=$(id -un)"
echo "diagnostic.wp_root=$WP_ROOT"
echo "diagnostic.backup_root=$BACKUP_ROOT"
echo "diagnostic.deployment_root=$DEPLOYMENT_ROOT"
echo "diagnostic.theme_root=$THEME_ROOT"

printf 'runtime.php='; php -r 'echo PHP_VERSION, "\n";'
printf 'runtime.wp_cli='; wp --info 2>/dev/null | awk -F': ' '/WP-CLI version/{print $2; found=1} END{if(!found) print "unknown"}'
printf 'runtime.rsync='; rsync --version | awk 'NR==1{print $3}'
printf 'runtime.disk='; df -Pk "$WP_ROOT" | awk 'NR==2{print $4 "KB_available"}'

for path_name in "$WP_ROOT" "$WP_ROOT/wp-content" "$THEME_ROOT"; do
  [[ -r "$path_name" ]] || fail "path is not readable: $path_name"
  [[ -x "$path_name" ]] || fail "path is not traversable: $path_name"
  echo "path.readable.$path_name=yes"
done

if [[ -d "$BACKUP_ROOT" ]]; then
  [[ -w "$BACKUP_ROOT" ]] || fail "backup root is not writable: $BACKUP_ROOT"
  echo 'path.backup_root_status=existing_writable'
else
  [[ -d "$BACKUP_PARENT" ]] || fail "backup parent does not exist: $BACKUP_PARENT"
  [[ -w "$BACKUP_PARENT" ]] || fail "backup parent cannot create the staging2 directory: $BACKUP_PARENT"
  echo 'path.backup_root_status=absent_parent_writable'
fi

if [[ -d "$DEPLOYMENT_ROOT" ]]; then
  [[ -w "$DEPLOYMENT_ROOT" ]] || fail "deployment root is not writable: $DEPLOYMENT_ROOT"
  echo 'path.deployment_root_status=existing_writable'
else
  [[ -w "$DEPLOYMENT_PARENT" ]] || fail "deployment parent cannot create the release directory: $DEPLOYMENT_PARENT"
  echo 'path.deployment_root_status=absent_parent_writable'
fi

(
  cd "$WP_ROOT"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  active_theme="$(wp theme list --status=active --field=name | head -n 1)"
  trash_days="$(wp eval 'echo defined("EMPTY_TRASH_DAYS") ? (int) EMPTY_TRASH_DAYS : -1;')"
  maintenance="$(wp maintenance-mode status 2>/dev/null || true)"
  db_check="$(wp db check 2>&1 | tail -n 1)"

  echo "wordpress.siteurl=$siteurl"
  echo "wordpress.home=$home"
  echo "wordpress.active_theme=$active_theme"
  echo "wordpress.empty_trash_days=$trash_days"
  echo "wordpress.maintenance=$maintenance"
  echo "wordpress.db_check=$db_check"

  [[ "$siteurl" == "$EXPECTED_URL" ]] || fail "unexpected siteurl: $siteurl"
  [[ "$home" == "$EXPECTED_URL" ]] || fail "unexpected home URL: $home"
  [[ "$active_theme" == 'nuvanx-medical' ]] || fail "unexpected active theme: $active_theme"
  [[ "$trash_days" =~ ^[0-9]+$ && "$trash_days" -ge 1 ]] || fail "WordPress trash is disabled or invalid: $trash_days"
)

if [[ -f "$THEME_ROOT/.nvx-deploy-sha" ]]; then
  echo "deployment.current_marker=$(tr -d '\r\n' < "$THEME_ROOT/.nvx-deploy-sha")"
else
  echo 'deployment.current_marker=absent'
fi

curl_status="$(curl --silent --show-error --connect-timeout 15 --max-time 30 --output /dev/null --write-out '%{http_code}' "$EXPECTED_URL/")"
echo "http.home_status=$curl_status"
[[ "$curl_status" == '200' ]] || fail "staging2 home returned HTTP $curl_status"

echo 'STAGING2_PREFLIGHT_OK'
