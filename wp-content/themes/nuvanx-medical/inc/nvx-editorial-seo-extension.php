<?php
/**
 * SEO metadata for governed editorial routes.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides SEO metadata for governed editorial routes outside the principal SEO catalog.
 *
 * @return array<string, array{title: string, description: string}> Metadata keyed by route path.
 */
function nvx_editorial_seo_catalog(): array {
	return array(
		'/soluciones-medicas/' => array(
			'title'       => 'Tratamientos Estéticos Madrid | Basta de Clínicas Clonadas',
			'description' => 'Si estás harta de resultados artificiales y presupuestos engañosos, el Dr. Rivera Tejeda en NUVANX Madrid aplica criterio médico real sin vender infomerciales.',
		),
		'/remodelacion-corporal-laser-madrid/' => array(
			'title'       => 'Remodelación Corporal Láser Madrid | Di Adiós a la Lipo y Flacidez',
			'description' => 'La liposucción no es la única salida. NUVANX Contour Architecture™ en Madrid: remodelación láser precisa sin quirófano ni flacidez residual. Diagnóstico estricto.',
		),
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/' => array(
			'title'       => 'Tratamiento Postparto Madrid | Criterio Médico Frente al Engaño',
			'description' => 'Tu cuerpo no volverá mágicamente con cremas. El Dr. Rivera Tejeda evalúa tu diástasis y flacidez para un abordaje NUVANX Post-Maternity Contour™ infalible.',
		),
		'/papada-definicion-mandibular-madrid/' => array(
			'title'       => 'Eliminar Papada Madrid | Tensión Real, Sin Rostros Inflados',
			'description' => 'No inyectes más volumen para ocultar la papada. Redefine tu óvalo facial con láser médico y diagnóstico estricto en NUVANX Madrid.',
		),
		'/calidad-piel-firmeza-luminosidad-madrid/' => array(
			'title'       => 'Firmeza y Calidad de Piel Madrid | Resultados, No Cosmética',
			'description' => 'Las cremas caras no devuelvan la firmeza. NUVANX Surface Renewal™ ataca la flacidez y falta de luminosidad con láser y radiofrecuencia médica.',
		),
		'/cicatrices-acne-poros-textura-madrid/' => array(
			'title'       => 'Borrar Cicatrices de Acné Madrid | Láser Médico Avanzado',
			'description' => 'La textura irregular y las marcas de acné requieren energía médica, no peelings superficiales. Descubre el abordaje clínico de NUVANX Madrid.',
		),
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' => array(
			'title'       => 'Eliminar Manchas y Rojeces Madrid | Diagnóstico Médico',
			'description' => 'Deja de esconder tu piel bajo maquillaje. Tratamos manchas y daño solar con luz pulsada médica y criterio dermatológico en NUVANX.',
		),
		'/grasa-localizada-abdomen-flancos-madrid/' => array(
			'title'       => 'Grasa Localizada Abdomen Madrid | Láser Médico',
			'description' => 'Esa grasa rebelde en abdomen y flancos que no cede al gimnasio. Remodelación anatómica sin quirófano en NUVANX Madrid.',
		),
		'/flacidez-grasa-localizada-brazos-madrid/' => array(
			'title'       => 'Eliminar Flacidez en Brazos Madrid | Tensión y Contorno',
			'description' => 'Para que la manga caiga bien sin que la piel cuelgue. Tratamiento médico de retracción y remodelación de brazos en NUVANX.',
		),
		'/grasa-espalda-zona-sujetador-madrid/' => array(
			'title'       => 'Grasa Espalda y Sujetador Madrid | Remodelación NUVANX',
			'description' => 'Elimina el pliegue del sujetador sin pasar por quirófano. Evaluación médica precisa y tecnología láser avanzada en Madrid.',
		),
		'/flacidez-muslos-internos-subgluteo-madrid/' => array(
			'title'       => 'Flacidez Muslos y Subglúteo Madrid | Retracción Médica',
			'description' => 'La piel más delicada exige el láser más preciso. Retracción médica de flacidez en muslos internos y zona subglútea en NUVANX.',
		),
		'/tratamiento-rodillas-grasa-flacidez-madrid/' => array(
			'title'       => 'Grasa y Flacidez en Rodillas Madrid | Tratamiento Médico',
			'description' => 'Una zona pequeña que cambia toda la pierna. Tratamiento médico de flacidez y acúmulos grasos en rodillas. Agenda en NUVANX Madrid.',
		),
		'/contorno-corporal-masculino-madrid/' => array(
			'title'       => 'Contorno Corporal Masculino Madrid | Estética Médica para Él',
			'description' => 'Tratamientos diseñados para la anatomía masculina. Remodelación sin quirófano ni bajas prolongadas. Valoración médica en NUVANX.',
		),
	);
}

/**
 * Resolves editorial SEO metadata for the current request path.
 *
 * @return array|null The matching SEO metadata, or null for 404 and unmatched requests.
 */
function nvx_editorial_seo_current(): ?array {
	if ( is_404() ) {
		return null;
	}
	$path    = function_exists( 'nvx_seo_current_path' ) ? nvx_seo_current_path() : '/' . trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' ) . '/';
	$catalog = nvx_editorial_seo_catalog();
	return $catalog[ $path ] ?? null;
}

/**
 * Overrides the SEO title for the current editorial route when metadata is available.
 *
 * @param string $title The existing SEO title.
 * @return string The editorial title when configured, or the existing title otherwise.
 */
function nvx_editorial_seo_title( $title ) {
	$metadata = nvx_editorial_seo_current();
	return is_array( $metadata ) ? $metadata['title'] : $title;
}
add_filter( 'wpseo_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'pre_get_document_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'wpseo_opengraph_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'wpseo_twitter_title', 'nvx_editorial_seo_title', 120 );

/**
 * Provides the editorial SEO description for the current route.
 *
 * @param string $description The existing description to preserve when no editorial metadata matches.
 * @return string The editorial description when available, otherwise the provided description.
 */
function nvx_editorial_seo_description( $description ) {
	$metadata = nvx_editorial_seo_current();
	return is_array( $metadata ) ? $metadata['description'] : $description;
}
add_filter( 'wpseo_metadesc', 'nvx_editorial_seo_description', 120 );
add_filter( 'wpseo_opengraph_desc', 'nvx_editorial_seo_description', 120 );
add_filter( 'wpseo_twitter_description', 'nvx_editorial_seo_description', 120 );

/**
 * Provides the canonical URL for the current editorial SEO route.
 *
 * @param string $url The existing URL to preserve when no editorial metadata matches.
 * @return string The editorial route URL when metadata exists, or the provided URL otherwise.
 */
function nvx_editorial_seo_url( $url ) {
	return null === nvx_editorial_seo_current() ? $url : home_url( nvx_seo_current_path() );
}
add_filter( 'wpseo_canonical', 'nvx_editorial_seo_url', 120 );
add_filter( 'wpseo_opengraph_url', 'nvx_editorial_seo_url', 120 );

/**
 * Determines the robots directive for the current editorial SEO route.
 *
 * @param string $robots The existing robots directive.
 * @return string The existing directive when no editorial metadata exists; otherwise, `noindex, nofollow` in nonproduction environments or `index, follow`.
 */
function nvx_editorial_seo_robots( $robots ) {
	if ( null === nvx_editorial_seo_current() ) {
		return $robots;
	}
	return function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment() ? 'noindex, nofollow' : 'index, follow';
}
add_filter( 'wpseo_robots', 'nvx_editorial_seo_robots', 120 );
