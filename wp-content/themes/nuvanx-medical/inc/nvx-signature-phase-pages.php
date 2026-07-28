<?php
/**
 * Governed Phase 1 and Phase 2 Signature landing pages.
 *
 * Clinical catalogue content lives in inc/data/nvx-signature-phase-catalog.json.
 * This module hydrates that data and owns routing, markup, SEO and navigation.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

if ( ! defined( 'NVX_CONTOUR_ARCHITECTURE' ) ) {
	define( 'NVX_CONTOUR_ARCHITECTURE', 'NUVANX Contour Architecture™' );
}

/**
 * Load raw Signature phase specs from the versioned JSON catalogue.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_signature_phase_catalog_specs(): array {
	return nvx_theme_load_json_catalog( 'nvx-signature-phase-catalog.json' );
}

/**
 * Resolve catalogue tokens for Contour Architecture naming variants.
 *
 * @param mixed $value
 * @return mixed
 */
function nvx_signature_phase_resolve_token( $value ) {
	if ( ! is_string( $value ) ) {
		return $value;
	}
	if ( 'contour_upper' === $value ) {
		return 'CONTOUR ARCHITECTURE™';
	}
	if ( 'contour_mixed' === $value ) {
		return NVX_CONTOUR_ARCHITECTURE;
	}
	return $value;
}

/**
 * Hydrate one raw JSON entry into a runtime catalogue page.
 *
 * @param array<string, mixed> $spec
 * @return array<string, mixed>
 */
function nvx_signature_phase_hydrate_entry( array $spec ): array {
	$entry = array();
	foreach ( $spec as $key => $value ) {
		if ( is_array( $value ) ) {
			$entry[ $key ] = array_map( 'nvx_signature_phase_resolve_token', $value );
			continue;
		}
		$entry[ $key ] = nvx_signature_phase_resolve_token( $value );
	}
	return $entry;
}

/**
 * Provides the approved landing-page content and metadata for Signature phases 1 and 2.
 *
 * @return array The catalogue keyed by internal page identifier.
 */
function nvx_signature_phase_catalog(): array {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}

	$catalog = array();
	foreach ( nvx_signature_phase_catalog_specs() as $key => $spec ) {
		if ( ! is_array( $spec ) ) {
			continue;
		}
		$catalog[ $key ] = nvx_signature_phase_hydrate_entry( $spec );
	}
	return $catalog;
}

/**
 * Identifies the governed landing page for the current request.
 *
 * @return string|null The matching catalog key, or null when the request does not
 *     target a governed landing page.
 */
function nvx_signature_phase_current_key(): ?string {
	if ( ! is_page() || is_404() ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( '' === $slug ) {
		return null;
	}
	foreach ( nvx_signature_phase_catalog() as $key => $page ) {
		if ( isset( $page['slug'] ) && $page['slug'] === $slug ) {
			return $key;
		}
	}
	return null;
}

/**
 * Builds an HTML section containing a titled list of items.
 *
 * @param string $title The section heading.
 * @param array  $items The list items to display.
 * @param string $class Optional additional CSS class.
 * @return string The rendered HTML section.
 */
function nvx_signature_phase_list( string $title, array $items, string $class = '' ): string {
	$html  = '<section class="nvx-brand-section ' . esc_attr( $class ) . '"><div class="nvx-brand-section__inner">';
	$html .= '<h2>' . esc_html( $title ) . '</h2><ul class="nvx-check-list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( (string) $item ) . '</li>';
	}
	return $html . '</ul></div></section>';
}

/**
 * Generates the governed landing page markup for a catalog entry.
 *
 * @param array $page Catalog entry containing the page content and related protocol.
 * @return string The generated landing page HTML.
 */
