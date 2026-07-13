cd /home/customer/www/nuvanx.com/public_html || exit 1

set -u

THEME="wp-content/themes/nuvanx-medical"
PLUGIN="wp-content/plugins/nuvanx-fix/nuvanx-fix.php"
STAMP="$(date +%Y%m%d-%H%M%S)"
REPORT="$HOME/nuvanx-audits/nuvanx-fix-$STAMP"

mkdir -p "$REPORT/html"

{
    echo "=================================================="
    echo "1. ESTADO DEL PLUGIN"
    echo "=================================================="

    wp plugin status nuvanx-fix 2>&1 || true

    echo
    echo "Archivo:"
    ls -lh "$PLUGIN" 2>/dev/null || true

    echo
    echo "Contenido:"
    sed -n '1,240p' "$PLUGIN" 2>/dev/null || true

    echo
    echo "=================================================="
    echo "2. FOOTER CANÓNICO DEL TEMA ACTIVO"
    echo "=================================================="

    grep -nA25 -B20 \
      "nvx-footer__legal-nav" \
      "$THEME/footer.php" \
      2>/dev/null || true

    echo
    echo "Enlaces legales encontrados en footer.php:"

    grep -nE \
      'politica-privacidad|politica-de-privacidad|aviso-legal|politica-de-cookies' \
      "$THEME/footer.php" \
      2>/dev/null || true

    echo
    echo "=================================================="
    echo "3. REDIRECCIONES DE PRIVACIDAD EN CÓDIGO ACTIVO"
    echo "=================================================="

    grep -RniE \
      --include="*.php" \
      'politica-de-privacidad|politica-privacidad|wp_safe_redirect|wp_redirect|template_redirect' \
      "$THEME" \
      wp-content/mu-plugins \
      wp-content/plugins/nuvanx-fix \
      2>/dev/null || true

    echo
    echo "=================================================="
    echo "4. AVISOS DE PRIVACIDAD EN CÓDIGO ACTIVO"
    echo "=================================================="

    grep -RniE \
      --include="*.php" \
      --include="*.js" \
      --include="*.css" \
      'nvx-form-privacy-disclaimer|has-privacy-injected|Al facilitar tus datos|Política de privacidad|consentimiento|consent' \
      "$THEME" \
      wp-content/mu-plugins \
      wp-content/plugins/nuvanx-fix \
      2>/dev/null || true

    echo
    echo "=================================================="
    echo "5. CODE SNIPPETS RELACIONADOS"
    echo "=================================================="

    PREFIX="$(wp db prefix)"
    SNIPPETS_TABLE="${PREFIX}snippets"

    TABLE_FOUND="$(
      wp db query \
        "SHOW TABLES LIKE '${SNIPPETS_TABLE}';" \
        --skip-column-names \
        2>/dev/null || true
    )"

    if [ "$TABLE_FOUND" = "$SNIPPETS_TABLE" ]; then
        wp db query "
        SELECT
            id,
            name,
            active,
            CASE
                WHEN code LIKE '%politica-de-privacidad%'
                THEN 1 ELSE 0
            END AS redirect_privacidad,
            CASE
                WHEN code LIKE '%nvx-form-privacy-disclaimer%'
                THEN 1 ELSE 0
            END AS aviso_formulario,
            CASE
                WHEN code LIKE '%nvx-footer__legal-nav%'
                THEN 1 ELSE 0
            END AS footer_legal
        FROM ${SNIPPETS_TABLE}
        WHERE
            code LIKE '%politica-de-privacidad%'
            OR code LIKE '%politica-privacidad%'
            OR code LIKE '%nvx-form-privacy-disclaimer%'
            OR code LIKE '%nvx-footer__legal-nav%'
        ORDER BY id;
        " --skip-column-names 2>/dev/null || true
    else
        echo "No se encontró la tabla $SNIPPETS_TABLE"
    fi

    echo
    echo "=================================================="
    echo "6. CADENA DE REDIRECCIÓN PÚBLICA"
    echo "=================================================="

    curl -sSIL \
      --max-redirs 5 \
      "https://nuvanx.com/politica-de-privacidad/?redirect_audit=$STAMP" |
    grep -iE \
      '^HTTP/|^location:|^x-redirect-by:' \
      || true

    echo
    echo "Destino canónico:"

    curl -LsS \
      -o /dev/null \
      -w "HTTP %{http_code} · %{url_effective}\n" \
      "https://nuvanx.com/politica-de-privacidad/?redirect_audit=$STAMP"

    echo
    echo "=================================================="
    echo "7. FOOTER PÚBLICO"
    echo "=================================================="

    curl -Ls \
      "https://nuvanx.com/?footer_audit=$STAMP" \
      > "$REPORT/html/home.html"

    python3 - "$REPORT/html/home.html" <<'PY'
