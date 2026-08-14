<?php
/**
 * Global front-end runtime governance and head contract.
 *
 * Full-document rewrite buffers were retired: SiteGround Optimizer + Complianz +
 * core already own the front-end buffer stack, and nesting another rewrite layer
 * produced HTTP 200 + empty body on /soluciones-medicas/. Head contract pieces
 * (canonical, document marker) and runtime assets are enforced via wp_head /
 * Yoast filters and enqueues instead of full-document preg rewrites.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Yoast may skip or duplicate canonical under staging noindex. Theme emits the
 * single public canonical from wp_head; suppress Yoast's copy as the final word
 * on wpseo_canonical (priority PHP_INT_MAX so no peer filter can re-enable it).
 *
 * Non-production Open Graph host policy is owned below
 * (nvx_document_governance_nonproduction_opengraph_url) — not on this filter.
 *
 * @param string|false $canonical Existing canonical.
 * @return false
 */
function nvx_document_governance_suppress_yoast_canonical( $canonical ) {
	unset( $canonical );
	return false;
}
add_filter( 'wpseo_canonical', 'nvx_document_governance_suppress_yoast_canonical', PHP_INT_MAX );

/**
 * Normalized path from the actual HTTP request, independent of the global post.
 */
function nvx_document_governance_request_path(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = wp_parse_url( $uri, PHP_URL_PATH );
	$path = is_string( $path ) && '' !== $path ? $path : '/';
	$path = '/' . trim( $path, '/' );

	return '/' === $path ? '/' : $path . '/';
}

/**
 * Resolve governed journal metadata by the requested slug rather than a mutable
 * global query object. This is the final head-contract guard against a stale
 * Yoast/indexable/global-post context leaking metadata from a neighbouring post.
 *
 * @return array{slug:string,path:string,metadata:array<string,mixed>}|null
 */
function nvx_document_governance_governed_blog_request(): ?array {
	if ( is_admin() || wp_doing_ajax() || is_404() || is_search() || is_feed() || is_preview() ) {
		return null;
	}

	if ( ! function_exists( 'nvx_seo_blog_post_metadata_catalog' ) ) {
		return null;
	}

	$path = nvx_document_governance_request_path();
	$slug = trim( $path, '/' );
	if ( '' === $slug || false !== strpos( $slug, '/' ) ) {
		return null;
	}

	$catalog = nvx_seo_blog_post_metadata_catalog();
	if ( ! isset( $catalog[ $slug ] ) || ! is_array( $catalog[ $slug ] ) ) {
		return null;
	}

	return array(
		'slug'     => $slug,
		'path'     => '/' . $slug . '/',
		'metadata' => $catalog[ $slug ],
	);
}

/**
 * Keep an already-canonical governed journal route bound to its own published post.
 *
 * WordPress redirect_canonical can occasionally infer a neighbouring singular as
 * the destination when runtime/indexable state is stale. For a route that is
 * already the exact canonical path of a separately published governed post, a
 * cross-post redirect is never valid. Non-canonical forms (for example a missing
 * trailing slash), non-governed routes and unpublished posts retain core behavior.
 *
 * @param string|false $redirect_url  Canonical redirect proposed by WordPress.
 * @param string       $requested_url Requested absolute URL.
 * @return string|false
 */
function nvx_document_governance_preserve_exact_governed_blog_route( $redirect_url, $requested_url ) {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'nvx_seo_blog_post_metadata_catalog' ) ) {
		return $redirect_url;
	}

	$raw_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$raw_path = wp_parse_url( $raw_uri, PHP_URL_PATH );
	if ( ! is_string( $raw_path ) || '' === $raw_path ) {
		return $redirect_url;
	}

	$normalized_path = nvx_document_governance_request_path();
	if ( $raw_path !== $normalized_path ) {
		return $redirect_url;
	}

	$slug = trim( $normalized_path, '/' );
	if ( '' === $slug || false !== strpos( $slug, '/' ) ) {
		return $redirect_url;
	}

	$catalog = nvx_seo_blog_post_metadata_catalog();
	if ( ! isset( $catalog[ $slug ] ) || ! is_array( $catalog[ $slug ] ) ) {
		return $redirect_url;
	}

	$post = get_page_by_path( $slug, OBJECT, 'post' );
	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || $slug !== $post->post_name ) {
		return $redirect_url;
	}

	unset( $requested_url );
	return false;
}
add_filter( 'redirect_canonical', 'nvx_document_governance_preserve_exact_governed_blog_route', PHP_INT_MAX, 2 );

