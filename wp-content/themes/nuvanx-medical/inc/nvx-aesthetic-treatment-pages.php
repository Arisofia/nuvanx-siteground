<?php
/**
 * Canonical facial aesthetic treatment pages.
 *
 * One versioned catalogue drives visible content, metadata, FAQ schema and the
 * staging-only page seeder. Production pages remain drafts until medical review.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical catalogue for facial injectable/regenerative treatment pages.
 *
 * Orientative reference tariffs, session counts, durations and clinical parameters
 * are published here, subject to individual medical assessment in consultation.
 *
 * @return array<string, array<string, mixed>>
 */

/**
 * Normalize aesthetic catalog entry clinical fields from nested sources.
 *
 * @param array<string,mixed> $entry Catalog record reference.
 */
function nvx_aesthetic_catalog_normalize_entry( array &$entry ): void {
	$sources = array(
		$entry['protocol'] ?? array(),
		$entry['schema'] ?? array(),
	);
	foreach ( $sources as $src ) {
		if ( ! is_array( $src ) ) {
			continue;
		}
		if ( empty( $entry['price_range'] ) && ! empty( $src['price_range'] ) ) {
			$entry['price_range'] = $src['price_range'];
		}
		if ( empty( $entry['session_time'] ) && ! empty( $src['session_time'] ) ) {
			$entry['session_time'] = $src['session_time'];
		}
		if ( empty( $entry['duration'] ) ) {
			if ( ! empty( $src['duration_result'] ) ) {
				$entry['duration'] = $src['duration_result'];
			} elseif ( ! empty( $src['duration'] ) ) {
				$entry['duration'] = $src['duration'];
			}
		}
		if ( empty( $entry['anesthesia'] ) && ! empty( $src['anesthesia'] ) ) {
			$entry['anesthesia'] = $src['anesthesia'];
		}
		if ( empty( $entry['brands'] ) ) {
			if ( ! empty( $src['brands'] ) ) {
				$entry['brands'] = (array) $src['brands'];
			} elseif ( ! empty( $src['products_used'] ) ) {
				$entry['brands'] = (array) $src['products_used'];
			}
		}
		if ( empty( $entry['sessions'] ) && ! empty( $src['sessions'] ) ) {
			$entry['sessions'] = $src['sessions'];
		}
		if ( empty( $entry['downtime'] ) && ! empty( $src['downtime'] ) ) {
			$entry['downtime'] = $src['downtime'];
		}
	}
}

function nvx_aesthetic_treatment_catalog(): array {
	static $catalog = null;

	if ( null === $catalog ) {
		require_once __DIR__ . '/nvx-catalog-json.php';
		$raw_catalog = nvx_catalog_json_resolved( 'aesthetic-treatment-pages.json' );

		if ( is_array( $raw_catalog ) ) {
			foreach ( $raw_catalog as &$entry ) {
				if ( is_array( $entry ) ) {
					nvx_aesthetic_catalog_normalize_entry( $entry );
				}
			}
			unset( $entry );
		}


		$catalog = nvx_catalog_filter_records(
			$raw_catalog,
			array(
				'slug',
				'kicker',
				'h1',
				'lead',
				'description',
				'diagnosis',
				'indications',
				'precautions',
				'mechanism',
				'process',
				'evolution',
				'risks',
				'combinations',
				'faqs',
				'schema',
			),
			'aesthetic-treatment-pages.json'
		);
	}


	return $catalog;
}


/** Resolve a treatment key from slug or current singular page. */
function nvx_aesthetic_treatment_key_from_slug( string $slug ): ?string {
	$slug = trim( $slug, '/' );
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( $slug === $entry['slug'] ) {
			return $key;
		}
	}
	return null;
}

function nvx_aesthetic_treatment_current_key(): ?string {
	if ( is_admin() || ! is_singular( 'page' ) ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return nvx_aesthetic_treatment_key_from_slug( $slug );
}

/** @return array<string, array<int, array{q:string,a:string}>> */
function nvx_aesthetic_treatment_faq_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['faqs'];
	}
	return $result;
}

/** @return array<string, array<string, mixed>> */
function nvx_aesthetic_treatment_schema_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['schema'];
	}
	return $result;
}

