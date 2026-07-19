<?php
/**
 * Canonical SEO metadata and environment indexing policy.
 *
 * Keeps titles, descriptions, robots and social URLs independent from Yoast's
 * database state while preserving Yoast as the sole metadata/schema emitter.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Metadata catalogue for the principal commercial, local and authority pages.
 *
 * @return array<string, array{title:string,description:string}>
 */
function nvx_seo_metadata_catalog(): array {
	return array(
		'home'         => array(
			'title'       => 'Medicina Estética Láser Madrid | Endolift y CO₂ | NUVANX',
			'description' => 'Medicina estética láser en Madrid con equipo médico hospitalario. Endolift®, CO₂, EXION® BTL y well-aging en Chamberí y Salamanca–Goya.',
		),
		'tratamientos' => array(
			'title'       => 'Tratamientos Medicina Estética Láser Madrid | NUVANX',
			'description' => 'Tratamientos de medicina estética láser en Madrid: Endolift®, Láser CO₂, EXION® BTL, IPL y medicina facial con valoración clínica.',
		),
		'clinicas'     => array(
			'title'       => 'Clínicas NUVANX Madrid | Chamberí y Salamanca–Goya',
			'description' => 'Clínicas de medicina estética láser NUVANX en Chamberí y Salamanca–Goya. Direcciones, horarios, registros sanitarios y mapas.',
		),
		'chamberi'     => array(
			'title'       => 'Medicina estética en Chamberí | NUVANX Madrid',
			'description' => 'Clínica de medicina estética láser en Chamberí, Madrid. Equipo médico, registro sanitario CS20144 y valoración individualizada.',
		),
		'goya'         => array(
			'title'       => 'Medicina estética en Salamanca–Goya | NUVANX',
			'description' => 'Clínica de medicina estética láser en Salamanca–Goya, Madrid. Equipo médico, registro sanitario CS20073 y valoración individualizada.',
		),
		'endolift'     => array(
			'title'       => 'Endolift® Facial Madrid | Precio y Resultados | NUVANX',
			'description' => 'Endolift® facial en Madrid para papada, mandíbula y cuello. Tarifas por zona, indicaciones y recuperación. Valoración médica en NUVANX.',
		),
		'endolaser'    => array(
			'title'       => 'Endoláser Corporal Madrid | Grasa y Firmeza | NUVANX',
			'description' => 'Endoláser corporal en Madrid para grasa localizada y firmeza en abdomen, flancos, muslos y brazos. Valoración médica y presupuesto individualizado.',
		),
		'co2'          => array(
			'title'       => 'Láser CO₂ Madrid | Cicatrices, Poros y Textura | NUVANX',
			'description' => 'Láser CO₂ fraccionado en Madrid para cicatrices de acné, poros, textura y fotodaño. Recuperación estimada y tarifas en NUVANX.',
		),
		'exion'        => array(
			'title'       => 'EXION® BTL Madrid | Face, Body y Fractional RF | NUVANX',
			'description' => 'EXION® BTL en Madrid: Face, Body y Fractional RF. Modalidades seleccionadas según indicación clínica, zona y plan definido tras valoración médica.',
		),
		'exilite'      => array(
			'title'       => 'IPL BTL EXILITE™ Madrid | Manchas y Rojeces | NUVANX',
			'description' => 'IPL BTL EXILITE™ en Madrid para indicaciones pigmentarias, vasculares y calidad cutánea tras diagnóstico, fototipo y selección de parámetros.',
		),
		'equipo'       => array(
			'title'       => 'Equipo Médico NUVANX Madrid | Criterio Clínico',
			'description' => 'Equipo médico NUVANX en Madrid: colegiación, experiencia y áreas clínicas de los profesionales responsables de valoración y seguimiento.',
		),
		'por_que_nuvanx' => array(
			'title'       => 'Por qué NUVANX | Criterio médico en Madrid',
			'description' => 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.',
		),
		'inversion'    => array(
			'title'       => 'Inversión en medicina estética | NUVANX Madrid',
			'description' => 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.',
		),
		'valoracion'   => array(
			'title'       => 'Consulta médica estética en Madrid | NUVANX',
			'description' => 'Solicita una consulta médica estética en Chamberí o Salamanca–Goya. Diagnóstico, indicación y presupuesto individualizado.',
		),
		'blog'         => array(
			'title'       => 'Blog NUVANX | Medicina estética láser Madrid',
			'description' => 'Artículos de NUVANX sobre Endolift®, EXION® BTL, IPL, well-aging y criterio médico en clínicas de Madrid.',
		),
	);
}

