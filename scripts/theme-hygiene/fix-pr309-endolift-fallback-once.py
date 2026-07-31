#!/usr/bin/env python3
from pathlib import Path

root = Path(__file__).resolve().parents[2]
endolift = root / 'wp-content/themes/nuvanx-medical/inc/nvx-endolift-page.php'
test = root / 'scripts/theme-hygiene/test-page-render-helpers.php'

source = endolift.read_text(encoding='utf-8')
old = "\treturn nvx_page_render_brand_wrapper( $content, $hero . $body );\n"
new = (
    "\treturn nvx_page_render_brand_wrapper(\n"
    "\t\t$content,\n"
    "\t\t$hero . $body,\n"
    "\t\t'nvx-brand-page nvx-brand-page--endolift'\n"
    "\t);\n"
)
if source.count(old) != 1:
    raise SystemExit(f'Expected one Endolift wrapper call, found {source.count(old)}')
endolift.write_text(source.replace(old, new, 1), encoding='utf-8')

contract = test.read_text(encoding='utf-8')
anchor = "nvx_page_helper_assert(\n    nvx_page_render_brand_wrapper('<p>plain</p>', '<section>new</section>', 'nvx-brand-page nvx-brand-page--laser')\n        === '<div class=\"nvx-brand-page nvx-brand-page--laser\"><section>new</section></div>',\n    'Fallback brand wrapper changed.'\n);\n"
addition = anchor + "\n$endoliftSource = (string) file_get_contents(\n    dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/nvx-endolift-page.php'\n);\nnvx_page_helper_assert(\n    str_contains($endoliftSource, \"'nvx-brand-page nvx-brand-page--endolift'\"),\n    'Endolift caller must preserve its fallback brand wrapper.'\n);\n"
if contract.count(anchor) != 1:
    raise SystemExit('Fallback contract insertion point not found')
test.write_text(contract.replace(anchor, addition, 1), encoding='utf-8')

print('Restored Endolift fallback wrapper and regression contract.')
