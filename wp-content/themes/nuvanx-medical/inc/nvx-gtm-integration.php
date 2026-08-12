<?php
/**
 * NUVANX analytics context provider.
 *
 * Site Kit is the single owner of Google Tag / GTM / GA4 / Google Ads and
 * Consent Mode snippets. This module never loads GTM, emits a GTM noscript
 * iframe, or resolves Google Ads conversion-action IDs.
 *
 * The theme owns only business context that GTM can consume from dataLayer.
 * Keeping this push independent from the GTM loader means it is available
 * before Site Kit's container executes, including when third-party scripts are
 * delayed by the theme performance layer.
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
		if ( is_page( 'valoracion' ) || false !== strpos( (string) get_the_permalink(), '/valoracion/' ) ) {
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
 * Push NUVANX business context before Site Kit executes the GTM container.
 */
function nvx_gtm_push_context(): void {
	if ( is_admin() ) {
		return;
	}

	$context = wp_json_encode(
		array(
			'nvx_env'       => nvx_environment_is_staging2() ? 'staging2' : 'production',
			'nvx_page_type' => nvx_gtm_context_page_type(),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);

	if ( ! is_string( $context ) || '' === $context ) {
		return;
	}

	printf(
		"<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(%s);</script>\n",
		$context // wp_json_encode() returns executable JSON, not user-authored markup.
	);
}
add_action( 'wp_head', 'nvx_gtm_push_context', 1 );