/**
 * Canonical SEO metadata for published medical blog posts (by post_name).
 *
 * Titles ≤ 60 characters and descriptions ≤ 160 so SERP truncations stay stable.
 *
 * @return array<string, array{title:string,description:string}>
 */
function nvx_seo_blog_post_metadata_catalog(): array {
	return array(
		'endolift-primeras-72-horas-que-esperar' => array(
			'title'       => 'Endolift: primeras 72 horas | Qué esperar',
			'description' => 'Qué es normal tras Endolift: inflamación, molestias y cuándo avisar. Guía de recuperación del protocolo clínico NUVANX en Madrid.',
		),
		'endolift-ciencia-laser-subdermico' => array(
			'title'       => 'Cómo funciona Endolift | Láser subdérmico',
			'description' => 'Física y biología del Endolift: cómo el láser bajo la piel estimula colágeno sin cirugía. Explicación médica clara de NUVANX.',
		),
		'endolift-vs-lifting-quirurgico-cuando-operarse' => array(
			'title'       => 'Endolift vs lifting quirúrgico | Madrid',
			'description' => 'Comparativa Endolift y lifting quirúrgico: invasividad, recuperación, resultados y cuándo valorar cirugía en NUVANX Madrid.',
		),
		'ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento' => array(
			'title'       => 'IPL BTL EXILITE™ Madrid | Manchas y rojeces',
			'description' => 'IPL médica BTL EXILITE™ en Madrid para manchas, rojeces, acné y fotorejuvenecimiento tras diagnóstico y fototipo.',
		),
		'exion-btl-fractional-rf-face-body' => array(
			'title'       => 'EXION® BTL Face, Body y Fractional RF',
			'description' => 'Diferencias entre EXION® Face, Body y Fractional RF: indicaciones, tolerancia y cuándo combinar tras valoración médica.',
		),
		'well-aging-48-cambios-hormonales-piel' => array(
			'title'       => 'Well-aging a los 48 | Cambios hormonales',
			'description' => 'Cómo cambian piel y colágeno cuando bajan los estrógenos. Guía de well-aging con criterio médico en NUVANX Madrid.',
		),
		'intrusismo-tratamientos-inyectables-riesgos' => array(
			'title'       => 'Intrusismo estético y rellenos | Riesgos',
			'description' => 'Riesgos del Botox y rellenos fuera de consulta médica: legalidad, complicaciones y por qué importa el criterio clínico.',
		),
	);
}

/**
 * Normalize the current site path for metadata routing.
 */
