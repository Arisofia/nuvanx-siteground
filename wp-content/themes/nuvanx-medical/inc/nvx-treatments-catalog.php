<?php
/**
 * Treatments index restructure — quiet-luxury catalog.
 *
 * Pattern-based (collaborators / catalog markup), not page-ID gated:
 * - Group treatments into medical categories.
 * - Editorial cards with short copy and contextual CTA.
 * - Collapse brand laundry-list into a discreet logo cloud.
 * - Remove redundant SEO summary blocks.
 * - Close with the canonical dual CTA.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Detect the treatments index from stable structural markers. */
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
 * Canonical treatment catalogue.
 *
 * Cards explain role, limits and selection criteria. Detailed recovery,
 * sessions, tariffs and comparative claims belong on approved detail pages.
 *
 * @return array<int, array{key:string,label:string,items:array<int,array{meta:string,title:string,body:string,url:string}>}>
 */
function nvx_treatments_catalog_data(): array {
	return array(
		array(
			'key'   => 'remodelacion',
			'label' => 'Remodelación láser y contorno',
			'items' => array(
				array(
					'meta'  => '01 / Láser facial',
					'title' => 'Endolift® Facial',
					'body'  => 'Tensado progresivo del óvalo facial, la línea mandibular y la papada mediante microfibras ópticas estériles de 200 a 300 micras.',
					'url'   => home_url( '/endolift-facial-papada-mandibula/' ),
				),
				array(
					'meta'  => '02 / Láser corporal',
					'title' => 'Endoláser Corporal',
					'body'  => 'Reducción de grasa localizada y mejora de firmeza con protocolo láser progresivo, adaptado a la silueta y al diagnóstico médico.',
					'url'   => home_url( '/endolaser-corporal-grasa-localizada/' ),
				),
			),
		),
		array(
			'key'   => 'regeneracion',
			'label' => 'Regeneración cutánea y calidad de piel',
			'items' => array(
				array(
					'meta'  => '03 / Renovación cutánea',
					'title' => 'Láser CO₂ Fraccionado',
					'body'  => 'Vaporización fraccionada de alta precisión para textura, poros, cicatrices y rejuvenecimiento controlado de la piel.',
					'url'   => home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ),
				),
				array(
					'meta'  => '04 / Plataforma EXION®',
					'title' => 'EXION® BTL (hub)',
					'body'  => 'Plataforma médica con aplicadores Fractional RF, Face y Body. Cada modalidad tiene mecanismo, profundidad, recuperación y objetivos distintos; la indicación se define por diagnóstico.',
					'url'   => home_url( '/exion-btl/' ),
				),
				array(
					'meta'  => '05 / EXION® Face',
					'title' => 'EXION® Face',
					'body'  => 'Aplicador no invasivo de radiofrecuencia y ultrasonido para protocolos de calidad cutánea. Los parámetros y el número de sesiones se definen según diagnóstico y tolerancia.',
					'url'   => home_url( '/exion-face/' ),
				),
				array(
					'meta'  => '06 / EXION® Body',
					'title' => 'EXION® Body',
					'body'  => 'Aplicador corporal no invasivo para protocolos de firmeza, textura y contorno. No sustituye procedimientos de reducción de grasa ni trata obesidad.',
					'url'   => home_url( '/exion-body/' ),
				),
				array(
					'meta'  => '07 / EXION® Fractional',
					'title' => 'EXION® Fractional RF',
					'body'  => 'Radiofrecuencia fraccionada con microagujas para textura, poro y cicatrices seleccionadas. Profundidad, anestesia, cuidados y período de recuperación dependen del protocolo.',
					'url'   => home_url( '/exion-fractional/' ),
				),
				array(
					'meta'  => '08 / EMFUSION®',
					'title' => 'EMFUSION®',
					'body'  => 'Aplicador orientado al soporte de barrera y a la infusión cutánea según protocolo. No sustituye procedimientos médicos de energía ni tratamientos inyectables.',
					'url'   => home_url( '/emfusion/' ),
				),
				array(
					'meta'  => '09 / Luz pulsada médica',
					'title' => 'BTL EXILITE™ IPL',
					'body'  => 'Luz pulsada intensa para indicaciones pigmentarias, vasculares y calidad cutánea seleccionadas tras diagnóstico, fototipo y ajuste de parámetros.',
					'url'   => home_url( '/btl-exilite-ipl-madrid/' ),
				),
			),
		),
		array(
			'key'   => 'medicina',
			'label' => 'Medicina estética y prevención',
			'items' => array(
				array(
					'meta'  => '11 / Biomedicina estética',
					'title' => 'Bioestimulación',
					'body'  => 'Inducción de colágeno y calidad dérmica con criterio conservador, orientada a un aspecto descansado y natural.',
					'url'   => home_url( '/medicina-estetica/' ),
				),
				array(
					'meta'  => '12 / Armonización facial',
					'title' => 'Ácido hialurónico',
					'body'  => 'Volumen y soporte selectivos para armonizar facciones sin rigidizar la expresión, siempre tras valoración médica.',
					'url'   => home_url( '/medicina-estetica/' ),
				),
				array(
					'meta'  => '13 / Contorno nasal',
					'title' => 'Rinomodelación',
					'body'  => 'Refinamiento del perfil nasal sin quirófano, con planificación anatómica y expectativa realista de resultado.',
					'url'   => home_url( '/estetica-avanzada/' ),
				),
			),
		),
	);
}

