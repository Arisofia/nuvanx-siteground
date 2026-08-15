<?php
/**
 * DB-authoritative runtime hardening for governed journal routes.
 *
 * This module is loaded during theme bootstrap, then rebinds the main query on
 * the early `wp` hook. That timing is deliberate: loading only from
 * single-post.php is too late because SEO/indexable integrations may have
 * already derived presentation state from a stale singular query.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NVX_GOVERNED_BLOG_RUNTIME_CONTRACT' ) ) {
	define( 'NVX_GOVERNED_BLOG_RUNTIME_CONTRACT', '20260815-db-authoritative-wp-bootstrap-v2' );
}

/** Actual one-segment public slug, independent of WP_Query/global post state. */
function nvx_governed_blog_runtime_request_slug(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = wp_parse_url( $uri, PHP_URL_PATH );
	$path = is_string( $path ) ? trim( $path, '/' ) : '';

	if ( '' === $path || false !== strpos( $path, '/' ) ) {
		return '';
	}

	return sanitize_title( $path );
}

/**
 * Resolve a governed published post directly from wp_posts.
 *
 * Persistent/object caches are repaired only after the authoritative row has
 * been validated. The result is memoized for the remainder of this request.
 */
function nvx_governed_blog_runtime_db_post_by_slug( string $slug ): ?WP_Post {
	static $memo = array();

	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return null;
	}
	if ( array_key_exists( $slug, $memo ) ) {
		return $memo[ $slug ];
	}

	global $wpdb;
	if ( ! isset( $wpdb ) || ! ( $wpdb instanceof wpdb ) ) {
		$memo[ $slug ] = null;
		return null;
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'post' AND post_status = 'publish' ORDER BY ID ASC LIMIT 1",
			$slug
		)
	);

	if (
		! is_object( $row )
		|| ! isset( $row->ID, $row->post_name, $row->post_status, $row->post_type )
		|| (int) $row->ID <= 0
		|| 'publish' !== (string) $row->post_status
		|| 'post' !== (string) $row->post_type
		|| $slug !== (string) $row->post_name
	) {
		$memo[ $slug ] = null;
		return null;
	}

	$post = new WP_Post( $row );
	clean_post_cache( (int) $row->ID );
	wp_cache_set( (int) $row->ID, $post, 'posts' );
	$memo[ $slug ] = $post;

	return $post;
}

/**
 * Resolve the exact governed request from path + versioned SEO catalog + DB.
 *
 * @return array{slug:string,path:string,post:WP_Post,metadata:array<string,mixed>}|null
 */
function nvx_governed_blog_runtime_context(): ?array {
	if ( ! function_exists( 'nvx_seo_blog_post_metadata_catalog' ) ) {
		return null;
	}

	$slug = nvx_governed_blog_runtime_request_slug();
	if ( '' === $slug ) {
		return null;
	}

	$catalog = nvx_seo_blog_post_metadata_catalog();
	if ( ! isset( $catalog[ $slug ] ) || ! is_array( $catalog[ $slug ] ) ) {
		return null;
	}

	$post = nvx_governed_blog_runtime_db_post_by_slug( $slug );
	if ( ! ( $post instanceof WP_Post ) ) {
		return null;
	}

	return array(
		'slug'     => $slug,
		'path'     => '/' . $slug . '/',
		'post'     => $post,
		'metadata' => $catalog[ $slug ],
	);
}

/**
 * Rebind both public query globals and the loop post before downstream SEO and
 * template consumers observe the stale singular state.
 */
function nvx_governed_blog_runtime_rebind_queries(): ?WP_Post {
	$context = nvx_governed_blog_runtime_context();
	if ( null === $context || ! function_exists( 'nvx_single_post_rebind_query' ) ) {
		return null;
	}

	global $post, $wp, $wp_query, $wp_the_query;
	$resolved = $context['post'];
	$slug     = $context['slug'];

	if ( $wp_query instanceof WP_Query ) {
		nvx_single_post_rebind_query( $wp_query, $resolved, $slug );
	}
	if ( $wp_the_query instanceof WP_Query && $wp_the_query !== $wp_query ) {
		nvx_single_post_rebind_query( $wp_the_query, $resolved, $slug );
	}

	// Keep WP::query_vars coherent for consumers that inspect the request object
	// rather than the global WP_Query instance.
	if ( isset( $wp ) && is_object( $wp ) && isset( $wp->query_vars ) && is_array( $wp->query_vars ) ) {
		$wp->query_vars['p']         = (int) $resolved->ID;
		$wp->query_vars['name']      = $slug;
		$wp->query_vars['post_type'] = 'post';
		$wp->query_vars['pagename']  = '';
		$wp->query_vars['page_id']   = 0;
	}

	$post = $resolved;
	setup_postdata( $post );

	return $resolved;
}

