<?php
/**
 * SEO metadata for governed editorial routes.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Metadata for governed editorial routes outside the principal SEO catalogue. */
function nvx_editorial_seo_catalog(): array {
	return array(
		'/soluciones-medicas/' => array(
			'title'       => 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid',
			'description' => 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.',
		),
		'/remodelacion-corporal-laser-madrid/' => array(
			'title'       => 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
			'description' => 'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas para grasa localizada, laxitud y continuidad tras valoración médica.',
		),
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/' => array(
			'title'       => 'Tratamiento postparto abdomen Madrid | NUVANX',
			'description' => 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.',
		),
	);
}

function nvx_editorial_seo_current(): ?array {
	if ( is_404() ) {
		return null;
	}
	$path = function_exists( 'nvx_seo_current_path' )
		? nvx_seo_current_path()
		: '/' . trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' ) . '/';
	$catalog = nvx_editorial_seo_catalog();
	return $catalog[ $path ] ?? null;
}

function nvx_editorial_seo_title( $title ) {
	$metadata = nvx_editorial_seo_current();
	return is_array( $metadata ) ? $metadata['title'] : $title;
}
add_filter( 'wpseo_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'pre_get_document_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'wpseo_opengraph_title', 'nvx_editorial_seo_title', 120 );
add_filter( 'wpseo_twitter_title', 'nvx_editorial_seo_title', 120 );

function nvx_editorial_seo_description( $description ) {
	$metadata = nvx_editorial_seo_current();
	return is_array( $metadata ) ? $metadata['description'] : $description;
}
add_filter( 'wpseo_metadesc', 'nvx_editorial_seo_description', 120 );
add_filter( 'wpseo_opengraph_desc', 'nvx_editorial_seo_description', 120 );
add_filter( 'wpseo_twitter_description', 'nvx_editorial_seo_description', 120 );

function nvx_editorial_seo_url( $url ) {
	return null === nvx_editorial_seo_current() ? $url : home_url( nvx_seo_current_path() );
}
add_filter( 'wpseo_canonical', 'nvx_editorial_seo_url', 120 );
add_filter( 'wpseo_opengraph_url', 'nvx_editorial_seo_url', 120 );

function nvx_editorial_seo_robots( $robots ) {
	if ( null === nvx_editorial_seo_current() ) {
		return $robots;
	}
	return function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment()
		? 'noindex, nofollow'
		: 'index, follow';
}
add_filter( 'wpseo_robots', 'nvx_editorial_seo_robots', 120 );
