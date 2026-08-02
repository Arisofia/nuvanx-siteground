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
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_json_resolved( 'seo-metadata.json' );
}

/**
 * Canonical SEO metadata for published medical blog posts (by post_name).
 *
 * Titles ≤ 60 characters and descriptions ≤ 160 so SERP truncations stay stable.
 *
 * @return array<string, array{title:string,description:string}>
 */
function nvx_seo_blog_post_metadata_catalog(): array {
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_json_resolved( 'seo-blog-post-metadata.json' );
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
		'/soluciones-medicas/' => 'soluciones',
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
 *
 * Explicitly guards staging2 (staging2.nuvanx.com): even if WP_ENVIRONMENT_TYPE
 * is set to 'production' or host detection varies, staging2 is a QA/review host
 * and must always be treated as non-production to enforce noindex directives
 * and prevent search engine indexing of pre-release content.
 */
function nvx_seo_is_nonproduction_environment(): bool {
	// Staging2 must always be treated as non-production regardless of host or WP_ENVIRONMENT_TYPE.
	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() ) {
		return true;
	}

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
 * Keep Open Graph URLs on the current public host (production).
 *
 * HTML link[rel=canonical] is emitted only by nvx-document-governance and
 * Yoast's wpseo_canonical is suppressed there — do not re-hook that filter.
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