function nvx_aesthetic_treatment_list_markup( array $items ): string {
	$html = '<ul class="nvx-aes-panel-list" role="list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	return $html . '</ul>';
}

function nvx_aesthetic_treatment_faq_markup( array $faqs ): string {
	$html = '<div class="nvx-faq nvx-aes-faq-list">';
	foreach ( $faqs as $index => $faq ) {
		$html .= '<details class="nvx-brand-faq-item"' . ( 0 === $index ? ' open' : '' ) . '>';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}
	return $html . '</div>';
}

function nvx_aesthetic_treatment_cta_markup(): string {
	$buttons = function_exists( 'nvx_cta_pair_markup' )
		? nvx_cta_pair_markup( 'nvx-aesthetic-treatment__ctas' )
		: '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Reservar valoración médica', 'nuvanx-medical' ) . '</a>';

	return '<section class="nvx-aes-section nvx-aesthetic-treatment__cta" aria-labelledby="nvx-aesthetic-treatment-cta-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">Valoración médica</p><h2 id="nvx-aesthetic-treatment-cta-title" class="nvx-aes-heading">Diagnóstico antes que producto</h2><p class="nvx-aes-body nvx-aes-body--lead">La indicación, el material, el plano y el presupuesto se confirman después de explorar la anatomía, los antecedentes y los objetivos.</p>' . $buttons . '</div></section>';
}

/**
 * Renders the optional clinical parameters section for an aesthetic treatment page.
 *
 * @param array $entry Catalog entry.
 * @return string The rendered HTML section, or an empty string when no parameters apply.
 */
function nvx_aesthetic_treatment_details_markup( array $entry ): string {
	$text_fields = array(
		'price_range'  => __( 'Tarifa orientativa:', 'nuvanx-medical' ),
		'sessions'     => __( 'Sesiones orientativas:', 'nuvanx-medical' ),
		'session_time' => __( 'Duración en cabina:', 'nuvanx-medical' ),
		'duration'     => __( 'Durabilidad orientativa:', 'nuvanx-medical' ),
		'downtime'     => __( 'Recuperación y downtime:', 'nuvanx-medical' ),
		'anesthesia'   => __( 'Anestesia:', 'nuvanx-medical' ),
	);

	$items = '';
	foreach ( $text_fields as $key => $label ) {
		if ( ! empty( $entry[ $key ] ) ) {
			$items .= '<li><strong>' . esc_html( $label ) . '</strong> ' . esc_html( (string) $entry[ $key ] ) . '</li>';
		}
	}

	if ( ! empty( $entry['brands'] ) && is_array( $entry['brands'] ) ) {
		$items .= '<li><strong>' . esc_html__( 'Productos de referencia:', 'nuvanx-medical' ) . '</strong> ' . esc_html( implode( ', ', $entry['brands'] ) ) . '</li>';
	}
	if ( ! empty( $entry['techniques'] ) && is_array( $entry['techniques'] ) ) {
		$items .= '<li><strong>' . esc_html__( 'Técnicas / Áreas de abordaje:', 'nuvanx-medical' ) . '</strong> ' . esc_html( implode( ' · ', $entry['techniques'] ) ) . '</li>';
	}

	if ( '' === $items ) {
		return '';
	}

	return '<section class="nvx-aes-section nvx-aes-clinical-data" aria-labelledby="nvx-aesthetic-data-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">'
		. esc_html__( 'Parámetros de tratamiento', 'nuvanx-medical' )
		. '</p><h2 id="nvx-aesthetic-data-title" class="nvx-aes-heading">'
		. esc_html__( 'Datos clínicos y de consulta', 'nuvanx-medical' )
		. '</h2><ul class="nvx-strategy-checklist">' . $items . '</ul></div></section>';
}

/**
 * Renders the complete HTML body for a canonical aesthetic treatment page.
 *
 * @param string $key The catalogue key identifying the treatment.
 * @return string The rendered page HTML, or an empty string when the treatment is unavailable.
 */
