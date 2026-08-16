<?php
/**
 * Transactionally reconcile Staging2 page/post publication state from a
 * Production snapshot that has already passed the committed manifest contract.
 *
 * This file is executed through `wp eval-file`. Do not add a strict_types
 * declaration here: WP-CLI evaluates the file inside an existing PHP execution
 * context, where `declare(strict_types=1)` is no longer the first statement and
 * causes a fatal error before the reconciliation can run.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

final class NvxPublicationParityException extends RuntimeException {}

/** @return array<string,mixed> */
function nvxPublicationLoadSnapshot( string $file ): array {
    if ( '' === $file || ! is_readable( $file ) ) {
        return array();
    }

    $snapshot = json_decode( (string) file_get_contents( $file ), true );
    if (
        ! is_array( $snapshot )
        || 'nuvanx-production-publication-snapshot' !== ( $snapshot['schema'] ?? '' )
        || ! isset( $snapshot['routes'] )
        || ! is_array( $snapshot['routes'] )
    ) {
        return array();
    }

    return $snapshot;
}

/** @return WP_Post|null */
function nvxPublicationFindSlugCollision( string $slug, string $postType ): ?WP_Post {
    $query = new WP_Query(
        array(
            'name'           => $slug,
            'post_type'      => $postType,
            'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );

    $candidate = is_array( $query->posts ) ? reset( $query->posts ) : null;
    return $candidate instanceof WP_Post ? $candidate : null;
}

/** @param array<string,mixed> $source @return array<string,mixed> */
function nvxPublicationPostPayload( array $source ): array {
    return array(
        'post_author'    => (int) ( $source['post_author'] ?? 0 ),
        'post_date'      => (string) ( $source['post_date'] ?? '' ),
        'post_date_gmt'  => (string) ( $source['post_date_gmt'] ?? '' ),
        'post_content'   => wp_slash( (string) ( $source['post_content'] ?? '' ) ),
        'post_title'     => wp_slash( (string) ( $source['post_title'] ?? '' ) ),
        'post_excerpt'   => wp_slash( (string) ( $source['post_excerpt'] ?? '' ) ),
        'post_status'    => 'publish',
        'comment_status' => wp_slash( (string) ( $source['comment_status'] ?? 'closed' ) ),
        'ping_status'    => wp_slash( (string) ( $source['ping_status'] ?? 'closed' ) ),
        'post_password'  => wp_slash( (string) ( $source['post_password'] ?? '' ) ),
        'post_name'      => wp_slash( (string) ( $source['post_name'] ?? '' ) ),
        'post_parent'    => (int) ( $source['post_parent'] ?? 0 ),
        'menu_order'     => (int) ( $source['menu_order'] ?? 0 ),
        'post_type'      => (string) ( $source['post_type'] ?? 'post' ),
    );
}

/** @param array<string,mixed> $sourceMeta @param array<int,string> $managedKeys */
function nvxPublicationSyncMeta( int $postId, array $sourceMeta, array $managedKeys ): void {
    foreach ( $managedKeys as $metaKey ) {
        delete_post_meta( $postId, $metaKey );
        $values = $sourceMeta[ $metaKey ] ?? array();
        if ( ! is_array( $values ) ) {
            continue;
        }
        foreach ( $values as $value ) {
            add_post_meta( $postId, $metaKey, maybe_unserialize( $value ) );
        }
    }
}

/** @param array<string,mixed> $terms */
function nvxPublicationSyncTerms( int $postId, array $terms ): void {
    foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
        $slugs = is_array( $terms[ $taxonomy ] ?? null ) ? $terms[ $taxonomy ] : array();
        $termIds = array();
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', sanitize_title( (string) $slug ), $taxonomy );
            if ( $term instanceof WP_Term ) {
                $termIds[] = (int) $term->term_id;
            }
        }
        wp_set_object_terms( $postId, $termIds, $taxonomy, false );
    }
}

