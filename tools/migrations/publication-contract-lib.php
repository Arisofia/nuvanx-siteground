<?php
/**
 * Shared read-only helpers for publication topology contracts.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

/** @return array<string,mixed> */
function nvxPublicationLoadManifest( string $file ): array {
    if ( '' === $file || ! is_readable( $file ) ) {
        return array();
    }

    $manifest = json_decode( (string) file_get_contents( $file ), true );
    if (
        ! is_array( $manifest )
        || 'nuvanx-publication-manifest' !== ( $manifest['schema'] ?? '' )
        || ! isset( $manifest['routes'] )
        || ! is_array( $manifest['routes'] )
    ) {
        return array();
    }

    return $manifest;
}

function nvxPublicationRouteFromPermalink( string $permalink ): string {
    $home = trailingslashit( home_url( '/' ) );
    if ( 0 !== strpos( $permalink, $home ) ) {
        return '';
    }

    $relative = trim( substr( $permalink, strlen( $home ) ), '/' );
    return '' === $relative ? '/' : '/' . $relative . '/';
}

/** @return array<int,int> */
function nvxPublicationPublishedIds(): array {
    $query = new WP_Query(
        array(
            'post_type'              => array( 'page', 'post' ),
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        )
    );

    return array_values( array_map( 'intval', is_array( $query->posts ) ? $query->posts : array() ) );
}

/** @param array<string,mixed> $manifest @return array<int,string> */
function nvxPublicationExpectedRoutes( array $manifest ): array {
    $routes = array_keys( is_array( $manifest['routes'] ?? null ) ? $manifest['routes'] : array() );
    sort( $routes, SORT_STRING );
    return $routes;
}

/** @param array<int,string> $left @param array<int,string> $right */
function nvxPublicationSameRouteSet( array $left, array $right ): bool {
    sort( $left, SORT_STRING );
    sort( $right, SORT_STRING );
    return $left === $right;
}
