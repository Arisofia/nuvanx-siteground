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
	$fallback = home_url( '/' );
	$url      = '';

	if ( ! is_404() && function_exists( 'nvx_seo_current_canonical_url' ) ) {
		$url = trim( (string) nvx_seo_current_canonical_url() );
	}

	if ( '' === $url && ! is_404() && ! is_front_page() ) {
		$page_id   = (int) get_queried_object_id();
		$permalink = $page_id > 0 ? get_permalink( $page_id ) : '';
		$url       = is_string( $permalink ) ? trim( $permalink ) : '';
	}

	if ( '' === $url ) {
		$url = $fallback;
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
	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	echo '<meta name="nvx-document-contract" content="1" />' . "\n";
}
add_action( 'wp_head', 'nvx_document_governance_print_head_contract', 2 );

/**
 * Start a narrow output buffer for wp_head on front/page requests.
 * Absorbed from retired contacto MU: strip competing Schema.org JSON-LD and
 * enforce contact og:image when Yoast omits it. Scoped to head only.
 */
function nvx_document_governance_head_buffer_start(): void {
	if ( is_admin() || ( ! is_front_page() && ! is_page() ) ) {
		return;
	}

	$GLOBALS['nvx_schema_head_buffer_level'] = ob_get_level();
	ob_start();
}
add_action( 'wp_head', 'nvx_document_governance_head_buffer_start', -9999 );

/**
 * Filter a single application/ld+json script: keep Yoast, drop other Schema.org.
 *
 * @param array<int,string> $matches preg_replace_callback matches.
 */
function nvx_document_governance_filter_ldjson_script( array $matches ): string {
	$tag     = isset( $matches[0] ) ? (string) $matches[0] : '';
	$payload = isset( $matches[2] ) ? (string) $matches[2] : '';

	if ( false !== stripos( $tag, 'yoast-schema-graph' ) ) {
		return $tag;
	}

	if ( function_exists( 'nvx_jsonld_is_schema_org_payload' ) ) {
		return nvx_jsonld_is_schema_org_payload( $payload ) ? '' : $tag;
	}

	if ( preg_match( '/schema\.org|@graph\b|"@type"\s*:/i', $payload ) ) {
		return '';
	}

	return $tag;
}

/**
 * Strip competing Schema.org JSON-LD from buffered head HTML.
 */
function nvx_document_governance_strip_non_yoast_ldjson( string $html ): string {
	if ( false === stripos( $html, 'ld+json' ) ) {
		return $html;
	}

	$pattern = function_exists( 'nvx_jsonld_script_pattern' )
		? nvx_jsonld_script_pattern()
		: '#<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\1)[^>]*>([\s\S]*?)</script>#iu';

	$schema_filtered = preg_replace_callback(
		$pattern,
		'nvx_document_governance_filter_ldjson_script',
		$html
	);

	return is_string( $schema_filtered ) ? $schema_filtered : $html;
}

/**
 * End head buffer: schema strip + contact og:image safeguard.
 */
function nvx_document_governance_head_buffer_end(): void {
	if ( ! isset( $GLOBALS['nvx_schema_head_buffer_level'] ) ) {
		return;
	}

	$initial_level = (int) $GLOBALS['nvx_schema_head_buffer_level'];
	unset( $GLOBALS['nvx_schema_head_buffer_level'] );

	if ( ob_get_level() !== $initial_level + 1 ) {
		return;
	}

	$html = ob_get_clean();
	if ( ! is_string( $html ) ) {
		return;
	}

	$filtered = nvx_document_governance_strip_non_yoast_ldjson( $html );
	if ( function_exists( 'nvx_contacto_enforce_final_og_image' ) ) {
		$filtered = nvx_contacto_enforce_final_og_image( $filtered );
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head buffer of trusted markup.
	echo $filtered;
}
add_action( 'wp_head', 'nvx_document_governance_head_buffer_end', PHP_INT_MAX );

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
	// Never emit a full hsforms URL in server HTML: consent/optimizer scanners
	// treat any inline mention of that domain as an eager marketing embed and can
	// drop the entire runtime-governance handle from modal-enabled routes.
	$config = array(
		'modalEnabled'     => $modal_enabled,
		'modalId'          => 'nvx-valoracion-modal',
		'mobileNavId'      => 'nvx-mobile-nav',
		'hubspotScriptId'  => 'nvx-hubspot-forms-runtime',
		'hubspotPortalId'  => '',
		'hubspotRegion'    => 'eu1',
		'hubspotPageMount' => true,
	);

	if ( function_exists( 'nvx_valoracion_modal_hubspot_config' ) ) {
		$hubspot = nvx_valoracion_modal_hubspot_config();
		$config['hubspotPortalId'] = isset( $hubspot['portal_id'] ) ? (string) $hubspot['portal_id'] : '';
		$config['hubspotRegion']   = isset( $hubspot['region'] ) ? (string) $hubspot['region'] : 'eu1';
	}

	$encoded = wp_json_encode( $config, JSON_UNESCAPED_SLASHES );
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
