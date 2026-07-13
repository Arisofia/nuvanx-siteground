#!/bin/bash
cd /home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html/wp-content/themes/nuvanx-medical || exit 1

CSS_FILE="style.css"

cat << 'EOF' >> "$CSS_FILE"

/* Phase 9C: Contact Page Hardening & Overflow Fix */
body {
  overflow-x: clip; /* Protect entire body from WhatsApp absolute elements */
}

.nvx-p1b {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  margin-left: 0 !important;
  margin-right: 0 !important;
  padding-left: 1rem;
  padding-right: 1rem;
  overflow-x: hidden;
}

.joinchat__powered {
  max-width: 100vw; /* Prevent absolute widget from forcing overflow */
}

.nvx-contact-page,
.nvx-contact-page * {
  box-sizing: border-box;
}

.nvx-contact-page {
  width: 100%;
  max-width: 100%;
  overflow-x: clip;
}

.nvx-contact-page img,
.nvx-contact-page iframe,
.nvx-contact-page embed,
.nvx-contact-page object {
  max-width: 100%;
}

.nvx-contact-page iframe {
  width: 100%;
  display: block;
  border: 0;
}

.nvx-contact-grid,
.nvx-contact-card,
.nvx-contact-form,
.nvx-contact-map,
.nvx-contact-details {
  min-width: 0;
  max-width: 100%;
}

.nvx-contact-page .hbspt-form,
.nvx-contact-page form,
.nvx-contact-page .hs-form,
.nvx-contact-page .hs-form-field {
  max-width: 100%;
}

@media (max-width: 767px) {
  .nvx-contact-page {
    overflow-x: clip;
  }

  .nvx-contact-grid {
    grid-template-columns: 1fr;
  }

  .nvx-contact-card,
  .nvx-contact-form,
  .nvx-contact-map {
    width: 100%;
    max-width: 100%;
  }
}
EOF

echo "CSS injected into style.css"

# Flush cache if wp-cli is available here
wp cache flush || true

