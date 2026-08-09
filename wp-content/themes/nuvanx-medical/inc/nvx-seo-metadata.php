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
 * Resolve governed Signature metadata from an explicit WordPress post ID.
 *
 * Unlike request-condition helpers such as is_page(), the Yoast indexable
 * context remains available when metadata is built for REST/headless surfaces.
 * This keeps the versioned Signature catalogs as the sole source of truth for
 * frontend HTML, Yoast REST metadata, Open Graph and canonical URLs.
 *
 * @param int $post_id WordPress post ID represented by the Yoast indexable.
 * @return array{title:string,description:string,canonical:string}|null
 */
function nvx_seo_signature_metadata_for_post_id( int $post_id ): ?array {
	if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', $post_id );
	if ( '' === $slug ) {
		return null;
	}

	$source = null;
	if ( function_exists( 'nvx_signature_phase_catalog' ) ) {
		foreach ( nvx_signature_phase_catalog() as $page ) {
			if ( isset( $page['slug'] ) && $slug === (string) $page['slug'] ) {
				$source = $page;
				break;
			}
		}
	}

	if ( null === $source && function_exists( 'nvx_signature_hub_catalog' ) ) {
		foreach ( nvx_signature_hub_catalog() as $hub ) {
			if ( isset( $hub['slug'] ) && $slug === (string) $hub['slug'] ) {
				$source = $hub;
				break;
			}
		}
	}

	if ( ! is_array( $source ) ) {
		return null;
	}

	$title       = isset( $source['seo_title'] ) ? trim( (string) $source['seo_title'] ) : '';
	$description = isset( $source['seo_desc'] ) ? trim( (string) $source['seo_desc'] ) : '';
	$canonical   = get_permalink( $post_id );

	if ( '' === $title || '' === $description || ! is_string( $canonical ) || '' === $canonical ) {
		return null;
	}

	return array(
		'title'       => $title,
		'description' => $description,
		'canonical'   => $canonical,
	);
}

/**
 * Normalize Yoast's complete presentation for governed Signature pages.
 *
 * Yoast exposes the represented post through Meta_Tags_Context::indexable,
 * including REST/headless requests where WordPress conditional tags do not
 * identify the requested page. Setting the presentation once keeps every
 * presenter aligned instead of maintaining independent per-tag exceptions.
 *
 * @param mixed $presentation Yoast Indexable_Presentation instance.
 * @param mixed $context      Yoast Meta_Tags_Context instance.
 * @return mixed The normalized presentation.
 */
function nvx_seo_signature_yoast_presentation( $presentation, $context ) {
	if ( ! is_object( $presentation ) || ! is_object( $context ) ) {
		return $presentation;
	}

	$post_id = 0;
	if ( isset( $context->indexable ) && is_object( $context->indexable ) && isset( $context->indexable->object_id ) ) {
		$post_id = (int) $context->indexable->object_id;
	} elseif ( isset( $context->post ) && is_object( $context->post ) && isset( $context->post->ID ) ) {
		$post_id = (int) $context->post->ID;
	}

	$metadata = nvx_seo_signature_metadata_for_post_id( $post_id );
	if ( null === $metadata ) {
		return $presentation;
	}

	$presentation->title                  = $metadata['title'];
	$presentation->meta_description       = $metadata['description'];
	$presentation->canonical              = $metadata['canonical'];
	$presentation->open_graph_title       = $metadata['title'];
	$presentation->open_graph_description = $metadata['description'];
	$presentation->open_graph_url         = $metadata['canonical'];
	$presentation->twitter_title          = $metadata['title'];
	$presentation->twitter_description    = $metadata['description'];

	return $presentation;
}
add_filter( 'wpseo_frontend_presentation', 'nvx_seo_signature_yoast_presentation', 80, 2 );

/**
 * Normalize the current site path for metadata routing.
 */
function nvx_seo_current_path(): string {
	if ( function_exists( 'nvx_schema_current_path' ) ) {
		return (string) nvx_schema_current_path( (int) get_queried_object_id() );
	}

	$uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$uri     = (string) strtok( $uri, '?' );
	$trimmed = trim( $uri, '/' );
	return '' !== $trimmed ? '/' . $trimmed . '/' : '/';
}

/**
 * Resolves the catalog metadata key for the current request path.
 *
 * @return string|null The metadata key, or null when the request is not catalogued or is a 404 response.
 */
