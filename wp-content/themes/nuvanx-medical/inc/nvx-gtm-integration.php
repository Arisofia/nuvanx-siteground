<?php
/**
 * NUVANX analytics context provider.
 *
 * Site Kit is the single owner of Google Tag / GTM / GA4 / Google Ads and
 * Consent Mode snippets. This module never loads GTM, emits a GTM noscript
 * iframe, or resolves Google Ads conversion-action IDs.
 *
 * The theme owns only business context consumed by GTM/dataLayer and by the
 * NUVANX conversion-events client. Keeping this context independent from the
 * GTM loader makes it available before Site Kit's container executes, including
 * when third-party scripts are delayed by the theme performance layer.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the canonical route metadata for the current request.
 *
 * @return array<string, mixed>
 */
function nvx_gtm_context_route(): array {
	if ( ! function_exists( 'nvx_theme_request_path' ) || ! function_exists( 'nvx_catalog_json_load' ) ) {
		return array();
	}

	$path   = '/' . trim( nvx_theme_request_path(), '/' ) . '/';
	$routes = nvx_catalog_json_load( 'routes.json' );
	$route  = isset( $routes[ $path ] ) && is_array( $routes[ $path ] ) ? $routes[ $path ] : array();

	if ( isset( $route['route_alias'] ) && is_string( $route['route_alias'] ) ) {
		$alias = '/' . trim( $route['route_alias'], '/' ) . '/';
		if ( isset( $routes[ $alias ] ) && is_array( $routes[ $alias ] ) ) {
			$route = $routes[ $alias ];
		}
	}

	return $route;
}

/**
 * Resolve the canonical NUVANX analytics page type for the current request.
 */
function nvx_gtm_context_page_type(): string {
	if ( is_front_page() ) {
		return 'home';
	}

	if ( is_singular( 'post' ) ) {
		return 'blog';
	}

	if ( is_archive() || is_category() ) {
		return 'listado';
	}

	if ( ! is_page() ) {
		return 'other';
	}

	$is_valoracion = function_exists( 'nvx_theme_is_valoracion_form_page' ) && nvx_theme_is_valoracion_form_page();
	$request_path  = function_exists( 'nvx_theme_request_path' ) ? nvx_theme_request_path() : '';
	if ( $is_valoracion || false !== strpos( $request_path, '/valoracion/' ) ) {
		return 'valoracion';
	}

	if ( function_exists( 'nvx_theme_is_thank_you_page' ) && nvx_theme_is_thank_you_page() ) {
		return 'conversion';
	}

	$route        = nvx_gtm_context_route();
	$schema_group = isset( $route['schema_group'] ) && is_string( $route['schema_group'] )
		? $route['schema_group']
		: '';

	if ( 'treatments' === $schema_group ) {
		return 'tratamiento';
	}

	if ( in_array( $schema_group, array( 'clinics', 'clinic_hub' ), true ) ) {
		return 'clinica';
	}

	return 'page';
}

/**
 * Resolve non-Google business configuration consumed by nvx-conversion-events.js.
 *
 * This deliberately excludes GTM and Google Ads conversion IDs. Site Kit and
 * the GTM container own Google tag configuration; the theme only exposes the
 * canonical HubSpot form identity required by the NUVANX event classifier.
 *
 * @return array{env:string,forms:array{valoracion:string}}
 */
function nvx_gtm_client_context(): array {
	$valoracion_form_id = defined( 'NVX_HUBSPOT_VALORACION_FORM_ID' )
		? (string) NVX_HUBSPOT_VALORACION_FORM_ID
		: (string) ( getenv( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ?: '' );

	return array(
		'env'   => nvx_environment_is_staging2() ? 'staging2' : 'production',
		'forms' => array(
			'valoracion' => $valoracion_form_id,
		),
	);
}

/**
 * Enqueue the attribution contract runtime before conversion events.
 * Priority 9 ensures it loads before the conversion relay at priority 10.
 */
function nvx_gtm_enqueue_attribution_contract(): void {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'nvx-attribution-contract',
		get_template_directory_uri() . '/assets/js/nvx-attribution-contract.js',
		array(),
		nvx_asset_version( 'assets/js/nvx-attribution-contract.js' ),
		array(
			'in_footer' => false,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 );

/**
 * Push NUVANX business context before Site Kit executes the GTM container.
 */
function nvx_gtm_push_context(): void {
	if ( is_admin() ) {
		return;
	}

	$client_context = nvx_gtm_client_context();
	$json_flags     = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
	$data_layer     = wp_json_encode(
		array(
			'nvx_env'       => $client_context['env'],
			'nvx_page_type' => nvx_gtm_context_page_type(),
		),
		$json_flags
	);
	$client_env     = wp_json_encode( $client_context['env'], $json_flags );
	$client_forms   = wp_json_encode( $client_context['forms'], $json_flags );

	if (
		! is_string( $data_layer ) || '' === $data_layer
		|| ! is_string( $client_env ) || '' === $client_env
		|| ! is_string( $client_forms ) || '' === $client_forms
	) {
		return;
	}

	$script = sprintf(
		'window.dataLayer=window.dataLayer||[];window.dataLayer.push(%s);window.nvxConversionEvents=window.nvxConversionEvents||{};window.nvxConversionEvents.env=%s;window.nvxConversionEvents.forms=%s;',
		$data_layer,
		$client_env,
		$client_forms
	);

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'nvx_gtm_push_context', 1 );