from pathlib import Path
import html
import re
import sys

document = Path(sys.argv[1]).read_text(
    encoding="utf-8",
    errors="ignore",
)

match = re.search(
    r'<nav\b[^>]*class=["\'][^"\']*'
    r'nvx-footer__legal-nav[^"\']*["\'][^>]*>'
    r'(.*?)</nav>',
    document,
    flags=re.I | re.S,
)

if not match:
    print("NO SE ENCONTRÓ .nvx-footer__legal-nav")
    raise SystemExit(0)

content = match.group(1)

links = re.findall(
    r'<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>'
    r'(.*?)</a>',
    content,
    flags=re.I | re.S,
)

for href, label in links:
    clean_label = re.sub(r'<[^>]+>', '', label)
    clean_label = html.unescape(clean_label).strip()
    print(f"{clean_label}\t{html.unescape(href)}")
PY

    echo
    echo "=================================================="
    echo "8. FORMULARIOS PÚBLICOS"
    echo "=================================================="

    for entry in \
      "home|https://nuvanx.com/" \
      "contacto|https://nuvanx.com/contacto/" \
      "valoracion|https://nuvanx.com/madrid/valoracion/"
    do
        name="${entry%%|*}"
        url="${entry#*|}"
        file="$REPORT/html/$name.html"

        curl -Ls \
          "${url}?form_audit=$STAMP" \
          > "$file"

        echo
        echo "----- $name · $url -----"

        python3 - "$file" <<'PY'
from pathlib import Path
import re
import sys

document = Path(sys.argv[1]).read_text(
    encoding="utf-8",
    errors="ignore",
)

checks = {
    "forms_html": r"<form\b",
    "iframes": r"<iframe\b",
    "hubspot": r"hubspot|hs-form|hbspt",
    "privacy_disclaimer": r"nvx-form-privacy-disclaimer",
    "privacy_text": r"Al facilitar tus datos",
    "consent_text": r"consentimiento|Política de privacidad",
}

for label, pattern in checks.items():
    count = len(
        re.findall(
            pattern,
            document,
            flags=re.I,
        )
    )
    print(f"{label}: {count}")
PY
    done

    echo
    echo "=================================================="
    echo "9. JS PÚBLICO DEL PLUGIN"
    echo "=================================================="

    grep -nE \
      'footerNav|privacyHTML|MutationObserver|has-privacy-injected' \
      "$REPORT/html/home.html" \
      "$REPORT/html/contacto.html" \
      "$REPORT/html/valoracion.html" \
      2>/dev/null || true

    echo
    echo "=================================================="
    echo "10. RESUMEN DE ARCHIVOS HUBSPOT"
    echo "=================================================="

    for file in \
      wp-content/mu-plugins/nuvanx-contacto-hubspot-form.php \
      wp-content/mu-plugins/zzzzzzzzzzzz-nuvanx-valoracion-native-hubspot-form.php \
      wp-content/mu-plugins/nuvanx-redirects.php
    do
        echo
        echo "----- $file -----"

        if [ -f "$file" ]; then
            grep -nE \
              'privacy|privacidad|consent|form|iframe|politica-de-privacidad|politica-privacidad|redirect' \
              "$file" \
              | head -120 \
              || true
        else
            echo "NO EXISTE"
        fi
    done

} | tee "$REPORT/audit.txt"

echo
echo "Informe:"
echo "$REPORT/audit.txt"