function nvx_seo_current_path(): string {
	if ( function_exists( 'nvx_schema_current_path' ) ) {
		return (string) nvx_schema_current_path( (int) get_queried_object_id() );
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$uri = (string) strtok( $uri, '?' );
	return '/' . trim( $uri, '/' ) . '/';
}

/**
 * Resolve the metadata key for the current request.
 */
function nvx_seo_current_metadata_key(): ?string {
	// Never lend a legitimate title/description to a not-found route.
	if ( is_404() ) {
		return null;
	}

	if ( is_front_page() ) {
		return 'home';
	}

	// Posts index (/blog/) — not the front page.
	if ( is_home() && ! is_front_page() ) {
		return 'blog';
	}

	if ( function_exists( 'nvx_schema_resolve_treatment_key' ) ) {
		$treatment = nvx_schema_resolve_treatment_key( (int) get_queried_object_id() );
		$map       = array(
			'endolift_facial'    => 'endolift',
			'endolaser_corporal' => 'endolaser',
			'laser_co2'          => 'co2',
			'exion_btl'          => 'exion',
			'exilite_btl'        => 'exilite',
		);
		if ( isset( $map[ $treatment ] ) ) {
			return $map[ $treatment ];
		}
	}

	$path = nvx_seo_current_path();
	$map  = array(
		'/tratamientos/' => 'tratamientos',
		'/clinicas-de-medicina-estetica-nuvanx/' => 'clinicas',
		'/medicina-estetica-chamberi/' => 'chamberi',
		'/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' => 'goya',
		'/equipo-medico/' => 'equipo',
		'/por-que-nuvanx/' => 'por_que_nuvanx',
		'/inversion-medicina-estetica/' => 'inversion',
		'/madrid/valoracion/' => 'valoracion',
		'/blog/' => 'blog',
	);

	return $map[ $path ] ?? null;
}

/**
 * Resolve metadata for a single published post by slug when catalogued.
 *
 * @return array{title?:string,description?:string}|null
 */
function nvx_seo_current_blog_post_metadata(): ?array {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( '' === $slug ) {
		return null;
	}

	$catalog = nvx_seo_blog_post_metadata_catalog();
	return $catalog[ $slug ] ?? null;
}

/**
 * Return one canonical metadata value for the current page.
 */
function nvx_seo_current_metadata( string $field, string $fallback = '' ): string {
	$post_meta = nvx_seo_current_blog_post_metadata();
	if ( is_array( $post_meta ) && ! empty( $post_meta[ $field ] ) ) {
		return (string) $post_meta[ $field ];
	}

	$key     = nvx_seo_current_metadata_key();
	$catalog = nvx_seo_metadata_catalog();

	if ( null === $key || empty( $catalog[ $key ][ $field ] ) ) {
		return $fallback;
	}

	return (string) $catalog[ $key ][ $field ];
}

/**
 * Whether the current installation is not the public production host.
 */
function nvx_seo_is_nonproduction_environment(): bool {
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	$host        = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$public      = in_array( strtolower( $host ), array( 'nuvanx.com', 'www.nuvanx.com' ), true );

	return 'production' !== $environment || ! $public;
}

/**
 * Current page URL without query parameters.
 */
function nvx_seo_current_canonical_url(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$page_id = (int) get_queried_object_id();
	if ( $page_id > 0 ) {
		$url = get_permalink( $page_id );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( nvx_seo_current_path() );
}

/** Yoast and core title. */
function nvx_seo_filter_title( $title ) {
	return nvx_seo_current_metadata( 'title', (string) $title );
}
add_filter( 'wpseo_title', 'nvx_seo_filter_title', 100 );
add_filter( 'pre_get_document_title', 'nvx_seo_filter_title', 100 );
add_filter( 'wpseo_opengraph_title', 'nvx_seo_filter_title', 100 );
add_filter( 'wpseo_twitter_title', 'nvx_seo_filter_title', 100 );

/** Yoast and social descriptions. */
function nvx_seo_filter_description( $description ) {
	return nvx_seo_current_metadata( 'description', (string) $description );
}
add_filter( 'wpseo_metadesc', 'nvx_seo_filter_description', 100 );
add_filter( 'wpseo_opengraph_desc', 'nvx_seo_filter_description', 100 );
add_filter( 'wpseo_twitter_description', 'nvx_seo_filter_description', 100 );

/**
 * Keep canonical and Open Graph URLs on the current public host.
 */
function nvx_seo_filter_canonical_url( $url ) {
	if ( nvx_seo_is_nonproduction_environment() ) {
		return $url;
	}

	// Keep blog posts and catalogued pages on the public host.
	if ( null === nvx_seo_current_metadata_key() && null === nvx_seo_current_blog_post_metadata() ) {
		return $url;
	}

	return nvx_seo_current_canonical_url();
}
add_filter( 'wpseo_canonical', 'nvx_seo_filter_canonical_url', 100 );
add_filter( 'wpseo_opengraph_url', 'nvx_seo_filter_canonical_url', 100 );

/**
 * Environment-aware Yoast robots policy.
 */
function nvx_seo_filter_yoast_robots( $robots ) {
	if ( nvx_seo_is_nonproduction_environment() ) {
		return 'noindex, nofollow';
	}

	// Archive pages with a few repeating cards add no unique clinical value yet.
	// Keep them crawlable through the linked articles, not as competing thin URLs.
	if ( is_category() || is_tag() ) {
		return 'noindex, follow';
	}

	if ( null !== nvx_seo_current_metadata_key() ) {
		$page_id = (int) get_queried_object_id();
		if ( ! function_exists( 'nvx_noindex_page_ids' ) || ! in_array( $page_id, nvx_noindex_page_ids(), true ) ) {
			return 'index, follow';
		}
	}

	return $robots;
}
add_filter( 'wpseo_robots', 'nvx_seo_filter_yoast_robots', 100 );

/**
 * Environment-aware WordPress robots array for non-Yoast consumers.
 *
 * @param array<string,bool> $robots Robots directives.
 * @return array<string,bool>
 */
function nvx_seo_filter_core_robots( array $robots ): array {
	if ( nvx_seo_is_nonproduction_environment() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
		return $robots;
	}

	if ( is_category() || is_tag() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'], $robots['nofollow'] );
		return $robots;
	}

	if ( null !== nvx_seo_current_metadata_key() ) {
		$page_id = (int) get_queried_object_id();
		if ( ! function_exists( 'nvx_noindex_page_ids' ) || ! in_array( $page_id, nvx_noindex_page_ids(), true ) ) {
			$robots['index']  = true;
			$robots['follow'] = true;
			unset( $robots['noindex'], $robots['nofollow'] );
		}
	}

	return $robots;
}
add_filter( 'wp_robots', 'nvx_seo_filter_core_robots', 100 );
