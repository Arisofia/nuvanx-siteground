#!/bin/bash
cd /home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html/wp-content/themes/nuvanx-medical || exit 1

CSS_FILE="style.css"

cat << 'EOF' >> "$CSS_FILE"

/* Phase 9C Hardening Part 2 */
html, body {
  overflow-x: hidden !important;
  max-width: 100vw !important;
}

section.nvx-p1b {
  margin-right: 0 !important;
  margin-left: 0 !important;
  width: 100vw !important;
  max-width: 100% !important;
}
EOF

echo "CSS Part 2 injected"
wp cache flush || true
