<?php
/**
 * NUVANX Deploy Stamp - Immutable Production Identity
 *
 * Provides an immutable deploy stamp for release verification without creating
 * a second Schema.org JSON-LD source. Canonical structured data remains owned
 * exclusively by Yoast's @graph plus NUVANX wpseo_schema_graph extensions.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the deploy stamp array.
 *
 * @return array<string, mixed> Deploy stamp information.
 */
function nvx_get_deploy_stamp(): array {
	$stamp = array(
		'DEPLOY_SHA'       => '',
		'DEPLOY_RUN_ID'    => '',
		'DEPLOY_TIMESTAMP' => '',
		'RELEASE_ID'       => '',
	);

	$environment_keys = array_keys( $stamp );
	foreach ( $environment_keys as $key ) {
		$value = getenv( $key );
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			$stamp[ $key ] = trim( $value );
		}
	}

	if ( empty( $stamp['DEPLOY_SHA'] ) ) {
		$deploy_stamp_file = get_template_directory() . '/inc/data/deploy-stamp.json';
		if ( is_readable( $deploy_stamp_file ) ) {
			$deploy_stamp_data = json_decode( (string) file_get_contents( $deploy_stamp_file ), true );
			if ( is_array( $deploy_stamp_data ) ) {
				foreach ( $environment_keys as $key ) {
					if ( isset( $deploy_stamp_data[ $key ] ) && is_scalar( $deploy_stamp_data[ $key ] ) ) {
						$stamp[ $key ] = trim( (string) $deploy_stamp_data[ $key ] );
					}
				}
			}
		}
	}

	return $stamp;
}

/**
 * Get a specific deploy stamp value.
 */
function nvx_get_deploy_stamp_value( string $key ): string {
	$stamp = nvx_get_deploy_stamp();
	return isset( $stamp[ $key ] ) ? (string) $stamp[ $key ] : '';
}

/**
 * Render deploy identity as non-schema HTML meta tags.
 *
 * These tags are deliberately not application/ld+json. Deployment identity is
 * operational metadata, not a public medical/business entity, and emitting it
 * as SoftwareApplication created a duplicate JSON-LD source on every page.
 */
function nvx_render_deploy_stamp_meta(): void {
	foreach ( nvx_get_deploy_stamp() as $key => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}
		echo '<meta name="nvx-' . esc_attr( strtolower( $key ) ) . '" content="' . esc_attr( (string) $value ) . '">' . "\n";
	}
}

/**
 * Validate deploy stamp chain of trust.
 */
function nvx_validate_deploy_stamp_chain( string $expected_sha ): bool {
	$deployed_sha = nvx_get_deploy_stamp_value( 'DEPLOY_SHA' );
	return '' !== $deployed_sha && hash_equals( $expected_sha, $deployed_sha );
}

add_action( 'wp_head', 'nvx_render_deploy_stamp_meta', 1 );