function nvx_aesthetic_treatment_render( string $key ): string {
	$catalog = nvx_aesthetic_treatment_catalog();
	if ( empty( $catalog[ $key ] ) ) {
		return '';
	}
	$entry = $catalog[ $key ];

	$html  = '<div class="nvx-aesthetic-treatment nvx-aesthetic-treatment--' . esc_attr( $key ) . '">';
	$html .= '<section class="nvx-brand-hero" aria-labelledby="nvx-aesthetic-treatment-h1"><div class="nvx-brand-hero__inner"><div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $entry['kicker'] ) . '</p>';
	$html .= '<h1 id="nvx-aesthetic-treatment-h1" class="nvx-brand-hero__title">' . esc_html( $entry['h1'] ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html( $entry['lead'] ) . '</p>';
	$html .= ( function_exists( 'nvx_cta_pair_markup' ) ? nvx_cta_pair_markup( 'nvx-brand-actions' ) : '<div class="nvx-brand-actions"><a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Reservar valoración médica', 'nuvanx-medical' ) . '</a></div>' );
	$html .= '<p class="nvx-brand-meta">Chamberí (CS20144) · Salamanca–Goya (CS20073) · Según valoración médica</p>';
	$html .= '</div></div></section>';

	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() ) {
		$html .= '<aside class="nvx-aes-section nvx-aesthetic-treatment__review" aria-label="Estado de revisión médica"><div class="nvx-aes-section__inner"><p class="nvx-aes-body"><strong>Revisión médica pendiente.</strong> Esta página permanece bloqueada para publicación en producción hasta registrar revisor, fecha y aprobación clínica.</p></div></aside>';
	}

	$html .= '<section class="nvx-aes-section" aria-labelledby="nvx-aesthetic-diagnosis-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">Diagnóstico</p><h2 id="nvx-aesthetic-diagnosis-title" class="nvx-aes-heading">Qué problema aborda y cuándo no debe tratarse</h2><p class="nvx-aes-body nvx-aes-body--lead">' . esc_html( $entry['diagnosis'] ) . '</p><div class="nvx-aes-card-grid"><article class="nvx-aes-card"><h3 class="nvx-aes-card__title">Indicaciones seleccionadas</h3>' . nvx_aesthetic_treatment_list_markup( $entry['indications'] ) . '</article><article class="nvx-aes-card"><h3 class="nvx-aes-card__title">Precauciones y no indicación</h3>' . nvx_aesthetic_treatment_list_markup( $entry['precautions'] ) . '</article></div></div></section>';

	$html .= '<section class="nvx-aes-section" aria-labelledby="nvx-aesthetic-mechanism-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">Mecanismo</p><h2 id="nvx-aesthetic-mechanism-title" class="nvx-aes-heading">Cómo se plantea el tratamiento</h2><p class="nvx-aes-body nvx-aes-body--lead">' . esc_html( $entry['mechanism'] ) . '</p><ol class="nvx-aes-panel-list">';
	foreach ( $entry['process'] as $step ) {
		$html .= '<li>' . esc_html( $step ) . '</li>';
	}
	$html .= '</ol></div></section>';
	$html .= nvx_aesthetic_treatment_details_markup( $entry );
	$html .= '<section class="nvx-aes-section" aria-labelledby="nvx-aesthetic-evolution-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">Evolución y seguridad</p><h2 id="nvx-aesthetic-evolution-title" class="nvx-aes-heading">Recuperación, límites y riesgos</h2><p class="nvx-aes-body nvx-aes-body--lead">' . esc_html( $entry['evolution'] ) . '</p><div class="nvx-aes-card-grid"><article class="nvx-aes-card"><h3 class="nvx-aes-card__title">Riesgos que deben explicarse</h3>' . nvx_aesthetic_treatment_list_markup( $entry['risks'] ) . '</article><article class="nvx-aes-card"><h3 class="nvx-aes-card__title">Combinaciones posibles</h3>' . nvx_aesthetic_treatment_list_markup( $entry['combinations'] ) . '</article></div></div></section>';

	$html .= '<section class="nvx-aes-section nvx-aes-faq" aria-labelledby="nvx-aesthetic-faq-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">Preguntas frecuentes</p><h2 id="nvx-aesthetic-faq-title" class="nvx-aes-heading">Respuestas clínicas antes de decidir</h2>' . nvx_aesthetic_treatment_faq_markup( $entry['faqs'] ) . '</div></section>';

	$html .= '<section class="nvx-aes-section" aria-labelledby="nvx-aesthetic-clinics-title"><div class="nvx-aes-section__inner"><p class="nvx-aes-kicker">NUVANX Madrid</p><h2 id="nvx-aesthetic-clinics-title" class="nvx-aes-heading">Valoración en Chamberí o Salamanca–Goya</h2><p class="nvx-aes-body"><a href="' . esc_url( home_url( '/medicina-estetica-chamberi/' ) ) . '">Clínica NUVANX Chamberí</a> · <a href="' . esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' ) ) . '">Clínica NUVANX Salamanca–Goya</a></p></div></section>';
	$html .= nvx_aesthetic_treatment_cta_markup();

	return $html . '</div>';
}

