<?php
/**
 * Environment-specific deployment metadata.
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
