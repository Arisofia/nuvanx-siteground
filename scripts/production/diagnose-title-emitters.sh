#!/usr/bin/env bash
set -Eeuo pipefail

: "${PROD_ROOT:?Missing PROD_ROOT}"
BASE_URL="${BASE_URL:-https://nuvanx.com}"
BASE_URL="${BASE_URL%/}"
cd "$PROD_ROOT"

printf '%s\n' '=== NUVANX TITLE EMITTER DIAGNOSTIC ==='
printf 'PROD_ROOT=%s\n' "$PROD_ROOT"
printf 'BASE_URL=%s\n' "$BASE_URL"
printf 'DEPLOY_SHA=%s\n' "$(tr -d '\r\n[:space:]' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)"
printf 'ACTIVE_THEME=%s\n' "$(wp theme list --status=active --field=name)"
printf 'TITLE_TAG_SUPPORT='; wp eval 'echo current_theme_supports("title-tag") ? "yes\n" : "no\n";'
printf 'CORE_TITLE_CALLBACK_PRIORITY='; wp eval '$p=has_action("wp_head","_wp_render_title_tag"); echo false === $p ? "absent\n" : $p."\n";'

printf '%s\n' '--- RAW TITLES: HOME ---'
curl -fsSL --max-time 30 -A 'NUVANX-Title-Diagnostic/1.0' "$BASE_URL/" | php -r '$h=stream_get_contents(STDIN); preg_match_all("~<title\\b[^>]*>.*?</title>~is",$h,$m); echo "count=".count($m[0])."\n"; foreach($m[0] as $i=>$t){echo ($i+1)."=".trim(preg_replace("~\\s+~u"," ",$t))."\n";}'

printf '%s\n' '--- RAW TITLES: SIGNATURE HUB ---'
curl -fsSL --max-time 30 -A 'NUVANX-Title-Diagnostic/1.0' "$BASE_URL/remodelacion-corporal-laser-madrid/" | php -r '$h=stream_get_contents(STDIN); preg_match_all("~<title\\b[^>]*>.*?</title>~is",$h,$m); echo "count=".count($m[0])."\n"; foreach($m[0] as $i=>$t){echo ($i+1)."=".trim(preg_replace("~\\s+~u"," ",$t))."\n";}'

printf '%s\n' '--- ACTIVE PLUGINS ---'
wp plugin list --status=active --fields=name,status,version --format=json

printf '%s\n' '--- MU PLUGINS ---'
wp plugin list --status=must-use --fields=name,status,version --format=json

printf '%s\n' '--- TITLE/HEAD HOOK CALLBACKS ---'
wp eval '
function nvx_diag_callable_label($cb) {
    if (is_string($cb)) return $cb;
    if (is_array($cb) && count($cb) >= 2) {
        $owner = is_object($cb[0]) ? get_class($cb[0]) : (string) $cb[0];
        return $owner . "::" . (string) $cb[1];
    }
    if ($cb instanceof Closure) {
        $r = new ReflectionFunction($cb);
        return "Closure@" . $r->getFileName() . ":" . $r->getStartLine();
    }
    if (is_object($cb) && method_exists($cb, "__invoke")) return get_class($cb) . "::__invoke";
    return gettype($cb);
}
global $wp_filter;
$hooks = ["wp_head","pre_get_document_title","document_title_parts","wpseo_title","wpseo_frontend_presentation"];
foreach ($hooks as $hook) {
    echo "HOOK=" . $hook . "\n";
    if (!isset($wp_filter[$hook]) || !($wp_filter[$hook] instanceof WP_Hook)) {
        echo "  (none)\n";
        continue;
    }
    foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $entry) {
            $label = nvx_diag_callable_label($entry["function"] ?? null);
            if ($hook !== "wp_head" || preg_match("/(title|seo|yoast|head|document|wp_render)/i", $label)) {
                echo "  priority=" . $priority . " callback=" . $label . "\n";
            }
        }
    }
}
'

printf '%s\n' '--- ALL WP_HEAD CALLBACKS (ORDERED) ---'
wp eval '
function nvx_diag_all_label($cb) {
    if (is_string($cb)) return $cb;
    if (is_array($cb) && count($cb) >= 2) return (is_object($cb[0]) ? get_class($cb[0]) : (string)$cb[0]) . "::" . (string)$cb[1];
    if ($cb instanceof Closure) { $r=new ReflectionFunction($cb); return "Closure@".$r->getFileName().":".$r->getStartLine(); }
    if (is_object($cb) && method_exists($cb,"__invoke")) return get_class($cb)."::__invoke";
    return gettype($cb);
}
global $wp_filter;
if (isset($wp_filter["wp_head"]) && $wp_filter["wp_head"] instanceof WP_Hook) {
    foreach ($wp_filter["wp_head"]->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $entry) echo "priority=".$priority." callback=".nvx_diag_all_label($entry["function"] ?? null)."\n";
    }
}
'