function nvx_seo_current_metadata_key(): ?string {
	// Never lend a legitimate title/description to a not-found route.
	if ( is_404() ) {
		return null;
	}

	$path = nvx_seo_current_path();

	if ( function_exists( 'nvx_catalog_json_resolved' ) ) {
		$routes = nvx_catalog_json_resolved( 'routes.json' );
		if ( isset( $routes[ $path ]['seo_id'] ) ) {
			return $routes[ $path ]['seo_id'];
		}
	}

	return null;
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
 * Determines whether the current installation should be treated as non-production.
 *
 * @return bool `true` for staging, local, configured non-production, or unrecognized environments; `false` only when `NVX_ENV` is set to `production`.
 */
function nvx_seo_is_nonproduction_environment(): bool {
	// Staging2 must always be treated as non-production regardless of host or WP_ENVIRONMENT_TYPE.
	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() ) {
		return true;
	}

	// SiteGround preview/staging hosts must never be indexable.
	$raw_host    = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	// parse_url correctly strips the port from both 'host:port' and '[::1]:port' (IPv6) forms.
	$parsed_host = parse_url( 'https://' . $raw_host, PHP_URL_HOST );
	$host        = ( $parsed_host !== false && $parsed_host !== null ) ? $parsed_host : $raw_host;
	if ( false !== strpos( $raw_host, '.sg-host.com' ) || false !== strpos( $raw_host, 'staging' ) || false !== strpos( $host, '.sg-host.com' ) || false !== strpos( $host, 'staging' ) ) {
		return true;
	}

	if ( defined( 'NVX_ENV' ) ) {
		return NVX_ENV !== 'production';
	}
	
	// Local development environments (localhost, 127.0.0.1, local domains)
	if ( in_array( $host, array( 'localhost', '127.0.0.1', 'nuvanx.local', 'nuvanx.test' ), true ) ) {
		return true;
	}
	
	// No NVX_ENV defined and not in known non-production hosts:
	// FAIL SAFE (noindex) rather than FAIL OPEN (index) to prevent accidental indexing
	error_log( '[nuvanx] CRITICAL: NVX_ENV constant is not defined and host is not recognized. Assuming non-production environment (noindex enabled) for safety. Define NVX_ENV=production in wp-config.php for production hosts.' );
	return true;
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
 * Determines the robots indexing and crawling policy for the current request.
 *
 * @return int The applicable `NVX_ROBOTS_*` policy constant.
 */
function nvx_seo_resolve_robots_policy(): int {
	if ( nvx_seo_is_nonproduction_environment() ) {
		return NVX_ROBOTS_NOINDEX_NOFOLLOW;
	}

	// Archive pages with a few repeating cards add no unique clinical value yet.
	// Keep them crawlable through the linked articles, not as competing thin URLs.
	if ( is_category() || is_tag() ) {
		return NVX_ROBOTS_NOINDEX_FOLLOW;
	}

	$page_id = (int) get_queried_object_id();

	if ( function_exists( 'nvx_nofollow_page_ids' ) && in_array( $page_id, nvx_nofollow_page_ids(), true ) ) {
		return NVX_ROBOTS_NOINDEX_NOFOLLOW;
	}

	if ( function_exists( 'nvx_noindex_page_ids' ) && in_array( $page_id, nvx_noindex_page_ids(), true ) ) {
		return NVX_ROBOTS_NOINDEX_FOLLOW;
	}

	if ( null !== nvx_seo_current_metadata_key() ) {
		return NVX_ROBOTS_INDEX_FOLLOW;
	}

	return NVX_ROBOTS_INHERIT;
}

/**
 * Applies the resolved robots policy to Yoast and Core robots values.
 *
 * @param string|array $robots Incoming robots value.
 * @return string|array
 */
function nvx_seo_filter_robots( $robots ) {
	$policy = nvx_seo_resolve_robots_policy();

	if ( NVX_ROBOTS_INHERIT === $policy ) {
		return $robots;
	}

	$directives = array(
		NVX_ROBOTS_NOINDEX_NOFOLLOW => array( 'noindex', 'nofollow' ),
		NVX_ROBOTS_NOINDEX_FOLLOW   => array( 'noindex', 'follow' ),
		NVX_ROBOTS_INDEX_FOLLOW     => array( 'index', 'follow' ),
	);

	if ( ! isset( $directives[ $policy ] ) ) {
		return $robots;
	}

	if ( is_string( $robots ) ) {
		return implode( ', ', $directives[ $policy ] );
	}

	if ( is_array( $robots ) ) {
		unset( $robots['index'], $robots['follow'], $robots['noindex'], $robots['nofollow'] );
		foreach ( $directives[ $policy ] as $directive ) {
			$robots[ $directive ] = true;
		}
	}

	return $robots;
}
add_filter( 'wpseo_robots', 'nvx_seo_filter_robots', 100 );
add_filter( 'wp_robots', 'nvx_seo_filter_robots', 100 );

/**
 * Harden non-production environments by emitting HTTP X-Robots-Tag.
 * This ensures noindex/nofollow applies globally at the network layer,
 * preempting any HTML parsing or plugin-level filter bypasses.
 */
function nvx_seo_enforce_http_robots_header() {
	if ( nvx_seo_is_nonproduction_environment() && ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}
add_action( 'send_headers', 'nvx_seo_enforce_http_robots_header', 1 );

/**
 * Perform HTTP 301 redirect for routes configured with a route_alias in routes.json.
 */
function nvx_seo_handle_route_alias_redirect(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$path = nvx_seo_current_path();
	if ( function_exists( 'nvx_catalog_json_resolved' ) ) {
		$routes = nvx_catalog_json_resolved( 'routes.json' );
		if ( ! empty( $routes[ $path ]['route_alias'] ) && is_string( $routes[ $path ]['route_alias'] ) ) {
			$target = (string) $routes[ $path ]['route_alias'];
			wp_safe_redirect( home_url( $target ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'nvx_seo_handle_route_alias_redirect', 1 );
