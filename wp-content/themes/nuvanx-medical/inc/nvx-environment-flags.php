<?php
/**
 * Environment-specific presentation and deployment flags.
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
 *
 * Host-only match for staging2.nuvanx.com.
 */
function nvx_environment_is_staging2(): bool {
	$raw_host    = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( trim( (string) $_SERVER['HTTP_HOST'] ) ) : '';
	$parsed_host = parse_url( 'http://' . $raw_host, PHP_URL_HOST );
	$host        = ( $parsed_host !== false && $parsed_host !== null ) ? $parsed_host : $raw_host;

	/**
	 * Filter whether the request is treated as staging2.
	 *
	 * @param bool   $is_staging2 Detected host match.
	 * @param string $host        Normalized HTTP host without port.
	 */
	return (bool) apply_filters( 'nvx_environment_is_staging2', 'staging2.nuvanx.com' === $host, $host );
}

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

/**
 * Rewrite staging uploads URLs to production to prevent broken images
 * when the staging environment hasn't synced the latest media.
 */
function nvx_environment_fallback_staging_media( $content ) {
	if ( nvx_environment_is_staging2() && is_string( $content ) ) {
		return str_replace(
			array( 'https://staging2.nuvanx.com/wp-content/uploads/', 'http://staging2.nuvanx.com/wp-content/uploads/' ),
			'https://www.nuvanx.com/wp-content/uploads/',
			$content
		);
	}
	return $content;
}
add_filter( 'the_content', 'nvx_environment_fallback_staging_media', 999 );
add_filter( 'wp_get_attachment_url', 'nvx_environment_fallback_staging_media', 999 );
add_filter( 'content_url', 'nvx_environment_fallback_staging_media', 999 );
add_filter( 'wp_calculate_image_srcset', function( $sources ) {
	if ( ! nvx_environment_is_staging2() || ! is_array( $sources ) ) {
		return $sources;
	}
	foreach ( $sources as &$source ) {
		if ( isset( $source['url'] ) ) {
			$source['url'] = nvx_environment_fallback_staging_media( $source['url'] );
		}
	}
	return $sources;
}, 999 );
