<?php
/**
 * Environment-specific presentation flags.
 *
 * Production keeps the temporary hero blackout enabled by default until approved
 * photography replaces every opening image. Staging2 disables it so the real
 * hero media, contrast, CTA hierarchy and mobile crop can be reviewed safely.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request belongs to the staging2 review environment.
 */
function nvx_environment_is_staging2(): bool {
	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
	$host        = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	$host        = preg_replace( '/:\d+$/', '', $host );

	return 'staging' === $environment || 'staging2.nuvanx.com' === $host;
}

/**
 * Reveal the underlying hero media on staging2 without changing production.
 *
 * @param bool $enabled Current blackout flag.
 */
function nvx_environment_filter_hero_blackout( bool $enabled ): bool {
	return nvx_environment_is_staging2() ? false : $enabled;
}
add_filter( 'nvx_theme_hero_blackout_enabled', 'nvx_environment_filter_hero_blackout', 5 );
