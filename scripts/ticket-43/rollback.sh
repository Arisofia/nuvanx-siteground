#!/usr/bin/env bash
# Emergency rollback for Ticket 43 staging deploy.
# Restores post_content and CSS from a backup directory created during deploy.
set -Eeuo pipefail

BACKUP_DIR="${NUVX_BACKUP_DIR:-}"
STAGING_PATH="${NUVX_STAGING_PATH:-$(pwd)}"
POST_ID="${NUVX_POST_ID:-9}"

if [[ -z "$BACKUP_DIR" || ! -d "$BACKUP_DIR" ]]; then
	echo "ERROR: NUVX_BACKUP_DIR must point to an existing backup directory." >&2
	exit 1
fi

if ! command -v wp >/dev/null 2>&1; then
	echo "ERROR: wp-cli is required." >&2
	exit 1
fi

cd "$STAGING_PATH"

SITEURL="$(wp option get siteurl 2>/dev/null || true)"
if [[ "$SITEURL" != *"staging2.nuvanx.com"* ]]; then
	echo "ERROR: Refusing rollback outside staging2 (siteurl=$SITEURL)." >&2
	exit 1
fi

THEME_CSS="${STAGING_PATH}/wp-content/themes/nuvanx-medical/assets/css"

if [[ -s "${BACKUP_DIR}/post_content_before.html" ]]; then
	wp post update "$POST_ID" "${BACKUP_DIR}/post_content_before.html"
	echo "Restored post_content for post ${POST_ID}."
else
	echo "WARNING: ${BACKUP_DIR}/post_content_before.html missing or empty." >&2
fi

if [[ -f "${BACKUP_DIR}/nvx-brand-home.css" ]]; then
	cp "${BACKUP_DIR}/nvx-brand-home.css" "${THEME_CSS}/"
	echo "Restored nvx-brand-home.css."
fi

if [[ -f "${BACKUP_DIR}/nvx-brand-home.min.css" ]]; then
	cp "${BACKUP_DIR}/nvx-brand-home.min.css" "${THEME_CSS}/"
	echo "Restored nvx-brand-home.min.css."
fi

wp cache flush >/dev/null 2>&1 || true
wp sg purge >/dev/null 2>&1 || true

echo "Rollback completed from ${BACKUP_DIR}"