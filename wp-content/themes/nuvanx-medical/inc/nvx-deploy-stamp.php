<?php
/**
 * NUVANX Deploy Stamp - Immutable Production Identity
 *
 * Provides an immutable deploy stamp for production identity verification.
 * Exposes DEPLOY_SHA, DEPLOY_RUN_ID, DEPLOY_TIMESTAMP, RELEASE_ID.
 *
 * The deploy stamp must match the chain of trust:
 * master/release SHA = accepted staging SHA = production deployed SHA
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the deploy stamp array.
 *
 * @return array<string, mixed> Deploy stamp information
 */
function nvx_get_deploy_stamp(): array {
	$stamp = [
		'DEPLOY_SHA'       => '',
		'DEPLOY_RUN_ID'    => '',
		'DEPLOY_TIMESTAMP' => '',
		'RELEASE_ID'       => '',
	];

	// Try to get from environment variables (set during deployment)
	$deploy_sha = getenv( 'DEPLOY_SHA' );
	if ( $deploy_sha && is_string( $deploy_sha ) ) {
		$stamp['DEPLOY_SHA'] = $deploy_sha;
	}

	$deploy_run_id = getenv( 'DEPLOY_RUN_ID' );
	if ( $deploy_run_id && is_string( $deploy_run_id ) ) {
		$stamp['DEPLOY_RUN_ID'] = $deploy_run_id;
	}

	$deploy_timestamp = getenv( 'DEPLOY_TIMESTAMP' );
	if ( $deploy_timestamp && is_string( $deploy_timestamp ) ) {
		$stamp['DEPLOY_TIMESTAMP'] = $deploy_timestamp;
	}

	$release_id = getenv( 'RELEASE_ID' );
	if ( $release_id && is_string( $release_id ) ) {
		$stamp['RELEASE_ID'] = $release_id;
	}

	// Fallback: try to get from theme file if not in environment
	if ( empty( $stamp['DEPLOY_SHA'] ) ) {
		$deploy_stamp_file = get_template_directory() . '/inc/data/deploy-stamp.json';
		if ( is_readable( $deploy_stamp_file ) ) {
			$deploy_stamp_data = json_decode( file_get_contents( $deploy_stamp_file ), true );
			if ( is_array( $deploy_stamp_data ) ) {
				$stamp = array_merge( $stamp, $deploy_stamp_data );
			}
		}
	}

	return $stamp;
}

/**
 * Get a specific deploy stamp value.
 *
 * @param string $key Stamp key (DEPLOY_SHA, DEPLOY_RUN_ID, DEPLOY_TIMESTAMP, RELEASE_ID)
 * @return string Stamp value or empty string if not set
 */
function nvx_get_deploy_stamp_value( string $key ): string {
	$stamp = nvx_get_deploy_stamp();
	return $stamp[ $key ] ?? '';
}

/**
 * Render deploy stamp as HTML meta tags.
 *
 * @return void
 */
function nvx_render_deploy_stamp_meta(): void {
	$stamp = nvx_get_deploy_stamp();

	foreach ( $stamp as $key => $value ) {
		if ( ! empty( $value ) ) {
			echo '<meta name="nvx-' . strtolower( $key ) . '" content="' . esc_attr( $value ) . '">' . "\n";
		}
	}
}

/**
 * Render deploy stamp as JSON-LD.
 *
 * @return void
 */
function nvx_render_deploy_stamp_jsonld(): void {
	$stamp = nvx_get_deploy_stamp();

	// Only render if we have at least DEPLOY_SHA
	if ( empty( $stamp['DEPLOY_SHA'] ) ) {
		return;
	}

	$jsonld = [
		'@context' => 'https://schema.org',
		'@type'    => 'SoftwareApplication',
		'name'     => 'NUVANX Medical Aesthetics',
		'version'  => $stamp['DEPLOY_SHA'],
		'deployment' => [
			'deploySha'       => $stamp['DEPLOY_SHA'],
			'deployRunId'    => $stamp['DEPLOY_RUN_ID'],
			'deployTimestamp' => $stamp['DEPLOY_TIMESTAMP'],
			'releaseId'       => $stamp['RELEASE_ID'],
		],
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $jsonld ) . '</script>' . "\n";
}

/**
 * Validate deploy stamp chain of trust.
 *
 * Checks: master/release SHA = accepted staging SHA = production deployed SHA
 *
 * @param string $expected_sha Expected SHA (e.g., from staging acceptance)
 * @return bool True if chain of trust is valid
 */
function nvx_validate_deploy_stamp_chain( string $expected_sha ): bool {
	$stamp = nvx_get_deploy_stamp();

	if ( empty( $stamp['DEPLOY_SHA'] ) ) {
		return false;
	}

	// Simple SHA comparison
	return hash_equals( $expected_sha, $stamp['DEPLOY_SHA'] );
}

// Hook deploy stamp rendering to wp_head
add_action( 'wp_head', 'nvx_render_deploy_stamp_meta', 1 );
add_action( 'wp_head', 'nvx_render_deploy_stamp_jsonld', 1 );