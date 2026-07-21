<?php
/**
 * Portafolio Clínico hub — quiet-luxury clinical architecture.
 *
 * Pattern-based (collaborators / catalog markup), not page-ID gated.
 * Updated to use Couture Sculpt, Skin Architecture, etc.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determines whether content matches the treatments index markers.
 *
 * Legacy catalog markers remain supported during the CMS content migration.
 *
 * @param string $content Content to inspect.
 * @return bool `true` if the content contains a treatments index marker and `nvx-catalog` is absent, `false` otherwise.
 */
function nvx_content_is_treatments_index( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-catalog' ) ) {
		return false;
	}
	return (bool) preg_match(
		'/nvx-brand-collaborators|Selección profesional|Catálogo de tratamientos NUVANX|aria-label="Catálogo de tratamientos/iu',
		$content
	);
}

/**
 * Provides the treatment categories and protocols used by the clinical catalog.
 *
 * @return array Treatment categories containing labels and protocol details with URLs.
 */
function nvx_treatments_catalog_data(): array {
	return array(
		array(
			'key'   => 'contorno-facial',
			'label' => 'Contorno y Proporción Facial',
			'items' => array(
				array(
					'meta'  => 'Protocolo Profile Definition™',
					'title' => 'Papada y Línea Mandibular',
					'body'  => 'Abordaje del perfil inferior cuando la valoración identifica laxitud o grasa localizada susceptible de tratamiento con láser intersticial (Endolift®).',
					'url'   => home_url( '/papada-definicion-mandibular-madrid/' ),
				),
			),
		),
		array(
			'key'   => 'arquitectura-corporal',
			'label' => 'Arquitectura Corporal (Couture Sculpt™)',
			'items' => array(
				array(
					'meta'  => 'Protocolo Couture Sculpt™',
					'title' => 'Remodelación Láser Corporal',
					'body'  => 'Sistema de diagnóstico y tratamiento por unidades anatómicas, orientado a mejorar la continuidad del contorno sin imponer formas estándar.',
					'url'   => home_url( '/remodelacion-corporal-laser-madrid/' ),
				),
			),
		),
		array(
			'key'   => 'calidad-piel',
			'label' => 'Calidad de Piel, Tono y Superficie',
			'items' => array(
				array(
					'meta'  => 'Protocolo Skin Architecture™',
					'title' => 'Firmeza y Densidad (EXION)',
					'body'  => 'Protocolos orientados a mejorar firmeza y calidad cutánea mediante estimulación tisular, sin modificar deliberadamente los volúmenes.',
					'url'   => home_url( '/calidad-piel-firmeza-luminosidad-madrid/' ),
				),
				array(
					'meta'  => 'Protocolo Surface Renewal™',
					'title' => 'Cicatrices y Poros (CO₂)',
					'body'  => 'Resurfacing ablativo médico para cicatrices de acné, poros, textura y estrías cuando existe indicación clínica.',
					'url'   => home_url( '/cicatrices-acne-poros-textura-madrid/' ),
				),
				array(
					'meta'  => 'Protocolo Tone Correction™',
					'title' => 'Manchas y Rojeces (IPL)',
					'body'  => 'Abordaje de alteraciones pigmentarias y vasculares con parámetros seleccionados según diagnóstico y fototipo.',
					'url'   => home_url( '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' ),
				),
			),
		),
	);
}

/**
 * Provides the partner and laboratory labels displayed in the treatments catalog.
 *
 * @return array<string> Partner and laboratory names.
 */
function nvx_treatments_partner_labels(): array {
	return array(
		'BTL Aesthetics',
		'DEKA',
		'Teoxane',
		'Merz Pharma',
		'Galderma',
		'Allergan',
		'IBSA',
	);
}

/**
 * Builds the clinical treatments catalog section from structured catalog data.
 *
 * @return string The rendered catalog HTML.
 */
