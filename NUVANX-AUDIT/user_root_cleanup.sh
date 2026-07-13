#!/bin/bash
cd /home/u54-jiiuzkghob55/www/nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

echo "========================================="
echo "1. Backup único de este punto"
echo "========================================="
DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="wp-content/backups-nuvanx/root-cleanup-no-patches-$DATE"
mkdir -p "$BACKUP_DIR"

wp db export "$BACKUP_DIR/db-before-root-cleanup.sql"
tar -czf "$BACKUP_DIR/theme-before-root-cleanup.tgz" wp-content/themes/nuvanx-medical
tar -czf "$BACKUP_DIR/mu-before-root-cleanup.tgz" wp-content/mu-plugins

echo "========================================="
echo "2. Limpiar JSON-LD activo de Thermage / 1594"
echo "========================================="
python3 - <<'PY'
from pathlib import Path
import json
import re

path = Path("wp-content/mu-plugins/nuvanx-jsonld-data.json")

if not path.exists():
    raise SystemExit("NO GO: no existe nuvanx-jsonld-data.json")

raw = path.read_text(errors="ignore")
backup = path.with_suffix(".json.pre-root-cleanup.bak")
backup.write_text(raw)

def bad_text(value):
    if not isinstance(value, str):
        return False
    v = value.lower()
    return "thermage" in v or "1594" == v.strip() or "thermage-flx-radiofrecuencia-monopolar-madrid" in v

def clean_string(s):
    s = s.replace("Thermage FLX®", "")
    s = s.replace("Thermage FLX", "")
    s = s.replace("Thermage®", "")
    s = s.replace("Thermage", "")
    s = s.replace("thermage-flx-radiofrecuencia-monopolar-madrid", "")
    s = s.replace("Endolift, ,", "Endolift,")
    s = s.replace("Endolift®, ,", "Endolift®,")
    s = s.replace(", ,", ",")
    s = re.sub(r"\s+,", ",", s)
    s = re.sub(r",\s*,", ",", s)
    s = re.sub(r"\s{2,}", " ", s)
    return s.strip()

def clean_obj(obj):
    if isinstance(obj, dict):
        out = {}
        for k, v in obj.items():
            if str(k) == "1594":
                continue

            if isinstance(v, str) and bad_text(v):
                # Si es entidad/URL explícita Thermage, eliminar campo completo.
                if k in {"slug", "url", "@id", "mainEntityOfPage", "headline", "name", "serviceType"}:
                    continue
                v = clean_string(v)

            cleaned = clean_obj(v)
            if cleaned in (None, {}, []):
                continue
            out[k] = cleaned
        return out

    if isinstance(obj, list):
        out = []
        for item in obj:
            cleaned = clean_obj(item)
            if cleaned not in (None, {}, []):
                # si el item completo aún contiene thermage, no se conserva
                if "thermage" in json.dumps(cleaned, ensure_ascii=False).lower():
                    continue
                out.append(cleaned)
        return out

    if isinstance(obj, str):
        stripped = obj.strip()

        # Hay JSON embebido como string: limpiarlo también.
        if stripped.startswith("{") or stripped.startswith("["):
            try:
                parsed = json.loads(stripped)
                cleaned = clean_obj(parsed)
                dumped = json.dumps(cleaned, ensure_ascii=False, separators=(",", ":"))
                if "thermage" in dumped.lower() or "1594" in dumped:
                    return None
                return dumped
            except Exception:
                pass

        return clean_string(obj)

    return obj

data = json.loads(raw)
cleaned = clean_obj(data)
out = json.dumps(cleaned, ensure_ascii=False, indent=2)

if "thermage" in out.lower() or '"1594"' in out or "1594:" in out:
    raise SystemExit("NO GO: todavía queda Thermage/1594 después de limpiar JSON-LD")

path.write_text(out)
print("OK: JSON-LD activo limpio de Thermage/1594")
PY

grep -RInEi "Thermage|thermage|1594" wp-content/mu-plugins/nuvanx-jsonld-data.json || echo "OK JSON-LD limpio"

echo "========================================="
echo "3. Quitar el filtro global que borra !important"
echo "========================================="
python3 - <<'PY'
from pathlib import Path
import re

