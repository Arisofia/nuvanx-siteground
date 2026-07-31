#!/usr/bin/env python3
from __future__ import annotations

from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
INC = ROOT / "wp-content/themes/nuvanx-medical/inc"


def replace_once(path: Path, old: str, new: str, label: str) -> None:
    source = path.read_text(encoding="utf-8")
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f"{label}: expected one match in {path}, found {count}")
    path.write_text(source.replace(old, new, 1), encoding="utf-8")


def load(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def save(path: Path, source: str) -> None:
    path.write_text(source, encoding="utf-8")


helper_path = INC / "nvx-page-render-helpers.php"
helper_source = load(helper_path)
helper_marker = "function nvx_page_brand_section_open_markup("
if helper_marker not in helper_source:
    helper_source += r'''

/**
 * Open a canonical brand section and its inner shell.
 *
 * Callers keep translated copy in their own source and pass escaped markup.
 *
 * @param array<string,string> $section_attributes Additional safe attributes.
 */
function nvx_page_brand_section_open_markup(
	string $section_class,
	string $labelledby,
	string $inner_extra_class = '',
	array $section_attributes = array()
): string {
	$section_classes = 'nvx-brand-section';
	$section_suffix  = trim( $section_class );
	if ( '' !== $section_suffix ) {
		$section_classes .= ' ' . $section_suffix;
	}

	$inner_classes = 'nvx-shell nvx-brand-section__inner';
	$inner_suffix  = trim( $inner_extra_class );
	if ( '' !== $inner_suffix ) {
		$inner_classes .= ' ' . $inner_suffix;
	}

	$html = '<section class="' . esc_attr( $section_classes ) . '" aria-labelledby="' . esc_attr( $labelledby ) . '"';
	foreach ( $section_attributes as $attribute => $value ) {
		if ( ! preg_match( '/^[a-zA-Z_:][a-zA-Z0-9:._-]*$/', $attribute ) ) {
			continue;
		}
		$html .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
	}

	return $html . '><div class="' . esc_attr( $inner_classes ) . '">';
}

/**
 * Render the canonical kicker and H2 pair.
 *
 * The kicker and heading arguments must already be escaped by the caller.
 */
function nvx_page_brand_section_heading_markup(
	string $kicker,
	string $heading_id,
	string $heading
): string {
	return '<p class="nvx-brand-kicker">' . $kicker . '</p>'
		. '<h2 id="' . esc_attr( $heading_id ) . '" class="nvx-brand-title">' . $heading . '</h2>';
}
'''
    save(helper_path, helper_source)


# Load helpers once with the page modules instead of repeating runtime requires.
for filename in (
    "nvx-endolift-page.php",
    "nvx-endolaser-page.php",
    "nvx-btl-detail-pages.php",
):
    path = INC / filename
    source = load(path)
    source = source.replace("\n\trequire_once __DIR__ . '/nvx-page-render-helpers.php';\n", "\n")
    guard = "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n"
    replacement = guard + "\nrequire_once __DIR__ . '/nvx-page-render-helpers.php';\n"
    if source.count(guard) != 1:
        raise RuntimeError(f"ABSPATH guard not unique in {path}")
    source = source.replace(guard, replacement, 1)
    save(path, source)


endolift = INC / "nvx-endolift-page.php"
endolift_replacements = [
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-what\" aria-labelledby=\"nvx-endolift-what-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'La técnica', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-what-title\" class=\"nvx-brand-title\">' . esc_html__( '¿Qué es el Endolift® facial y cómo altera la estructura anatómica?', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-what', 'nvx-endolift-what-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'La técnica', 'nuvanx-medical' ), 'nvx-endolift-what-title', esc_html__( '¿Qué es el Endolift® facial y cómo altera la estructura anatómica?', 'nuvanx-medical' ) );\n",
        "Endolift what section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-diagnosis\" aria-labelledby=\"nvx-endolift-diagnosis-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner nvx-endolift-diagnosis__grid\">';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-diagnosis', 'nvx-endolift-diagnosis-title', 'nvx-endolift-diagnosis__grid' );\n",
        "Endolift diagnosis open",
    ),
    (
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Indicaciones clínicas', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-diagnosis-title\" class=\"nvx-brand-title\">' . esc_html__( 'Selección rigurosa del paciente ideal', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Indicaciones clínicas', 'nuvanx-medical' ), 'nvx-endolift-diagnosis-title', esc_html__( 'Selección rigurosa del paciente ideal', 'nuvanx-medical' ) );\n",
        "Endolift diagnosis heading",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-compare\" aria-labelledby=\"nvx-endolift-compare-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Comparativa clínica', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-compare-title\" class=\"nvx-brand-title\">' . esc_html__( 'Endolift® vs lifting cérvicofacial quirúrgico', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-compare', 'nvx-endolift-compare-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Comparativa clínica', 'nuvanx-medical' ), 'nvx-endolift-compare-title', esc_html__( 'Endolift® vs lifting cérvicofacial quirúrgico', 'nuvanx-medical' ) );\n",
        "Endolift comparison section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-biophysics\" aria-labelledby=\"nvx-endolift-bio-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'La biofísica', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-bio-title\" class=\"nvx-brand-title\">' . esc_html__( '1470 nm: deposición térmica controlada', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-biophysics', 'nvx-endolift-bio-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'La biofísica', 'nuvanx-medical' ), 'nvx-endolift-bio-title', esc_html__( '1470 nm: deposición térmica controlada', 'nuvanx-medical' ) );\n",
        "Endolift biophysics section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-process\" aria-labelledby=\"nvx-endolift-process-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'El procedimiento en NUVANX', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-process-title\" class=\"nvx-brand-title\">' . esc_html__( 'Ejecución paso a paso', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-process', 'nvx-endolift-process-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'El procedimiento en NUVANX', 'nuvanx-medical' ), 'nvx-endolift-process-title', esc_html__( 'Ejecución paso a paso', 'nuvanx-medical' ) );\n",
        "Endolift process section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-postop\" aria-labelledby=\"nvx-endolift-postop-title\" id=\"postoperatorio-endolift\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Recuperación Transparente', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-postop-title\" class=\"nvx-brand-title\">' . esc_html__( 'Cómo es el postoperatorio real del Endolift® en Madrid (sin clichés)', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-postop', 'nvx-endolift-postop-title', '', array( 'id' => 'postoperatorio-endolift' ) );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Recuperación Transparente', 'nuvanx-medical' ), 'nvx-endolift-postop-title', esc_html__( 'Cómo es el postoperatorio real del Endolift® en Madrid (sin clichés)', 'nuvanx-medical' ) );\n",
        "Endolift postoperative section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-investment\" aria-labelledby=\"nvx-endolift-price-title\" id=\"inversion-endolift\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Presupuesto médico', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-price-title\" class=\"nvx-brand-title\">' . esc_html__( 'Valoración y presupuesto Endolift® en NUVANX Madrid', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-investment', 'nvx-endolift-price-title', '', array( 'id' => 'inversion-endolift' ) );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Presupuesto médico', 'nuvanx-medical' ), 'nvx-endolift-price-title', esc_html__( 'Valoración y presupuesto Endolift® en NUVANX Madrid', 'nuvanx-medical' ) );\n",
        "Endolift investment section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolift-faq\" aria-labelledby=\"nvx-endolift-faq-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Base de conocimiento', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolift-faq-title\" class=\"nvx-brand-title\">' . esc_html__( 'Preguntas clínicas frecuentes', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-faq', 'nvx-endolift-faq-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Base de conocimiento', 'nuvanx-medical' ), 'nvx-endolift-faq-title', esc_html__( 'Preguntas clínicas frecuentes', 'nuvanx-medical' ) );\n",
        "Endolift FAQ section",
    ),
]
for old, new, label in endolift_replacements:
    replace_once(endolift, old, new, label)

replace_once(
    endolift,
    "\t$media = '';\n"
    "\tif ( preg_match( '/<figure class=\"nvx-brand-hero__media\"[\\s\\S]*?<\\/figure>/iu', $content, $m ) ) {\n"
    "\t\t$media = $m[0];\n"
    "\t} elseif ( preg_match( '/<div class=\"nvx-brand-hero__media\"[\\s\\S]*?<\\/div>/iu', $content, $m ) ) {\n"
    "\t\t$media = $m[0];\n"
    "\t}\n",
    "\t$media = nvx_page_extract_brand_hero_media( $content );\n",
    "Endolift hero media extraction",
)
replace_once(
    endolift,
    "\tif ( preg_match( '/(<div class=\"nvx-brand-page[^\"]*\"[^>]*>)/iu', $content, $wrap ) ) {\n"
    "\t\treturn $wrap[1] . $hero . $body . '</div>';\n"
    "\t}\n\n"
    "\treturn $hero . $body;\n",
    "\treturn nvx_page_render_brand_wrapper( $content, $hero . $body );\n",
    "Endolift brand wrapper",
)


endolaser = INC / "nvx-endolaser-page.php"
endolaser_replacements = [
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolaser-mechanism\" aria-labelledby=\"nvx-endolaser-mech-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Laserlipólisis corporal', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolaser-mech-title\" class=\"nvx-brand-title\">' . esc_html__( 'Cómo actúa: grasa localizada y soporte de la piel', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-mechanism', 'nvx-endolaser-mech-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Laserlipólisis corporal', 'nuvanx-medical' ), 'nvx-endolaser-mech-title', esc_html__( 'Cómo actúa: grasa localizada y soporte de la piel', 'nuvanx-medical' ) );\n",
        "Endolaser mechanism section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolaser-zones\" aria-labelledby=\"nvx-endolaser-zones-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Mapa clínico', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolaser-zones-title\" class=\"nvx-brand-title\">' . esc_html__( 'Zonas anatómicas de alta respuesta', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-zones', 'nvx-endolaser-zones-title' );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Mapa clínico', 'nuvanx-medical' ), 'nvx-endolaser-zones-title', esc_html__( 'Zonas anatómicas de alta respuesta', 'nuvanx-medical' ) );\n",
        "Endolaser zones section",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolaser-exclusion\" aria-labelledby=\"nvx-endolaser-excl-title\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner nvx-endolift-diagnosis__grid\">';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-exclusion', 'nvx-endolaser-excl-title', 'nvx-endolift-diagnosis__grid' );\n",
        "Endolaser exclusion open",
    ),
    (
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Criterio médico', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolaser-excl-title\" class=\"nvx-brand-title\">' . esc_html__( 'Criterios de exclusión y alternativas', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Criterio médico', 'nuvanx-medical' ), 'nvx-endolaser-excl-title', esc_html__( 'Criterios de exclusión y alternativas', 'nuvanx-medical' ) );\n",
        "Endolaser exclusion heading",
    ),
    (
        "\t$html .= '<section class=\"nvx-brand-section nvx-endolaser-planning\" aria-labelledby=\"nvx-endolaser-plan-title\" id=\"planificacion-endolaser\">';\n"
        "\t$html .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$html .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Planificación', 'nuvanx-medical' ) . '</p>';\n"
        "\t$html .= '<h2 id=\"nvx-endolaser-plan-title\" class=\"nvx-brand-title\">' . esc_html__( 'Inversión y planificación por zonas', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-planning', 'nvx-endolaser-plan-title', '', array( 'id' => 'planificacion-endolaser' ) );\n"
        "\t$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Planificación', 'nuvanx-medical' ), 'nvx-endolaser-plan-title', esc_html__( 'Inversión y planificación por zonas', 'nuvanx-medical' ) );\n",
        "Endolaser planning section",
    ),
]
for old, new, label in endolaser_replacements:
    replace_once(endolaser, old, new, label)


btl = INC / "nvx-btl-detail-pages.php"
btl_replacements = [
    (
        "\t$body .= '<section class=\"nvx-brand-section\" aria-labelledby=\"' . esc_attr( $id ) . '-mech\">';\n"
        "\t$body .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$body .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';\n"
        "\t$body .= '<h2 id=\"' . esc_attr( $id ) . '-mech\" class=\"nvx-brand-title\">' . esc_html( (string) ( $c['mechanism']['title'] ?? '' ) ) . '</h2>';\n",
        "\t$body .= nvx_page_brand_section_open_markup( '', $id . '-mech' );\n"
        "\t$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Mecanismo', 'nuvanx-medical' ), $id . '-mech', esc_html( (string) ( $c['mechanism']['title'] ?? '' ) ) );\n",
        "BTL mechanism section",
    ),
    (
        "\t$body .= '<section class=\"nvx-brand-section\" aria-labelledby=\"' . esc_attr( $id ) . '-ind\">';\n"
        "\t$body .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$body .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Indicaciones', 'nuvanx-medical' ) . '</p>';\n"
        "\t$body .= '<h2 id=\"' . esc_attr( $id ) . '-ind\" class=\"nvx-brand-title\">' . esc_html__( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$body .= nvx_page_brand_section_open_markup( '', $id . '-ind' );\n"
        "\t$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Indicaciones', 'nuvanx-medical' ), $id . '-ind', esc_html__( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ) );\n",
        "BTL indications section",
    ),
    (
        "\t$body .= '<section class=\"nvx-brand-section\" aria-labelledby=\"' . esc_attr( $id ) . '-proc\">';\n"
        "\t$body .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$body .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'Proceso médico', 'nuvanx-medical' ) . '</p>';\n"
        "\t$body .= '<h2 id=\"' . esc_attr( $id ) . '-proc\" class=\"nvx-brand-title\">' . esc_html__( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$body .= nvx_page_brand_section_open_markup( '', $id . '-proc' );\n"
        "\t$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Proceso médico', 'nuvanx-medical' ), $id . '-proc', esc_html__( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ) );\n",
        "BTL process section",
    ),
    (
        "\t$body .= '<section class=\"nvx-brand-section\" aria-labelledby=\"' . esc_attr( $id ) . '-faq\">';\n"
        "\t$body .= '<div class=\"nvx-shell nvx-brand-section__inner\">';\n"
        "\t$body .= '<p class=\"nvx-brand-kicker\">' . esc_html__( 'FAQ', 'nuvanx-medical' ) . '</p>';\n"
        "\t$body .= '<h2 id=\"' . esc_attr( $id ) . '-faq\" class=\"nvx-brand-title\">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';\n",
        "\t$body .= nvx_page_brand_section_open_markup( '', $id . '-faq' );\n"
        "\t$body .= nvx_page_brand_section_heading_markup( esc_html__( 'FAQ', 'nuvanx-medical' ), $id . '-faq', esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) );\n",
        "BTL FAQ section",
    ),
]
for old, new, label in btl_replacements:
    replace_once(btl, old, new, label)


# Remove a duplicated regression test introduced by a concurrent review edit and
# add exact-output contracts for the new helpers.
test_path = ROOT / "scripts/theme-hygiene/test-page-render-helpers.php"
test_source = load(test_path)
duplicate_declaration = (
    "$nestedDivision = '<div class=\"nvx-brand-hero__media\"><div class=\"frame\"><img src=\"nested.jpg\" alt=\"\"></div></div>';\n"
    "$nestedDivision = '<div class=\"nvx-brand-hero__media\"><div class=\"frame\"><img src=\"nested.jpg\" alt=\"\"></div></div>';\n"
)
if duplicate_declaration in test_source:
    test_source = test_source.replace(duplicate_declaration, duplicate_declaration.splitlines(keepends=True)[0], 1)

duplicate_assertion = """nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $nestedDivision . '</main>') === $nestedDivision,
    'Nested div hero media extraction changed.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $nestedDivision . '</main>') === $nestedDivision,
    'Nested div hero media extraction changed.'
);
"""
if duplicate_assertion in test_source:
    test_source = test_source.replace(duplicate_assertion, duplicate_assertion[: duplicate_assertion.find("nvx_page_helper_assert(", 1)], 1)

contract = r'''

$sectionOpen = nvx_page_brand_section_open_markup(
    'nvx-example',
    'example-title',
    'nvx-example-grid',
    array('id' => 'example-section')
);
nvx_page_helper_assert(
    $sectionOpen === '<section class="nvx-brand-section nvx-example" aria-labelledby="example-title" id="example-section"><div class="nvx-shell nvx-brand-section__inner nvx-example-grid">',
    'Canonical section opening markup changed.'
);
nvx_page_helper_assert(
    nvx_page_brand_section_open_markup('', 'plain-title')
        === '<section class="nvx-brand-section" aria-labelledby="plain-title"><div class="nvx-shell nvx-brand-section__inner">',
    'Canonical section opening without modifiers changed.'
);
nvx_page_helper_assert(
    nvx_page_brand_section_heading_markup('Kicker', 'heading-id', 'Heading')
        === '<p class="nvx-brand-kicker">Kicker</p><h2 id="heading-id" class="nvx-brand-title">Heading</h2>',
    'Canonical section heading markup changed.'
);
'''
if "$sectionOpen = nvx_page_brand_section_open_markup(" not in test_source:
    test_source = test_source.replace('\necho "Page render helper contract passed.\\n";\n', contract + '\necho "Page render helper contract passed.\\n";\n', 1)
save(test_path, test_source)

print("Consolidated canonical section renderers for PR 309.")
