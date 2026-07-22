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
