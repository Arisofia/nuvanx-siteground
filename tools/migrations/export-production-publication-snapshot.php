<?php
/**
 * Export the canonical public page/post snapshot from Production (read-only).
 *
 * This file is executed through `wp eval-file`. Do not add a strict_types
 * declaration here: WP-CLI evaluates the file inside an existing PHP execution
 * context, where `declare(strict_types=1)` is no longer the first statement and
 * causes a fatal error before the publication contract can run.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

$identity = array(
    'db_name' => defined( 'DB_NAME' ) ? (string) DB_NAME : '',
    'home'    => (string) get_option( 'home' ),
    'siteurl' => (string) get_option( 'siteurl' ),
);
if (
    'db0ecrycwv2tgb' !== $identity['db_name']
    || 'https://nuvanx.com' !== untrailingslashit( $identity['home'] )
    || 'https://nuvanx.com' !== untrailingslashit( $identity['siteurl'] )
) {
    fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=wrong_environment\n" );
    exit( 1 );
}

$manifest = nvxPublicationLoadManifest( trim( (string) getenv( 'PUBLICATION_MANIFEST_FILE' ) ) );
if ( empty( $manifest ) ) {
    fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=manifest_invalid\n" );
    exit( 1 );
}

$metaKeys = array(
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

$actual = array();
foreach ( nvxPublicationPublishedIds() as $postId ) {
    $post = get_post( $postId );
    if ( ! ( $post instanceof WP_Post ) ) {
        continue;
    }

    $permalink = get_permalink( $post );
    $route = is_string( $permalink ) ? nvxPublicationRouteFromPermalink( $permalink ) : '';
    if ( '' === $route ) {
        continue;
    }

    $meta = array();
    foreach ( $metaKeys as $key ) {
        $values = get_post_meta( $post->ID, $key, false );
        if ( ! empty( $values ) ) {
            $meta[ $key ] = array_values( $values );
        }
    }

    $terms = array();
    foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
        $resolved = get_the_terms( $post->ID, $taxonomy );
        if ( is_array( $resolved ) ) {
            $terms[ $taxonomy ] = array_values(
                array_map(
                    static fn( WP_Term $term ): string => (string) $term->slug,
                    $resolved
                )
            );
        }
    }

    $actual[ $route ] = array(
        'ID'             => (int) $post->ID,
        'post_author'    => (int) $post->post_author,
        'post_date'      => (string) $post->post_date,
        'post_date_gmt'  => (string) $post->post_date_gmt,
        'post_content'   => (string) $post->post_content,
        'post_title'     => (string) $post->post_title,
        'post_excerpt'   => (string) $post->post_excerpt,
        'post_status'    => (string) $post->post_status,
        'comment_status' => (string) $post->comment_status,
        'ping_status'    => (string) $post->ping_status,
        'post_password'  => (string) $post->post_password,
        'post_name'      => (string) $post->post_name,
        'post_parent'    => (int) $post->post_parent,
        'menu_order'     => (int) $post->menu_order,
        'post_type'      => (string) $post->post_type,
        'permalink'      => (string) $permalink,
        'meta'           => $meta,
        'terms'          => $terms,
    );
}

$expectedRoutes = nvxPublicationExpectedRoutes( $manifest );
$actualRoutes = array_keys( $actual );
sort( $actualRoutes, SORT_STRING );
$missing = array_values( array_diff( $expectedRoutes, $actualRoutes ) );
$surplus = array_values( array_diff( $actualRoutes, $expectedRoutes ) );
$changed = array();

foreach ( $manifest['routes'] as $route => $expected ) {
    if ( ! isset( $actual[ $route ] ) || ! is_array( $expected ) ) {
        continue;
    }

    $source = $actual[ $route ];
    $fieldMap = array(
        'post_id'   => 'ID',
        'post_type' => 'post_type',
        'slug'      => 'post_name',
        'status'    => 'post_status',
    );
    foreach ( $fieldMap as $expectedKey => $sourceKey ) {
        if ( ( $expected[ $expectedKey ] ?? null ) !== ( $source[ $sourceKey ] ?? null ) ) {
            $changed[] = array(
                'route'    => $route,
                'field'    => $expectedKey,
                'expected' => $expected[ $expectedKey ] ?? null,
                'actual'   => $source[ $sourceKey ] ?? null,
            );
        }
    }
}

if ( ! empty( $missing ) || ! empty( $surplus ) || ! empty( $changed ) ) {
    fwrite(
        STDERR,
        sprintf(
            "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=manifest_drift missing=%d surplus=%d changed=%d\n",
            count( $missing ),
            count( $surplus ),
            count( $changed )
        )
    );
    exit( 1 );
}

$snapshot = array(
    'schema'           => 'nuvanx-production-publication-snapshot',
    'manifest_version' => (string) $manifest['version'],
    'exported_at'      => gmdate( 'c' ),
    'source'           => home_url( '/' ),
    'route_count'      => count( $actual ),
    'routes'           => $actual,
);

echo wp_json_encode( $snapshot );
fwrite( STDERR, sprintf( "PRODUCTION_PUBLICATION_EXPORT=PASS routes=%d\n", count( $actual ) ) );