/** Final governed journal title from the requested public route. */
function nvx_document_governance_governed_blog_title( $title ) {
	$request = nvx_document_governance_governed_blog_request();
	if ( null === $request ) {
		return $title;
	}

	$value = trim( (string) ( $request['metadata']['title'] ?? '' ) );
	return '' !== $value ? $value : $title;
}
add_filter( 'wpseo_title', 'nvx_document_governance_governed_blog_title', PHP_INT_MAX );
add_filter( 'pre_get_document_title', 'nvx_document_governance_governed_blog_title', PHP_INT_MAX );
add_filter( 'wpseo_opengraph_title', 'nvx_document_governance_governed_blog_title', PHP_INT_MAX );
add_filter( 'wpseo_twitter_title', 'nvx_document_governance_governed_blog_title', PHP_INT_MAX );

/** Final governed journal description from the requested public route. */
function nvx_document_governance_governed_blog_description( $description ) {
	$request = nvx_document_governance_governed_blog_request();
	if ( null === $request ) {
		return $description;
	}

	$value = trim( (string) ( $request['metadata']['description'] ?? '' ) );
	return '' !== $value ? $value : $description;
}
add_filter( 'wpseo_metadesc', 'nvx_document_governance_governed_blog_description', PHP_INT_MAX );
add_filter( 'wpseo_opengraph_desc', 'nvx_document_governance_governed_blog_description', PHP_INT_MAX );
add_filter( 'wpseo_twitter_description', 'nvx_document_governance_governed_blog_description', PHP_INT_MAX );

/**
 * Final Open Graph URL for governed journal routes.
 *
 * Staging remains noindex and intentionally advertises the production URL for
 * social previews; production emits its own self URL. Both are derived from the
 * requested route, never from a mutable queried-object/indexable context.
 */
function nvx_document_governance_governed_blog_opengraph_url( $url ) {
	$request = nvx_document_governance_governed_blog_request();
	if ( null === $request ) {
		return $url;
	}

	if ( function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment() ) {
		return 'https://nuvanx.com' . $request['path'];
	}

	return home_url( $request['path'] );
}
add_filter( 'wpseo_opengraph_url', 'nvx_document_governance_governed_blog_opengraph_url', PHP_INT_MAX );

/**
 * Whether the current strategy route is approved for publication.
 */
function nvx_document_governance_is_approved_strategy_route(): bool {
	if ( ! function_exists( 'nvx_strategy_current_page_key' ) || ! function_exists( 'nvx_strategy_page_catalog' ) ) {
		return false;
	}

	$key     = nvx_strategy_current_page_key();
	$catalog = nvx_strategy_page_catalog();

	return null !== $key
		&& 'approved_for_publication' === ( $catalog[ $key ]['review_status'] ?? null );
}

/**
 * Production-host URL for social previews on non-production environments.
 *
 * Staging stays noindex; approved public routes may still expose the production
 * URL for Open Graph. Protected working-name routes return empty.
 */
function nvx_document_governance_production_public_url(): string {
	$is_strategy_page = function_exists( 'nvx_strategy_current_page_key' ) && null !== nvx_strategy_current_page_key();
	$is_protected     = $is_strategy_page && ! nvx_document_governance_is_approved_strategy_route();

	if ( $is_protected || is_404() || is_search() || is_preview() ) {
		return '';
	}

	if ( ! is_front_page() && ! is_home() && ! is_singular() ) {
		return '';
	}

	if ( function_exists( 'nvx_seo_current_path' ) ) {
		$path = nvx_seo_current_path();
	} else {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	}

	$path = '/' . trim( (string) $path, '/' );
	if ( '/' !== $path ) {
		$path .= '/';
	}

	return 'https://nuvanx.com' . $path;
}

