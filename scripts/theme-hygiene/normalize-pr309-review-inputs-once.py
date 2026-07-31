#!/usr/bin/env python3
from pathlib import Path

path = Path('wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-treatment-pages.php')
source = path.read_text(encoding='utf-8')
start_marker = 'function nvx_aesthetic_treatment_catalog(): array {'
end_marker = '/** Resolve a treatment key from slug or current singular page. */'
start = source.find(start_marker)
end = source.find(end_marker, start)
if start < 0 or end < 0:
    raise SystemExit('Aesthetic treatment catalog boundaries not found')

canonical = """function nvx_aesthetic_treatment_catalog(): array {
\trequire_once __DIR__ . '/nvx-catalog-json.php';

\treturn nvx_catalog_resolve_tokens(
\t\tnvx_catalog_json_load( 'aesthetic-treatment-pages.json' ),
\t\tnull
\t);
}

"""
path.write_text(source[:start] + canonical + source[end:], encoding='utf-8')
print('Normalized aesthetic treatment catalog input.')
