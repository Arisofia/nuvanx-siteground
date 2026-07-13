#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

printf '%s\n' '== FRONT PAGE REAL =='
wp option get show_on_front
wp option get page_on_front
wp post get 9 --fields=ID,post_title,post_status,post_type,post_modified --format=table

printf '%s\n' '== HOME DB CONTENT =='
wp post get 9 --field=post_content | grep -Ei 'nvx-home-video-feature|<video|Thermage|thermage|nvx-thermage|nvx-phase3c' || echo 'DB home limpia'

printf '%s\n' '== HOME FILTRADA POR WORDPRESS =='
wp eval '
$post = get_post(9);
echo apply_filters("the_content", $post->post_content);
' | grep -Ei 'nvx-home-video-feature|<video|Thermage|thermage|nvx-thermage|nvx-phase3c' || echo 'the_content filtrado limpio'

printf '%s\n' '== HOME PUBLICA CURL =='
curl -sL -H "Cache-Control: no-cache" -H "Pragma: no-cache" "https://nuvanx.com/?nocache=$(date +%s)" | grep -Ei 'nvx-home-video-feature|<video|Thermage|thermage|nvx-thermage|nvx-phase3c' || echo 'HTML público limpio'

printf '%s\n' '== BUSCAR THERMAGE EN ARCHIVOS ACTIVOS =='
grep -RInEi 'Thermage|thermage|1594|nvx-thermage' \
  wp-content/themes/nuvanx-medical \
  wp-content/mu-plugins \
  --exclude-dir='*backup*' \
  --exclude-dir='_archive*' \
  --exclude-dir='_disabled*' \
  2>/dev/null || echo 'OK: no Thermage en archivos activos'

printf '%s\n' '== BUSCAR THERMAGE EN DB COMPLETA =='
wp db query "SELECT option_name FROM wp_options WHERE option_value LIKE '%Thermage%' OR option_value LIKE '%thermage%';"
wp db query "SELECT post_id, meta_key FROM wp_postmeta WHERE meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%';"
wp db query "SELECT ID, post_title, post_status, post_type, post_name FROM wp_posts WHERE post_content LIKE '%Thermage%' OR post_title LIKE '%Thermage%' OR post_name LIKE '%thermage%' OR post_excerpt LIKE '%Thermage%';"

printf '%s\n' '== LIMPIAR JSON-LD THERMAGE =='

python3 - <<'PY'
from pathlib import Path
import json
import re

path = Path("wp-content/mu-plugins/nuvanx-jsonld-data.json")
if not path.exists():
    raise SystemExit("No existe nuvanx-jsonld-data.json")

raw = path.read_text(errors="ignore")
path.with_suffix(".json.pre-thermage-clean.bak").write_text(raw)

def clean_string(s):
    for old in [
        "Thermage FLX®",
        "Thermage FLX",
        "Thermage®",
        "Thermage",
        "thermage-flx-radiofrecuencia-monopolar-madrid",
    ]:
        s = s.replace(old, "")
    s = re.sub(r",\s*,", ",", s)
    s = re.sub(r"\s{2,}", " ", s)
    s = s.replace("Endolift, ,", "Endolift,")
    return s.strip()

def contains_bad(obj):
    return "thermage" in json.dumps(obj, ensure_ascii=False).lower() or '"1594"' in json.dumps(obj, ensure_ascii=False)

