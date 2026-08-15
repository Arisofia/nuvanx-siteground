<?php
/**
 * Normalize governed NUVANX journal posts that still contain legacy Markdown.
 *
 * Dry-run is the default. Apply only with NVX_MIGRATION_APPLY=yes after review.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "MIGRATION_VALIDATION=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/content-normalizer.php';

/** Return the currently published post for a governed slug. */
function nvxFindPublishedPostBySlug( string $slug ): ?WP_Post {
    $query = new WP_Query(
        array(
            'name'           => $slug,
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        )
    );
    $candidate = is_array( $query->posts ) ? reset( $query->posts ) : null;
    return $candidate instanceof WP_Post ? $candidate : null;
}

$apply = 'yes' === strtolower( trim( (string) getenv( 'NVX_MIGRATION_APPLY' ) ) );
$catalogFile = get_template_directory() . '/inc/data/seo-blog-post-metadata.json';
if ( ! is_readable( $catalogFile ) ) {
    fwrite( STDERR, "MIGRATION_VALIDATION=FAIL reason=seo_catalog_unavailable\n" );
    exit( 1 );
}

$catalog = json_decode( (string) file_get_contents( $catalogFile ), true );
if ( ! is_array( $catalog ) ) {
    fwrite( STDERR, "MIGRATION_VALIDATION=FAIL reason=seo_catalog_invalid\n" );
    exit( 1 );
}

$results = array(
    'total_catalogued'  => count( $catalog ),
    'published_checked' => 0,
    'needs_migration'   => 0,
    'migrated'          => 0,
    'validation_failed' => 0,
    'posts'             => array(),
);

foreach ( array_keys( $catalog ) as $slug ) {
    $post = nvxFindPublishedPostBySlug( (string) $slug );
    if ( ! $post ) {
        continue;
    }

    ++$results['published_checked'];
    $before = nvxValidateNormalizedContent( (string) $post->post_content );
    $postResult = array(
        'post_id'         => (int) $post->ID,
        'slug'            => (string) $post->post_name,
        'needs_migration' => ! $before['valid'],
        'migrated'        => false,
        'issues_before'   => $before['issues'],
        'issues_after'    => array(),
    );

    if ( $before['valid'] ) {
        $results['posts'][] = $postResult;
        continue;
    }

    ++$results['needs_migration'];
    $normalized = nvxNormalizeToHtml( (string) $post->post_content );
    $after = nvxValidateNormalizedContent( $normalized );
    $postResult['issues_after'] = $after['issues'];

    if ( ! $after['valid'] ) {
        ++$results['validation_failed'];
        $results['posts'][] = $postResult;
        fwrite(
            STDERR,
            sprintf(
                "SKIP id=%d slug=%s validation_failed=%s\n",
                $post->ID,
                $post->post_name,
                implode( '|', $after['issues'] )
            )
        );
        continue;
    }

    if ( $apply ) {
        $updated = wp_update_post(
            array(
                'ID'           => (int) $post->ID,
                'post_content' => $normalized,
            ),
            true
        );
        if ( is_wp_error( $updated ) || (int) $updated !== (int) $post->ID ) {
            ++$results['validation_failed'];
            $postResult['issues_after'][] = is_wp_error( $updated ) ? $updated->get_error_message() : 'unexpected_update_id';
            $results['posts'][] = $postResult;
            continue;
        }

        clean_post_cache( (int) $post->ID );
        $verified = get_post( (int) $post->ID );
        $verifiedResult = $verified instanceof WP_Post
            ? nvxValidateNormalizedContent( (string) $verified->post_content )
            : array( 'valid' => false, 'issues' => array( 'post_missing_after_update' ) );

        if ( ! $verifiedResult['valid'] ) {
            ++$results['validation_failed'];
            $postResult['issues_after'] = $verifiedResult['issues'];
            $results['posts'][] = $postResult;
            continue;
        }

        $postResult['migrated'] = true;
        ++$results['migrated'];
    }

    $results['posts'][] = $postResult;
}

$report = array(
    'schema'       => 'governed-blog-content-migration',
    'generated_at' => gmdate( 'c' ),
    'source'       => home_url( '/' ),
    'apply'        => $apply,
    'summary'      => array(
        'total_catalogued'  => $results['total_catalogued'],
        'published_checked' => $results['published_checked'],
        'needs_migration'   => $results['needs_migration'],
        'migrated'          => $results['migrated'],
        'validation_failed' => $results['validation_failed'],
    ),
    'posts'        => $results['posts'],
);

echo wp_json_encode( $report );

if ( $results['validation_failed'] > 0 ) {
    fwrite( STDERR, sprintf( "\nMIGRATION_VALIDATION=FAIL failed=%d\n", $results['validation_failed'] ) );
    exit( 1 );
}

if ( ! $apply && $results['needs_migration'] > 0 ) {
    fwrite( STDERR, sprintf( "\nMIGRATION_VALIDATION=DRY_RUN needs_migration=%d\n", $results['needs_migration'] ) );
    exit( 0 );
}

fwrite(
    STDERR,
    sprintf(
        "\nMIGRATION_VALIDATION=PASS checked=%d migrated=%d\n",
        $results['published_checked'],
        $results['migrated']
    )
);
