<?php
/**
 * Treatments index restructure — quiet-luxury catalog (Portafolio Clínico).
 *
 * Pattern-based (collaborators / catalog markup), not page-ID gated.
 * Updated to use Couture Sculpt, Skin Architecture, etc.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nvx_content_is_treatments_index( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-catalog' ) ) {
		return false;
	}
	return (bool) preg_match(
		'/nvx-brand-collaborators|Selección profesional|Catálogo de tratamientos NUVANX|aria-label="Catálogo de tratamientos/iu',
		$content
	);
}

function nvx_treatments_catalog_data(): array {
	return array(
		array(
			'key'   => 'contorno-facial',
			'label' => 'Contorno y Proporción Facial',
			'items' => array(
				array(
					'meta'  => 'Protocolo Profile Definition™',
					'title' => 'Papada y Línea Mandibular',
					'body'  => 'Redefinición del perfil inferior. Tratamos la laxitud y los depósitos grasos del tercio inferior mediante láser intersticial (Endolift®).',
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
					'body'  => 'Nuestro sistema de diagnóstico y tratamiento térmico para abdomen, flancos y extremidades. Esculpe el contorno sin imponer formas estándar.',
					'url'   => home_url( '/remodelacion-corporal-laser-madrid/' ),
				),
				array(
					'meta'  => 'Protocolo Post-Maternity™',
					'title' => 'Recuperación Posgestacional',
					'body'  => 'Abordaje integral del abdomen posparto. Valoración médica de diástasis, grasa localizada, laxitud cutánea y cicatriz de cesárea.',
					'url'   => home_url( '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' ),
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
					'body'  => 'Recuperación de la matriz dérmica sin alterar volúmenes. Incrementamos la producción de colágeno y ácido hialurónico.',
					'url'   => home_url( '/calidad-piel-firmeza-luminosidad-madrid/' ),
				),
				array(
					'meta'  => 'Protocolo Surface Renewal™',
					'title' => 'Cicatrices y Poros (CO₂)',
					'body'  => 'El estándar de oro para resurfacing ablativo. Renovación epidérmica severa para marcas de acné, poros dilatados y estrías.',
					'url'   => home_url( '/cicatrices-acne-poros-textura-madrid/' ),
				),
				array(
					'meta'  => 'Protocolo Tone Correction™',
					'title' => 'Manchas y Rojeces (IPL)',
					'body'  => 'Fotorejuvenecimiento de precisión. Fragmentación de pigmento y coagulación de lesiones vasculares con control térmico absoluto.',
					'url'   => home_url( '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' ),
				),
			),
		),
	);
}

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

function nvx_treatments_catalog_markup(): string {
	$html  = '<section class="nvx-catalog" aria-label="Portafolio Clínico NUVANX">';
	$html .= '<div class="nvx-catalog__inner">';
	$html .= '<header class="nvx-catalog__intro">';
	$html .= '<span class="nvx-catalog__kicker">' . esc_html__( 'MEDICINA ESTÉTICA LÁSER', 'nuvanx-medical' ) . '</span>';
	$html .= '<h2 class="nvx-catalog__title">' . esc_html__( 'Portafolio Clínico', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-catalog__lead">' . esc_html__( 'En NUVANX las plataformas no mandan; manda el médico. Nuestro portafolio se organiza por necesidades anatómicas.', 'nuvanx-medical' ) . '</p>';
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

function nvx_treatments_logo_cloud_markup(): string {
	$html  = '<section class="nvx-logo-cloud" aria-label="Tecnología y laboratorios">';
	$html .= '<div class="nvx-logo-cloud__inner">';
	$html .= '<h2 class="nvx-logo-cloud__title">' . esc_html__( 'Tecnología médica y laboratorios aliados', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-logo-cloud__list">';
	foreach ( nvx_treatments_partner_labels() as $label ) {
		$html .= '<li class="nvx-logo-cloud__item">' . esc_html( $label ) . '</li>';
	}
	return $html . '</ul></div></section>';
}

function nvx_content_restructure_treatments_index( string $content ): string {
	if ( ! nvx_content_is_treatments_index( $content ) ) {
		return $content;
	}

	$catalog = nvx_treatments_catalog_markup();
	$cloud   = nvx_treatments_logo_cloud_markup();

	// Limpiar contenido anterior e inyectar
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
	
	// Limpiar restos
	$content = preg_replace('/<section\b[^>]*aria-label="Resumen de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;
	$content = preg_replace('/<section\b[^>]*class="[^"]*\bnvx-brand-section\b[^"]*"[^>]*>[\s\S]*?¿Qué tratamientos realizamos en NUVANX\?[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;
	$content = preg_replace('/<section\b[^>]*class="[^"]*nvx-brand-section--cta[^"]*"[^>]*>[\s\S]*?<\/section>/iu', '', $content, 1) ?? $content;
	
	$links  = '<section class="nvx-brand-section nvx-brand-section--soft" aria-label="Tu primera valoración clínica">';
	$links .= '<div class="nvx-shell nvx-brand-section__inner">';
	$links .= '<h2 class="nvx-brand-heading-2">' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$links .= '<p class="nvx-brand-body">' . esc_html__( 'No vendemos bonos de máquinas. Elaboramos planes médicos personalizados basados en evidencia anatómica.', 'nuvanx-medical' ) . '</p>';
	$links .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar mi valoración', 'nuvanx-medical' ) . '</a></p>';
	$links .= '</div></section>';

	$content = preg_replace('/<section\b[^>]*aria-label="Enlaces de interés"[^>]*>[\s\S]*?<\/section>/iu', $links, $content, 1) ?? $content;

	return is_string( $content ) ? $content : '';
}
add_filter( 'the_content', 'nvx_content_restructure_treatments_index', 18 );
