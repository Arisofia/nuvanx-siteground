<?php
/**
 * Environment-specific presentation and deployment flags.
 *
 * Production keeps the temporary hero blackout enabled by default until approved
 * photography replaces every opening image. Staging2 disables it so the real
 * hero media, contrast, CTA hierarchy and mobile crop can be reviewed safely.
 *
 * Deploy workflows stamp the exact checked-out commit into `.nvx-deploy-sha`.
 * The public marker is intentionally non-secret and allows staging/production
 * verification to prove which immutable revision is actually rendered.
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

/**
 * Resolve the exact deployed Git commit SHA.
 *
 * Resolution order supports controlled host configuration while keeping the
 * workflow-generated marker as the normal source of truth.
 */
function nvx_environment_deploy_sha(): string {
	static $resolved = null;

	if ( is_string( $resolved ) ) {
		return $resolved;
	}

	$candidates = array();
	if ( defined( 'NVX_DEPLOY_SHA' ) ) {
		$candidates[] = (string) NVX_DEPLOY_SHA;
	}

	$environment_sha = getenv( 'NVX_DEPLOY_SHA' );
	if ( is_string( $environment_sha ) ) {
		$candidates[] = $environment_sha;
	}

	$marker = get_template_directory() . '/.nvx-deploy-sha';
	if ( is_readable( $marker ) ) {
		$marker_sha = file_get_contents( $marker );
		if ( is_string( $marker_sha ) ) {
			$candidates[] = $marker_sha;
		}
	}

	foreach ( $candidates as $candidate ) {
		$candidate = strtolower( trim( $candidate ) );
		if ( 1 === preg_match( '/^[a-f0-9]{40}$/', $candidate ) ) {
			$resolved = $candidate;
			return $resolved;
		}
	}

	$resolved = '';
	return $resolved;
}

/**
 * Emit the immutable deployment marker in the rendered document head.
 */
function nvx_environment_render_deploy_sha(): void {
	if ( is_admin() ) {
		return;
	}

	$sha = nvx_environment_deploy_sha();
	if ( '' === $sha ) {
		return;
	}

	printf( "<meta name=\"nvx-deploy-sha\" content=\"%s\" />\n", esc_attr( $sha ) );
}
add_action( 'wp_head', 'nvx_environment_render_deploy_sha', 1 );
