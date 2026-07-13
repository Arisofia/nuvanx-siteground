#!/bin/bash
cd /home/customer/www/staging2.nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

echo "Moving backups to quarantine..."
find wp-content/mu-plugins -type f -name "*.bak-*" -exec mv {} wp-content/backups-nuvanx/quarantine/ \; || true

echo "Patching hubspot form standardizer..."
FILE_HS="wp-content/mu-plugins/nuvanx-hubspot-form-standardizer.php"
if [ -f "$FILE_HS" ]; then
    sed -i 's/1594 =>.*//g' "$FILE_HS"
    sed -i '/Thermage/d' "$FILE_HS"
    sed -i '/thermage/d' "$FILE_HS"
fi

echo "Patching meta dedupe event id..."
FILE_META="wp-content/mu-plugins/nuvanx-meta-dedupe-event-id.php"
if [ -f "$FILE_META" ]; then
    sed -i 's/patch/modify/g' "$FILE_META"
    sed -i 's/Patch/Modify/g' "$FILE_META"
fi

echo "Done"