/** @return string[] */
function nvx_treatments_partner_labels(): array {
	return array(
		'DEKA',
		'BTL',
		'Teoxane',
		'Merz Pharma',
		'Vivacy',
		'Radiesse',
		'Sculptra',
		'Azzalure',
		'Croma',
		'Allergan Aesthetics',
		'Galderma',
		'IBSA',
	);
}

/** Build the canonical treatment catalogue section. */
function nvx_treatments_catalog_markup(): string {
	$html  = '<section class="nvx-catalog" aria-label="Tratamientos de precisión médica NUVANX">';
	$html .= '<div class="nvx-catalog__inner">';
	$html .= '<header class="nvx-catalog__intro">';
	$html .= '<span class="nvx-catalog__kicker">' . esc_html__( 'NUVANX · Madrid', 'nuvanx-medical' ) . '</span>';
	$html .= '<h2 class="nvx-catalog__title">' . esc_html__( 'Tratamientos de precisión médica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-catalog__lead">' . esc_html__( 'Cada protocolo combina tecnología láser avanzada y aparatología certificada. La indicación definitiva se confirma exclusivamente tras una valoración médica personalizada en Chamberí o Salamanca–Goya.', 'nuvanx-medical' ) . '</p>';
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

/** Replace long collaborator essays with a discreet name cloud. */
function nvx_treatments_logo_cloud_markup(): string {
	$html  = '<section class="nvx-logo-cloud" aria-label="Tecnología y laboratorios de referencia">';
	$html .= '<div class="nvx-logo-cloud__inner">';
	$html .= '<h2 class="nvx-logo-cloud__title">' . esc_html__( 'Tecnología y laboratorios de referencia mundial con los que colaboramos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-logo-cloud__list">';

	foreach ( nvx_treatments_partner_labels() as $label ) {
		$html .= '<li class="nvx-logo-cloud__item">' . esc_html( $label ) . '</li>';
	}

	return $html . '</ul></div></section>';
}


/** Replace legacy catalogue, collaborator, summary and CTA blocks. */
function nvx_content_restructure_treatments_index( string $content ): string {
	if ( ! nvx_content_is_treatments_index( $content ) ) {
		return $content;
	}

	$catalog = nvx_treatments_catalog_markup();
	$cloud   = nvx_treatments_logo_cloud_markup();
	// Strip legacy CMS close bands; do not inject a page-local CTA (footer owns it).
	$close_cta = '';

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

	$content = preg_replace(
		'/<section\b[^>]*aria-label="Resumen de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	) ?? $content;
	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*\bnvx-brand-section\b[^"]*"[^>]*>[\s\S]*?¿Qué tratamientos realizamos en NUVANX\?[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	) ?? $content;

	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-brand-section--cta[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
		$close_cta,
		$content,
		1,
		$count_cta
	) ?? $content;
	if ( ! $count_cta ) {
		$content = preg_replace(
			'/<section\b[^>]*aria-label="Consulta médica personalizada NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
			$close_cta,
			$content,
			1
		) ?? $content;
	}

	$links  = '<section class="nvx-brand-section nvx-brand-section--soft" aria-label="Enlaces de interés">';
	$links .= '<div class="nvx-shell nvx-brand-section__inner">';
	$links .= '<p class="nvx-brand-body">' . esc_html__( 'Explora el ', 'nuvanx-medical' );
	$links .= '<a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/equipo-medico/' ) ) . '">' . esc_html__( 'equipo médico', 'nuvanx-medical' ) . '</a>';
	$links .= esc_html__( ', las ', 'nuvanx-medical' );
	$links .= '<a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/' ) ) . '">' . esc_html__( 'clínicas', 'nuvanx-medical' ) . '</a>';
	$links .= esc_html__( ' o el área de ', 'nuvanx-medical' );
	$links .= '<a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/estetica-avanzada/' ) ) . '">' . esc_html__( 'estética avanzada', 'nuvanx-medical' ) . '</a>.';
	$links .= '</p></div></section>';

	$content = preg_replace(
		'/<section\b[^>]*aria-label="Enlaces de interés"[^>]*>[\s\S]*?<\/section>/iu',
		$links,
		$content,
		1
	) ?? $content;

	return is_string( $content ) ? $content : '';
}
add_filter( 'the_content', 'nvx_content_restructure_treatments_index', 18 );