/** Final title derived only from the governed request path/catalog. */
function nvx_governed_blog_runtime_title( $title ) {
	$context = nvx_governed_blog_runtime_context();
	if ( null === $context ) {
		return $title;
	}
	$value = trim( (string) ( $context['metadata']['title'] ?? '' ) );
	return '' !== $value ? $value : $title;
}

/** Final description derived only from the governed request path/catalog. */
function nvx_governed_blog_runtime_description( $description ) {
	$context = nvx_governed_blog_runtime_context();
	if ( null === $context ) {
		return $description;
	}
	$value = trim( (string) ( $context['metadata']['description'] ?? '' ) );
	return '' !== $value ? $value : $description;
}

/** Final canonical derived only from the governed request path. */
function nvx_governed_blog_runtime_canonical( $canonical ) {
	$context = nvx_governed_blog_runtime_context();
	if ( null === $context ) {
		return $canonical;
	}
	return home_url( $context['path'] );
}

/** Final Open Graph URL; staging retains the production-host social policy. */
function nvx_governed_blog_runtime_opengraph_url( $url ) {
	$context = nvx_governed_blog_runtime_context();
	if ( null === $context ) {
		return $url;
	}
	if ( function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment() ) {
		return 'https://nuvanx.com' . $context['path'];
	}
	return home_url( $context['path'] );
}

/** Normalize Yoast's presentation after every earlier object/indexable mapper. */
function nvx_governed_blog_runtime_yoast_presentation( $presentation, $context ) {
	unset( $context );
	$runtime = nvx_governed_blog_runtime_context();
	if ( null === $runtime || ! is_object( $presentation ) ) {
		return $presentation;
	}

	$title       = trim( (string) ( $runtime['metadata']['title'] ?? '' ) );
	$description = trim( (string) ( $runtime['metadata']['description'] ?? '' ) );
	$canonical   = home_url( $runtime['path'] );
	$og_url      = nvx_governed_blog_runtime_opengraph_url( $canonical );

	if ( '' !== $title ) {
		$presentation->title            = $title;
		$presentation->open_graph_title = $title;
		$presentation->twitter_title    = $title;
	}
	if ( '' !== $description ) {
		$presentation->meta_description        = $description;
		$presentation->open_graph_description  = $description;
		$presentation->twitter_description     = $description;
	}
	$presentation->canonical      = $canonical;
	$presentation->open_graph_url = $og_url;

	return $presentation;
}

/**
 * Replace the generic head contract on governed posts and expose a runtime
 * sentinel. The sentinel proves the HTTP/FPM request executed this source.
 */
function nvx_governed_blog_runtime_print_head_contract(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		return;
	}

	$context   = nvx_governed_blog_runtime_context();
	$canonical = null !== $context
		? home_url( $context['path'] )
		: ( function_exists( 'nvx_document_governance_canonical_url' ) ? nvx_document_governance_canonical_url() : '' );

	if ( '' !== $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}
	echo '<meta name="nvx-document-contract" content="1" />' . "\n";
	if ( null !== $context ) {
		echo '<meta name="nvx-governed-blog-runtime-contract" content="' . esc_attr( NVX_GOVERNED_BLOG_RUNTIME_CONTRACT ) . '" />' . "\n";
	}
}

// This module is loaded from functions.php after the blog/SEO helpers exist.
// Rebind on `wp` before template loading and before SEO/indexable consumers can
// persist a neighbouring queried object for this response.
add_action( 'wp', 'nvx_governed_blog_runtime_rebind_queries', -999999 );

remove_action( 'wp_head', 'nvx_document_governance_print_head_contract', 2 );
add_action( 'wp_head', 'nvx_governed_blog_runtime_print_head_contract', 2 );

add_filter( 'wpseo_title', 'nvx_governed_blog_runtime_title', PHP_INT_MAX );
add_filter( 'pre_get_document_title', 'nvx_governed_blog_runtime_title', PHP_INT_MAX );
add_filter( 'wpseo_opengraph_title', 'nvx_governed_blog_runtime_title', PHP_INT_MAX );
add_filter( 'wpseo_twitter_title', 'nvx_governed_blog_runtime_title', PHP_INT_MAX );
add_filter( 'wpseo_metadesc', 'nvx_governed_blog_runtime_description', PHP_INT_MAX );
add_filter( 'wpseo_opengraph_desc', 'nvx_governed_blog_runtime_description', PHP_INT_MAX );
add_filter( 'wpseo_twitter_description', 'nvx_governed_blog_runtime_description', PHP_INT_MAX );
add_filter( 'wpseo_canonical', 'nvx_governed_blog_runtime_canonical', PHP_INT_MAX );
add_filter( 'wpseo_opengraph_url', 'nvx_governed_blog_runtime_opengraph_url', PHP_INT_MAX );
add_filter( 'wpseo_frontend_presentation', 'nvx_governed_blog_runtime_yoast_presentation', PHP_INT_MAX, 2 );
