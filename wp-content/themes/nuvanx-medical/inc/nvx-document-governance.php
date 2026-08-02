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
 * Staging production-host policy for social tags lives on wpseo_opengraph_url
 * in nvx-staging2-canonical-closure.php — not on this filter.
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