path = Path("wp-content/themes/nuvanx-medical/functions.php")
text = path.read_text(errors="ignore")
original = text
backup = path.with_suffix(".php.pre-remove-runtime-patches.bak")
backup.write_text(text)

# Eliminar funciones/hook que contengan el patrón exacto de strip !important.
# Si la función está completa entre "function ... { ... }", se elimina.
text = re.sub(
    r"\n\s*function\s+[a-zA-Z0-9_]*\s*\([^)]*\)\s*\{[^{}]*preg_replace\s*\(\s*['\"]\/\\s\*!important\\b\/i['\"][\s\S]*?\n\s*\}\s*",
    "\n",
    text,
    flags=re.M
)

# Eliminar hooks que apunten a funciones tipo strip important.
text = re.sub(
    r"\n\s*add_filter\s*\([^;]*(strip|important)[^;]*;\s*",
    "\n",
    text,
    flags=re.I
)

# Si quedó solo la línea exacta, bloquear NO GO.
if re.search(r"preg_replace\s*\(\s*['\"]\/\\s\*!important\\b\/i", text):
    raise SystemExit("NO GO: sigue existiendo filtro preg_replace de !important en functions.php")

path.write_text(text)

print("OK: filtro runtime de !important eliminado o no encontrado")
PY

php -l wp-content/themes/nuvanx-medical/functions.php

grep -RIn "\!important" wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null | grep -v "screen-reader-text" || echo "OK: no hay important indebido"
grep -RIn "preg_replace.*important" wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null || echo "OK: no hay strip important runtime"

echo "========================================="
echo "4. Eliminar CSS legacy y columnas falsas"
echo "========================================="
python3 - <<'PY'
from pathlib import Path
import re

css_dir = Path("wp-content/themes/nuvanx-medical/assets/css")

targets = [
    "nvx-home.css",
    "nvx-home.min.css",
    "nvx-site-layout.css",
    "nvx-site-layout.min.css",
    "nvx-pages.css",
    "nvx-pages.min.css",
    "nvx-gutenberg-pages.css",
    "nvx-gutenberg-pages.min.css",
    "nvx-visual-system.css",
    "nvx-visual-system.min.css",
    "nvx-forms.css",
    "nvx-forms.min.css",
]

def remove_blocks(text, token):
    # Elimina bloques CSS simples donde el selector contiene token.
    pattern = re.compile(r"[^{}]*" + re.escape(token) + r"[^{}]*\{[^{}]*\}", re.I | re.S)
    previous = None
    while previous != text:
        previous = text
        text = pattern.sub("", text)
    return text

def clean_css(text):
    # Eliminar bloques legacy explícitos.
    for token in [
        "nvx-thermage",
        "nvx-phase3c",
        "thermage",
        "Thermage",
    ]:
        text = remove_blocks(text, token)

    # Eliminar comentarios legacy.
    text = re.sub(r"/\*[\s\S]*?(Thermage|thermage|phase3c|legacy|Fallback)[\s\S]*?\*/", "", text, flags=re.I)

    # Corregir CSS roto por minificación previa.
    text = text.replace("solidvar(", "solid var(")
    text = text.replace("padding:16px0", "padding:16px 0")
    text = text.replace(".nvx-card--treatmentp:not", ".nvx-card--treatment p:not")
    text = text.replace("var(--nvx-accent, #b8956b)", "var(--nvx-accent)")

    # Liberar grids principales que estaban falsamente estrechos.
    text = re.sub(
        r"(\.nvx-(?:treatment|tech|method|director|clinic|positioning|locations|team|commercial-media|commercial-related)[^{]*\{[^{}]*?)max-width\s*:\s*72ch\s*;",
        r"\1max-width: var(--nvx-shell);",
        text,
        flags=re.I | re.S
    )

    # Leads muy estrechos: subir a ancho editorial amplio.
    text = re.sub(r"max-width\s*:\s*58ch\s*;", "max-width: min(860px, 100%);", text, flags=re.I)

    # Mantener 72ch solo para párrafos de lectura, no grids. Luego se valida.
    return text

for name in targets:
    path = css_dir / name
    if not path.exists():
        continue

    original = path.read_text(errors="ignore")
    text = clean_css(original)

    if text != original:
        path.with_suffix(path.suffix + ".pre-root-cleanup.bak").write_text(original)
        path.write_text(text)
        print(f"OK CSS limpiado: {path}")