def clean(obj):
    if isinstance(obj, dict):
        out = {}
        for k, v in obj.items():
            if str(k) == "1594":
                continue
            cleaned = clean(v)
            if cleaned in (None, {}, []):
                continue
            if isinstance(cleaned, (dict, list)) and contains_bad(cleaned):
                continue
            if isinstance(cleaned, str) and "thermage" in cleaned.lower():
                continue
            out[k] = cleaned
        return out

    if isinstance(obj, list):
        out = []
        for item in obj:
            cleaned = clean(item)
            if cleaned in (None, {}, []):
                continue
            if contains_bad(cleaned):
                continue
            out.append(cleaned)
        return out

    if isinstance(obj, str):
        stripped = obj.strip()
        if stripped.startswith("{") or stripped.startswith("["):
            try:
                parsed = json.loads(stripped)
                cleaned = clean(parsed)
                dumped = json.dumps(cleaned, ensure_ascii=False, separators=(",", ":"))
                if "thermage" in dumped.lower() or '"1594"' in dumped:
                    return None
                return dumped
            except Exception:
                pass
        return clean_string(obj)

    return obj

data = json.loads(raw)
out = json.dumps(clean(data), ensure_ascii=False, indent=2)

if "thermage" in out.lower() or '"1594"' in out:
    raise SystemExit("NO GO: queda Thermage/1594 en JSON-LD")

path.write_text(out)
print("OK JSON-LD limpio")
PY

grep -RInEi 'Thermage|thermage|1594' wp-content/mu-plugins/nuvanx-jsonld-data.json || echo 'OK: JSON-LD sin Thermage'

printf '%s\n' '== REMOVER STRIP IMPORTANT RUNTIME =='

grep -RIn 'preg_replace.*important\|important.*preg_replace' wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null || true

python3 - <<'PY'
from pathlib import Path
import re

path = Path("wp-content/themes/nuvanx-medical/functions.php")
text = path.read_text(errors="ignore")
path.with_suffix(".php.pre-strip-important-remove.bak").write_text(text)

# Eliminar hooks relacionados con strip important.
text = re.sub(
    r"\n\s*add_filter\s*\([^;]*(strip|important)[^;]*;\s*",
    "\n",
    text,
    flags=re.I
)

# Eliminar funciones que contienen preg_replace de !important.
text = re.sub(
    r"\n\s*function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{[\s\S]*?preg_replace\s*\(\s*['\"][^'\"]*!important[^'\"]*['\"][\s\S]*?\n\s*\}\s*",
    "\n",
    text,
    flags=re.I
)

if re.search(r"preg_replace\s*\([^)]*!important", text, re.I):
    raise SystemExit("NO GO: sigue existiendo preg_replace de !important")

path.write_text(text)
print("OK: runtime strip important eliminado")
PY

php -l wp-content/themes/nuvanx-medical/functions.php

printf '%s\n' '== VALIDAR IMPORTANT =='
grep -RIn '\!important' wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null | grep -v 'screen-reader-text' || echo 'OK: no hay important fuera de screen-reader-text'


printf '%s\n' '== LIMPIAR CSS FUENTE Y MINIFICADO =='

python3 - <<'PY'
from pathlib import Path
import re

css_dir = Path("wp-content/themes/nuvanx-medical/assets/css")

def remove_blocks(text, token):
    pattern = re.compile(r"[^{}]*" + re.escape(token) + r"[^{}]*\{[^{}]*\}", re.I | re.S)
    prev = None
    while prev != text:
        prev = text
        text = pattern.sub("", text)
    return text

def minify(css):
    css = re.sub(r"/\*[\s\S]*?\*/", "", css)
    css = re.sub(r"\s+", " ", css)
    css = re.sub(r"\s*([{}:;,>+~])\s*", r"\1", css)
    css = css.replace(";}", "}")
    return css.strip()

