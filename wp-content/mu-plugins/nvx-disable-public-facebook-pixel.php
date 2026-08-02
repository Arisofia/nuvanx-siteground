<?php
/**
 * Plugin Name: NUVANX — disable public Meta Pixel / FacebookSignal
 * Description: Prevents official-facebook-pixel from loading on public HTML. Acceptance rejects FacebookSignal; stripping via document buffers was retired.
 * Version: 1.0.0
 *
 * @package NUVANX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param mixed $plugins Active plugin basenames or sitewide map.
 * @return mixed
 */
function nvx_mu_disable_public_facebook_pixel( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}

	// Keep available in wp-admin / CLI for configuration.
	if (
		( function_exists( 'is_admin' ) && is_admin() && ! wp_doing_ajax() )
		|| wp_doing_cron()
		|| ( defined( 'WP_CLI' ) && WP_CLI )
	) {
		return $plugins;
	}

	// Sitewide plugins use plugin => timestamp map.
	$is_map = array() !== $plugins && ! array_is_list( $plugins );

	if ( $is_map ) {
		foreach ( array_keys( $plugins ) as $plugin ) {
			if ( is_string( $plugin ) && false !== strpos( $plugin, 'official-facebook-pixel/' ) ) {
				unset( $plugins[ $plugin ] );
			}
		}
		return $plugins;
	}

	return array_values(
		array_filter(
			$plugins,
			static function ( $plugin ): bool {
				return ! is_string( $plugin )
					|| false === strpos( $plugin, 'official-facebook-pixel/' );
			}
		)
	);
}
add_filter( 'option_active_plugins', 'nvx_mu_disable_public_facebook_pixel', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'nvx_mu_disable_public_facebook_pixel', 1 );