# Reescribir layout final como fuente canónica, no parche acumulado.
layout_path = css_dir / "nvx-site-layout.css"
layout_original = layout_path.read_text(errors="ignore") if layout_path.exists() else ""
layout_clean = re.sub(
    r"/\* NUVANX layout system final · 2026-07-08[\s\S]*$",
    "",
    layout_original
)
layout_clean = re.sub(
    r"/\* NUVANX · Canonical layout alignment · 2026-07-08[\s\S]*$",
    "",
    layout_clean
).rstrip()

canonical = """
/* NUVANX layout system final · 2026-07-08
   Fuente única: anchura, alineación y grids.
   Sin !important. Sin legacy. Sin clases antiguas.
*/

:root {
  --nvx-layout-shell: min(1180px, calc(100% - 40px));
  --nvx-layout-content: min(860px, 100%);
  --nvx-layout-reading: min(72ch, 100%);
}

.nvx-shell,
.nvx-wrap,
.nvx-site-shell,
.nvx-page__shell,
.nvx-page-body__inner,
.nvx-page-hero__inner,
.nvx-contact-section,
.nvx-contact-hero,
.nvx-contact-grid,
.nvx-valoracion-form-section {
  width: var(--nvx-layout-shell);
  max-width: 100%;
  margin-inline: auto;
  box-sizing: border-box;
}

.nvx-page-hero,
.nvx-hero-section,
.nvx-text-center {
  text-align: center;
}

.nvx-page-hero h1,
.nvx-hero-section h1,
.nvx-commercial-title,
.nvx-med-hero h1,
.nvx-nosotros-hero h1,
.entry-title {
  max-width: min(100%, 1120px);
  width: 100%;
  margin-inline: auto;
  text-wrap: balance;
}

.nvx-page-hero p,
.nvx-hero-section p,
.nvx-lead,
.nvx-hero-subtitle,
.nvx-commercial-lead {
  max-width: var(--nvx-layout-content);
  margin-inline: auto;
}

.nvx-body,
.nvx-copy,
.entry-content p {
  max-width: var(--nvx-layout-reading);
}

.nvx-treatment-grid,
.nvx-tech-grid,
.nvx-method__grid,
.nvx-director__grid,
.nvx-clinic-grid,
.nvx-positioning-grid,
.nvx-locations-grid,
.nvx-team-grid,
.nvx-commercial-media-grid,
.nvx-commercial-related-grid,
.nvx-commercial-hero-grid,
.nvx-eeat-treatment-block {
  width: 100%;
  max-width: var(--nvx-layout-shell);
  margin-inline: auto;
}

.nvx-treatment-grid,
.nvx-tech-grid,
.nvx-clinic-grid,
.nvx-positioning-grid,
.nvx-locations-grid,
.nvx-team-grid,
.nvx-commercial-media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
  gap: clamp(20px, 3vw, 36px);
}

.nvx-page__cta,
.nvx-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.nvx-page-hero .nvx-page__cta,
.nvx-hero-section .nvx-page__cta,
.nvx-text-center .nvx-page__cta,
.nvx-text-center .nvx-actions {
  justify-content: center;
}

.nvx-pillars {
  gap: 12px;
}

@media (max-width: 767px) {
  :root {
    --nvx-layout-shell: min(100% - 28px, 1180px);
  }

  .nvx-page-hero h1,
  .nvx-hero-section h1,
  .nvx-commercial-title,
  .entry-title,
  .nvx-lead,
  .nvx-hero-subtitle,
  .nvx-body,
  .nvx-copy,
  .entry-content p {
    max-width: 100%;
  }

  .nvx-page__cta,
  .nvx-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .nvx-page__cta .nvx-btn,
  .nvx-actions .nvx-btn {
    width: 100%;
    justify-content: center;
  }
}
"""

layout_path.write_text(layout_clean + "\n\n" + canonical)

# Minificar fuentes después de limpiar.
def minify(css):
    css = re.sub(r"/\*[\s\S]*?\*/", "", css)
    css = re.sub(r"\s+", " ", css)
    css = re.sub(r"\s*([{}:;,>+~])\s*", r"\1", css)
    css = css.replace(";}", "}")
    return css.strip()

