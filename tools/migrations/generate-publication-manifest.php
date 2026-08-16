<?php
/**
 * Validate live WordPress public topology against the committed manifest.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "PUBLICATION_MANIFEST=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

$manifest = nvxPublicationLoadManifest( get_template_directory() . '/inc/data/publication-manifest.json' );
if ( empty( $manifest ) ) {
    fwrite( STDERR, "PUBLICATION_MANIFEST=FAIL reason=manifest_invalid\n" );
    exit( 1 );
}

$actual = array();
foreach ( nvxPublicationPublishedIds() as $postId ) {
    $post = get_post( $postId );
    if ( ! ( $post instanceof WP_Post ) ) {
        continue;
    }

    $permalink = get_permalink( $post );
    if ( ! is_string( $permalink ) || '' === $permalink ) {
        continue;
    }

    $route = nvxPublicationRouteFromPermalink( $permalink );
    if ( '' === $route ) {
        continue;
    }

    $renderer = 'single-post.php';
    if ( 'page' === $post->post_type ) {
        $renderer = (string) get_page_template_slug( $post->ID );
    }

    $actual[ $route ] = array(
        'post_id'   => (int) $post->ID,
        'post_type' => (string) $post->post_type,
        'slug'      => (string) $post->post_name,
        'status'    => (string) $post->post_status,
        'canonical' => (string) $permalink,
        'renderer'  => $renderer,
    );
}

$expectedRoutes = nvxPublicationExpectedRoutes( $manifest );
$actualRoutes   = array_keys( $actual );
sort( $actualRoutes, SORT_STRING );

$missing    = array_values( array_diff( $expectedRoutes, $actualRoutes ) );
$surplus    = array_values( array_diff( $actualRoutes, $expectedRoutes ) );
$mismatches = array();
$errors     = array();

foreach ( $missing as $route ) {
    $errors[] = 'Missing expected public URL: ' . $route;
}
foreach ( $surplus as $route ) {
    $errors[] = 'Surplus public URL not present in canonical manifest: ' . $route;
}

foreach ( $manifest['routes'] as $route => $expected ) {
    if ( ! isset( $actual[ $route ] ) || ! is_array( $expected ) ) {
        continue;
    }

    $changes = array();
    foreach ( array( 'post_id', 'post_type', 'slug', 'status' ) as $field ) {
        $expectedValue = $expected[ $field ] ?? null;
        $actualValue   = $actual[ $route ][ $field ] ?? null;
        if ( $expectedValue !== $actualValue ) {
            $changes[] = sprintf(
                '%s: expected=%s actual=%s',
                $field,
                wp_json_encode( $expectedValue ),
                wp_json_encode( $actualValue )
            );
        }
    }

    if ( isset( $expected['canonical'] ) ) {
        $expectedCanonical = (string) $expected['canonical'];
    } elseif ( '/' === $route ) {
        $expectedCanonical = trailingslashit( home_url( '/' ) );
    } else {
        $expectedCanonical = home_url( $route );
    }

    if ( untrailingslashit( $expectedCanonical ) !== untrailingslashit( (string) $actual[ $route ]['canonical'] ) ) {
        $changes[] = sprintf(
            'canonical: expected=%s actual=%s',
            $expectedCanonical,
            (string) $actual[ $route ]['canonical']
        );
    }

    if ( ! empty( $changes ) ) {
        $mismatches[] = array( 'route' => (string) $route, 'changes' => $changes );
        $errors[] = 'Attribute mismatch for ' . $route . ': ' . implode( '; ', $changes );
    }
}

$manifest['validation'] = array(
    'pass'           => empty( $errors ),
    'errors_count'   => count( $errors ),
    'errors'         => $errors,
    'missing'        => $missing,
    'surplus'        => $surplus,
    'changed'        => $mismatches,
    'expected_count' => count( $expectedRoutes ),
    'actual_count'   => count( $actualRoutes ),
    'checked_at'     => gmdate( 'c' ),
    'checked_host'   => wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
    'actual'         => $actual,
);

echo wp_json_encode( $manifest );

if ( ! empty( $errors ) ) {
    fwrite(
        STDERR,
        sprintf(
            "PUBLICATION_MANIFEST=FAIL expected=%d actual=%d missing=%d surplus=%d changed=%d\n",
            count( $expectedRoutes ),
            count( $actualRoutes ),
            count( $missing ),
            count( $surplus ),
            count( $mismatches )
        )
    );
    exit( 1 );
}

fwrite( STDERR, sprintf( "PUBLICATION_MANIFEST=PASS routes=%d\n", count( $expectedRoutes ) ) );
