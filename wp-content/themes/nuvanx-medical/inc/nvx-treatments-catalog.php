<?php
/**
 * Treatments index restructure — quiet-luxury catalog.
 *
 * Pattern-based (collaborators / catalog markup), not page-ID gated:
 * - Group treatments into 3 medical categories
 * - Editorial cards with short copy + contextual CTA
 * - Collapse brand laundry-list into discreet logo cloud
 * - Remove redundant SEO bullet block
 * - Premium dual-CTA close band
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect treatments index content by structural markers.
 */
function nvx_content_is_treatments_index( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-catalog' ) ) {
		return false; // Already transformed.
	}

	return (bool) preg_match(
		'/nvx-brand-collaborators|Selección profesional|Catálogo de tratamientos NUVANX|aria-label="Catálogo de tratamientos/iu',
		$content
	);
}

/**
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
					'meta'  => '04 / Radiofrecuencia + ultrasonido',
					'title' => 'EXION® BTL',
					'body'  => 'Estimulación profunda para aumentar de forma natural hasta un 224% el ácido hialurónico endógeno, sin infiltrar rellenos.',
					'url'   => home_url( '/exion-btl/' ),
				),
				array(
					'meta'  => '05 / Luz pulsada médica',
					'title' => 'BTL EXILITE™ IPL',
					'body'  => 'Fotorejuvenecimiento y mejora de manchas, rojeces y calidad cutánea con parámetros ajustados al fototipo y la indicación.',
					'url'   => home_url( '/btl-exilite-ipl-madrid/' ),
				),
			),
		),
		array(
			'key'   => 'medicina',
			'label' => 'Medicina estética y prevención',
			'items' => array(
				array(
					'meta'  => '06 / Biomedicina estética',
					'title' => 'Bioestimulación',
					'body'  => 'Inducción de colágeno y calidad dérmica con criterio conservador, orientada a un aspecto descansado y natural.',
					'url'   => home_url( '/medicina-estetica/' ),
				),
				array(
					'meta'  => '07 / Armonización facial',
					'title' => 'Ácido hialurónico',
					'body'  => 'Volumen y soporte selectivos para armonizar facciones sin rigidizar la expresión, siempre tras valoración médica.',
					'url'   => home_url( '/medicina-estetica/' ),
				),
				array(
					'meta'  => '08 / Contorno nasal',
					'title' => 'Rinomodelación',
					'body'  => 'Refinamiento del perfil nasal sin quirófano, con planificación anatómica y expectativa realista de resultado.',
					'url'   => home_url( '/estetica-avanzada/' ),
				),
			),
		),
	);
}

/**
 * Partner names for discreet logo cloud (no long brand essays).
 *
 * @return string[]
 */
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

/**
 * Premium catalog section markup.
 */
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

	$html .= '</div></section>';

	return $html;
}

/**
 * Discreet partner logo cloud (replaces brand essays).
 */
function nvx_treatments_logo_cloud_markup(): string {
	$html  = '<section class="nvx-logo-cloud" aria-label="Tecnología y laboratorios de referencia">';
	$html .= '<div class="nvx-logo-cloud__inner">';
	$html .= '<h2 class="nvx-logo-cloud__title">' . esc_html__( 'Tecnología y laboratorios de referencia mundial con los que colaboramos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-logo-cloud__list">';

	foreach ( nvx_treatments_partner_labels() as $label ) {
		$html .= '<li class="nvx-logo-cloud__item">' . esc_html( $label ) . '</li>';
	}

	$html .= '</ul></div></section>';

	return $html;
}

/**
 * Closing dual-CTA band (consistent with site conversion system).
 */
function nvx_treatments_close_cta_markup(): string {
	if ( ! function_exists( 'nvx_cta_pair_markup' ) ) {
		return '';
	}

	$html  = '<section class="nvx-catalog-close" aria-label="Reservar valoración médica">';
	$html .= '<div class="nvx-catalog-close__inner">';
	$html .= '<div>';
	$html .= '<p class="nvx-catalog-close__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-catalog-close__title">' . esc_html__( '¿No sabes por dónde empezar?', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-catalog-close__text">' . esc_html__( 'Revisamos tu caso en una valoración médica gratuita presencial y te orientamos hacia el protocolo más adecuado, con criterio clínico y expectativa realista.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= nvx_cta_pair_markup( 'nvx-catalog-close__actions' );
	$html .= '</div></section>';

	return $html;
}

/**
 * Replace catalog / collaborators / SEO summary / weak CTA with premium structure.
 */
function nvx_content_restructure_treatments_index( string $content ): string {
	if ( ! nvx_content_is_treatments_index( $content ) ) {
		return $content;
	}

	$catalog   = nvx_treatments_catalog_markup();
	$cloud     = nvx_treatments_logo_cloud_markup();
	$close_cta = nvx_treatments_close_cta_markup();

	// 1) Replace main catalog section.
	$content = preg_replace(
		'/<section\b[^>]*aria-label="Catálogo de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
		$catalog,
		$content,
		1,
		$count_catalog
	);
	if ( ! $count_catalog ) {
		$content = preg_replace(
			'/<section\b[^>]*class="[^"]*\bnvx-brand-section\b(?![^"]*collaborators)(?![^"]*cta)[^"]*"[^>]*>[\s\S]*?(?:Áreas de tratamiento|Facial, corporal, láser)[\s\S]*?<\/section>/iu',
			$catalog,
			$content,
			1
		);
	}

	// 2) Brand essays → discreet logo cloud.
	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-brand-collaborators[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
		$cloud,
		$content,
		1,
		$count_collab
	);
	if ( ! $count_collab ) {
		$content = preg_replace(
			'/<section\b[^>]*aria-label="[^"]*Marcas colaboradoras[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			$cloud,
			$content,
			1
		);
	}

	// 3) Remove redundant SEO summary list.
	$content = preg_replace(
		'/<section\b[^>]*aria-label="Resumen de tratamientos NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	);
	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*\bnvx-brand-section\b[^"]*"[^>]*>[\s\S]*?¿Qué tratamientos realizamos en NUVANX\?[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	);

	// 4) Replace plain CTA section with dual-CTA premium close.
	$content = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-brand-section--cta[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
		$close_cta,
		$content,
		1,
		$count_cta
	);
	if ( ! $count_cta ) {
		$content = preg_replace(
			'/<section\b[^>]*aria-label="Consulta médica personalizada NUVANX"[^>]*>[\s\S]*?<\/section>/iu',
			$close_cta,
			$content,
			1
		);
	}

	// 5) Soften "Enlaces de interés" if it still sells the same CTA thrice — keep short.
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
	);

	return is_string( $content ) ? $content : '';
}
add_filter( 'the_content', 'nvx_content_restructure_treatments_index', 18 );
