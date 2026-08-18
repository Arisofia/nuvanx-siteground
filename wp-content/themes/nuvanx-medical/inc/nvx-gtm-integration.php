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

/** Resolve the canonical NUVANX analytics page type for the current request. */
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
	$schema_group = isset( $route['schema_group'] ) && is_string( $route['schema_group'] ) ? $route['schema_group'] : '';
	if ( 'treatments' === $schema_group ) {
		return 'tratamiento';
	}
	if ( in_array( $schema_group, array( 'clinics', 'clinic_hub' ), true ) ) {
		return 'clinica';
	}
	return 'page';
}

/**
 * Server-owned QA identity. A public URL/query parameter can never opt a
 * production visitor into test mode. Staging2 is always test traffic.
 *
 * @return array{is_test_lead:bool,test_run_id:string}
 */
function nvx_attribution_qa_context(): array {
	$is_test = function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2();
	if ( ! $is_test ) {
		return array(
			'is_test_lead' => false,
			'test_run_id'  => '',
		);
	}

	$run_id = '';
	if ( function_exists( 'nvx_get_deploy_stamp_value' ) ) {
		$run_id = trim( nvx_get_deploy_stamp_value( 'DEPLOY_RUN_ID' ) );
	}
	if ( '' === $run_id && function_exists( 'nvx_environment_deploy_sha' ) ) {
		$sha = trim( nvx_environment_deploy_sha() );
		if ( '' !== $sha ) {
			$run_id = 'sha-' . substr( preg_replace( '/[^0-9a-f]/i', '', $sha ) ?: '', 0, 12 );
		}
	}
	if ( '' === $run_id ) {
		$run_id = 'staging2';
	}

	return array(
		'is_test_lead' => true,
		'test_run_id'  => substr( sanitize_key( 'staging2-' . $run_id ), 0, 200 ),
	);
}

/**
 * Resolve non-Google business configuration consumed by conversion clients.
 *
 * @return array{env:string,forms:array{valoracion:string},qa:array{is_test_lead:bool,test_run_id:string}}
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
		'qa'    => nvx_attribution_qa_context(),
	);
}

/**
 * Load the first-party attribution contract before the conversion relay.
 * Site Kit remains the sole Google tag owner.
 */
function nvx_gtm_enqueue_attribution_contract(): void {
	if ( is_admin() ) {
		return;
	}

	$relative = 'assets/js/nvx-attribution-contract.js';
	$version  = function_exists( 'nvx_asset_version' )
		? nvx_asset_version( $relative )
		: ( defined( 'NVX_THEME_VERSION' ) ? NVX_THEME_VERSION : null );

	wp_enqueue_script(
		'nvx-attribution-contract',
		get_template_directory_uri() . '/' . $relative,
		array(),
		$version,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 );

/** Push NUVANX business context before Site Kit executes the GTM container. */
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
	$client_env   = wp_json_encode( $client_context['env'], $json_flags );
	$client_forms = wp_json_encode( $client_context['forms'], $json_flags );
	$client_qa    = wp_json_encode( $client_context['qa'], $json_flags );

	if (
		! is_string( $data_layer ) || '' === $data_layer
		|| ! is_string( $client_env ) || '' === $client_env
		|| ! is_string( $client_forms ) || '' === $client_forms
		|| ! is_string( $client_qa ) || '' === $client_qa
	) {
		return;
	}

	$script = sprintf(
		'window.dataLayer=window.dataLayer||[];window.dataLayer.push(%s);window.nvxConversionEvents=window.nvxConversionEvents||{};window.nvxConversionEvents.env=%s;window.nvxConversionEvents.forms=Object.assign({},window.nvxConversionEvents.forms||{},%s);window.nvxConversionEvents.qa=Object.assign({},window.nvxConversionEvents.qa||{},%s);',
		$data_layer,
		$client_env,
		$client_forms,
		$client_qa
	);

	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'nvx_gtm_push_context', 1 );

require_once __DIR__ . '/nvx-hubspot-secure-attribution.php';
