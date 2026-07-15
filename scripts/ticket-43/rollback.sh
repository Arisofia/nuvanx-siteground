#!/usr/bin/env bash
# Emergency rollback for Ticket 43 staging deployment.
# Restores page 9 and every CSS asset deployed by the editorial candidate.
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
HOMEURL="$(wp option get home 2>/dev/null || true)"
if [[ "$SITEURL" != "https://staging2.nuvanx.com" || "$HOMEURL" != "https://staging2.nuvanx.com" ]]; then
	echo "ERROR: Refusing rollback outside staging2 (siteurl=$SITEURL, home=$HOMEURL)." >&2
	exit 1
fi

THEME_CSS="${STAGING_PATH}/wp-content/themes/nuvanx-medical/assets/css"
CSS_FILES=(
	"nvx-tokens.css"
	"nvx-tokens.min.css"
	"nvx-components.css"
	"nvx-components.min.css"
	"nvx-brand-home.css"
	"nvx-brand-home.min.css"
)

if [[ ! -s "${BACKUP_DIR}/post_content_before.html" ]]; then
	echo "ERROR: ${BACKUP_DIR}/post_content_before.html missing or empty." >&2
	exit 1
fi

wp post update "$POST_ID" "${BACKUP_DIR}/post_content_before.html"
echo "Restored post_content for post ${POST_ID}."

for file in "${CSS_FILES[@]}"; do
	if [[ ! -f "${BACKUP_DIR}/${file}" ]]; then
		echo "ERROR: Missing rollback asset ${BACKUP_DIR}/${file}." >&2
		exit 1
	fi
	cp "${BACKUP_DIR}/${file}" "${THEME_CSS}/${file}"
	echo "Restored ${file}."
done

if [[ -f "${BACKUP_DIR}/metadata.sha256" ]]; then
	(
		cd "$BACKUP_DIR"
		sha256sum -c metadata.sha256
	)
fi

echo "Rollback completed from ${BACKUP_DIR}. No cache purge executed."