printf '%s\n' '=== END TITLE EMITTER DIAGNOSTIC ==='

# -----------------------------------------------------------------------------
# Post-cutover compensation.
#
# production.yml invokes this script only when the blocking origin audit fails.
# deploy-to-prod.sh has already committed its internal transaction at that point,
# so restore the external pre-release snapshot rather than relying on its traps.
# The database is restored only when the migration log proves the release wrote
# content, preventing unrelated leads/submissions from being discarded when the
# release was code-only.
# -----------------------------------------------------------------------------

printf '%s\n' '=== NUVANX POST-CUTOVER COMPENSATING ROLLBACK ==='

LIVE_THEME="$PROD_ROOT/wp-content/themes/nuvanx-medical"
PROD_PARENT="${PROD_ROOT%/public_html}"
BACKUP_ROOT="$PROD_PARENT/.nvx-backups"

[[ "$PROD_ROOT" == '/home/customer/www/nuvanx.com/public_html' ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=unexpected_prod_root root=$PROD_ROOT" >&2
  exit 2
}
[[ "$PROD_PARENT" == '/home/customer/www/nuvanx.com' ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=unexpected_prod_parent parent=$PROD_PARENT" >&2
  exit 2
}
[[ -d "$BACKUP_ROOT" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=backup_root_missing root=$BACKUP_ROOT" >&2
  exit 2
}
[[ -f "$LIVE_THEME/.nvx-deploy-sha" ]] || {
  echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=live_sha_missing' >&2
  exit 2
}

CURRENT_SHA="$(tr -d '\r\n[:space:]' < "$LIVE_THEME/.nvx-deploy-sha")"
[[ "$CURRENT_SHA" =~ ^[0-9a-f]{40}$ ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=invalid_current_sha sha=$CURRENT_SHA" >&2
  exit 2
}
SHORT_SHA="${CURRENT_SHA:0:12}"

BACKUP_DIR="$(find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -name "pre-prod-*-${SHORT_SHA}" -printf '%T@ %p\n' 2>/dev/null | sort -nr | head -n 1 | cut -d' ' -f2-)"
[[ -n "$BACKUP_DIR" && "$BACKUP_DIR" == "$BACKUP_ROOT/pre-prod-"* ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=matching_snapshot_not_found current_sha=$CURRENT_SHA" >&2
  exit 2
}
[[ -s "$BACKUP_DIR/theme.tgz" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=theme_snapshot_missing backup=$BACKUP_DIR" >&2
  exit 2
}
[[ -s "$BACKUP_DIR/db.sql" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=db_snapshot_missing backup=$BACKUP_DIR" >&2
  exit 2
}
[[ -f "$BACKUP_DIR/previous-sha.txt" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=previous_sha_missing backup=$BACKUP_DIR" >&2
  exit 2
}

PREVIOUS_SHA="$(tr -d '\r\n[:space:]' < "$BACKUP_DIR/previous-sha.txt")"
[[ "$PREVIOUS_SHA" =~ ^[0-9a-f]{40}$ ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=invalid_previous_sha sha=$PREVIOUS_SHA backup=$BACKUP_DIR" >&2
  exit 2
}
[[ "$PREVIOUS_SHA" != "$CURRENT_SHA" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=snapshot_not_previous sha=$PREVIOUS_SHA backup=$BACKUP_DIR" >&2
  exit 2
}

echo "PRODUCTION_COMPENSATING_ROLLBACK=ARMED current_sha=$CURRENT_SHA previous_sha=$PREVIOUS_SHA backup=$BACKUP_DIR"

ROLLBACK_TMP="$BACKUP_ROOT/.compensating-${CURRENT_SHA:0:12}-$$"
FAILED_THEME="$PROD_ROOT/wp-content/themes/.nuvanx-failed-post-audit-${CURRENT_SHA:0:12}-$$"
rm -rf "$ROLLBACK_TMP" "$FAILED_THEME"
mkdir -p "$ROLLBACK_TMP"
tar -xzf "$BACKUP_DIR/theme.tgz" -C "$ROLLBACK_TMP"
RESTORED_THEME="$ROLLBACK_TMP/wp-content/themes/nuvanx-medical"

[[ -d "$RESTORED_THEME" && -f "$RESTORED_THEME/.nvx-deploy-sha" ]] || {
  echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=restored_theme_invalid' >&2
  rm -rf "$ROLLBACK_TMP"
  exit 2
}
RESTORED_DISK_SHA="$(tr -d '\r\n[:space:]' < "$RESTORED_THEME/.nvx-deploy-sha")"
[[ "$RESTORED_DISK_SHA" == "$PREVIOUS_SHA" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=snapshot_sha_mismatch actual=$RESTORED_DISK_SHA expected=$PREVIOUS_SHA" >&2
  rm -rf "$ROLLBACK_TMP"
  exit 2
}

mv "$LIVE_THEME" "$FAILED_THEME"
if ! mv "$RESTORED_THEME" "$LIVE_THEME"; then
  echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=theme_restore_move_failed' >&2
  rm -rf "$LIVE_THEME" 2>/dev/null || true
  mv "$FAILED_THEME" "$LIVE_THEME" 2>/dev/null || true
  rm -rf "$ROLLBACK_TMP"
  exit 2
fi

DB_ROLLBACK='skipped-no-release-db-writes'
MIGRATION_LOG="$BACKUP_DIR/migration-production.log"
if [[ -f "$MIGRATION_LOG" ]] && grep -Fq 'MIGRATION_WRITE_MARKER_CREATED' "$MIGRATION_LOG"; then
  echo 'PRODUCTION_COMPENSATING_ROLLBACK_DB=RESTORE reason=release-migration-wrote-database'
  if ! ( cd "$PROD_ROOT" && wp db import "$BACKUP_DIR/db.sql" --allow-root ); then
    echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=db_restore_failed' >&2
    exit 2
  fi
  DB_ROLLBACK='restored-release-snapshot'
else
  echo 'PRODUCTION_COMPENSATING_ROLLBACK_DB=SKIP reason=no-release-db-write-marker'
fi

cd "$PROD_ROOT"
wp cache flush
if wp plugin is-installed sg-cachepress >/dev/null 2>&1; then
  sg_was_active=0
  if wp plugin is-active sg-cachepress >/dev/null 2>&1; then
    sg_was_active=1
  else
    wp plugin activate sg-cachepress --quiet
  fi
  wp sg purge || true
  if [[ "$sg_was_active" -eq 0 ]]; then
    wp plugin deactivate sg-cachepress --quiet || true
  fi
fi
rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache/* wp-content/cache/* 2>/dev/null || true
wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); }' || true

[[ "$(wp config get DB_NAME)" == 'db0ecrycwv2tgb' ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=db_identity' >&2; exit 2; }
[[ "$(wp option get home)" == "$BASE_URL" ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=home_identity' >&2; exit 2; }
[[ "$(wp option get siteurl)" == "$BASE_URL" ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=siteurl_identity' >&2; exit 2; }
[[ "$(wp option get blog_public)" == '1' ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=blog_public_identity' >&2; exit 2; }
[[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=theme_identity' >&2; exit 2; }
[[ "$(tr -d '\r\n[:space:]' < "$LIVE_THEME/.nvx-deploy-sha")" == "$PREVIOUS_SHA" ]] || { echo 'PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=restored_disk_sha' >&2; exit 2; }

PUBLIC_SHA=''
for attempt in {1..12}; do
  set +e
  PUBLIC_SHA="$(curl -fsSL --max-time 30 -A 'NUVANX-Compensating-Rollback/1.0' -H 'Cache-Control: no-cache' -H 'Pragma: no-cache' "$BASE_URL/" | php -r '$h=stream_get_contents(STDIN); if (preg_match("~<meta[^>]+name=[\"\x27]nvx-deploy-sha[\"\x27][^>]+content=[\"\x27]([0-9a-f]{40})[\"\x27]~i",$h,$m)) echo $m[1];')"
  public_rc=$?
  set -e
  if [[ "$public_rc" -eq 0 && "$PUBLIC_SHA" == "$PREVIOUS_SHA" ]]; then
    break
  fi
  sleep 5
done
[[ "$PUBLIC_SHA" == "$PREVIOUS_SHA" ]] || {
  echo "PRODUCTION_COMPENSATING_ROLLBACK=FAIL reason=public_sha_not_restored expected=$PREVIOUS_SHA actual=${PUBLIC_SHA:-missing}" >&2
  exit 2
}

rm -rf "$FAILED_THEME" "$ROLLBACK_TMP"
echo "PRODUCTION_COMPENSATING_ROLLBACK=PASS restored_sha=$PREVIOUS_SHA failed_sha=$CURRENT_SHA db=$DB_ROLLBACK backup=$BACKUP_DIR"
