<?php
/**
 * Remove Staging-only ID/slug collisions before importing the canonical
 * Production publication snapshot.
 *
 * This script is intentionally Staging-only and is executed through
 * `wp eval-file`. The enclosing deployment workflow owns a full DB/theme
 * rollback snapshot, so any later acceptance failure restores the prior state.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

$identity = array(
    'db_name'        => defined( 'DB_NAME' ) ? (string) DB_NAME : '',
    'home'           => (string) get_option( 'home' ),
    'siteurl'        => (string) get_option( 'siteurl' ),
    'blog_public'    => (string) get_option( 'blog_public' ),
    'nvx_env'        => defined( 'NVX_ENV' ) ? (string) NVX_ENV : '',
    'wp_environment' => function_exists( 'wp_get_environment_type' ) ? (string) wp_get_environment_type() : '',
);
if (
    'dbshcocboodiwr' !== $identity['db_name']
    || 'https://staging2.nuvanx.com' !== untrailingslashit( $identity['home'] )
    || 'https://staging2.nuvanx.com' !== untrailingslashit( $identity['siteurl'] )
    || '0' !== $identity['blog_public']
    || 'staging' !== $identity['nvx_env']
    || 'staging' !== $identity['wp_environment']
) {
    fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=wrong_environment\n" );
    exit( 1 );
}

$snapshotFile = trim( (string) getenv( 'PUBLICATION_SNAPSHOT_FILE' ) );
$manifestFile = get_template_directory() . '/inc/data/publication-manifest.json';
$snapshot = '' !== $snapshotFile && is_readable( $snapshotFile )
    ? json_decode( (string) file_get_contents( $snapshotFile ), true )
    : null;
$manifest = nvxPublicationLoadManifest( $manifestFile );

if (
    ! is_array( $snapshot )
    || 'nuvanx-production-publication-snapshot' !== ( $snapshot['schema'] ?? '' )
    || ! isset( $snapshot['routes'] )
    || ! is_array( $snapshot['routes'] )
    || empty( $manifest )
    || (string) ( $snapshot['manifest_version'] ?? '' ) !== (string) ( $manifest['version'] ?? '' )
    || ! nvxPublicationSameRouteSet( nvxPublicationExpectedRoutes( $manifest ), array_keys( $snapshot['routes'] ) )
) {
    fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=snapshot_or_manifest_mismatch\n" );
    exit( 1 );
}

$targetIds = array();
foreach ( $snapshot['routes'] as $source ) {
    if ( is_array( $source ) ) {
        $targetIds[] = (int) ( $source['ID'] ?? 0 );
    }
}
$targetLookup = array_fill_keys( array_filter( $targetIds ), true );
$conflicts = array();

foreach ( $manifest['routes'] as $route => $expected ) {
    $source = $snapshot['routes'][ $route ] ?? null;
    if ( ! is_array( $source ) ) {
        fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=source_missing route={$route}\n" );
        exit( 1 );
    }

    $sourceId = (int) ( $source['ID'] ?? 0 );
    $sourceType = (string) ( $source['post_type'] ?? '' );
    $sourceSlug = (string) ( $source['post_name'] ?? '' );
    if ( $sourceId <= 0 || ! in_array( $sourceType, array( 'page', 'post' ), true ) || '' === $sourceSlug ) {
        fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=invalid_source route={$route}\n" );
        exit( 1 );
    }

    $existing = get_post( $sourceId );
    if ( $existing instanceof WP_Post && $sourceType !== (string) $existing->post_type ) {
        $conflicts[ $sourceId ] = array(
            'id'     => $sourceId,
            'reason' => 'id_type_collision',
            'route'  => $route,
        );
    }

    $slugQuery = new WP_Query(
        array(
            'name'           => $sourceSlug,
            'post_type'      => $sourceType,
            'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );
    $slugCollision = is_array( $slugQuery->posts ) ? reset( $slugQuery->posts ) : null;
    if ( $slugCollision instanceof WP_Post && (int) $slugCollision->ID !== $sourceId ) {
        $collisionId = (int) $slugCollision->ID;
        if ( isset( $targetLookup[ $collisionId ] ) ) {
            fwrite(
                STDERR,
                "STAGING_PUBLICATION_COLLISIONS=FAIL reason=canonical_slug_collision route={$route} existing_id={$collisionId}\n"
            );
            exit( 1 );
        }
        $conflicts[ $collisionId ] = array(
            'id'     => $collisionId,
            'reason' => 'staging_slug_collision',
            'route'  => $route,
        );
    }
}

$removed = array();
foreach ( $conflicts as $collision ) {
    $collisionId = (int) $collision['id'];
    $post = get_post( $collisionId );
    if ( ! ( $post instanceof WP_Post ) ) {
        continue;
    }

    $collisionType = (string) $post->post_type;
    // Canonical page/post collisions and auto-generated WordPress revisions are
    // safe to remove on Staging because the full database is snapshotted before
    // this step. Attachments and arbitrary custom post types remain fail-closed:
    // deleting them could remove media files or plugin-owned state.
    if ( ! in_array( $collisionType, array( 'page', 'post', 'revision' ), true ) ) {
        fwrite(
            STDERR,
            "STAGING_PUBLICATION_COLLISIONS=FAIL reason=unsafe_post_type id={$collisionId} type={$collisionType}\n"
        );
        exit( 1 );
    }

    $deleted = wp_delete_post( $collisionId, true );
    if ( ! ( $deleted instanceof WP_Post ) || null !== get_post( $collisionId ) ) {
        fwrite( STDERR, "STAGING_PUBLICATION_COLLISIONS=FAIL reason=delete_failed id={$collisionId} type={$collisionType}\n" );
        exit( 1 );
    }
    clean_post_cache( $collisionId );
    $collision['post_type'] = $collisionType;
    $removed[] = $collision;
}

$report = array(
    'schema'  => 'nuvanx-staging-publication-collision-prep',
    'removed' => array_values( $removed ),
    'count'   => count( $removed ),
);

echo wp_json_encode( $report );
fwrite( STDERR, sprintf( "STAGING_PUBLICATION_COLLISIONS=PASS removed=%d\n", count( $removed ) ) );
