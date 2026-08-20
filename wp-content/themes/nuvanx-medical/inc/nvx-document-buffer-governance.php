<?php
/**
 * Public document-buffer governance.
 *
 * The theme-level full-document output buffer registered from nvx-integrations.php
 * at template_redirect priority 999999 conflicts with SiteGround Optimizer's
 * front-end buffer stack and can produce HTTP 200 responses with an empty body.
 *
 * Remove only that exact source-owned callback. Never clear the whole hook and
 * never manipulate buffers owned by WordPress, SiteGround Optimizer, Complianz,
 * or other plugins.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retire the legacy NUVANX full-document output-buffer callback.
 */
function nvx_retire_legacy_document_buffer(): void {
	global $wp_filter;

	$hook = $wp_filter['template_redirect'] ?? null;
	if ( ! $hook instanceof WP_Hook || empty( $hook->callbacks[999999] ) ) {
		return;
	}

	$integrations_file = realpath( __DIR__ . '/nvx-integrations.php' );
	if ( false === $integrations_file ) {
		return;
	}

	foreach ( $hook->callbacks[999999] as $registered ) {
		$callback = $registered['function'] ?? null;
		if ( ! $callback instanceof Closure ) {
			continue;
		}

		try {
			$reflection = new ReflectionFunction( $callback );
		} catch ( ReflectionException $exception ) {
			continue;
		}

		$source_file = $reflection->getFileName();
		if ( ! is_string( $source_file ) || realpath( $source_file ) !== $integrations_file ) {
			continue;
		}

		$hook->remove_filter( 'template_redirect', $callback, 999999 );
	}
}

nvx_retire_legacy_document_buffer();