for src in css_dir.glob("*.css"):
    if src.name.endswith(".min.css"):
        continue
    min_path = src.with_name(src.stem + ".min.css")
    min_path.write_text(minify(src.read_text(errors="ignore")))
    print(f"OK minificado: {min_path}")

# Validación dura.
bad = []
for path in css_dir.glob("*.css"):
    content = path.read_text(errors="ignore")
    if re.search(r"Thermage|thermage|nvx-thermage|nvx-phase3c|solidvar|padding:16px0|\.nvx-card--treatmentp", content):
        bad.append(str(path))

if bad:
    raise SystemExit("NO GO CSS: quedan residuos en\n" + "\n".join(bad))

print("OK CSS limpio de Thermage/phase3c y errores conocidos")
PY

echo "========================================="
echo "5. Regenerar SiteGround, pero desde fuente limpia"
echo "========================================="
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-css-*.css || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-js-*.js || true

wp cache flush
wp sg purge
wp sg optimize combine-css disable || true
wp sg purge
wp sg optimize combine-css enable || true
wp sg purge

echo "========================================="
echo "6. Validación correcta de !important"
echo "========================================="
printf '%s\n' '== VALIDAR IMPORTANT =='
grep -RIn '\!important' wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null | grep -v 'screen-reader-text' || echo 'OK: no hay important fuera de screen-reader-text'

echo "========================================="
echo "7. Validación final"
echo "========================================="
printf '%s\n' '== DB =='
wp db query "SELECT option_name FROM wp_options WHERE option_value LIKE '%Thermage%' OR option_value LIKE '%thermage%';"
wp db query "SELECT post_id, meta_key FROM wp_postmeta WHERE meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%';"
wp db query "SELECT ID, post_title, post_status, post_type, post_name FROM wp_posts WHERE post_content LIKE '%Thermage%' OR post_title LIKE '%Thermage%' OR post_name LIKE '%thermage%';"

printf '%s\n' '== ARCHIVOS ACTIVOS =='
grep -RInEi 'Thermage|thermage|1594|nvx-thermage|nvx-phase3c|brand-manual|zzzz' \
  wp-content/themes/nuvanx-medical \
  wp-content/mu-plugins \
  --exclude-dir='_archive*' \
  --exclude-dir='_disabled*' \
  2>/dev/null || echo 'OK: archivos activos limpios'

printf '%s\n' '== CSS CH RESTRICTIVO =='
grep -RInE 'max-width:[[:space:]]*[0-9.]+ch|width:[[:space:]]*[0-9.]+ch' \
  wp-content/themes/nuvanx-medical/assets/css \
  2>/dev/null || echo 'OK: no hay ch restrictivo'

printf '%s\n' '== HTML PUBLICO =='
for url in \
  'https://nuvanx.com/' \
  'https://nuvanx.com/medicina-estetica-laser/' \
  'https://nuvanx.com/madrid/valoracion/' \
  'https://nuvanx.com/equipo-medico/' \
  'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/' \
  'https://nuvanx.com/contacto/' \
  'https://nuvanx.com/gracias/'
do
  echo "---- $url"
  curl -sL "$url?nocache=$(date +%s)" | grep -Ei 'Thermage|thermage|nvx-thermage|nvx-phase3c|et_pb_|brand-manual|zzzz' || echo 'OK limpio'
done

printf '%s\n' '== CRITICOS =='
echo 'GTM:'
curl -sL "https://nuvanx.com/?nocache=$(date +%s)" | grep -o 'GTM-W55RGVF2' | wc -l

echo 'META PIXEL ACTUAL:'
curl -sL "https://nuvanx.com/?nocache=$(date +%s)" | grep -o '1497940655079106' | wc -l

echo 'HUBSPOT:'
curl -sL "https://nuvanx.com/madrid/valoracion/?nocache=$(date +%s)" | grep -Ei 'hs-form|hubspot|147416356|5042522a' | head -20

echo 'GRACIAS GUARD:'
curl -sL "https://nuvanx.com/gracias/?source=google_lead_form&nocache=$(date +%s)" | grep -Ei 'GOOGLE_LEAD_FORM|SUPPRESS_WEB_CONVERSION|4BC2CKSat8YcEPXX-t1D' || true

echo "========================================="
echo "COMPLETADO"
echo "========================================="
