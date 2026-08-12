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
 * Resolve the canonical NUVANX analytics page type for the current request.
 */
function nvx_gtm_context_page_type(): string {
	if ( is_front_page() ) {
		return 'home';
	}

	if ( is_singular( 'post' ) ) {
		return 'blog';
	}

	if ( is_page() ) {
		$is_valoracion = function_exists( 'nvx_theme_is_valoracion_form_page' ) && nvx_theme_is_valoracion_form_page();
		$request_path  = function_exists( 'nvx_theme_request_path' ) ? nvx_theme_request_path() : '';

		if ( $is_valoracion || false !== strpos( $request_path, '/valoracion/' ) ) {
			return 'valoracion';
		}

		return 'tratamiento';
	}

	if ( is_archive() || is_category() ) {
		return 'listado';
	}

	return 'other';
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
 * Push NUVANX business context before Site Kit executes the GTM container.
 */
function nvx_gtm_push_context(): void {
	if ( is_admin() ) {
		return;
	}

	$client_context = nvx_gtm_client_context();
	$data_layer     = wp_json_encode(
		array(
			'nvx_env'       => $client_context['env'],
			'nvx_page_type' => nvx_gtm_context_page_type(),
		),
		JSON_UNESCAPED_UNICODE
	);
	$client_env     = wp_json_encode( $client_context['env'], JSON_UNESCAPED_UNICODE );
	$client_forms   = wp_json_encode( $client_context['forms'], JSON_UNESCAPED_UNICODE );

	if (
		! is_string( $data_layer ) || '' === $data_layer
		|| ! is_string( $client_env ) || '' === $client_env
		|| ! is_string( $client_forms ) || '' === $client_forms
	) {
		return;
	}

	printf(
		"<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(%s);window.nvxConversionEvents=window.nvxConversionEvents||{};window.nvxConversionEvents.env=%s;window.nvxConversionEvents.forms=Object.assign({},window.nvxConversionEvents.forms||{},%s);</script>\n",
		$data_layer, // wp_json_encode() returns executable JSON, not user-authored markup.
		$client_env,
		$client_forms
	);
}
add_action( 'wp_head', 'nvx_gtm_push_context', 1 );