/**
 * Open Graph URL on non-production: point social previews at the production host
 * for approved routes. HTML link[rel=canonical] remains owned by this module's
 * wp_head emission — never re-attach production host policy to wpseo_canonical.
 *
 * @param mixed $url Yoast Open Graph URL.
 * @return mixed
 */
function nvx_document_governance_nonproduction_opengraph_url( $url ) {
	if ( ! function_exists( 'nvx_seo_is_nonproduction_environment' ) || ! nvx_seo_is_nonproduction_environment() ) {
		return $url;
	}

	$production = nvx_document_governance_production_public_url();
	return '' !== $production ? $production : $url;
}
add_filter( 'wpseo_opengraph_url', 'nvx_document_governance_nonproduction_opengraph_url', 1000 );

/**
 * Resolve the canonical URL without changing the staging robots policy.
 *
 * Always returns a non-empty absolute URL for public HTML so the rendered
 * document never ships without a canonical link.
 */
function nvx_document_governance_canonical_url(): string {
	$request = nvx_document_governance_governed_blog_request();
	if ( null !== $request ) {
		return home_url( $request['path'] );
	}

	$url = '';

	if ( ! is_404() && function_exists( 'nvx_seo_current_canonical_url' ) ) {
		$url = trim( (string) nvx_seo_current_canonical_url() );
	}

	if ( '' === $url && ! is_404() && ! is_front_page() && ! is_search() ) {
		$page_id   = (int) get_queried_object_id();
		$permalink = $page_id > 0 ? get_permalink( $page_id ) : '';
		$url       = is_string( $permalink ) ? trim( $permalink ) : '';
	}

	if ( '' === $url && ! is_404() && ! is_search() ) {
		$url = home_url( '/' );
	}

	return $url;
}

/**
 * Emit document contract pieces: contract marker + exactly one canonical.
 */
function nvx_document_governance_print_head_contract(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		return;
	}

	$canonical = nvx_document_governance_canonical_url();
	if ( '' !== $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}
	echo '<meta name="nvx-document-contract" content="1" />' . "\n";
}
add_action( 'wp_head', 'nvx_document_governance_print_head_contract', 2 );

/* Head buffering logic removed in favor of targeted wp_footer script tags stripping. */

/**
 * Enqueue the platform accessibility/runtime layer.
 */
function nvx_document_governance_enqueue_assets(): void {
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'nvx-accessibility-governance',
		$uri . '/assets/css/nvx-accessibility-governance.css',
		array( 'nvx-header', 'nvx-footer' ),
		function_exists( 'nvx_asset_version' )
			? nvx_asset_version( 'assets/css/nvx-accessibility-governance.css' )
			: NVX_THEME_VERSION
	);

	wp_enqueue_script(
		'nvx-runtime-governance',
		$uri . '/assets/js/nvx-runtime-governance.js',
		array( 'nvx-main' ),
		function_exists( 'nvx_asset_version' )
			? nvx_asset_version( 'assets/js/nvx-runtime-governance.js' )
			: NVX_THEME_VERSION,
		true
	);

	// Eager HubSpot strip is owned solely by nvx-integrations.php
	// (wp_enqueue_scripts + script_loader_tag).

	$modal_enabled = function_exists( 'nvx_valoracion_modal_enabled' )
		? nvx_valoracion_modal_enabled()
		: false;

	$page_url = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );

	// Never emit a full hsforms URL in server HTML: consent/optimizer scanners
	// treat any inline mention of that domain as an eager marketing embed and can
	// drop the entire runtime-governance handle from modal-enabled routes.
	$config = array(
		'modalEnabled'     => $modal_enabled,
		'modalId'          => 'nvx-valoracion-modal',
		'pageUrl'          => $page_url,
		'mobileNavId'      => 'nvx-mobile-nav',
		'hubspotScriptId'  => 'nvx-hubspot-forms-runtime',
		'hubspotPageMount' => true,
		'debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG === true,
	);

	$encoded = wp_json_encode( $config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
	if ( ! is_string( $encoded ) ) {
		$encoded = '{}';
	}

	wp_add_inline_script(
		'nvx-runtime-governance',
		'window.nvxRuntimeGovernance=' . $encoded . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_document_governance_enqueue_assets', 100 );
