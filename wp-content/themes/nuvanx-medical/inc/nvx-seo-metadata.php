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
 * Helper to build metadata array.
 */
function nvx_meta_item( string $title, string $description ): array {
	return array(
		'title'       => $title,
		'description' => $description,
	);
}

/**
 * Metadata catalogue for the principal commercial, local and authority pages.
 *
 * @return array<string, array{title:string,description:string}>
 */
function nvx_seo_metadata_catalog(): array {
	return array(
		'home'         => nvx_meta_item( 'Medicina estética láser en Madrid | NUVANX', 'Medicina estética láser en Madrid con valoración médica, diagnóstico individual y tratamientos para rostro, piel y contorno corporal en NUVANX.' ),
		'protocolos_signature' => nvx_meta_item( 'Protocolos Signature | NUVANX Madrid', 'Protocolos Signature de medicina estética en Madrid diseñados desde el diagnóstico anatómico, la indicación médica y el seguimiento individualizado.' ),
		'clinicas'     => nvx_meta_item( 'Clínicas NUVANX Madrid | Exclusividad y Criterio Clínico', 'Tus tratamientos estéticos merecen respeto médico, no una cadena de montaje. Descubre las clínicas NUVANX en Chamberí y Salamanca-Goya.' ),
		'chamberi'     => nvx_meta_item( 'Medicina Estética en Chamberí | Tu Rostro No es un Experimento', 'Clínica NUVANX Chamberí. Alta tecnología láser y diagnóstico estricto. Si buscas calidad médica y huir de los resultados artificiales, agenda tu cita.' ),
		'goya'         => nvx_meta_item( 'Medicina Estética Salamanca-Goya | NUVANX', 'Clínica NUVANX Salamanca-Goya. Alta tecnología láser y diagnóstico estricto. La exclusividad médica en Madrid que exige tu anatomía.' ),
		'endolift'     => nvx_meta_item(
			'Endolift® Facial Madrid — Papada, Mandíbula y Cuello | NUVANX',
			'Endolift® facial en Madrid con plataforma Eufoton original. Indicación médica previa. Desde 798,60 € (ojeras). NUVANX Chamberí y Goya · Barrio Salamanca.'
		),
		'endolaser'    => nvx_meta_item( 'Endoláser Corporal Madrid | Firmeza que la Dieta No Logra', 'La dieta no pega la piel al músculo. El Endoláser Corporal en NUVANX Madrid destruye la grasa localizada y retrae la flacidez severa. Criterio médico.' ),
		'co2'          => nvx_meta_item( 'Láser CO2 Fraccionado Madrid | Borra Cicatrices sin Cremas Inútiles', 'Las cremas cosméticas no quitan las cicatrices. El Láser CO2 médico de NUVANX renueva la piel dañada de raíz. Pide valoración con el Dr. Rivera Tejeda.' ),
		'exion'        => nvx_meta_item( 'Radiofrecuencia EXION Madrid | Firmeza Facial y Corporal', 'La aparatología estética barata no funciona. EXION BTL con IA en NUVANX Madrid ofrece regeneración de ácido hialurónico y tensión cutánea demostrada.' ),
		'exilite'      => nvx_meta_item( 'IPL Médico Madrid | Elimina Manchas y Rojeces de Verdad', 'No maquilles más tus rojeces ni gastes en peelings superficiales. IPL EXILITE en NUVANX Madrid ofrece fotorejuvenecimiento médico contundente.' ),
		'equipo'       => nvx_meta_item( 'Equipo Médico NUVANX | Doctores Reales, No Vendedores', 'El Dr. Rivera Tejeda y su equipo médico asumen tu caso con rigor clínico. Cero comerciales, solo doctores diagnosticando y aplicando láser en Madrid.' ),
		'por_que_nuvanx' => nvx_meta_item( 'Por qué NUVANX | Criterio médico en Madrid', 'Cómo decide NUVANX una indicación en medicina estética: valoración médica, información clara, seguimiento y centros sanitarios autorizados en Madrid.' ),
		'inversion'    => nvx_meta_item( 'Inversión en medicina estética | NUVANX Madrid', 'Tarifas orientativas verificadas y cómo se confirma un presupuesto de medicina estética tras la valoración médica presencial en NUVANX Madrid.' ),
		'valoracion'   => nvx_meta_item( 'Consulta Médica Estética Madrid | Exige Diagnóstico Real', 'Tu cuerpo no es un menú de restaurante. Solicita una valoración estricta en NUVANX Madrid y descubre el protocolo médico que realmente necesitas.' ),
		'anti_fear_remodelacion' => nvx_meta_item(
			'Remodelación corporal sin anestesia general en Madrid | NUVANX',
			'Alternativa médica mínimamente invasiva a la liposucción tradicional. Endoláser y Endolift® en NUVANX Madrid cuando la anatomía lo permite, con anestesia local y recuperación ambulatoria.'
		),
		'blog'         => nvx_meta_item( 'Blog NUVANX | Autoridad en Medicina Estética Láser', 'Educación médica directa y sin filtros. Desmontamos mitos sobre tratamientos estéticos, láseres y la industria masificada en nuestro blog NUVANX.' ),
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
		'endolift-primeras-72-horas-que-esperar' => nvx_meta_item( 'Endolift: primeras 72 horas | Qué esperar', 'Qué es normal tras Endolift: inflamación, molestias y cuándo avisar. Guía de recuperación del protocolo clínico NUVANX en Madrid.' ),
		'endolift-ciencia-laser-subdermico' => nvx_meta_item( 'Cómo funciona Endolift | Láser subdérmico', 'Física y biología del Endolift: cómo el láser bajo la piel estimula colágeno sin cirugía. Explicación médica clara de NUVANX.' ),
		'endolift-vs-lifting-quirurgico-cuando-operarse' => nvx_meta_item( 'Endolift vs lifting quirúrgico | Madrid', 'Comparativa Endolift y lifting quirúrgico: invasividad, recuperación, resultados y cuándo valorar cirugía en NUVANX Madrid.' ),
		'ipl-medica-btl-exilite-manchas-rojeces-acne-fotorejuvenecimiento' => nvx_meta_item( 'IPL BTL EXILITE™ Madrid | Manchas y rojeces', 'IPL médica BTL EXILITE™ en Madrid para manchas, rojeces, acné y fotorejuvenecimiento tras diagnóstico y fototipo.' ),
		'exion-btl-fractional-rf-face-body' => nvx_meta_item( 'EXION® BTL Face, Body y Fractional RF', 'Diferencias entre EXION® Face, Body y Fractional RF: indicaciones, tolerancia y cuándo combinar tras valoración médica.' ),
		'well-aging-48-cambios-hormonales-piel' => nvx_meta_item( 'Well-aging a los 48 | Cambios hormonales', 'Cómo cambian piel y colágeno cuando bajan los estrógenos. Guía de well-aging con criterio médico en NUVANX Madrid.' ),
		'intrusismo-tratamientos-inyectables-riesgos' => nvx_meta_item( 'Intrusismo estético y rellenos | Riesgos', 'Riesgos del Botox y rellenos fuera de consulta médica: legalidad, complicaciones y por qué importa el criterio clínico.' ),
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

		'/protocolos-signature/' => 'protocolos_signature',
		'/remodelacion-corporal-laser-madrid/' => 'contour-architecture',
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/' => 'post-maternity',
		'/papada-definicion-mandibular-madrid/' => 'papada_mandibular',
		'/calidad-piel-firmeza-luminosidad-madrid/' => 'calidad_piel',
		'/cicatrices-acne-poros-textura-madrid/' => 'cicatrices_textura',
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' => 'manchas_rojeces',
		'/grasa-localizada-abdomen-flancos-madrid/' => 'abdomen_flancos',
		'/flacidez-grasa-localizada-brazos-madrid/' => 'brazos',
		'/grasa-espalda-zona-sujetador-madrid/' => 'espalda',
		'/flacidez-muslos-internos-subgluteo-madrid/' => 'muslos_subgluteo',
		'/tratamiento-rodillas-grasa-flacidez-madrid/' => 'rodillas',
		'/contorno-corporal-masculino-madrid/' => 'masculino',
		'/clinicas-de-medicina-estetica-nuvanx/' => 'clinicas',
		'/medicina-estetica-chamberi/' => 'chamberi',
		'/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' => 'goya',
		'/equipo-medico/' => 'equipo',
		'/por-que-nuvanx/' => 'por_que_nuvanx',
		'/inversion-medicina-estetica/' => 'inversion',
		'/madrid/valoracion/'                         => 'valoracion',
		'/remodelacion-corporal-sin-anestesia-madrid/' => 'anti_fear_remodelacion',
		'/blog/'                                      => 'blog',
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
 * Resolves metadata value from the protocol catalog.
 */
function nvx_seo_metadata_from_protocols( string $key, string $field ): ?string {
	if ( ! function_exists( 'nvx_protocol_pages_catalog' ) ) {
		return null;
	}
	$protocols = nvx_protocol_pages_catalog();
	if ( ! isset( $protocols[ $key ] ) ) {
		return null;
	}
	if ( 'title' === $field && ! empty( $protocols[ $key ]['seo_title'] ) ) {
		return (string) $protocols[ $key ]['seo_title'];
	}
	if ( 'description' === $field && ! empty( $protocols[ $key ]['description'] ) ) {
		return (string) $protocols[ $key ]['description'];
	}
	return null;
}

/**
 * Resolves metadata value from the solutions catalog.
 */
function nvx_seo_metadata_from_solutions( string $key, string $field ): ?string {
	if ( ! function_exists( 'nvx_solution_pages_catalog' ) ) {
		return null;
	}
	$solutions = nvx_solution_pages_catalog();
	if ( ! isset( $solutions[ $key ] ) ) {
		return null;
	}
	if ( 'title' === $field && ! empty( $solutions[ $key ]['seo_title'] ) ) {
		return (string) $solutions[ $key ]['seo_title'];
	}
	if ( 'description' === $field && ! empty( $solutions[ $key ]['description'] ) ) {
		return (string) $solutions[ $key ]['description'];
	}
	return null;
}

/**
 * Return one canonical metadata value for the current page.
 */
function nvx_seo_current_metadata( string $field, string $fallback = '' ): string {
	$post_meta = nvx_seo_current_blog_post_metadata();
	if ( is_array( $post_meta ) && ! empty( $post_meta[ $field ] ) ) {
		return (string) $post_meta[ $field ];
	}

	$key = nvx_seo_current_metadata_key();
	if ( null === $key ) {
		return $fallback;
	}

	$protocol_val = nvx_seo_metadata_from_protocols( $key, $field );
	if ( null !== $protocol_val ) {
		return $protocol_val;
	}

	$solution_val = nvx_seo_metadata_from_solutions( $key, $field );
	if ( null !== $solution_val ) {
		return $solution_val;
	}

	$catalog = nvx_seo_metadata_catalog();
	return ! empty( $catalog[ $key ][ $field ] ) ? (string) $catalog[ $key ][ $field ] : $fallback;
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

	$page_id = (int) get_queried_object_id();

	if ( function_exists( 'nvx_nofollow_page_ids' ) && in_array( $page_id, nvx_nofollow_page_ids(), true ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
		return $robots;
	}

	if ( function_exists( 'nvx_noindex_page_ids' ) && in_array( $page_id, nvx_noindex_page_ids(), true ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'], $robots['nofollow'] );
		return $robots;
	}

	if ( null !== nvx_seo_current_metadata_key() ) {
		$robots['index']  = true;
		$robots['follow'] = true;
		unset( $robots['noindex'], $robots['nofollow'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'nvx_seo_filter_core_robots', 100 );