function nvx_treatments_catalog_markup(): string {
	$html  = '<section class="nvx-catalog" aria-label="Portafolio Clínico NUVANX">';
	$html .= '<div class="nvx-catalog__inner">';
	$html .= '<header class="nvx-catalog__intro">';
	$html .= '<span class="nvx-catalog__kicker">' . esc_html__( 'MEDICINA ESTÉTICA LÁSER', 'nuvanx-medical' ) . '</span>';
	$html .= '<h2 class="nvx-catalog__title">' . esc_html__( 'Portafolio Clínico', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-catalog__lead">' . esc_html__( 'En NUVANX la tecnología se selecciona después del diagnóstico. Nuestro portafolio se organiza por necesidades anatómicas y objetivos clínicos.', 'nuvanx-medical' ) . '</p>';
	$html .= '</header>';

	foreach ( nvx_treatments_catalog_data() as $category ) {
		$html .= '<div class="nvx-catalog-category" data-category="' . esc_attr( $category['key'] ) . '">';
		$html .= '<h3 class="nvx-catalog-category__label">' . esc_html( $category['label'] ) . '</h3>';
		$html .= '<div class="nvx-catalog-grid">';

		foreach ( $category['items'] as $item ) {
			$html .= '<article class="nvx-catalog-card">';
			$html .= '<div class="nvx-catalog-card__main">';
			$html .= '<span class="nvx-catalog-card__meta">' . esc_html( $item['meta'] ) . '</span>';
			$html .= '<h4 class="nvx-catalog-card__title">' . esc_html( $item['title'] ) . '</h4>';
			$html .= '<p class="nvx-catalog-card__body">' . esc_html( $item['body'] ) . '</p>';
			$html .= '</div>';
			$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( $item['url'] ) . '">';
			$html .= esc_html__( 'Explorar protocolo', 'nuvanx-medical' );
			$html .= ' <span aria-hidden="true">→</span></a>';
			$html .= '</article>';
		}
		$html .= '</div></div>';
	}
	return $html . '</div></section>';
}

/**
 * Builds the technology and partner laboratory logo cloud markup.
 *
 * @return string The rendered HTML section containing partner laboratory labels.
 */
function nvx_treatments_logo_cloud_markup(): string {
	$html  = '<section class="nvx-logo-cloud" aria-label="Tecnología y laboratorios">';
	$html .= '<div class="nvx-logo-cloud__inner">';
	$html .= '<h2 class="nvx-logo-cloud__title">' . esc_html__( 'Tecnologías y laboratorios con los que trabajamos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-logo-cloud__list">';
	foreach ( nvx_treatments_partner_labels() as $label ) {
		$html .= '<li class="nvx-logo-cloud__item">' . esc_html( $label ) . '</li>';
	}
	return $html . '</ul></div></section>';
}

/**
 * Reorganizes treatments index content with the current catalog, partner section, and clinical assessment call to action.
 *
 * @param string $content The rendered page content to evaluate and restructure.
 * @return string The restructured content, or the original content when it is not the treatments index.
 */
function nvx_content_restructure_treatments_index( string $content ): string {
	if ( ! nvx_content_is_treatments_index( $content ) ) {
		return $content;
	}

	$catalog = nvx_treatments_catalog_markup();
	$cloud   = nvx_treatments_logo_cloud_markup();

	$content = preg_replace(
		'/<section\b[^>]*aria-label="Catálogo de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
		$catalog,
		$content,
		1,
		$count_catalog
	) ?? $content;

	if ( ! $count_catalog ) {
		$content = preg_replace(
			'/<section\b[^>]*class="[^"]*\bnvx-brand-section\b(?![^"]*collaborators)(?![^"]*cta)[^"]*"[^>]*>[\s\S]*?(?:Áreas de tratamiento|Facial, corporal, láser)[\s\S]*?<\/section>/iu',
			$catalog,
			$content,
			1
		) ?? $content;
	}

	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-brand-collaborators[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
		$cloud,
		$content,
		1,
		$count_collab
	) ?? $content;

	if ( ! $count_collab ) {
		$content = preg_replace(
			'/<section\b[^>]*aria-label="[^"]*Marcas colaboradoras[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			$cloud,
			$content,
			1
		) ?? $content;
	}

	$content = preg_replace('/<section\b[^>]*aria-label="Resumen de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;
	$content = preg_replace('/<section\b[^>]*class="[^"]*\bnvx-brand-section\b[^"]*"[^>]*>[\s\S]*?¿Qué tratamientos realizamos en NUVANX\?[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;
	$content = preg_replace('/<section\b[^>]*class="[^"]*nvx-brand-section--cta[^"]*"[^>]*>[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;

	$links  = '<section class="nvx-brand-section nvx-brand-section--soft" aria-label="Tu primera valoración clínica">';
	$links .= '<div class="nvx-shell nvx-brand-section__inner">';
	$links .= '<h2 class="nvx-brand-heading-2">' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$links .= '<p class="nvx-brand-body">' . esc_html__( 'La valoración no parte de una máquina concreta. Elaboramos planes médicos individualizados según anatomía, diagnóstico y objetivos realistas.', 'nuvanx-medical' ) . '</p>';
	$links .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar mi valoración', 'nuvanx-medical' ) . '</a></p>';
	$links .= '</div></section>';

	$content = preg_replace('/<section\b[^>]*aria-label="Enlaces de interés"[^>]*>[\s\S]*?<\/section>/iu', $links, $content, 1) ?? $content;

	return is_string( $content ) ? $content : '';
}
add_filter( 'the_content', 'nvx_content_restructure_treatments_index', 18 );
