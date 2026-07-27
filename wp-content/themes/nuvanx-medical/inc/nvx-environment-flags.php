<?php
/**
 * Environment-specific runtime services and deployment metadata.
 *
 * The environment classifier is shared by staging-only content safeguards and
 * must remain independent from temporary presentation features. Deploy workflows
 * stamp the exact checked-out commit into `.nvx-deploy-sha` so the rendered site
 * can prove which immutable revision is active.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/nvx-runtime-compatibility.php';

/**
 * Resolve the current WordPress host in web and WP-CLI contexts.
 *
 * The snake_case name is retained because this is a WordPress-facing helper and
 * existing runtime consumers use the established theme convention.
 */
function nvx_environment_host(): string { // NOSONAR -- Intentional WordPress-compatible public API.
    $host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
    if ( ! is_string( $host ) || '' === $host ) {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : '';
    }

    $host = strtolower( trim( $host ) );
    $host = (string) preg_replace( '/:\d+$/', '', $host );

    return $host;
}

/**
 * Whether WordPress is running on the protected staging2 environment.
 *
 * The snake_case name deliberately matches the related WordPress filter and the
 * active staging-only consumers.
 */
function nvx_environment_is_staging2(): bool { // NOSONAR -- Intentional WordPress-compatible public API.
    $host = nvx_environment_host();

    /**
     * Filter staging2 detection without coupling it to presentation behavior.
     *
     * @param bool   $is_staging2 Whether the canonical staging2 host is active.
     * @param string $host        Normalized WordPress host.
     */
    return (bool) apply_filters( 'nvx_environment_is_staging2', 'staging2.nuvanx.com' === $host, $host );
}

/** Backward-compatible camelCase adapter used by older theme snapshots. */
function nvxEnvironmentIsStaging2(): bool {
    return nvx_environment_is_staging2();
}

/**
 * Resolve the exact deployed Git commit SHA.
 *
 * Resolution order supports controlled host configuration while keeping the
 * workflow-generated marker as the normal source of truth.
 */
function nvxEnvironmentDeploySha(): string {
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

/** Emit the immutable deployment marker in the rendered document head. */
function nvxEnvironmentRenderDeploySha(): void {
    if ( is_admin() ) {
        return;
    }

    $sha = nvxEnvironmentDeploySha();
    if ( '' === $sha ) {
        return;
    }

    printf( "<meta name=\"nvx-deploy-sha\" content=\"%s\" />\n", esc_attr( $sha ) );
}
add_action( 'wp_head', 'nvxEnvironmentRenderDeploySha', 1 );