/** @param array<string,mixed> $manifest */
function nvxPublicationVerifyRuntime( array $manifest ): void {
    $verified = array();
    foreach ( nvxPublicationPublishedIds() as $postId ) {
        $post = get_post( $postId );
        if ( ! ( $post instanceof WP_Post ) ) {
            continue;
        }

        $permalink = get_permalink( $post );
        $route = is_string( $permalink ) ? nvxPublicationRouteFromPermalink( $permalink ) : '';
        if ( '' !== $route ) {
            $verified[ $route ] = array(
                'post_id'   => (int) $post->ID,
                'post_type' => (string) $post->post_type,
                'slug'      => (string) $post->post_name,
                'status'    => (string) $post->post_status,
            );
        }
    }

    $actualRoutes = array_keys( $verified );
    if ( ! nvxPublicationSameRouteSet( nvxPublicationExpectedRoutes( $manifest ), $actualRoutes ) ) {
        throw new NvxPublicationParityException( 'post_write_verification_route_set_mismatch' );
    }

    foreach ( $manifest['routes'] as $route => $expected ) {
        $actual = $verified[ $route ] ?? array();
        foreach ( array( 'post_id', 'post_type', 'slug', 'status' ) as $field ) {
            if ( ( $expected[ $field ] ?? null ) !== ( $actual[ $field ] ?? null ) ) {
                throw new NvxPublicationParityException( "post_write_verification_attribute_mismatch route={$route} field={$field}" );
            }
        }
    }
}

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
    fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=wrong_environment\n" );
    exit( 1 );
}

$snapshot = nvxPublicationLoadSnapshot( trim( (string) getenv( 'PUBLICATION_SNAPSHOT_FILE' ) ) );
$manifest = nvxPublicationLoadManifest( get_template_directory() . '/inc/data/publication-manifest.json' );
if (
    empty( $snapshot )
    || empty( $manifest )
    || 'https://nuvanx.com/' !== trailingslashit( (string) ( $snapshot['source'] ?? '' ) )
    || (string) ( $snapshot['manifest_version'] ?? '' ) !== (string) ( $manifest['version'] ?? '' )
    || ! nvxPublicationSameRouteSet( nvxPublicationExpectedRoutes( $manifest ), array_keys( $snapshot['routes'] ) )
) {
    fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=snapshot_or_manifest_mismatch\n" );
    exit( 1 );
}

foreach ( $manifest['routes'] as $route => $expected ) {
    $source = $snapshot['routes'][ $route ] ?? null;
    if ( ! is_array( $source ) ) {
        fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=source_missing route={$route}\n" );
        exit( 1 );
    }

    $identityMatches = (int) ( $source['ID'] ?? 0 ) === (int) ( $expected['post_id'] ?? 0 )
        && (string) ( $source['post_type'] ?? '' ) === (string) ( $expected['post_type'] ?? '' )
        && (string) ( $source['post_name'] ?? '' ) === (string) ( $expected['slug'] ?? '' )
        && 'publish' === (string) ( $source['post_status'] ?? '' );
    if ( ! $identityMatches ) {
        fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=source_identity_mismatch route={$route}\n" );
        exit( 1 );
    }

    $sourceId = (int) $source['ID'];
    $existing = get_post( $sourceId );
    if ( $existing instanceof WP_Post && (string) $source['post_type'] !== $existing->post_type ) {
        fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=id_collision id={$sourceId}\n" );
        exit( 1 );
    }

    $collision = nvxPublicationFindSlugCollision( (string) $source['post_name'], (string) $source['post_type'] );
    if ( $collision instanceof WP_Post && (int) $collision->ID !== $sourceId ) {
        fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=slug_collision route={$route} existing_id={$collision->ID}\n" );
        exit( 1 );
    }
}