for path in css_dir.glob("*.css"):
    original = path.read_text(errors="ignore")
    text = original

    for token in ["nvx-thermage", "nvx-phase3c", "Thermage", "thermage"]:
        text = remove_blocks(text, token)

    text = re.sub(r"/\*[\s\S]*?(Thermage|thermage|phase3c|legacy|Fallback)[\s\S]*?\*/", "", text, flags=re.I)
    text = text.replace("solidvar(", "solid var(")
    text = text.replace("padding:16px0", "padding:16px 0")
    text = text.replace(".nvx-card--treatmentp:not", ".nvx-card--treatment p:not")
    text = text.replace("var(--nvx-accent, #b8956b)", "var(--nvx-accent)")

    # Quitar columnas falsas en grids principales.
    text = re.sub(
        r"(\.nvx-(?:treatment|tech|method|director|clinic|positioning|locations|team|commercial-media|commercial-related|eeat)[^{]*\{[^{}]*?)max-width\s*:\s*72ch\s*;",
        r"\1max-width: var(--nvx-shell);",
        text,
        flags=re.I | re.S
    )

    # Leads / subtítulos no deben quedarse comprimidos.
    text = re.sub(r"max-width\s*:\s*58ch\s*;", "max-width: min(860px, 100%);", text, flags=re.I)

    if text != original:
        path.with_suffix(path.suffix + ".pre-clean.bak").write_text(original)
        path.write_text(text)
        print(f"CSS limpiado: {path}")

# Regenerar minificados desde fuente.
for src in css_dir.glob("*.css"):
    if src.name.endswith(".min.css"):
        continue
    min_path = src.with_name(src.stem + ".min.css")
    min_path.write_text(minify(src.read_text(errors="ignore")))
    print(f"Minificado regenerado: {min_path}")

# Validación dura.
bad = []
for path in css_dir.glob("*.css"):
    content = path.read_text(errors="ignore")
    if re.search(r"Thermage|thermage|nvx-thermage|nvx-phase3c|solidvar|padding:16px0|\.nvx-card--treatmentp|#b8956b", content):
        bad.append(str(path))

if bad:
    raise SystemExit("NO GO CSS residuos en:\n" + "\n".join(bad))

print("OK CSS limpio")
PY


printf '%s\n' '== PURGA SITEGROUND =='
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-css-*.css || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-js-*.js || true

wp cache flush
wp sg purge
wp sg optimize combine-css disable || true
wp sg purge
wp sg optimize combine-css enable || true
wp sg purge

printf '%s\n' '== DB =='
wp db query "SELECT option_name FROM wp_options WHERE option_value LIKE '%Thermage%' OR option_value LIKE '%thermage%';"
wp db query "SELECT post_id, meta_key FROM wp_postmeta WHERE meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%';"
wp db query "SELECT ID, post_title, post_status, post_type, post_name FROM wp_posts WHERE post_content LIKE '%Thermage%' OR post_title LIKE '%Thermage%' OR post_name LIKE '%thermage%' OR post_excerpt LIKE '%Thermage%';"

printf '%s\n' '== ARCHIVOS ACTIVOS =='
grep -RInEi 'Thermage|thermage|1594|nvx-thermage|nvx-phase3c|brand-manual|zzzz|preg_replace.*important' \
  wp-content/themes/nuvanx-medical \
  wp-content/mu-plugins \
  --exclude-dir='_archive*' \
  --exclude-dir='_disabled*' \
  2>/dev/null || echo 'OK: archivos activos limpios'

printf '%s\n' '== CSS CH RESTRICTIVO =='
grep -RInE 'max-width:[[:space:]]*(14|20|30|34|40|52|58|68|72)ch|width:[[:space:]]*[0-9.]+ch' \
  wp-content/themes/nuvanx-medical/assets/css \
  2>/dev/null || echo 'OK: sin ch restrictivo crítico'

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
  curl -sL -H "Cache-Control: no-cache" "$url?nocache=$(date +%s)" | grep -Ei 'Thermage|thermage|nvx-thermage|nvx-phase3c|et_pb_|brand-manual|zzzz' || echo 'OK limpio'
done

printf '%s\n' '== VIDEO HOME PUBLICO =='
curl -sL -H "Cache-Control: no-cache" "https://nuvanx.com/?nocache=$(date +%s)" | grep -Ei 'nvx-home-video-feature|<video|nvx-home-hero-video' || echo 'NO GO: video no aparece en HTML público'