function nvx_signature_phase_markup( array $page ): string {
	$html  = '<article class="nvx-brand-page nvx-treatment-page nvx-protocol-page nvx-signature-phase-page">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-eyebrow">' . esc_html( (string) $page['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( (string) $page['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( (string) $page['lead'] ) . '</p><p>' . esc_html( (string) $page['intro'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La indicación, la tecnología, el número de sesiones, el período de recuperación y el presupuesto se confirman después de la exploración médica.', 'nuvanx-medical' ) . '</p></header>';
	$html .= nvx_signature_phase_list( 'Qué se valora', (array) $page['assessment'] );
	$html .= '<section class="nvx-brand-section"><div class="nvx-brand-section__inner"><h2>' . esc_html__( 'Cómo se decide el plan', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El médico identifica el componente predominante, revisa zonas contiguas y descarta problemas que no deben abordarse con medicina estética. Solo entonces se selecciona una modalidad y se documentan alternativas, cuidados y seguimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Protocolo relacionado:', 'nuvanx-medical' ) . '</strong> ' . esc_html( (string) $page['protocol'] ) . '</p></div></section>';
	$html .= nvx_signature_phase_list( 'Tecnologías que pueden formar parte del plan', (array) $page['technology'] );
	$html .= nvx_signature_phase_list( 'Límites y cuándo derivamos', (array) $page['limits'], 'nvx-strategy-checklist nvx-strategy-checklist--no' );
	$html .= '<section class="nvx-brand-section"><div class="nvx-brand-section__inner"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La valoración revisa antecedentes, anatomía, tejido predominante, tratamientos previos, expectativas y disponibilidad para cuidados. Si no existe una indicación proporcionada, se explica la alternativa, la derivación o la decisión de no intervenir.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a> <a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/protocolos-signature/' ) ) . '">' . esc_html__( 'Explorar Protocolos Signature', 'nuvanx-medical' ) . '</a></p></div></section></article>';
	return $html;
}

nvx_register_catalog_content_filter( 'nvx_signature_phase_catalog', 22, 'nvx_signature_phase_markup' );

/** Suppress the generic shell title because this module renders the canonical H1. */
function nvx_signature_phase_prepare_shell(): void {
	if ( null !== nvx_signature_phase_current_key() ) {
		set_query_var( 'nvx_shell_skip_header', true );
	}
}
add_action( 'wp', 'nvx_signature_phase_prepare_shell', 5 );

/**
 * Contour Architecture child routes for the primary navigation mega-menu.
 *
 * @return array<int, array{label:string,slugs:array<int,string>}>
 */
function nvx_signature_contour_nav_children(): array {
	return array(
		array( 'label' => 'Abdomen y flancos', 'slugs' => array( 'grasa-localizada-abdomen-flancos-madrid' ) ),
		array( 'label' => 'Brazos y axila', 'slugs' => array( 'flacidez-grasa-localizada-brazos-madrid' ) ),
		array( 'label' => 'Espalda y zona del sujetador', 'slugs' => array( 'grasa-espalda-zona-sujetador-madrid' ) ),
		array( 'label' => 'Muslos y región subglútea', 'slugs' => array( 'flacidez-muslos-internos-subgluteo-madrid' ) ),
		array( 'label' => 'Rodillas', 'slugs' => array( 'tratamiento-rodillas-grasa-flacidez-madrid' ) ),
		array( 'label' => 'Contorno masculino', 'slugs' => array( 'contorno-corporal-masculino-madrid' ) ),
	);
}

/**
 * Updates navigation routes for legacy Contour or post-maternity labels.
 *
 * @param array $child The navigation child to update.
 * @return array The updated navigation child.
 */
function nvx_signature_apply_contour_children( array $child ): array {
	$mixed       = defined( 'NVX_CONTOUR_ARCHITECTURE' ) ? NVX_CONTOUR_ARCHITECTURE : 'NUVANX Contour Architecture™';
	$child_label = isset( $child['label'] ) ? (string) $child['label'] : '';
	if (
		false !== stripos( $child_label, 'Contour Sculpt' )
		|| false !== stripos( $child_label, 'Contour Architecture' )
		|| false !== stripos( $child_label, 'Couture Sculpt' )
	) {
		$child['label']    = $mixed;
		$child['slugs']    = array( 'remodelacion-corporal-laser-madrid' );
		$child['children'] = nvx_signature_contour_nav_children();
	} elseif ( false !== stripos( $child_label, 'Post-Maternity' ) || false !== stripos( $child_label, 'Profile Definition' ) ) {
		$child['children'] = array();
	}
	return $child;
}

/** Filter protocol children for Signature menu items. */
function nvx_signature_filter_protocol_children( array $children ): array {
	$filtered = array();
	foreach ( $children as $child ) {
		$child_label = isset( $child['label'] ) ? (string) $child['label'] : '';
		if ( false !== stripos( $child_label, 'Eye Frame' ) ) {
			continue;
		}
		$filtered[] = nvx_signature_apply_contour_children( $child );
	}
	return $filtered;
}

/**
 * Restricts the primary navigation to supported Signature routes and published clinical case routes.
 *
 * @param array $blueprint The primary navigation blueprint.
 * @return array The updated navigation blueprint.
 */
function nvx_signature_phase_navigation_blueprint( array $blueprint ): array {
	foreach ( $blueprint as $top_index => $top ) {
		$label = isset( $top['label'] ) ? (string) $top['label'] : '';
		if ( 'Casos clínicos' === $label ) {
			$blueprint[ $top_index ]['slugs'] = array( 'casos-de-pacientes', 'casos-clinicos' );
		}
		if ( 'Protocolos Signature' === $label && ! empty( $top['children'] ) && is_array( $top['children'] ) ) {
			$blueprint[ $top_index ]['children'] = nvx_signature_filter_protocol_children( $top['children'] );
		}
	}
	return $blueprint;
}
add_filter( 'nvx_navigation_primary_blueprint', 'nvx_signature_phase_navigation_blueprint', 30 );

/**
 * Replaces legacy product names with the approved public product name.
 *
 * @param string $content Content containing product names to normalize.
 * @return string Content with legacy product names replaced by the approved public product name.
 */
function nvx_signature_phase_normalize_public_names( string $content ): string {
	return str_ireplace( array( 'Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Contour Sculpt™' ), NVX_CONTOUR_ARCHITECTURE, $content );
}
add_filter( 'the_content', 'nvx_signature_phase_normalize_public_names', 219 );

/** Resolve metadata for the current governed landing page. */
function nvx_signature_phase_current_metadata(): ?array {
	$key = nvx_signature_phase_current_key();
	if ( null === $key ) {
		return null;
	}
	$catalog = nvx_signature_phase_catalog();
	return $catalog[ $key ] ?? null;
}

/**
 * Provides the governed page's SEO title when available.
 *
 * @param string $title The current SEO title.
 * @return string The governed page's SEO title or the original title.
 */
function nvx_signature_phase_seo_title( $title ) {
	$page = nvx_signature_phase_current_metadata();
	if ( ! is_array( $page ) || empty( $page['seo_title'] ) ) {
		return $title;
	}
	return (string) $page['seo_title'];
}
add_filter( 'wpseo_title', 'nvx_signature_phase_seo_title', 90 );
add_filter( 'wpseo_opengraph_title', 'nvx_signature_phase_seo_title', 90 );
add_filter( 'wpseo_twitter_title', 'nvx_signature_phase_seo_title', 90 );

/**
 * Supplies the governed page's SEO description when available.
 *
 * @param mixed $description The existing SEO description.
 * @return mixed The governed SEO description or the existing description.
 */
function nvx_signature_phase_seo_description( $description ) {
	$page = nvx_signature_phase_current_metadata();
	if ( ! is_array( $page ) || empty( $page['seo_desc'] ) ) {
		return $description;
	}
	return (string) $page['seo_desc'];
}
add_filter( 'wpseo_metadesc', 'nvx_signature_phase_seo_description', 90 );
add_filter( 'wpseo_opengraph_desc', 'nvx_signature_phase_seo_description', 90 );
add_filter( 'wpseo_twitter_description', 'nvx_signature_phase_seo_description', 90 );