$managedMetaKeys = array(
    '_wp_page_template',
    '_thumbnail_id',
    '_nvx_aesthetic_treatment_key',
    '_nvx_medical_review_status',
    '_yoast_wpseo_title',
    '_yoast_wpseo_metadesc',
    '_yoast_wpseo_canonical',
    '_yoast_wpseo_meta-robots-noindex',
    '_yoast_wpseo_meta-robots-nofollow',
);
$targetIds = array_map(
    static fn( array $row ): int => (int) $row['ID'],
    array_values( $snapshot['routes'] )
);
$targetLookup = array_fill_keys( $targetIds, true );
$surplusIds = array_values(
    array_filter(
        nvxPublicationPublishedIds(),
        static fn( int $id ): bool => ! isset( $targetLookup[ $id ] )
    )
);

global $wpdb;
$wpdb->query( 'START TRANSACTION' );
$touchedIds = array();
$created = 0;
$updated = 0;
$drafted = 0;

try {
    foreach ( $snapshot['routes'] as $route => $source ) {
        $sourceId = (int) $source['ID'];
        $payload = nvxPublicationPostPayload( $source );
        $existing = get_post( $sourceId );

        if ( $existing instanceof WP_Post ) {
            $payload['ID'] = $sourceId;
            $result = wp_update_post( $payload, true );
            ++$updated;
        } else {
            $payload['import_id'] = $sourceId;
            $result = wp_insert_post( $payload, true );
            ++$created;
        }

        if ( is_wp_error( $result ) || (int) $result !== $sourceId ) {
            $message = is_wp_error( $result ) ? $result->get_error_message() : 'unexpected_insert_id';
            throw new NvxPublicationParityException( "post_write_failed route={$route} reason={$message}" );
        }
        $touchedIds[] = $sourceId;

        $sourceMeta = is_array( $source['meta'] ?? null ) ? $source['meta'] : array();
        nvxPublicationSyncMeta( $sourceId, $sourceMeta, $managedMetaKeys );
        if ( 'post' === (string) $source['post_type'] ) {
            nvxPublicationSyncTerms( $sourceId, is_array( $source['terms'] ?? null ) ? $source['terms'] : array() );
        }
    }

    foreach ( $surplusIds as $surplusId ) {
        $result = wp_update_post( array( 'ID' => $surplusId, 'post_status' => 'draft' ), true );
        if ( is_wp_error( $result ) ) {
            throw new NvxPublicationParityException(
                'surplus_draft_failed id=' . $surplusId . ' reason=' . $result->get_error_message()
            );
        }
        $touchedIds[] = $surplusId;
        ++$drafted;
    }

    foreach ( array_unique( $touchedIds ) as $touchedId ) {
        clean_post_cache( $touchedId );
    }
    nvxPublicationVerifyRuntime( $manifest );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    foreach ( array_unique( $touchedIds ) as $touchedId ) {
        clean_post_cache( $touchedId );
    }
    fwrite( STDERR, 'STAGING_PUBLICATION_PARITY=FAIL reason=' . preg_replace( '/\s+/', '_', $error->getMessage() ) . "\n" );
    exit( 1 );
}

flush_rewrite_rules( false );
wp_cache_flush();

$report = array(
    'schema'           => 'nuvanx-staging-publication-parity',
    'manifest_version' => (string) $manifest['version'],
    'synced_at'        => gmdate( 'c' ),
    'source'           => (string) $snapshot['source'],
    'target'           => home_url( '/' ),
    'route_count'      => count( nvxPublicationExpectedRoutes( $manifest ) ),
    'created'          => $created,
    'updated'          => $updated,
    'drafted_surplus'  => $drafted,
);

echo wp_json_encode( $report );
fwrite(
    STDERR,
    sprintf(
        "STAGING_PUBLICATION_PARITY=PASS routes=%d created=%d updated=%d drafted=%d\n",
        $report['route_count'],
        $created,
        $updated,
        $drafted
    )
);