add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) ) {
			return $owner; }
		if ( function_exists( 'nvx_aesthetic_treatment_current_key' ) && null !== nvx_aesthetic_treatment_current_key() ) {
			return 'nvx_aesthetic_treatment_pages';
		}
		return $owner;
	}
);

/** Replace marker content with the canonical versioned page. */
function nvx_aesthetic_treatment_filter_content( string $content ): string {
	$owner = function_exists( 'nvx_get_page_owner' ) ? nvx_get_page_owner() : null;
	if ( $owner !== 'nvx_aesthetic_treatment_pages' ) {
		return $content;
	}

	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$key = nvx_aesthetic_treatment_current_key();
	return null === $key ? $content : nvx_aesthetic_treatment_render( $key );
}
add_filter( 'the_content', 'nvx_aesthetic_treatment_filter_content', NVX_HOOK_PRIO_AESTHETIC_TREATMENT );

/** Canonical SEO metadata for the four pages. */
function nvx_aesthetic_treatment_current_entry(): ?array {
	$key     = nvx_aesthetic_treatment_current_key();
	$catalog = nvx_aesthetic_treatment_catalog();
	return null !== $key && isset( $catalog[ $key ] ) ? $catalog[ $key ] : null;
}

function nvx_aesthetic_treatment_filter_title( $title ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $title : $entry['seo_title'];
}
add_filter( 'wpseo_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_opengraph_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_twitter_title', 'nvx_aesthetic_treatment_filter_title', 90 );

function nvx_aesthetic_treatment_filter_description( $description ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $description : $entry['description'];
}
add_filter( 'wpseo_metadesc', 'nvx_aesthetic_treatment_filter_description', 90 );
add_filter( 'wpseo_opengraph_desc', 'nvx_aesthetic_treatment_filter_description', 90 );
add_filter( 'wpseo_twitter_description', 'nvx_aesthetic_treatment_filter_description', 90 );

function nvx_aesthetic_treatment_filter_canonical( $canonical ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $canonical : home_url( '/' . $entry['slug'] . '/' );
}
// HTML canonical: nvx-document-governance only. OG URL may still be adjusted.
add_filter( 'wpseo_opengraph_url', 'nvx_aesthetic_treatment_filter_canonical', 90 );

function nvx_aesthetic_treatment_document_title( array $parts ): array {
	$entry = nvx_aesthetic_treatment_current_entry();
	if ( null !== $entry ) {
		$parts['title'] = $entry['h1'];
	}
	return $parts;
}
add_filter( 'document_title_parts', 'nvx_aesthetic_treatment_document_title', 90 );

/** Seed the four pages only in staging2, which is globally noindex. */
function nvx_aesthetic_treatment_seed_staging_pages(): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}

	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$page = get_page_by_path( $entry['slug'], OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $entry['h1'],
				'post_name'    => $entry['slug'],
				'post_excerpt' => $entry['description'],
				'post_content' => '<div class="nvx-aesthetic-treatment-source" data-nvx-treatment="' . esc_attr( $key ) . '"></div>',
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_nvx_aesthetic_treatment_key', $key );
			update_post_meta( $post_id, '_nvx_medical_review_status', 'pending' );
		}
	}
}
add_action( 'init', 'nvx_aesthetic_treatment_seed_staging_pages', 30 );
